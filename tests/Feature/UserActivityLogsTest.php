<?php

namespace Tests\Feature;

use App\Http\Controllers\UserActivityLogController;
use App\Models\User;
use App\Models\UserActivityLog;
use App\Services\UserActivity;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class UserActivityLogsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::dropIfExists('user_activity_logs');
        Schema::dropIfExists('users');
        Schema::create('users', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('email')->unique(); $table->string('password');
            $table->string('role')->default('user'); $table->string('department')->nullable(); $table->string('student_id')->nullable();
            $table->unsignedSmallInteger('graduation_year')->nullable(); $table->boolean('is_department_dean')->default(false);
            $table->boolean('is_research_coordinator')->default(false); $table->boolean('is_active')->default(true);
            $table->boolean('is_approved')->default(true); $table->rememberToken(); $table->timestamps();
        });
        Schema::create('user_activity_logs', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('user_id')->nullable(); $table->string('department');
            $table->string('user_name'); $table->string('role'); $table->string('action'); $table->text('details');
            $table->timestamp('created_at');
        });
    }

    public function test_dean_only_sees_students_and_faculty_in_assigned_department(): void
    {
        $dean = $this->user('Dean', 'admin', 'CCS', ['is_department_dean' => true]);
        $student = $this->user('Student Match', 'user', 'CCS', ['student_id' => '1001']);
        $faculty = $this->user('Faculty Match', 'researcher', 'CCS');
        $other = $this->user('Other Student', 'user', 'CEA', ['student_id' => '2001']);
        UserActivity::record($student, 'login', 'Signed in successfully.');
        UserActivity::record($faculty, 'saved_research', 'Saved research: Test');
        UserActivity::record($other, 'login', 'Signed in successfully.');
        $this->actingAs($dean);

        $view = app(UserActivityLogController::class)->index(Request::create('/admin/user-activity-logs'));
        $this->assertSame(['Faculty Match', 'Student Match'], $view->getData()['logs']->pluck('user_name')->sort()->values()->all());

        $filtered = app(UserActivityLogController::class)->index(Request::create('/admin/user-activity-logs', 'GET', [
            'search' => 'Student', 'role' => 'student', 'action' => 'login', 'date' => now()->format('Y-m-d'),
        ]));
        $this->assertSame([$student->id], $filtered->getData()['logs']->pluck('user_id')->all());
        $this->assertStringNotContainsString('Other Student', $filtered->with('errors', new \Illuminate\Support\ViewErrorBag())->render());
    }

    public function test_non_dean_cannot_open_activity_logs(): void
    {
        $this->actingAs($this->user('Global Admin', 'admin', null));
        $this->expectException(HttpException::class);
        app(UserActivityLogController::class)->index(Request::create('/admin/user-activity-logs'));
    }

    public function test_system_history_only_includes_allowed_actions_and_preserves_actor(): void
    {
        $admin = $this->user('Main Admin', 'admin', null);
        $dean = $this->user('Department Dean', 'admin', 'CCS', ['is_department_dean' => true]);
        $student = $this->user('Student', 'user', 'CCS');
        UserActivity::record($admin, 'login', 'Old wording');
        UserActivity::record($dean, 'research_forwarded', 'Dean forwarded “Test Title” to the Research Coordinator.');
        UserActivity::record($student, 'viewed_research', 'Viewed research');
        $this->actingAs($admin);
        $view = app(UserActivityLogController::class)->systemIndex(Request::create('/admin/activity-logs'));
        $logs = $view->getData()['logs'];
        $this->assertCount(2, $logs);
        $this->assertSame(['research_forwarded', 'login'], $logs->pluck('action')->all());
        $this->assertSame('dean', $logs->first()->role);
        $this->assertSame('User logged in to the system.', $logs->last()->details);
        $admin->update(['name' => 'Renamed Admin']);
        $this->assertSame('Main Admin', $logs->last()->user_name);
    }

    public function test_system_history_filters_use_manila_calendar_date(): void
    {
        config(['app.timezone' => 'UTC']);
        $admin = $this->user('Main Admin', 'admin', null);
        $this->actingAs($admin);
        UserActivity::record($admin, 'report_exported', 'Admin exported a system report.');
        \App\Models\UserActivityLog::query()->update(['created_at' => '2026-09-30 16:30:00']);
        $view = app(UserActivityLogController::class)->systemIndex(Request::create('/admin/activity-logs', 'GET', [
            'date' => '2026-10-01', 'action' => 'report_exported', 'search' => 'Main',
        ]));
        $this->assertSame(1, $view->getData()['logs']->total());
        $view = app(UserActivityLogController::class)->systemIndex(Request::create('/admin/activity-logs', 'GET', ['date' => '2026-09-30']));
        $this->assertSame(0, $view->getData()['logs']->total());
    }

    public function test_dean_cannot_access_system_history(): void
    {
        $this->actingAs($this->user('Dean', 'admin', 'CCS', ['is_department_dean' => true]));
        $this->expectException(HttpException::class);
        app(UserActivityLogController::class)->systemIndex(Request::create('/admin/activity-logs'));
    }

    public function test_toggle_and_logout_record_completed_actions(): void
    {
        $admin = $this->user('Main Admin', 'admin', null);
        $student = $this->user('Student', 'user', 'CCS');
        $this->actingAs($admin)->withoutMiddleware();
        app(\App\Http\Controllers\AdminController::class)->toggleUserStatus($student->fresh());
        $this->assertDatabaseHas('user_activity_logs', ['user_id' => $admin->id, 'action' => 'account_deactivated', 'details' => 'Admin deactivated a user account.']);
        app(\App\Http\Controllers\AdminController::class)->toggleUserStatus($student->fresh());
        $this->assertDatabaseHas('user_activity_logs', ['action' => 'account_activated']);
        $request = Request::create('/logout', 'POST');
        $request->setLaravelSession(app('session.store'));
        app(\App\Http\Controllers\AuthController::class)->logout($request);
        $this->assertDatabaseHas('user_activity_logs', ['user_id' => $admin->id, 'action' => 'logout', 'details' => 'User logged out of the system.']);
    }

    public function test_system_role_department_and_student_year_filters(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->unsignedTinyInteger('year_level')->nullable());
        $admin = $this->user('Admin', 'admin', null);
        $first = $this->user('First Year', 'user', 'CCS', ['year_level' => 1]);
        $second = $this->user('Second Year', 'user', 'CCS', ['year_level' => 2]);
        $other = $this->user('Other Department', 'user', 'CEA', ['year_level' => 1]);
        $faculty = $this->user('Faculty', 'researcher', 'CCS');
        foreach ([$first, $second, $other, $faculty] as $user) UserActivity::record($user, 'login', 'Login');
        $this->actingAs($admin);
        $view = app(UserActivityLogController::class)->systemIndex(Request::create('/admin/activity-logs', 'GET', [
            'role' => 'student', 'department' => 'CCS', 'year_level' => 1,
        ]));
        $this->assertSame([$first->id], $view->getData()['logs']->pluck('user_id')->all());
        $view = app(UserActivityLogController::class)->systemIndex(Request::create('/admin/activity-logs', 'GET', [
            'role' => 'faculty', 'department' => 'CCS', 'year_level' => 1,
        ]));
        $this->assertSame([$faculty->id], $view->getData()['logs']->pluck('user_id')->all());
    }

    private function user(string $name, string $role, ?string $department, array $extra = []): User
    {
        return User::create(array_merge(['name' => $name, 'email' => strtolower(str_replace(' ', '.', $name)) . '@test.local',
            'password' => 'password', 'role' => $role, 'department' => $department], $extra));
    }
}
