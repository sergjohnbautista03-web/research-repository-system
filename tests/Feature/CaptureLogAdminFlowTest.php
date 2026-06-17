<?php

namespace Tests\Feature;

use App\Models\CaptureAttemptLog;
use App\Models\Research;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CaptureLogAdminFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('capture_log_reads');
        Schema::dropIfExists('capture_attempt_logs');
        Schema::dropIfExists('researches');
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('user');
            $table->string('department')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_approved')->default(true);
            $table->boolean('is_department_dean')->default(false);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('policy_accepted_at')->nullable();
            $table->string('policy_version')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('researches', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('department')->nullable();
            $table->string('status')->default('approved');
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('capture_attempt_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('research_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('event_type', 60);
            $table->string('viewer_name')->nullable();
            $table->string('viewer_email')->nullable();
            $table->string('viewer_department')->nullable();
            $table->string('viewer_scope', 30)->default('standard');
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('details')->nullable();
            $table->timestamps();
        });

        Schema::create('capture_log_reads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('capture_attempt_log_id');
            $table->timestamps();
            $table->unique(['user_id', 'capture_attempt_log_id']);
        });
    }

    public function test_capture_logs_default_view_shows_security_events_not_login_events(): void
    {
        $admin = $this->admin();
        $securityLog = $this->captureLog(CaptureAttemptLog::EVENT_PROTECTED_VIEW_OPENED);
        $this->captureLog(CaptureAttemptLog::EVENT_LOGIN_SUCCESS);

        $response = $this->actingAs($admin)->get(route('admin.capture-attempt-logs'));

        $response->assertOk();

        $logs = $response->viewData('logs');
        $activityLogs = $response->viewData('activityLogs');

        $this->assertSame(1, $logs->total());
        $this->assertSame($securityLog->id, $logs->getCollection()->first()->id);
        $this->assertSame([$securityLog->id], $activityLogs->pluck('id')->all());
    }

    public function test_capture_log_summary_counts_only_unread_security_events(): void
    {
        $admin = $this->admin();
        $securityLog = $this->captureLog(CaptureAttemptLog::EVENT_PROTECTED_VIEW_OPENED);
        $this->captureLog(CaptureAttemptLog::EVENT_LOGIN_SUCCESS);

        $response = $this->actingAs($admin)->getJson(route('admin.capture-attempt-logs.summary'));

        $response->assertOk()
            ->assertJson([
                'latest_id' => $securityLog->id,
                'unread_count' => 1,
                'event' => 'Protected View Opened',
            ]);
    }

    public function test_mark_viewed_marks_only_security_logs_as_read(): void
    {
        $admin = $this->admin();
        $securityLog = $this->captureLog('copy_blocked');
        $loginLog = $this->captureLog(CaptureAttemptLog::EVENT_LOGIN_SUCCESS);

        $response = $this->actingAs($admin)->postJson(route('admin.capture-attempt-logs.mark-viewed'), [
            'log_ids' => [$securityLog->id, $loginLog->id],
        ]);

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'unread_count' => 0,
                'read_log_ids' => [$securityLog->id],
            ]);

        $this->assertDatabaseHas('capture_log_reads', [
            'user_id' => $admin->id,
            'capture_attempt_log_id' => $securityLog->id,
        ]);

        $this->assertDatabaseMissing('capture_log_reads', [
            'user_id' => $admin->id,
            'capture_attempt_log_id' => $loginLog->id,
        ]);
    }

    public function test_department_dean_file_open_is_recorded_for_main_admin_review(): void
    {
        $dean = $this->dean();
        $research = $this->research();

        $response = $this->actingAs($dean)->postJson(route('research.capture-attempt', $research), [
            'event_type' => CaptureAttemptLog::EVENT_PROTECTED_VIEW_OPENED,
            'scope' => 'admin',
            'details' => ['viewer' => 'admin'],
        ]);

        $response->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseHas('capture_attempt_logs', [
            'research_id' => $research->id,
            'user_id' => $dean->id,
            'event_type' => CaptureAttemptLog::EVENT_PROTECTED_VIEW_OPENED,
            'viewer_name' => 'CCS Dean',
            'viewer_scope' => 'admin',
        ]);

        $response = $this->actingAs($this->admin())->get(route('admin.capture-attempt-logs'));

        $response->assertOk();
        $this->assertSame(1, $response->viewData('logs')->total());
    }

    public function test_capture_attempts_are_deduplicated_until_next_audit_day(): void
    {
        try {
            $dean = $this->dean();
            $research = $this->research();

            Carbon::setTestNow(Carbon::parse('2026-05-05 10:00:00', 'Asia/Manila'));

            $this->postProtectedViewOpened($dean, $research)->assertOk();
            $this->postProtectedViewOpened($dean, $research)->assertOk();

            $this->assertSame(1, CaptureAttemptLog::query()->count());

            Carbon::setTestNow(Carbon::parse('2026-05-06 10:00:00', 'Asia/Manila'));

            $this->postProtectedViewOpened($dean, $research)->assertOk();

            $this->assertSame(2, CaptureAttemptLog::query()->count());
        } finally {
            Carbon::setTestNow();
        }
    }

    private function admin(): User
    {
        return User::create([
            'name' => 'Main Admin',
            'email' => 'admin@example.com',
            'password' => 'password',
            'role' => 'admin',
            'is_active' => true,
            'is_approved' => true,
            'policy_accepted_at' => now(),
            'policy_version' => config('repository_policy.version', '2026-04-29'),
        ]);
    }

    private function dean(): User
    {
        return User::create([
            'name' => 'CCS Dean',
            'email' => 'ccs-dean@example.com',
            'password' => 'password',
            'role' => 'admin',
            'department' => 'College of Computer Studies',
            'is_department_dean' => true,
            'is_active' => true,
            'is_approved' => true,
            'policy_accepted_at' => now(),
            'policy_version' => config('repository_policy.version', '2026-04-29'),
        ]);
    }

    private function research(): Research
    {
        return Research::create([
            'title' => 'Protected Research',
            'department' => 'College of Computer Studies',
            'status' => 'approved',
        ]);
    }

    private function postProtectedViewOpened(User $user, Research $research)
    {
        return $this->actingAs($user)->postJson(route('research.capture-attempt', $research), [
            'event_type' => CaptureAttemptLog::EVENT_PROTECTED_VIEW_OPENED,
            'scope' => 'admin',
            'details' => ['viewer' => 'admin'],
        ]);
    }

    private function captureLog(string $eventType): CaptureAttemptLog
    {
        return CaptureAttemptLog::create([
            'event_type' => $eventType,
            'viewer_name' => 'Viewer User',
            'viewer_email' => 'viewer@example.com',
            'viewer_department' => 'College of Computer Studies',
            'viewer_scope' => 'standard',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ]);
    }
}
