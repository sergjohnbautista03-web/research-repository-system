<?php

namespace Tests\Feature;

use App\Models\Research;
use App\Models\ResearchHandoff;
use App\Models\Semester;
use App\Models\User;
use App\Services\NotificationFeed;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\JournalWorkflowSchema;
use Tests\TestCase;

class CoordinatorReviewWorkflowTest extends TestCase
{
    use JournalWorkflowSchema;

    private User $dean;

    private User $coordinator;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createJournalWorkflowSchema();
        Storage::fake('public');
        $this->withoutMiddleware(\App\Http\Middleware\EnsureRepositoryPolicyAccepted::class);
        $this->dean = $this->staff(['is_department_dean' => true]);
        $this->coordinator = $this->staff(['is_research_coordinator' => true]);
        $this->admin = $this->staff();
        Semester::create(['school_year' => '2026-2027', 'semester' => '1st', 'is_active' => false]);
    }

    private function staff(array $attributes = []): User
    {
        return User::create($attributes + ['name' => 'Staff '.(User::count() + 1), 'email' => 'staff'.(User::count() + 1).'@example.test',
            'role' => 'admin', 'department' => 'College of Computer Studies', 'is_active' => true]);
    }

    private function pdf(): UploadedFile
    {
        $pdf = new \FPDF;
        $pdf->AddPage();

        return UploadedFile::fake()->createWithContent('completed-journal.pdf', $pdf->Output('S'));
    }

    private function assign(string $year = '2026-2027'): ResearchHandoff
    {
        $this->actingAs($this->dean)->post(route('admin.research-handoffs.store'), [
            'submission_category' => Research::SUBMISSION_CATEGORY_FACULTY_JOURNAL,
            'school_year' => $year, 'program' => 'Information Technology',
            'notes' => 'Complete the Research Journal and submit it to me for review.',
        ])->assertRedirect(route('admin.research-handoffs'));

        // Existing single-journal tasks retain their original workflow after the batch update.
        $task = ResearchHandoff::latest('id')->firstOrFail();
        $task->update(['is_batch' => false]);
        return $task;
    }

    private function journalData(ResearchHandoff $task, string $action = 'submit'): array
    {
        return ['handoff_id' => $task->id, 'workflow_action' => $action, 'school_year' => $task->school_year,
            'title' => 'Completed Journal', 'submission_category' => Research::SUBMISSION_CATEGORY_FACULTY_JOURNAL,
            'type' => 'Quantitative', 'authors' => ['Researcher One'], 'year_published' => 2026,
            'abstract' => 'Completed journal abstract.', 'file' => $this->pdf()];
    }

    private function submit(ResearchHandoff $task): Research
    {
        $this->actingAs($this->coordinator)->post(route('admin.store-research'), $this->journalData($task))
            ->assertRedirect(route('admin.coordinator.submissions'));

        return Research::findOrFail($task->fresh()->research_id);
    }

    public function test_assign_prepare_submit_forward_directly_to_admin_and_publish(): void
    {
        $task = $this->assign();
        $this->assertNull($task->file_path);
        $this->assertSame($this->coordinator->id, $task->coordinator_id);
        $this->assertSame('2026-2027', $task->school_year);
        $journal = $this->submit($task);
        $this->assertSame(Research::STATUS_DEAN_REVIEW, $journal->status);
        $this->assertSame('2026-2027', $journal->school_year);
        $this->assertSame(0, Research::approved()->count());
        $this->assertSame(0, Research::forAdmin()->count());
        $this->assertCount(0, app(NotificationFeed::class)->events($this->admin));
        $this->assertTrue(app(NotificationFeed::class)->events($this->dean)->contains(fn ($event) => str_contains($event['message'], 'submitted for your review')));

        $this->actingAs($this->dean)->get(route('admin.research.show', $journal))->assertOk()
            ->assertSee('Forward to Admin for Publishing')->assertSee('AY 2026-2027')
            ->assertDontSee('Approve Journal')->assertDontSee('Return for Correction');
        $this->post(route('admin.research.dean-return', $journal), ['reason' => 'Corrections'])->assertStatus(400);
        $this->post(route('admin.research.dean-approve', $journal))->assertStatus(400);
        $this->assertSame(Research::STATUS_DEAN_REVIEW, $journal->fresh()->status);
        $this->assertSame(0, Research::forAdmin()->count());
        $this->actingAs($this->admin)->post(route('admin.research.approve', $journal))->assertStatus(409);
        $this->actingAs($this->dean)->post(route('admin.research.dean-forward', $journal))->assertRedirect();
        $this->assertSame(Research::STATUS_PENDING, $journal->fresh()->status);
        $this->assertSame(1, Research::forAdmin()->count());
        $this->assertCount(1, app(NotificationFeed::class)->events($this->admin));
        $this->actingAs($this->admin)->get(route('admin.research.show', $journal))->assertOk()->assertSee('Publish')->assertDontSee('onclick="openRejectModal', false);
        $this->post(route('admin.research.approve', $journal))->assertRedirect();
        $published = $journal->fresh();
        $this->assertSame(Research::STATUS_APPROVED, $published->status);
        $this->assertNull($published->dean_approved_by);
        $this->assertSame($this->admin->id, $published->approved_by);
        $this->assertNull($published->dean_approved_at);
        $this->assertNotNull($published->forwarded_to_admin_at);
        $this->assertSame('2026-2027', $published->school_year);
        $this->assertSame(1, Research::approved()->count());
        $this->post(route('admin.research.approve', $journal))->assertStatus(409);
        $this->app['auth']->guard()->logout();
        $this->get(route('research.show', $journal))->assertOk();
    }

    public function test_dean_assignments_reject_files_without_creating_a_task_or_storing_an_attachment(): void
    {
        $this->actingAs($this->dean)->post(route('admin.research-handoffs.store'), [
            'title' => 'Prepare Research Journal for AY 2026-2027',
            'submission_category' => Research::SUBMISSION_CATEGORY_FACULTY_JOURNAL,
            'school_year' => '2026-2027',
            'notes' => 'Compile the approved papers already available and submit the journal for review.',
            'file' => $this->pdf(),
        ])->assertSessionHasErrors('file');

        $this->assertSame(0, ResearchHandoff::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_task_and_preparation_pages_use_approved_papers_without_dean_file_controls(): void
    {
        $assignmentPage = $this->actingAs($this->dean)->get(route('admin.research-handoffs.create'))
            ->assertOk();
        preg_match('/<form\b[^>]*class="rhf-form"[^>]*>.*?<\/form>/s', $assignmentPage->getContent(), $assignmentForm);
        $this->assertNotEmpty($assignmentForm);
        $this->assertStringNotContainsString('type="file"', $assignmentForm[0]);
        $this->assertStringNotContainsString('multipart/form-data', $assignmentForm[0]);
        $this->get(route('admin.research-handoffs'))->assertOk()
            ->assertSee('No journal tasks yet.')->assertDontSee('Submit research file');
        $task = $this->assign();
        $this->assertDatabaseHas('user_activity_logs', ['action' => 'journal_task_assigned', 'user_id' => $this->dean->id]);

        // Historical attachments must not reintroduce a Dean-to-Coordinator file step.
        $task->update(['file_path' => 'research_handoffs/legacy.pdf', 'file_name' => 'legacy.pdf']);
        Storage::disk('public')->put($task->file_path, 'Legacy source paper');
        $this->get(route('admin.research-handoffs'))->assertOk()->assertDontSee('View Reference');
        $this->actingAs($this->coordinator)->get(route('admin.coordinator.dean-submissions'))
            ->assertOk()
            ->assertSee('Prepare Journal')->assertDontSee('View Reference')->assertDontSee('legacy.pdf');
        $this->get(route('admin.research-handoffs.add-research', $task))->assertOk()
            ->assertSee('Prepare Research Journal')->assertSee('Completed Journal PDF')
            ->assertDontSee('Reference PDF from Dean')->assertDontSee('legacy.pdf');
        $this->get('/admin/research-handoffs/'.$task->id.'/file')->assertNotFound();

        $data = $this->journalData($task);
        unset($data['file']);
        $this->post(route('admin.store-research'), $data)->assertSessionHasErrors('file');
        $this->assertSame(0, Research::count());
        Storage::disk('public')->assertExists($task->file_path);
    }

    public function test_roles_cannot_skip_stages_or_edit_submitted_journals(): void
    {
        $journal = $this->submit($this->assign());
        $this->actingAs($this->admin)->get(route('admin.research.show', $journal))->assertForbidden();
        $this->post(route('admin.store-research'), [])->assertForbidden();
        $this->get(route('admin.research.view-file', $journal))->assertForbidden();
        $this->post(route('admin.research.approve', $journal))->assertStatus(409);
        $this->post(route('admin.research.dean-approve', $journal))->assertForbidden();
        $this->post(route('admin.research.reject', $journal), ['reason' => 'Return'])->assertForbidden();
        $this->actingAs($this->coordinator)->post(route('admin.research.approve', $journal))->assertForbidden();
        $this->post(route('admin.research.dean-approve', $journal))->assertForbidden();
        $this->patch(route('admin.coordinator.summaries.update', $journal), [])->assertStatus(400);
        $this->actingAs($this->dean)->post(route('admin.research.dean-approve', $journal))->assertStatus(400);
        $this->post(route('admin.research.approve', $journal))->assertForbidden();
        $this->assertSame(Research::STATUS_DEAN_REVIEW, $journal->fresh()->status);
    }

    public function test_wrong_department_or_unassigned_staff_cannot_review_or_prepare(): void
    {
        $task = $this->assign();
        $otherCoordinator = $this->staff(['is_research_coordinator' => true]);
        $this->actingAs($otherCoordinator)->post(route('admin.store-research'), $this->journalData($task))->assertNotFound();
        $this->get(route('admin.research-handoffs.add-research', $task))->assertForbidden();
        $journal = $this->submit($task);
        foreach (['College of Computer Studies', 'College of Education'] as $department) {
            $otherDean = $this->staff(['is_department_dean' => true, 'department' => $department]);
            $this->actingAs($otherDean)->post(route('admin.research.dean-return', $journal), ['reason' => 'Return'])->assertForbidden();
            $this->post(route('admin.research.dean-approve', $journal))->assertForbidden();
        }
    }

    public function test_required_pdf_and_immutable_academic_year_and_forwarding_completeness(): void
    {
        $task = $this->assign();
        $data = $this->journalData($task);
        unset($data['file']);
        $this->actingAs($this->coordinator)->post(route('admin.store-research'), $data)->assertSessionHasErrors('file');
        $this->assertNull($task->fresh()->research_id);
        $data = $this->journalData($task);
        $data['school_year'] = '2025-2026';
        $this->post(route('admin.store-research'), $data)->assertSessionHasErrors('school_year');
        $journal = $this->submit($task);
        $this->actingAs($this->dean)->post(route('admin.research.dean-return', $journal), ['reason' => 'Corrections required.'])->assertStatus(400);
        Storage::disk('public')->delete($journal->file_path);
        $this->post(route('admin.research.dean-forward', $journal))->assertSessionHasErrors('file');
        $this->assertSame(Research::STATUS_DEAN_REVIEW, $journal->fresh()->status);
        $this->assertNull($journal->fresh()->forwarded_to_admin_at);
        $this->assertSame(0, Research::forAdmin()->count());
    }

    public function test_task_filters_and_drafts_show_assigned_year_without_semester(): void
    {
        $task = $this->assign();
        $this->actingAs($this->coordinator)->get(route('admin.coordinator.dean-submissions', ['academic_year' => '2026-2027']))
            ->assertOk()->assertSee($task->title)->assertSee('AY 2026-2027');
        $this->get(route('admin.coordinator.dean-submissions', ['academic_year' => '2025-2026']))->assertOk()->assertDontSee($task->title);
        $data = $this->journalData($task, 'draft');
        unset($data['file']);
        $this->post(route('admin.store-research'), $data)->assertRedirect();
        $journal = Research::findOrFail($task->fresh()->research_id);
        $this->assertSame(Research::STATUS_DRAFT, $journal->status);
        $this->assertNull($journal->semester_id);
        $this->get(route('admin.coordinator.summaries.edit', $journal))->assertOk()->assertSee('Submit to Dean')->assertSee('2026-2027');
        $this->actingAs($this->dean)->get(route('admin.research-handoffs', ['school_year' => '2026-2027']))->assertOk()
            ->assertViewHas('handoffs', fn ($handoffs) => $handoffs->contains('id', $task->id));
    }

    public function test_tasks_require_consecutive_academic_years_and_dean_role(): void
    {
        $data = ['title' => 'Prepare Research Journal', 'submission_category' => Research::SUBMISSION_CATEGORY_FACULTY_JOURNAL,
            'school_year' => '2026-2028', 'notes' => 'Complete this journal.'];
        $this->actingAs($this->dean)->post(route('admin.research-handoffs.store'), $data)->assertSessionHasErrors('school_year');
        $this->actingAs($this->coordinator)->post(route('admin.research-handoffs.store'), $data)->assertForbidden();
        $this->actingAs($this->admin)->post(route('admin.research-handoffs.store'), $data)->assertForbidden();
        $this->assertSame(0, ResearchHandoff::count());
    }

    public function test_migration_routes_existing_submissions_to_dean_and_backfills_year_without_unpublishing(): void
    {
        $migration = require database_path('migrations/2026_10_04_000001_add_dean_journal_review_workflow.php');
        $migration->down();
        $task = ResearchHandoff::create(['dean_id' => $this->dean->id, 'coordinator_id' => $this->coordinator->id,
            'title' => 'Legacy journal task', 'department' => $this->dean->department, 'year_published' => 2025]);
        $journal = Research::create(['title' => 'Legacy journal', 'coordinator_id' => $this->coordinator->id,
            'research_handoff_id' => $task->id, 'semester_id' => Semester::first()->id, 'status' => Research::STATUS_PENDING]);
        $published = Research::create(['title' => 'Previously published journal', 'coordinator_id' => $this->coordinator->id,
            'status' => Research::STATUS_APPROVED, 'approved_by' => $this->admin->id, 'approved_at' => now()]);
        $migration->up();
        $this->assertSame(Research::STATUS_DEAN_REVIEW, $journal->fresh()->status);
        $this->assertSame('2026-2027', $journal->fresh()->school_year);
        $this->assertSame('2026-2027', $task->fresh()->school_year);
        $this->assertSame(Research::STATUS_APPROVED, $published->fresh()->status);
        $this->assertFalse(Research::forAdmin()->whereKey($journal->id)->exists());
    }
}
