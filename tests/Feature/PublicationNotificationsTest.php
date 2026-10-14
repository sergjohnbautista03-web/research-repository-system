<?php

namespace Tests\Feature;

use App\Models\Research;
use App\Models\ResearchHandoff;
use App\Models\User;
use App\Notifications\JournalBatchPublished;
use App\Services\PublicationNotifications;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\JournalWorkflowSchema;
use Tests\TestCase;

class PublicationNotificationsTest extends TestCase
{
    use JournalWorkflowSchema;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createJournalWorkflowSchema();
        Storage::fake('public');
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-13 09:00:00', 'Asia/Manila'));
        $this->admin = $this->member(['role' => 'admin']);
    }

    private function member(array $attributes = []): User
    {
        $number = User::count() + 1;

        return User::create($attributes + [
            'name' => 'Member '.$number, 'email' => 'publication'.$number.'@example.test',
            'password' => 'password', 'role' => 'user', 'department' => 'College of Computer Studies',
            'is_active' => true, 'is_approved' => true, 'policy_accepted_at' => now(),
            'policy_version' => config('repository_policy.version'),
        ]);
    }

    private function pendingBatch(string $title = 'Technology Journals'): ResearchHandoff
    {
        $batch = ResearchHandoff::create([
            'title' => $title, 'department' => $this->admin->department, 'school_year' => '2026-2027',
            'is_batch' => true, 'batch_stage' => Research::STATUS_PENDING,
            'batch_forwarded_at' => now(), 'status' => ResearchHandoff::STATUS_ADDED,
        ]);
        $path = 'researches/publication-'.$batch->id.'.pdf';
        Storage::disk('public')->put($path, '%PDF-1.4 journal');
        $batch->journals()->create([
            'title' => 'Completed Journal '.$batch->id, 'department' => $batch->department,
            'school_year' => $batch->school_year, 'status' => Research::STATUS_PENDING,
            'authors' => ['Journal Author'], 'abstract' => 'Published journal abstract.',
            'type' => 'Quantitative', 'submission_category' => Research::SUBMISSION_CATEGORY_FACULTY_JOURNAL,
            'year_published' => 2026, 'file_path' => $path,
        ]);

        return $batch;
    }

    private function publish(ResearchHandoff $batch): void
    {
        $this->actingAs($this->admin)->post(route('admin.journal-batches.transition', [$batch, 'publish']))
            ->assertRedirect(route('admin.journal-batches.show', $batch));
    }

    public function test_publication_notifies_only_active_approved_students_and_faculty_in_its_department(): void
    {
        $student = $this->member(['graduation_year' => 2027]);
        $faculty = $this->member(['role' => 'researcher']);
        $currentCohort = $this->member(['role' => 'researcher', 'graduation_year' => 2026]);
        foreach ([
            ['is_active' => false], ['role' => 'researcher', 'is_active' => false], ['is_approved' => false],
            ['graduation_year' => 2025], ['role' => 'researcher', 'graduation_year' => 2025],
            ['role' => 'researcher', 'researcher_end_date' => '2026-10-12'],
            ['department' => 'College of Education'], ['department' => null], ['role' => 'guest'],
            ['role' => 'admin', 'is_department_dean' => true],
        ] as $attributes) {
            $this->member($attributes);
        }
        $batch = $this->pendingBatch();
        $this->assertSame(0, DB::table('notifications')->count());
        $this->publish($batch);
        $this->assertEqualsCanonicalizing([$student->id, $faculty->id, $currentCohort->id],
            DB::table('notifications')->pluck('notifiable_id')->all());
        $this->assertSame(Research::STATUS_APPROVED, $batch->fresh()->batch_stage);
        foreach ([$student, $faculty, $currentCohort] as $recipient) {
            $notification = $recipient->notifications()->sole();
            $this->assertSame(JournalBatchPublished::class, $notification->type);
            $this->assertSame($batch->title, $notification->data['title']);
            $this->assertSame($batch->department, $notification->data['department']);
            $this->assertSame($batch->fresh()->batch_published_at->toIso8601String(), $notification->data['published_at']);
            $this->assertSame(route('research.journal-batch', $batch), $notification->data['url']);
            $this->assertStringContainsString('October 13, 2026', $notification->data['message']);
            $this->assertNull($notification->read_at);
        }
        $laterUser = $this->member();
        $this->actingAs($laterUser)->getJson(route('notifications.feed'))->assertOk()->assertJsonPath('total_count', 0);
    }

    public function test_individual_and_bulk_reads_decrease_counts_persist_and_do_not_affect_other_recipients(): void
    {
        $student = $this->member();
        $faculty = $this->member(['role' => 'researcher']);
        $this->publish($this->pendingBatch('First Publication'));
        $this->travel(1)->minutes();
        $this->publish($this->pendingBatch('Second Publication'));
        $response = $this->actingAs($student)->getJson(route('notifications.feed'))->assertOk()
            ->assertJsonPath('unread_count', 2)->assertJsonPath('notifications.0.title', 'Second Publication');
        $id = $response->json('notifications.0.id');
        $this->getJson(route('notifications.feed'))->assertJsonPath('unread_count', 2);
        $this->postJson(route('notifications.read'), ['id' => $id])->assertOk()
            ->assertJsonPath('unread_count', 1)->assertJsonPath('notifications.0.is_read', true);
        $readAt = $student->notifications()->whereNotNull('read_at')->sole()->read_at;
        $this->travel(1)->minutes();
        $this->postJson(route('notifications.read'), ['id' => $id])->assertJsonPath('unread_count', 1);
        $this->assertTrue($readAt->equalTo($student->notifications()->whereNotNull('read_at')->sole()->read_at));
        $this->postJson(route('notifications.read-all'))->assertOk()->assertJsonPath('unread_count', 0);
        $this->assertSame(0, $student->unreadNotifications()->count());
        $this->app['auth']->forgetGuards();
        $this->actingAs($student)->getJson(route('notifications.feed'))->assertJsonPath('unread_count', 0);
        $this->actingAs($faculty)->getJson(route('notifications.feed'))->assertJsonPath('unread_count', 2);
        $this->postJson(route('notifications.read-all'))->assertJsonPath('unread_count', 0);
    }

    public function test_repeated_publication_and_delivery_do_not_duplicate_alerts_or_reset_read_state(): void
    {
        $student = $this->member();
        $batch = $this->pendingBatch();
        $this->publish($batch);
        $this->actingAs($student)->postJson(route('notifications.read-all'))->assertJsonPath('unread_count', 0);
        $original = $student->notifications()->sole();
        $this->travel(1)->minutes();
        foreach (range(1, 2) as $attempt) app(PublicationNotifications::class)->sendForBatch($batch->fresh());
        $this->actingAs($this->admin)->post(route('admin.journal-batches.transition', [$batch, 'publish']))->assertStatus(409);
        $this->assertSame(1, $student->notifications()->count());
        $this->assertTrue($original->read_at->equalTo($student->notifications()->sole()->read_at));
        $this->actingAs($student)->getJson(route('notifications.feed'))->assertJsonPath('unread_count', 0);
    }

    public function test_foreign_notifications_and_department_changes_cannot_expose_or_modify_another_feed(): void
    {
        $student = $this->member();
        $faculty = $this->member(['role' => 'researcher']);
        $this->publish($this->pendingBatch());
        $id = $this->actingAs($faculty)->getJson(route('notifications.feed'))->json('notifications.0.id');
        $this->actingAs($student)->postJson(route('notifications.read'), ['id' => $id])->assertNotFound();
        $this->postJson(route('notifications.read'), ['id' => 'publication:fake'])->assertNotFound();
        $this->postJson(route('notifications.read'))->assertUnprocessable();
        $ownId = $this->getJson(route('notifications.feed'))->json('notifications.0.id');
        $student->update(['department' => 'College of Education']);
        $this->getJson(route('notifications.feed'))->assertJsonPath('total_count', 0)->assertJsonPath('unread_count', 0);
        $this->postJson(route('notifications.read'), ['id' => $ownId])->assertNotFound();
        $this->assertSame(1, $faculty->unreadNotifications()->count());
    }

    public function test_guests_and_ineligible_users_cannot_access_member_notification_controls(): void
    {
        $this->getJson(route('notifications.feed'))->assertUnauthorized();
        foreach ([['is_active' => false], ['is_approved' => false], ['graduation_year' => 2025],
            ['researcher_end_date' => '2026-10-12'], ['department' => null], ['role' => 'guest'],
            ['role' => 'admin', 'is_active' => false]] as $attributes) {
            $this->actingAs($this->member($attributes))->getJson(route('notifications.feed'))->assertForbidden();
            $this->postJson(route('notifications.read'), ['id' => 'publication:fake'])->assertForbidden();
            $this->postJson(route('notifications.read-all'))->assertForbidden();
        }
    }

    public function test_failed_or_unauthorized_publication_does_not_notify_or_change_the_batch(): void
    {
        $this->member();
        $batch = $this->pendingBatch();
        $dean = $this->member(['role' => 'admin', 'is_department_dean' => true]);
        $this->actingAs($dean)->post(route('admin.journal-batches.transition', [$batch, 'publish']))->assertForbidden();
        Storage::disk('public')->delete($batch->journals()->sole()->file_path);
        $this->actingAs($this->admin)->post(route('admin.journal-batches.transition', [$batch, 'publish']))->assertSessionHasErrors('batch');
        $this->assertSame(0, DB::table('notifications')->count());
        $this->assertSame(Research::STATUS_PENDING, $batch->fresh()->batch_stage);
        $this->assertSame(Research::STATUS_PENDING, $batch->journals()->sole()->status);
    }

    public function test_notification_destination_shows_published_journals_and_hides_unpublished_batches(): void
    {
        $member = $this->member();
        $batch = $this->pendingBatch();
        $this->get(route('research.journal-batch', $batch))->assertNotFound();
        $this->publish($batch);
        $url = $member->notifications()->sole()->data['url'];
        $this->actingAs($member)->get($url)->assertOk()->assertSee($batch->title)
            ->assertSee($batch->department)->assertSee('October 13, 2026')
            ->assertSee(route('research.show', $batch->journals()->sole()), false);
        $batch->journals()->sole()->update(['status' => Research::STATUS_PENDING]);
        $this->get($url)->assertOk()->assertDontSee('Completed Journal '.$batch->id);
    }

    public function test_student_and_faculty_layouts_use_the_shared_bell_panel_and_read_controls(): void
    {
        Route::middleware(['web', 'auth'])->get('/publication-layout', fn () => view('layouts.user-dashboard', ['pinnedResearches' => collect()]));
        foreach (['user', 'researcher'] as $role) {
            $member = $this->member(['role' => $role]);
            $this->actingAs($member)->get('/publication-layout')->assertOk()->assertSee('id="notificationToggle"', false)
                ->assertSee('class="topbar-notification-count"', false)->assertSee('data-notification-panel', false)
                ->assertSee(route('notifications.feed'), false)->assertSee('Mark All as Read')
                ->assertSee('js/notifications.js')->assertSee('css/notifications.css');
            foreach (['about', 'faq', 'contact'] as $page) {
                $this->get(route($page))->assertOk()->assertDontSee('id="notificationToggle"', false)
                    ->assertDontSee('data-notification-panel', false)->assertDontSee('js/notifications.js');
            }
        }
        $this->actingAs($this->member(['is_active' => false]))->get(route('about'))->assertOk()
            ->assertDontSee('id="notificationToggle"', false);
    }
}
