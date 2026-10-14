<?php

namespace Tests\Feature;

use App\Models\Research;
use App\Models\ResearchHandoff;
use App\Models\User;
use App\Services\NotificationFeed;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\JournalWorkflowSchema;
use Tests\TestCase;

class JournalBatchWorkflowTest extends TestCase
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
    }

    private function staff(array $attributes = []): User
    {
        return User::create($attributes + ['name' => 'Staff '.(User::count() + 1),
            'email' => 'batch-staff'.(User::count() + 1).'@example.test', 'password' => 'password',
            'role' => 'admin', 'department' => 'College of Computer Studies', 'is_active' => true]);
    }

    private function source(array $attributes = []): Research
    {
        return Research::create($attributes + ['title' => 'Source Paper '.(Research::count() + 1),
            'school_year' => '2025-2026', 'department' => $this->dean->department,
            'submission_category' => Research::SUBMISSION_CATEGORY_RESEARCH, 'type' => 'Capstone 2',
            'status' => Research::STATUS_APPROVED, 'authors' => ['Paper Author'],
            'abstract' => 'Approved research abstract.', 'year_published' => 2025]);
    }

    private function assign(string $program = 'Information Technology'): ResearchHandoff
    {
        $this->actingAs($this->dean)->post(route('admin.research-handoffs.store'), [
            'school_year' => '2025-2026', 'program' => $program,
            'submission_category' => Research::SUBMISSION_CATEGORY_FACULTY_JOURNAL,
            'notes' => 'Prepare every journal and submit the completed batch for review.',
        ])->assertRedirect(route('admin.research-handoffs'));

        return ResearchHandoff::latest('id')->firstOrFail();
    }

    private function journalData(string $suffix = ''): array
    {
        $pdf = new \FPDF;
        $pdf->AddPage();

        return ['title' => 'Completed Journal '.($suffix ?: (Research::count() + 1)),
            'authors' => ['Journal Author One', 'Journal Author Two'], 'abstract' => 'Completed journal abstract.',
            'keywords' => 'research, technology',
            'type' => 'Quantitative', 'submission_category' => Research::SUBMISSION_CATEGORY_FACULTY_JOURNAL,
            'year_published' => 2026, 'file' => UploadedFile::fake()->createWithContent('completed-journal.pdf', $pdf->Output('S'))];
    }

    private function prepare(ResearchHandoff $batch): Research
    {
        $this->actingAs($this->coordinator)->post(route('admin.journal-batches.journals.store', $batch), $this->journalData())
            ->assertRedirect(route('admin.journal-batches.show', $batch));

        return $batch->journals()->latest('id')->firstOrFail();
    }

    private function act(ResearchHandoff $batch, string $action, User $actor, array $data = [])
    {
        return $this->actingAs($actor)->post(route('admin.journal-batches.transition', [$batch, $action]), $data);
    }

    public function test_published_research_academic_year_backfill_only_changes_the_legacy_2023_collection(): void
    {
        $legacy = $this->source(['year_published' => 2023, 'school_year' => null]);
        $blank = $this->source(['year_published' => 2023, 'school_year' => '']);
        $assigned = $this->source(['year_published' => 2023, 'school_year' => '2023-2024']);
        $pending = $this->source(['year_published' => 2023, 'school_year' => null, 'status' => Research::STATUS_PENDING]);
        $newer = $this->source(['year_published' => 2026, 'school_year' => null]);
        $deleted = $this->source(['year_published' => 2023, 'school_year' => null]);
        $deleted->delete();
        $linked = $this->source(['year_published' => 2023, 'school_year' => null, 'research_handoff_id' => $this->assign()->id]);
        $originalUpdatedAt = $legacy->updated_at;
        $migration = require database_path('migrations/2026_10_05_000003_backfill_published_research_academic_year.php');
        $migration->up();
        $migration->up();

        $this->assertSame('2022-2023', $legacy->fresh()->school_year);
        $this->assertSame('2022-2023', $blank->fresh()->school_year);
        $this->assertSame(2023, $legacy->fresh()->year_published);
        $this->assertTrue($originalUpdatedAt->equalTo($legacy->fresh()->updated_at));
        $this->assertSame('2023-2024', $assigned->fresh()->school_year);
        foreach ([$pending, $newer, $deleted, $linked] as $untouched) {
            $this->assertNull($untouched->fresh()->school_year);
        }
        $card = view('components.research-card', ['research' => $legacy->fresh()])->render();
        $this->assertStringContainsString('Academic Year: 2022-2023', $card);
        $this->assertStringContainsString('Publication Year: 2023', $card);
    }

    public function test_batch_filters_offer_programs_and_consecutive_academic_years_without_existing_batches(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-05', 'Asia/Manila'));

        foreach ([$this->coordinator, $this->dean, $this->admin] as $actor) {
            $response = $this->actingAs($actor)->get(route('admin.journal-batches'))->assertOk()
                ->assertSeeInOrder([
                    'id="batch-program"', 'Information Technology', 'Computer Science',
                    'id="batch-ay"', '>2027-2028</option>', '>2026-2027</option>',
                    '>2025-2026</option>', '>2024-2025</option>', '>2023-2024</option>',
                    '>2022-2023</option>',
                ], false)
                ->assertDontSee('>AY 2022-2023</option>', false)
                ->assertSee('id="jbl-filters"', false)->assertSee('id="jbl-results"', false)
                ->assertSee('js/journal-batch-list.js')
                ->assertDontSee('>Filter</button>', false)->assertDontSee('Assign Academic Year')
                ->assertSee('No journal batches found.');
            if ($actor->isDepartmentDean()) {
                $response->assertDontSee('id="batch-department"', false);
            } else {
                $response->assertSee('id="batch-department"', false);
            }
        }
    }

    public function test_batch_department_filters_offer_matching_programs_and_preserve_role_scope(): void
    {
        $it = $this->assign('Information Technology');
        $cs = $this->assign('Computer Science');
        foreach ([$it, $cs] as $batch) {
            $batch->update(['batch_stage' => Research::STATUS_PENDING, 'batch_submitted_at' => now(), 'batch_forwarded_at' => now()]);
        }
        $other = $it->fresh()->replicate();
        $other->fill(['title' => 'Criminology completed batch', 'department' => 'College of Criminal Justice Education', 'program' => 'Criminology'])->save();
        $unforwarded = $other->replicate();
        $unforwarded->fill(['title' => 'Not yet forwarded', 'batch_stage' => Research::STATUS_DEAN_REVIEW, 'batch_forwarded_at' => null])->save();
        $department = 'College of Computer Studies';
        $this->actingAs($this->admin)->get(route('admin.journal-batches', ['department' => $department]))->assertOk()
            ->assertViewHas('programs', fn ($programs) => $programs->all() === ['Information Technology', 'Computer Science'])
            ->assertViewHas('batches', fn ($batches) => $batches->total() === 2)
            ->assertDontSee($other->title)->assertDontSee($unforwarded->title)
            ->assertSee('value="College of Computer Studies" selected', false);
        $this->get(route('admin.journal-batches', ['department' => $department, 'program' => 'Information Technology', 'school_year' => '2025-2026']))->assertOk()
            ->assertViewHas('batches', fn ($batches) => $batches->pluck('id')->all() === [$it->id]);
        $this->get(route('admin.journal-batches', ['department' => $other->department]))->assertOk()
            ->assertViewHas('programs', fn ($programs) => $programs->all() === ['Criminology'])
            ->assertViewHas('batches', fn ($batches) => $batches->pluck('id')->all() === [$other->id]);
        foreach ([$this->dean, $this->coordinator] as $actor) {
            $this->actingAs($actor)->get(route('admin.journal-batches'))->assertOk()
                ->assertViewHas('departments', fn ($departments) => $departments->all() === [$department])
                ->assertViewHas('programsByDepartment', fn ($catalog) => $catalog->keys()->all() === [$department]);
            $response = $this->get(route('admin.journal-batches', ['department' => $other->department]))->assertOk();
            if ($actor->isDepartmentDean()) {
                $response->assertDontSee('id="batch-department"', false)
                    ->assertViewHas('programs', fn ($programs) => $programs->all() === ['Information Technology', 'Computer Science'])
                    ->assertViewHas('batches', fn ($batches) => $batches->total() === 2)
                    ->assertDontSee($other->title);
            } else {
                $response->assertViewHas('programs', fn ($programs) => $programs->isEmpty())
                    ->assertViewHas('batches', fn ($batches) => $batches->total() === 0);
            }
        }
    }

    public function test_dean_batch_list_shows_only_completed_submissions_and_keeps_them_after_forwarding_and_publication(): void
    {
        $batch = $this->assign();
        $unfinished = $this->assign('Computer Science');
        $this->actingAs($this->dean)->get(route('admin.journal-batches'))->assertOk()
            ->assertDontSee($batch->title)->assertDontSee($unfinished->title)
            ->assertSee('Completed journal batches submitted by the Research Coordinator will appear here.')
            ->assertViewHas('batches', fn ($batches) => $batches->total() === 0);
        $this->get(route('admin.research-handoffs'))->assertOk()
            ->assertViewHas('handoffs', fn ($handoffs) => $handoffs->total() === 2);
        $this->actingAs($this->coordinator)->get(route('admin.journal-batches'))->assertOk()
            ->assertDontSee($batch->title)->assertDontSee($unfinished->title)
            ->assertViewHas('batches', fn ($batches) => $batches->total() === 0);
        $this->get(route('admin.coordinator.dean-submissions'))->assertOk()
            ->assertSee($batch->title)->assertSee($unfinished->title)->assertSee('Prepare Batch');

        $this->prepare($batch);
        $this->actingAs($this->dean)->get(route('admin.journal-batches'))->assertOk()
            ->assertDontSee($batch->title)->assertViewHas('batches', fn ($batches) => $batches->total() === 0);
        $this->act($batch, 'submit', $this->coordinator)->assertRedirect();

        $otherDean = $this->staff(['is_department_dean' => true]);
        $foreignBatch = $batch->fresh()->replicate();
        $foreignBatch->fill(['title' => 'Another Dean completed batch', 'dean_id' => $otherDean->id])->save();
        $foreignDepartmentBatch = $batch->fresh()->replicate();
        $foreignDepartmentBatch->fill(['title' => 'Another department completed batch', 'department' => 'College of Education'])->save();
        $unfinished->update(['batch_stage' => Research::STATUS_REJECTED]);

        $this->actingAs($this->dean)->get(route('admin.journal-batches'))->assertOk()
            ->assertSee($batch->title)->assertSee('Batch Submitted to Dean')
            ->assertDontSee($unfinished->title)->assertDontSee($foreignBatch->title)->assertDontSee($foreignDepartmentBatch->title)
            ->assertViewHas('batches', fn ($batches) => $batches->pluck('id')->all() === [$batch->id]);
        $this->get(route('admin.journal-batches', ['program' => 'Computer Science', 'school_year' => '2025-2026']))->assertOk()
            ->assertViewHas('batches', fn ($batches) => $batches->total() === 0);
        $this->get(route('admin.journal-batches', ['program' => 'Information Technology', 'school_year' => '2025-2026']))->assertOk()
            ->assertViewHas('batches', fn ($batches) => $batches->pluck('id')->all() === [$batch->id]);

        $this->act($batch, 'forward', $this->dean)->assertRedirect();
        $this->get(route('admin.journal-batches'))->assertOk()->assertSee($batch->title);
        $this->act($batch, 'publish', $this->admin)->assertRedirect();
        $this->actingAs($this->dean)->get(route('admin.journal-batches'))->assertOk()->assertSee($batch->title)
            ->assertViewHas('batches', fn ($batches) => $batches->pluck('id')->all() === [$batch->id]);
    }

    public function test_coordinator_lists_preparation_tasks_separately_from_submitted_batches(): void
    {
        $pending = $this->assign();
        $pending->update(['title' => 'Pending preparation task']);
        $preparing = $this->assign('Computer Science');
        $preparing->update(['title' => 'Journal batch to submit']);
        $this->prepare($preparing);
        $returned = $this->assign();
        $returned->update(['title' => 'Legacy returned batch', 'batch_stage' => Research::STATUS_REJECTED,
            'status' => ResearchHandoff::STATUS_RECEIVED]);
        $this->actingAs($this->coordinator)->get(route('admin.coordinator.dean-submissions'))->assertOk()
            ->assertViewHas('handoffs', fn ($tasks) => $tasks->total() === 3)
            ->assertViewHas('workflowStatusOptions', ['pending' => 'Pending', 'preparing' => 'Preparing Journals'])
            ->assertSee('Prepare Batch')->assertDontSee('Open Batch');
        $this->get(route('admin.journal-batches'))->assertOk()
            ->assertViewHas('batches', fn ($batches) => $batches->total() === 0);

        $this->act($preparing, 'submit', $this->coordinator)->assertRedirect();
        $otherCoordinator = $preparing->fresh()->replicate();
        $otherCoordinator->fill(['title' => 'Another coordinator completed batch',
            'coordinator_id' => $this->staff(['is_research_coordinator' => true])->id])->save();
        $otherDepartment = $preparing->fresh()->replicate();
        $otherDepartment->fill(['title' => 'Another department completed batch', 'department' => 'College of Education'])->save();
        $legacyJournal = $this->source(['status' => Research::STATUS_DEAN_REVIEW]);
        $legacyTask = $pending->fresh()->replicate();
        $legacyTask->fill(['title' => 'Completed legacy single journal', 'is_batch' => false,
            'research_id' => $legacyJournal->id])->save();

        $assigned = $this->actingAs($this->coordinator)->get(route('admin.coordinator.dean-submissions'))->assertOk();
        $this->assertEqualsCanonicalizing([$pending->id, $returned->id], $assigned->viewData('handoffs')->pluck('id')->all());
        foreach ([[], ['program' => 'Computer Science', 'school_year' => '2025-2026']] as $filters) {
            $this->get(route('admin.journal-batches', $filters))->assertOk()
                ->assertViewHas('batches', fn ($batches) => $batches->pluck('id')->all() === [$preparing->id])
                ->assertSee('Open Batch')->assertDontSee('Prepare Batch')->assertDontSee($pending->title)
                ->assertDontSee($returned->title)->assertDontSee($otherCoordinator->title)->assertDontSee($otherDepartment->title);
        }
        $this->act($preparing, 'forward', $this->dean)->assertRedirect();
        $this->act($preparing, 'publish', $this->admin)->assertRedirect();
        $this->actingAs($this->coordinator)->get(route('admin.coordinator.dean-submissions'))->assertOk()
            ->assertDontSee($preparing->title)->assertDontSee($legacyTask->title);
        $this->get(route('admin.journal-batches'))->assertOk()
            ->assertViewHas('batches', fn ($batches) => $batches->pluck('id')->all() === [$preparing->id]);
    }

    public function test_ten_uploaded_journals_move_as_one_batch_directly_from_dean_to_admin_and_publish_separately(): void
    {
        $batch = $this->assign();
        $this->assertSame(0, Research::count());
        $this->assertTrue($batch->is_batch);
        $this->actingAs($this->coordinator)->get(route('admin.journal-batches.show', $batch))->assertOk()
            ->assertSee('Add Journal')->assertSee('AY 2025-2026')->assertSee('Completed Journal PDF')
            ->assertSee('name="keywords"', false)->assertSee('name="authors[]"', false)->assertSee('Add Co-author')
            ->assertDontSee('Approved Research')->assertDontSee('source_research_ids');
        foreach (range(1, 10) as $number) {
            $this->prepare($batch);
        }
        $this->assertSame(10, $batch->journals()->count());
        $this->act($batch, 'submit', $this->coordinator)->assertRedirect();
        $this->assertSame(10, $batch->journals()->where('status', Research::STATUS_DEAN_REVIEW)->count());
        $this->assertSame(0, Research::forAdmin()->where('research_handoff_id', $batch->id)->count());
        $this->assertCount(0, app(NotificationFeed::class)->events($this->admin));
        $this->assertCount(1, app(NotificationFeed::class)->events($this->dean)->filter(fn ($event) => str_starts_with($event['id'], 'journal-batch:')));
        $deanReview = $this->actingAs($this->dean)->get(route('admin.journal-batches.show', $batch))->assertOk()
            ->assertSee('Forward Batch to Admin')->assertSee('Scroll down to review every journal')
            ->assertDontSee('Approve Entire Batch')->assertDontSee('Revision Remarks')->assertDontSee('Return Batch for Revision');
        $this->assertSame(10, substr_count($deanReview->getContent(), 'data-journal-review-frame'));
        foreach ($batch->journals()->get() as $member) {
            $deanReview->assertSee(route('admin.research.view-file', ['research' => $member, 'embedded' => 1]), false);
        }
        $this->act($batch, 'return', $this->dean, ['reason' => 'Corrections'])->assertStatus(400);
        $this->act($batch, 'approve', $this->dean)->assertStatus(400);
        $this->assertSame(10, $batch->journals()->where('status', Research::STATUS_DEAN_REVIEW)->count());
        $journal = $batch->journals()->first();
        $this->assertSame(0, Research::forAdmin()->where('research_handoff_id', $batch->id)->count());
        $this->act($batch, 'publish', $this->admin)->assertForbidden();
        $this->act($batch, 'forward', $this->dean)->assertRedirect();
        $this->assertCount(1, app(NotificationFeed::class)->events($this->admin));
        $this->actingAs($this->admin)->get(route('admin.journal-batches'))->assertOk()->assertSee($batch->title);
        $adminReview = $this->get(route('admin.journal-batches.show', $batch))->assertOk()->assertSee('Publish All Journals in Batch')
            ->assertSee('Each journal will appear as a separate entry');
        $this->assertSame(10, substr_count($adminReview->getContent(), 'data-journal-review-frame'));
        $this->get(route('admin.researches'))->assertOk()
            ->assertViewHas('researches', fn ($rows) => $rows->total() === 0);
        $this->act($batch, 'publish', $this->admin)->assertRedirect();
        $this->assertSame(Research::STATUS_APPROVED, $batch->fresh()->batch_stage);
        $this->assertSame(10, $batch->journals()->approved()->count());
        $this->assertSame(10, $batch->journals()->whereNotNull('forwarded_to_admin_at')->where('approved_by', $this->admin->id)->count());
        $this->assertNull($batch->fresh()->batch_dean_approved_at);
        $this->assertSame(10, $batch->journals()->whereNull('dean_approved_at')->count());
        $this->assertSame(10, $batch->journals()->where('school_year', '2025-2026')->count());
        $this->assertSame(10, $batch->journals()->where('keywords', 'research, technology')->count());
        $this->get(route('admin.researches'))->assertOk()
            ->assertViewHas('researches', fn ($rows) => $rows->total() === 10
                && $rows->every(fn ($record) => $record->research_handoff_id === $batch->id
                    && $record->status === Research::STATUS_APPROVED));
        $this->actingAs($this->admin)->post(route('admin.research.archive', $journal))->assertStatus(409);
        $this->post(route('admin.research.publish', $journal))->assertStatus(409);
        $this->delete(route('admin.research.delete', $journal))->assertStatus(409);
        $this->assertSame(10, $batch->journals()->approved()->count());
        $this->act($batch, 'publish', $this->admin)->assertStatus(409);
        $this->app['auth']->guard()->logout();
        $this->get(route('research.show', $journal))->assertOk();
    }

    public function test_open_batch_uses_a_modal_and_embedded_review_preserves_authorization_and_actions(): void
    {
        $batch = $this->assign();
        $this->actingAs($this->coordinator)->get(route('admin.coordinator.dean-submissions'))->assertOk()
            ->assertSee('Prepare Batch')->assertSee('data-open-journal-batch', false);
        $this->prepare($batch);
        $this->act($batch, 'submit', $this->coordinator)->assertRedirect();

        $this->actingAs($this->dean)->get(route('admin.journal-batches'))->assertOk()
            ->assertSee('data-open-journal-batch', false)->assertSee('id="journalBatchModal"', false);
        $this->get(route('admin.research-handoffs'))->assertOk()
            ->assertSee('data-open-journal-batch', false)->assertSee('id="journalBatchModal"', false);
        $url = route('admin.journal-batches.show', ['batch' => $batch, 'embedded' => 1]);
        $this->get($url)->assertOk()->assertSee('data-journal-batch-embedded', false)
            ->assertSee('data-journal-review-frame', false)->assertSee('Forward Batch to Admin')
            ->assertSee('name="embedded" value="1"', false)->assertDontSee('class="admin-sidebar"', false)
            ->assertDontSee('All Batches')->assertDontSee('Add Journal');

        $this->actingAs($this->staff(['is_department_dean' => true]))->get($url)->assertForbidden();
        $this->actingAs($this->admin)->get($url)->assertForbidden();
        $this->act($batch, 'forward', $this->dean, ['embedded' => 1])->assertRedirect($url);
        $this->get($url)->assertOk()->assertSee('data-batch-updated', false)
            ->assertSee('Journal batch forwarded by the Dean to Admin for publishing.');

        $this->actingAs($this->admin)->get(route('admin.journal-batches'))->assertOk()
            ->assertSee('data-open-journal-batch', false);
        $this->get($url)->assertOk()->assertSee('Publish All Journals in Batch');
        $this->act($batch, 'publish', $this->admin, ['embedded' => 1])->assertRedirect($url);
        $this->assertSame(Research::STATUS_APPROVED, $batch->fresh()->batch_stage);
    }

    public function test_coordinator_can_prepare_edit_and_submit_a_batch_inside_the_modal(): void
    {
        $batch = $this->assign();
        $url = route('admin.journal-batches.show', ['batch' => $batch, 'embedded' => 1]);
        $addUrl = route('admin.journal-batches.show', ['batch' => $batch, 'embedded' => 1, 'add' => 1]);
        $this->actingAs($this->coordinator)->get(route('admin.coordinator.dean-submissions'))->assertOk()
            ->assertSee('Prepare Batch')->assertSee('data-open-journal-batch', false)
            ->assertSee('id="journalBatchModal"', false);
        $this->get($url)->assertOk()->assertViewHas('embedded', true)->assertViewHas('editable', true)
            ->assertSee('data-journal-batch-embedded', false)->assertDontSee('class="admin-sidebar"', false)
            ->assertSee('Save Journal in Batch')->assertSee('name="embedded" value="1"', false)
            ->assertSee($addUrl.'#journal-editor')->assertDontSee('All Batches');

        $data = $this->journalData();
        $data['embedded'] = 1;
        $this->post(route('admin.journal-batches.journals.store', $batch), $data)->assertRedirect($url);
        $journal = $batch->journals()->firstOrFail();
        $editUrl = route('admin.journal-batches.show', ['batch' => $batch, 'embedded' => 1, 'edit' => $journal->id]);
        $this->get($url)->assertOk()->assertSee('data-batch-updated', false)
            ->assertSee($editUrl.'#journal-editor')->assertSee($addUrl.'#journal-editor')
            ->assertDontSee('data-close-on-success', false)->assertDontSee('class="admin-sidebar"', false);
        $this->get($editUrl)->assertOk()->assertSee('Edit Journal in Batch')->assertSee($url)
            ->assertSee('name="embedded" value="1"', false);
        $invalid = ['embedded' => 1, 'title' => 'Keep my draft title'];
        $this->from($editUrl)->patch(route('admin.journal-batches.journals.update', [$batch, $journal]), $invalid)
            ->assertRedirect($editUrl)->assertSessionHasErrors('abstract');
        $this->get($editUrl)->assertOk()->assertSee('Keep my draft title')->assertSee('data-journal-batch-embedded', false)
            ->assertDontSee('class="admin-sidebar"', false);
        $data = $this->journalData('Edited in modal');
        $data['embedded'] = 1;
        $this->patch(route('admin.journal-batches.journals.update', [$batch, $journal]), $data)->assertRedirect($url);
        $this->assertSame($data['title'], $journal->fresh()->title);
        $this->get($addUrl)->assertOk()->assertViewHas('embedded', true)->assertSee('Add Completed Journal to Batch');
        $this->act($batch, 'submit', $this->coordinator, ['embedded' => 1])->assertRedirect($url);
        $this->get($url)->assertOk()->assertViewHas('editable', false)->assertViewHas('embedded', true)
            ->assertSee('data-batch-updated', false)->assertSee('data-journal-review-frame', false)
            ->assertDontSee('Save Journal in Batch')->assertDontSee('class="admin-sidebar"', false);
        $this->get(route('admin.coordinator.dean-submissions'))->assertOk()->assertDontSee($batch->title)
            ->assertViewHas('handoffs', fn ($handoffs) => $handoffs->total() === 0);
        $this->get(route('admin.journal-batches'))->assertOk()->assertSee('Open Batch')
            ->assertSee('data-open-journal-batch', false)->assertSee('id="journalBatchModal"', false);
        $this->actingAs($this->staff(['is_research_coordinator' => true]))->get($url)->assertForbidden();
    }

    public function test_embedded_review_uses_the_protected_viewer_and_enforces_dean_and_admin_access(): void
    {
        $batch = $this->assign();
        $journal = $this->prepare($batch);
        $url = route('admin.research.view-file', ['research' => $journal, 'embedded' => 1]);
        $this->act($batch, 'submit', $this->coordinator)->assertRedirect();
        $this->actingAs($this->dean)->get($url)->assertOk()->assertViewHas('embedded', true)
            ->assertSee('viewer-embedded')->assertSee('journal-review-height')->assertSee('document-protection.js');
        $this->get(route('admin.research.view-file', $journal))->assertOk()->assertViewHas('embedded', false);
        $this->actingAs($this->admin)->get($url)->assertForbidden();
        $this->actingAs($this->staff(['is_department_dean' => true]))->get($url)->assertForbidden();
        $this->act($batch, 'forward', $this->dean)->assertRedirect();
        $response = $this->actingAs($this->admin)->get($url)->assertOk()->assertViewHas('embedded', true);
        $signedUrl = $response->viewData('signedUrl');
        $this->assertTrue(\Illuminate\Http\Request::create($signedUrl)->hasValidSignature());
        $this->assertSame('admin', \Illuminate\Http\Request::create($signedUrl)->query('scope'));

        Storage::disk('public')->delete($journal->file_path);
        $this->get(route('admin.journal-batches.show', $batch))->assertOk()->assertDontSee('data-journal-review-frame')
            ->assertSee('The PDF for this journal is unavailable');
    }

    public function test_empty_batch_and_journals_without_pdf_cannot_be_submitted(): void
    {
        $batch = $this->assign();
        $this->act($batch, 'submit', $this->coordinator)->assertSessionHasErrors('batch');
        $journal = $this->prepare($batch);
        $data = $this->journalData();
        unset($data['file']);
        $this->post(route('admin.journal-batches.journals.store', $batch), $data)->assertRedirect();
        $missingPdfJournal = $batch->journals()->latest('id')->firstOrFail();
        $page = $this->get(route('admin.journal-batches.show', $batch))->assertOk();
        preg_match('/<button\b[^>]*>Submit Completed Batch to Dean<\/button>/', $page->getContent(), $button);
        $this->assertNotEmpty($button);
        $this->assertStringNotContainsString('disabled', $button[0]);
        $this->from(route('admin.journal-batches.show', $batch))->act($batch, 'submit', $this->coordinator)
            ->assertSessionHasErrors(['batch' => 'Upload the completed PDF for "'.$missingPdfJournal->title.'" through Edit Journal, then submit the batch again.']);
        $this->get(route('admin.journal-batches.show', $batch))->assertOk()->assertSee('Upload the completed PDF for')
            ->assertSee('Edit Journal')->assertDontSee('Add Completed Journal to Batch');
        $this->assertSame(Research::STATUS_DRAFT, $journal->fresh()->status);
        $this->assertSame(Research::STATUS_DRAFT, $batch->fresh()->batch_stage);
    }

    public function test_coordinator_can_add_more_journals_and_replace_a_pdf_without_changing_the_assigned_year(): void
    {
        $batch = $this->assign();
        $journal = $this->prepare($batch);
        $originalPath = $journal->file_path;
        $this->assertSame('research, technology', $journal->keywords);
        $this->assertSame(['Journal Author One', 'Journal Author Two'], $journal->authors);
        $this->actingAs($this->coordinator)->get(route('admin.journal-batches.show', ['batch' => $batch, 'edit' => $journal->id]))
            ->assertOk()->assertSee('value="research, technology"', false)
            ->assertSee('value="Journal Author One"', false)->assertSee('value="Journal Author Two"', false);
        $this->actingAs($this->coordinator)->get(route('admin.journal-batches.show', ['batch' => $batch, 'add' => 1]))
            ->assertOk()->assertSee('Add Completed Journal to Batch')->assertSee('Save Journal in Batch');
        $data = $this->journalData('Revised');
        $data['keywords'] = 'education, journal';
        $data['authors'] = ['Revised Author'];
        $this->patch(route('admin.journal-batches.journals.update', [$batch, $journal]), $data)->assertRedirect();
        $this->assertSame('education, journal', $journal->fresh()->keywords);
        $this->assertSame(['Revised Author'], $journal->fresh()->authors);
        Storage::disk('public')->assertMissing($originalPath);
        Storage::disk('public')->assertExists($journal->fresh()->file_path);
        $data = $this->journalData('Metadata only');
        $data['keywords'] = '';
        unset($data['file']);
        $this->patch(route('admin.journal-batches.journals.update', [$batch, $journal]), $data)->assertRedirect();
        $this->assertSame('2025-2026', $journal->fresh()->school_year);
        $this->assertNull($journal->fresh()->keywords);
        Storage::disk('public')->assertExists($journal->fresh()->file_path);
        $this->prepare($batch);
        $this->assertSame(2, $batch->journals()->count());
        $this->act($batch, 'submit', $this->coordinator)->assertRedirect();
        $this->assertSame(Research::STATUS_DEAN_REVIEW, $journal->fresh()->status);
    }

    public function test_access_ownership_and_legacy_endpoints_cannot_bypass_batch_stages(): void
    {
        $batch = $this->assign();
        foreach ([$this->admin, $this->staff(['is_research_coordinator' => true]),
            $this->staff(['is_department_dean' => true]), $this->staff(['is_department_dean' => true, 'department' => 'College of Education'])] as $actor) {
            $this->actingAs($actor)->get(route('admin.journal-batches.show', $batch))->assertForbidden();
            $this->act($batch, 'forward', $actor)->assertForbidden();
        }
        $this->actingAs($this->admin)->get(route('admin.journal-batches'))->assertOk()->assertDontSee($batch->title);
        $journal = $this->prepare($batch);
        $this->post(route('admin.coordinator.summaries.submit', $journal))->assertStatus(409);
        $this->patch(route('admin.coordinator.summaries.update', $journal), [])->assertStatus(409);
        $this->act($batch, 'approve', $this->coordinator)->assertForbidden();
        $this->act($batch, 'forward', $this->dean)->assertStatus(409);
        $this->act($batch, 'submit', $this->coordinator)->assertRedirect();
        $this->actingAs($this->coordinator)->patch(route('admin.journal-batches.journals.update', [$batch, $journal]), $this->journalData())->assertStatus(409);
        $this->actingAs($this->dean)->post(route('admin.research.dean-approve', $journal))->assertStatus(409);
        $this->act($batch, 'return', $this->dean, ['reason' => '   '])->assertStatus(400);
        $this->act($batch, 'approve', $this->dean)->assertStatus(400);
        $this->act($batch, 'forward', $this->dean)->assertRedirect();
        $this->actingAs($this->admin)->post(route('admin.research.approve', $journal))->assertStatus(409);
    }

    public function test_missing_pdf_blocks_publication_of_every_member_without_partial_updates(): void
    {
        $batch = $this->assign();
        $one = $this->prepare($batch);
        $two = $this->prepare($batch);
        $this->act($batch, 'submit', $this->coordinator)->assertRedirect();
        $this->act($batch, 'forward', $this->dean)->assertRedirect();
        Storage::disk('public')->delete($two->file_path);
        $this->act($batch, 'publish', $this->admin)->assertSessionHasErrors([
            'batch' => 'The completed PDF for "'.$two->title.'" is missing. The batch cannot proceed until the PDF is restored.',
        ]);
        $this->assertSame(Research::STATUS_PENDING, $one->fresh()->status);
        $this->assertSame(Research::STATUS_PENDING, $two->fresh()->status);
        $this->assertSame(Research::STATUS_PENDING, $batch->fresh()->batch_stage);
    }

    public function test_existing_source_records_do_not_gate_uploads_or_modify_research_data(): void
    {
        $legacy = $this->source(['school_year' => null]);
        $other = $this->source(['department' => 'College of Education']);
        $batch = $this->assign();
        $this->actingAs($this->dean)->get(route('admin.journal-batches.show', $batch))->assertOk()
            ->assertDontSee('Approved Papers Without an Academic Year')->assertDontSee('Add Journal');
        $this->post(route('admin.journal-batches.journals.store', $batch), $this->journalData())->assertStatus(409);
        $this->actingAs($this->coordinator)->get(route('admin.journal-batches.show', $batch))->assertOk()
            ->assertSee('Add Journal')->assertDontSee($legacy->title)->assertDontSee($other->title);
        $this->prepare($batch);
        $this->act($batch, 'submit', $this->coordinator)->assertRedirect();
        $this->assertNull($legacy->fresh()->school_year);
        $this->assertSame('College of Education', $other->fresh()->department);
        $this->assertSame(0, $batch->batchItems()->count());
    }

    public function test_missing_pdf_blocks_dean_forwarding_without_exposing_a_partial_batch_to_admin(): void
    {
        $batch = $this->assign();
        $one = $this->prepare($batch);
        $two = $this->prepare($batch);
        $this->act($batch, 'submit', $this->coordinator)->assertRedirect();
        Storage::disk('public')->delete($two->file_path);
        $this->act($batch, 'forward', $this->dean)->assertSessionHasErrors('batch');
        $this->assertSame(Research::STATUS_DEAN_REVIEW, $batch->fresh()->batch_stage);
        $this->assertSame(Research::STATUS_DEAN_REVIEW, $one->fresh()->status);
        $this->assertNull($one->fresh()->forwarded_to_admin_at);
        $this->assertNull($batch->fresh()->batch_forwarded_at);
        $this->actingAs($this->admin)->get(route('admin.journal-batches.show', $batch))->assertForbidden();
        $this->assertCount(0, app(NotificationFeed::class)->events($this->admin));
    }

    public function test_legacy_dean_approved_batches_can_still_be_forwarded_and_published(): void
    {
        $batch = $this->assign();
        $journal = $this->prepare($batch);
        $batch->update(['batch_stage' => Research::STATUS_DEAN_APPROVED,
            'batch_dean_approved_by' => $this->dean->id, 'batch_dean_approved_at' => now()]);
        $journal->update(['status' => Research::STATUS_DEAN_APPROVED,
            'dean_approved_by' => $this->dean->id, 'dean_approved_at' => now()]);
        $this->actingAs($this->dean)->get(route('admin.journal-batches.show', $batch))->assertOk()
            ->assertSee('Forward Batch to Admin')->assertDontSee('Approve Entire Batch')->assertDontSee('Return Batch for Revision');
        $this->act($batch, 'forward', $this->dean)->assertRedirect();
        $this->act($batch, 'publish', $this->admin)->assertRedirect();
        $this->assertSame(Research::STATUS_APPROVED, $journal->fresh()->status);
    }

    public function test_assigned_year_and_department_cannot_be_overridden_and_uploads_require_valid_details(): void
    {
        $batch = $this->assign();
        $this->prepare($batch);
        $beforeFiles = Storage::disk('public')->allFiles();
        $data = $this->journalData();
        $data['school_year'] = '2024-2025';
        $data['department'] = 'College of Education';
        $this->post(route('admin.journal-batches.journals.store', $batch), $data)->assertSessionHasErrors(['school_year', 'department']);
        $data = $this->journalData();
        $data['authors'] = [' '];
        $data['file'] = UploadedFile::fake()->create('not-a-journal.txt', 1, 'text/plain');
        $this->post(route('admin.journal-batches.journals.store', $batch), $data)->assertSessionHasErrors('file');
        $data['file'] = $this->journalData()['file'];
        $this->post(route('admin.journal-batches.journals.store', $batch), $data)->assertSessionHasErrors('authors.0');
        $data['authors'] = array_fill(0, 13, 'Author');
        $this->post(route('admin.journal-batches.journals.store', $batch), $data)->assertSessionHasErrors('authors');
        $data['authors'] = [str_repeat('A', 151)];
        $this->post(route('admin.journal-batches.journals.store', $batch), $data)->assertSessionHasErrors('authors.0');
        $this->assertSame($beforeFiles, Storage::disk('public')->allFiles());
        $this->assertSame(1, $batch->journals()->count());
        $this->act($batch, 'submit', $this->coordinator)->assertRedirect();
        $this->actingAs($this->dean)->get(route('admin.journal-batches', ['school_year' => '2024-2025']))->assertOk()->assertDontSee($batch->title);
        $this->get(route('admin.journal-batches', ['school_year' => '2025-2026', 'stage' => 'dean_review']))->assertOk()->assertSee($batch->title);
    }

    public function test_batch_migration_upgrades_unstarted_tasks_and_preserves_existing_journals(): void
    {
        $migration = require database_path('migrations/2026_10_05_000001_add_academic_year_journal_batches.php');
        $migration->down();
        $paper = $this->source();
        $common = ['dean_id' => $this->dean->id, 'coordinator_id' => $this->coordinator->id,
            'department' => $this->dean->department, 'school_year' => '2025-2026'];
        $unstarted = ResearchHandoff::create(['title' => 'Unstarted task', 'status' => 'pending'] + $common);
        $existing = ResearchHandoff::create(['title' => 'Existing single journal', 'research_id' => $paper->id, 'status' => 'added'] + $common);
        $migration->up();
        $this->assertTrue($unstarted->fresh()->is_batch);
        $this->assertFalse($existing->fresh()->is_batch);
        $this->assertSame($paper->id, $existing->fresh()->research_id);
        $this->assertSame(Research::STATUS_APPROVED, $paper->fresh()->status);
        $migration->down();
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('research_handoffs', 'is_batch'));
        $this->assertSame('2025-2026', $paper->fresh()->school_year);
        $migration->up();
    }

    public function test_a_journal_from_another_batch_cannot_be_viewed_in_the_editor_or_updated(): void
    {
        $batch = $this->assign();
        $journal = $this->prepare($batch);
        $otherBatch = $this->assign();
        $beforeFiles = Storage::disk('public')->allFiles();
        $this->actingAs($this->coordinator)->get(route('admin.journal-batches.show', ['batch' => $otherBatch, 'edit' => $journal->id]))->assertNotFound();
        $this->patch(route('admin.journal-batches.journals.update', [$otherBatch, $journal]), $this->journalData())->assertNotFound();
        $this->assertSame($beforeFiles, Storage::disk('public')->allFiles());
        $this->assertSame($batch->id, $journal->fresh()->research_handoff_id);
        $this->assertSame(0, $otherBatch->journals()->count());
    }

    public function test_dean_must_choose_a_program_from_the_assigned_department(): void
    {
        $this->actingAs($this->dean)->get(route('admin.research-handoffs.create'))->assertOk()
            ->assertSee('name="program"', false)->assertSee('Information Technology')->assertSee('Computer Science')
            ->assertDontSee('Criminology')->assertDontSee('Journal Batch Title')->assertDontSee('name="title"', false);
        $data = ['title' => 'Prepare journals for AY 2025-2026', 'school_year' => '2025-2026',
            'submission_category' => Research::SUBMISSION_CATEGORY_STUDENT_JOURNAL, 'notes' => 'Prepare the completed journals.'];
        $this->post(route('admin.research-handoffs.store'), $data)->assertSessionHasErrors('program');
        $data['program'] = 'Criminology';
        $this->post(route('admin.research-handoffs.store'), $data)->assertSessionHasErrors('program');
        $this->assertSame(0, ResearchHandoff::count());
    }

    public function test_assignment_modal_retains_validation_errors_and_closes_only_after_a_successful_save(): void
    {
        $this->actingAs($this->dean)->get(route('admin.research-handoffs'))->assertOk()
            ->assertSee('data-batch-mode="assignment"', false);
        $url = route('admin.research-handoffs.create', ['embedded' => 1]);
        $this->get($url)->assertOk()->assertSee('data-journal-batch-embedded', false)
            ->assertSee('data-batch-title="Assign Journal Task"', false)
            ->assertSee('name="embedded" value="1"', false)->assertSee('data-close-embedded', false)
            ->assertSee('Assign Journal Preparation')->assertDontSee('class="admin-sidebar"', false)
            ->assertDontSee('data-close-on-success', false);

        $data = ['embedded' => 1, 'school_year' => '2025-2026', 'program' => 'Information Technology',
            'submission_category' => 'invalid', 'notes' => 'Keep these task instructions.'];
        $this->from($url)->post(route('admin.research-handoffs.store'), $data)
            ->assertRedirect($url)->assertSessionHasErrors('submission_category');
        $this->get($url)->assertOk()->assertSee('journal category')->assertSee('Keep these task instructions.')
            ->assertDontSee('data-close-on-success', false);
        $this->assertSame(0, ResearchHandoff::count());

        $data['submission_category'] = Research::SUBMISSION_CATEGORY_STUDENT_JOURNAL;
        $this->from($url)->post(route('admin.research-handoffs.store'), $data)
            ->assertRedirect($url)->assertSessionHas('success');
        $this->assertSame(1, ResearchHandoff::count());
        $this->assertDatabaseHas('research_handoffs', ['dean_id' => $this->dean->id,
            'coordinator_id' => $this->coordinator->id, 'department' => $this->dean->department,
            'program' => 'Information Technology', 'school_year' => '2025-2026']);
        $this->get($url)->assertOk()->assertSee('data-batch-updated', false)->assertSee('data-close-on-success', false);

        $this->actingAs($this->coordinator)->get($url)->assertForbidden();
        $this->actingAs($this->admin)->get($url)->assertForbidden();
        $this->coordinator->update(['is_active' => false]);
        $this->actingAs($this->dean)->get($url)->assertOk()->assertSee('No active Research Coordinator found.')
            ->assertSee('class="rhf-btn rhf-btn-primary" disabled', false);
    }

    public function test_preparing_journals_filter_lists_received_drafts_and_excludes_submitted_batches(): void
    {
        $batch = $this->assign();
        $unreceived = $this->assign('Computer Science');
        $parameters = ['program' => 'Information Technology', 'academic_year' => '2025-2026',
            'submission_category' => Research::SUBMISSION_CATEGORY_FACULTY_JOURNAL, 'status' => 'preparing'];
        $url = route('admin.coordinator.dean-submissions', $parameters);
        $this->actingAs($this->coordinator)->get($url)->assertOk()
            ->assertSeeInOrder(['>All Statuses</option>', '>Pending</option>', '>Preparing Journals</option>'], false)
            ->assertDontSee('>Submitted to Dean</option>', false)
            ->assertDontSee('>Received by Research Coordinator</option>', false)
            ->assertDontSee('>Forwarded to Admin</option>', false)->assertDontSee('>Published</option>', false)
            ->assertSee('value="preparing" selected', false)
            ->assertViewHas('handoffs', fn ($handoffs) => $handoffs->total() === 0);
        $this->post(route('admin.coordinator.dean-submissions.receive', $batch))->assertRedirect();
        $this->get($url)->assertOk()
            ->assertViewHas('handoffs', fn ($handoffs) => $handoffs->pluck('id')->all() === [$batch->id]);
        $this->prepare($batch);
        $other = $batch->fresh()->replicate();
        $other->fill(['title' => 'Preparing in another department', 'department' => 'College of Education'])->save();
        $this->get($url)->assertOk()
            ->assertViewHas('handoffs', fn ($handoffs) => $handoffs->pluck('id')->all() === [$batch->id]);
        $legacyJournal = $this->source(['status' => Research::STATUS_REJECTED,
            'submission_category' => Research::SUBMISSION_CATEGORY_FACULTY_JOURNAL]);
        $legacy = $batch->fresh()->replicate();
        $legacy->fill(['title' => 'Legacy journal being prepared', 'is_batch' => false, 'research_id' => $legacyJournal->id,
            'status' => ResearchHandoff::STATUS_ADDED])->save();
        $response = $this->get($url)->assertOk();
        $this->assertEqualsCanonicalizing([$batch->id, $legacy->id], $response->viewData('handoffs')->pluck('id')->all());
        $this->act($batch, 'submit', $this->coordinator)->assertRedirect();
        $this->get($url)->assertOk()
            ->assertViewHas('handoffs', fn ($handoffs) => $handoffs->pluck('id')->all() === [$legacy->id]);
        $this->assertSame(ResearchHandoff::STATUS_PENDING, $unreceived->fresh()->status);
    }

    public function test_assigned_journal_task_filters_combine_program_year_category_and_workflow_status(): void
    {
        $assigned = $this->assign();
        $student = $this->assign('Computer Science');
        $student->update(['school_year' => '2024-2025', 'submission_category' => Research::SUBMISSION_CATEGORY_STUDENT_JOURNAL]);
        $submitted = $assigned->replicate();
        $submitted->fill(['title' => 'Submitted faculty task', 'batch_stage' => Research::STATUS_DEAN_REVIEW,
            'status' => ResearchHandoff::STATUS_ADDED])->save();
        $preparing = $assigned->replicate();
        $preparing->fill(['title' => 'Received faculty task', 'status' => ResearchHandoff::STATUS_RECEIVED])->save();
        $otherDepartment = $submitted->replicate();
        $otherDepartment->fill(['title' => 'Task from another department', 'department' => 'College of Education'])->save();
        $otherCoordinator = $submitted->replicate();
        $otherCoordinator->fill(['title' => 'Another coordinator task', 'coordinator_id' => $this->staff(['is_research_coordinator' => true])->id])->save();
        $url = route('admin.coordinator.dean-submissions');
        $this->actingAs($this->coordinator)->get($url)->assertOk()
            ->assertSeeInOrder(['id="handoff-program"', 'id="handoff-academic-year"', 'id="handoff-category"',
                'id="handoff-status"', 'id="ct-clear"', '>Clear</a>'], false)
            ->assertDontSee('>Filter</button>', false)->assertSee('js/coordinator-task-filters.js')
            ->assertDontSee('id="handoff-search"', false)
            ->assertViewHas('handoffs', fn ($handoffs) => $handoffs->total() === 3);
        $filters = ['program' => 'Information Technology', 'academic_year' => '2025-2026',
            'submission_category' => Research::SUBMISSION_CATEGORY_FACULTY_JOURNAL, 'status' => 'submitted'];
        $this->get(route('admin.coordinator.dean-submissions', $filters))->assertOk()
            ->assertViewHas('handoffs', fn ($handoffs) => $handoffs->total() === 0)
            ->assertSee('value="faculty_research_journal" selected', false);
        $filters['status'] = 'preparing';
        $this->get(route('admin.coordinator.dean-submissions', $filters))->assertOk()
            ->assertViewHas('handoffs', fn ($handoffs) => $handoffs->pluck('id')->all() === [$preparing->id])
            ->assertSee('value="preparing" selected', false);
        $this->get(route('admin.coordinator.dean-submissions', ['program' => 'Computer Science', 'academic_year' => '2024-2025',
            'submission_category' => Research::SUBMISSION_CATEGORY_STUDENT_JOURNAL, 'status' => 'pending']))->assertOk()
            ->assertViewHas('handoffs', fn ($handoffs) => $handoffs->pluck('id')->all() === [$student->id]);
        $this->get(route('admin.coordinator.dean-submissions', ['status' => 'received']))->assertOk()
            ->assertViewHas('handoffs', fn ($handoffs) => $handoffs->pluck('id')->all() === [$preparing->id]);
        $this->get(route('admin.coordinator.dean-submissions', $filters + ['search' => 'No matching task']))->assertOk()
            ->assertViewHas('handoffs', fn ($handoffs) => $handoffs->total() === 0);

        $legacyJournal = $this->source(['submission_category' => Research::SUBMISSION_CATEGORY_FACULTY_JOURNAL,
            'status' => Research::STATUS_DRAFT]);
        $legacy = $assigned->replicate();
        $legacy->fill(['title' => 'Legacy faculty task', 'is_batch' => false, 'research_id' => $legacyJournal->id,
            'submission_category' => Research::SUBMISSION_CATEGORY_STUDENT_JOURNAL, 'status' => ResearchHandoff::STATUS_ADDED])->save();
        $response = $this->get(route('admin.coordinator.dean-submissions', $filters))->assertOk();
        $this->assertEqualsCanonicalizing([$legacy->id, $preparing->id], $response->viewData('handoffs')->pluck('id')->all());
        $this->get($url)->assertOk()->assertViewHas('handoffs', fn ($handoffs) => $handoffs->total() === 4);
    }

    public function test_it_and_computer_science_batches_in_the_same_year_keep_their_journals_separate(): void
    {
        $it = $this->assign('Information Technology');
        $cs = $this->assign('Computer Science');
        $this->assertSame('Information Technology Journals - AY 2025-2026', $it->title);
        $this->assertSame('Computer Science Journals - AY 2025-2026', $cs->title);
        $one = $this->prepare($it);
        $two = $this->prepare($cs);
        $this->assertSame('Information Technology', $one->program);
        $this->assertSame('Information Technology', $one->course);
        $this->assertSame('Computer Science', $two->program);
        $this->actingAs($this->coordinator)->get(route('admin.journal-batches.show', ['batch' => $it, 'edit' => $one->id]))
            ->assertOk()->assertSee('value="Information Technology" readonly', false);
        $data = $this->journalData();
        $data['course'] = 'Computer Science';
        $this->post(route('admin.journal-batches.journals.store', $it), $data)->assertSessionHasErrors('course');
        $this->patch(route('admin.journal-batches.journals.update', [$it, $one]), $data)->assertSessionHasErrors('course');
        $this->assertSame(1, $it->journals()->count());
        $this->assertSame('Information Technology', $one->fresh()->course);
        $this->get(route('admin.journal-batches', ['school_year' => '2025-2026', 'program' => 'Information Technology']))
            ->assertOk()->assertViewHas('batches', fn ($batches) => $batches->total() === 0);
        $this->get(route('admin.journal-batches', ['school_year' => '2025-2026', 'program' => 'Computer Science']))
            ->assertOk()->assertViewHas('batches', fn ($batches) => $batches->total() === 0);
        $this->get(route('admin.coordinator.dean-submissions', ['academic_year' => '2025-2026', 'program' => 'Information Technology']))
            ->assertOk()->assertSee($it->title)->assertDontSee($cs->title)
            ->assertSeeInOrder(['id="handoff-program"', 'id="handoff-academic-year"'], false)
            ->assertSee('id="handoff-status"', false)
            ->assertSee('value="Information Technology" selected', false);
        $this->get(route('admin.coordinator.dean-submissions', ['academic_year' => '2025-2026', 'program' => 'Computer Science']))
            ->assertOk()->assertSee($cs->title)->assertDontSee($it->title);
        $one->update(['program' => 'Computer Science']);
        $this->act($it, 'submit', $this->coordinator)->assertSessionHasErrors('batch');
        $this->assertSame(Research::STATUS_DRAFT, $it->fresh()->batch_stage);
        $this->assertSame(Research::STATUS_DRAFT, $two->fresh()->status);
    }

    public function test_older_assignments_without_a_program_keep_their_existing_journals(): void
    {
        $batch = $this->assign();
        $journal = $this->prepare($batch);
        $migration = require database_path('migrations/2026_10_05_000002_add_program_to_journal_assignments.php');
        $migration->down();
        $migration->up();
        $this->assertNull($batch->fresh()->program);
        $this->assertSame('Information Technology', $journal->fresh()->program);
        $this->actingAs($this->coordinator)->get(route('admin.journal-batches.show', ['batch' => $batch, 'edit' => $journal->id]))
            ->assertOk()->assertSee('Select Program')->assertSee('Information Technology');
        $this->act($batch, 'submit', $this->coordinator)->assertRedirect();
        $this->assertSame(Research::STATUS_DEAN_REVIEW, $journal->fresh()->status);
    }
}
