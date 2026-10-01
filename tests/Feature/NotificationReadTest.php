<?php

namespace Tests\Feature;

use App\Models\NotificationRead;
use App\Models\Research;
use App\Models\ResearchHandoff;
use App\Models\User;
use App\Notifications\AcademicPeriodActivated;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NotificationReadTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            foreach (['name', 'email', 'password', 'role'] as $field) $table->string($field);
            $table->string('department')->nullable();
            $table->boolean('is_department_dean')->default(false);
            $table->boolean('is_research_coordinator')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_approved')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('policy_accepted_at')->nullable();
            $table->string('policy_version')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('researches', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('department');
            $table->string('status');
            $table->unsignedBigInteger('coordinator_id')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('research_handoffs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('department');
            $table->string('status');
            $table->unsignedBigInteger('dean_id');
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
        });
        (require database_path('migrations/2026_10_02_000001_create_notifications_table.php'))->up();
        (require database_path('migrations/2026_10_02_000002_create_notification_reads_table.php'))->up();
    }

    private function member(array $attributes = []): User
    {
        $number = User::count() + 1;
        return User::create(array_replace([
            'name' => 'Account ' . $number, 'email' => "account{$number}@example.com", 'password' => 'password',
            'role' => 'admin', 'department' => 'College of Computer Studies',
            'is_active' => true, 'is_approved' => true, 'policy_accepted_at' => now(),
            'policy_version' => config('repository_policy.version', '2026-04-29'),
        ], $attributes));
    }

    private function seedNotification(User $user): void
    {
        if ($user->isGlobalAdmin()) {
            Research::create([
                'title' => 'Ready for Review', 'department' => $user->department,
                'status' => Research::STATUS_PENDING, 'coordinator_id' => 99,
            ]);
        } else {
            ResearchHandoff::create([
                'title' => 'Dean Paper', 'department' => $user->department, 'dean_id' => $user->id,
                'status' => $user->isDepartmentDean() ? ResearchHandoff::STATUS_RECEIVED : ResearchHandoff::STATUS_PENDING,
                'received_at' => $user->isDepartmentDean() ? now() : null,
            ]);
        }
    }

    public function test_each_staff_role_keeps_notifications_unread_until_explicitly_marked_and_persists_across_sessions(): void
    {
        foreach ([[], ['is_department_dean' => true], ['is_research_coordinator' => true]] as $flags) {
            $user = $this->member($flags);
            $this->seedNotification($user);
            $response = $this->actingAs($user)->getJson(route('admin.notifications.feed'))
                ->assertOk()->assertJsonPath('unread_count', 1)->assertJsonPath('notifications.0.is_read', false);
            $id = $response->json('notifications.0.id');
            $this->getJson(route('admin.notifications.feed'))->assertJsonPath('unread_count', 1);
            $this->postJson(route('admin.notifications.read'), ['id' => $id])
                ->assertOk()->assertJsonPath('unread_count', 0)->assertJsonPath('notifications.0.is_read', true);
            $savedTime = NotificationRead::where('user_id', $user->id)->where('notification_key', $id)->sole()->read_at;
            $this->travel(1)->minutes();
            $this->postJson(route('admin.notifications.read'), ['id' => $id])->assertJsonPath('unread_count', 0);
            $this->assertTrue($savedTime->equalTo(NotificationRead::where('user_id', $user->id)->sole()->read_at));
            $this->app['auth']->forgetGuards();
            $this->actingAs($user->fresh())->getJson(route('admin.notifications.feed'))
                ->assertJsonPath('unread_count', 0)->assertJsonPath('notifications.0.is_read', true);
        }
    }

    public function test_bulk_read_covers_all_pages_only_for_current_account_and_new_events_remain_unread(): void
    {
        $admin = $this->member();
        $otherAdmin = $this->member();
        for ($index = 0; $index < 60; $index++) $this->seedNotification($admin);
        $this->actingAs($admin)->getJson(route('admin.notifications.feed'))
            ->assertJsonCount(50, 'notifications')->assertJsonPath('unread_count', 60)->assertJsonPath('has_more', true);
        $this->postJson(route('admin.notifications.read-all'))->assertOk()->assertJsonPath('unread_count', 0);
        $this->assertSame(60, NotificationRead::where('user_id', $admin->id)->count());
        $page = $this->getJson(route('admin.notifications.feed', ['page' => 2]))
            ->assertJsonCount(10, 'notifications')->assertJsonPath('unread_count', 0)->assertJsonPath('has_more', false);
        $this->assertTrue(collect($page->json('notifications'))->every(fn ($item) => $item['is_read']));
        $this->postJson(route('admin.notifications.read-all'))->assertJsonPath('unread_count', 0);
        $this->assertSame(60, NotificationRead::where('user_id', $admin->id)->count());
        $this->actingAs($otherAdmin)->getJson(route('admin.notifications.feed'))->assertJsonPath('unread_count', 60);
        $this->seedNotification($admin);
        $this->actingAs($admin)->getJson(route('admin.notifications.feed'))->assertJsonPath('unread_count', 1);
    }

    public function test_department_scope_and_recipient_ownership_apply_to_read_actions(): void
    {
        $dean = $this->member(['is_department_dean' => true]);
        $otherDean = $this->member(['is_department_dean' => true, 'department' => 'College of Education']);
        $this->seedNotification($dean);
        $this->seedNotification($otherDean);
        $foreignId = $this->actingAs($otherDean)->getJson(route('admin.notifications.feed'))->json('notifications.0.id');
        $this->actingAs($dean)->postJson(route('admin.notifications.read'), ['id' => $foreignId])->assertNotFound();
        $this->postJson(route('admin.notifications.read'), ['id' => 'fake:123'])->assertNotFound();
        $this->postJson(route('admin.notifications.read'))->assertUnprocessable();
        $this->assertSame(0, NotificationRead::count());
        $this->postJson(route('admin.notifications.read-all'))->assertJsonPath('unread_count', 0);
        $this->actingAs($otherDean)->getJson(route('admin.notifications.feed'))->assertJsonPath('unread_count', 1);
        $coordinator = $this->member(['is_research_coordinator' => true]);
        $this->seedNotification($coordinator);
        $this->actingAs($coordinator)->getJson(route('admin.notifications.feed'))->assertJsonPath('total_count', 1);
    }

    public function test_reading_academic_period_alert_updates_its_saved_notification_without_affecting_another_dean(): void
    {
        $dean = $this->member(['is_department_dean' => true]);
        $otherDean = $this->member(['is_department_dean' => true]);
        $data = ['message' => 'AY 2026-2027 is now active.', 'activated_at' => now()->toIso8601String(), 'url' => route('admin.users')];
        foreach ([$dean, $otherDean] as $recipient) $recipient->notifyNow(new AcademicPeriodActivated($data));
        $id = $this->actingAs($dean)->getJson(route('admin.notifications.feed'))->json('notifications.0.id');
        $this->postJson(route('admin.notifications.read'), ['id' => $id])->assertJsonPath('unread_count', 0);
        $this->assertNotNull($dean->notifications()->sole()->read_at);
        $this->assertNull($otherDean->notifications()->sole()->read_at);
        $this->getJson(route('admin.dean-notifications'))->assertJsonPath('notifications.0.is_read', true);
    }

    public function test_resubmitted_review_and_coordinator_review_outcomes_have_independent_read_state(): void
    {
        $admin = $this->member();
        $coordinator = $this->member(['is_research_coordinator' => true]);
        $this->seedNotification($admin);
        $research = Research::firstOrFail();
        $this->actingAs($admin)->postJson(route('admin.notifications.read-all'))->assertJsonPath('unread_count', 0);
        $this->travel(1)->minutes();
        $research->update(['status' => Research::STATUS_REJECTED, 'rejection_reason' => 'Correct the references.']);
        $this->actingAs($coordinator)->getJson(route('admin.notifications.feed'))
            ->assertJsonPath('unread_count', 1)->assertSee('Correct the references.');
        $this->postJson(route('admin.notifications.read-all'))->assertJsonPath('unread_count', 0);
        $this->travel(1)->minutes();
        $research->update(['status' => Research::STATUS_PENDING]);
        $this->actingAs($admin)->getJson(route('admin.notifications.feed'))->assertJsonPath('unread_count', 1);
        $research->update(['status' => Research::STATUS_APPROVED, 'approved_at' => now()]);
        $this->actingAs($coordinator)->getJson(route('admin.notifications.feed'))
            ->assertJsonPath('unread_count', 1)->assertSee('accepted by Admin');
    }

    public function test_guests_students_faculty_and_unassigned_staff_cannot_use_notification_controls(): void
    {
        $this->getJson(route('admin.notifications.feed'))->assertUnauthorized();
        foreach ([['role' => 'user'], ['role' => 'researcher'],
            ['is_department_dean' => true, 'department' => null],
            ['is_research_coordinator' => true, 'department' => null],
        ] as $attributes) {
            $user = $this->member($attributes);
            $this->actingAs($user)->getJson(route('admin.notifications.feed'))->assertForbidden();
            $this->postJson(route('admin.notifications.read'), ['id' => 'received:1'])->assertForbidden();
            $this->postJson(route('admin.notifications.read-all'))->assertForbidden();
        }
    }

    public function test_all_staff_layouts_render_the_shared_bell_and_coordinator_page_uses_the_same_controls(): void
    {
        \Illuminate\Support\Facades\Route::middleware(['web', 'auth', 'admin'])
            ->get('/notification-layout-test', fn () => view('layouts.admin'));
        foreach ([[], ['is_department_dean' => true], ['is_research_coordinator' => true]] as $flags) {
            $user = $this->member($flags);
            $this->actingAs($user)->get('/notification-layout-test')->assertOk()
                ->assertSee('id="notificationToggle"', false)->assertSee('class="topbar-notification-count"', false)
                ->assertSee('data-notification-panel', false)->assertSee('Mark All as Read')
                ->assertSee('js/notifications.js', false)->assertDontSee('localStorage', false);
            if ($user->isResearchCoordinator()) {
                $response = $this->get(route('admin.coordinator.notifications'))->assertOk();
                $this->assertSame(2, substr_count($response->getContent(), 'data-notification-view'));
                $response->assertSee('Research Requiring Coordinator Action');
            }
        }
    }
}
