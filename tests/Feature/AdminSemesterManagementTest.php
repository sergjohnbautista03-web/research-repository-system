<?php

namespace Tests\Feature;

use App\Models\Semester;
use App\Models\SemesterEnrollment;
use App\Models\User;
use App\Mail\AcademicPeriodActivatedMail;
use App\Notifications\AcademicPeriodActivated;
use App\Services\SemesterWorkflow;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminSemesterManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('notifications');
        (require database_path('migrations/2026_10_02_000001_create_notifications_table.php'))->up();
        Schema::dropIfExists('notification_reads');
        (require database_path('migrations/2026_10_02_000002_create_notification_reads_table.php'))->up();

        Schema::dropIfExists('capture_log_reads');
        Schema::dropIfExists('capture_attempt_logs');
        Schema::dropIfExists('semester_enrollments');
        Schema::dropIfExists('researches');
        Schema::dropIfExists('semesters');
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('middle_name')->nullable();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('user');
            $table->string('department')->nullable();
            $table->unsignedBigInteger('current_semester_id')->nullable();
            $table->string('student_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_approved')->default(true);
            $table->unsignedBigInteger('student_approved_by')->nullable();
            $table->timestamp('student_approved_at')->nullable();
            $table->unsignedBigInteger('researcher_approved_by')->nullable();
            $table->timestamp('researcher_approved_at')->nullable();
            $table->boolean('is_department_dean')->default(false);
            $table->boolean('is_research_coordinator')->default(false);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('policy_accepted_at')->nullable();
            $table->string('policy_version')->nullable();
            $table->unsignedTinyInteger('year_level')->nullable();
            $table->unsignedTinyInteger('course_duration')->nullable();
            $table->unsignedSmallInteger('graduation_year')->nullable();
            $table->date('researcher_end_date')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('semesters', function (Blueprint $table) {
            $table->id();
            $table->string('school_year', 9);
            $table->string('semester', 3);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->unique(['school_year', 'semester']);
        });

        Schema::create('semester_enrollments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('semester_id');
            $table->string('status', 20)->default(SemesterEnrollment::STATUS_ACTIVE);
            $table->timestamp('enrolled_at')->nullable();
            $table->unsignedBigInteger('enrolled_by')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'semester_id']);
        });

        Schema::create('researches', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('abstract')->nullable();
            $table->string('author_name')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('submission_category')->nullable();
            $table->string('type')->nullable();
            $table->unsignedBigInteger('semester_id')->nullable();
            $table->string('department')->nullable();
            $table->string('course')->nullable();
            $table->string('program')->nullable();
            $table->integer('year_published')->nullable();
            $table->string('keywords')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('status')->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->integer('view_count')->default(0);
            $table->integer('citation_copy_count')->default(0);
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
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

    private function policyVersion(): string
    {
        return config('repository_policy.version', '2026-04-29');
    }

    private function createAdmin(): User
    {
        return User::create([
            'name' => 'Main Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('AdminPass123!'),
            'role' => 'admin',
            'is_department_dean' => false,
            'is_active' => true,
            'policy_accepted_at' => now(),
            'policy_version' => $this->policyVersion(),
        ]);
    }

    private function createDean(): User
    {
        return User::create([
            'name' => 'CCS Dean',
            'email' => 'ccs-dean@example.com',
            'password' => bcrypt('DeanPass123!'),
            'role' => 'admin',
            'is_department_dean' => true,
            'department' => 'College of Computer Studies',
            'is_active' => true,
            'policy_accepted_at' => now(),
            'policy_version' => $this->policyVersion(),
        ]);
    }

    public function test_admin_can_view_semesters_page_with_school_year_groups(): void
    {
        $admin = $this->createAdmin();

        Semester::create([
            'school_year' => '2026-2027',
            'semester' => Semester::FIRST_SEMESTER,
            'is_active' => true,
            'start_date' => '2026-08-01',
            'end_date' => '2026-12-31',
        ]);

        Semester::create([
            'school_year' => '2026-2027',
            'semester' => Semester::SECOND_SEMESTER,
            'is_active' => false,
            'start_date' => '2027-01-15',
            'end_date' => '2027-05-31',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.semesters'));

        $response->assertOk();
        $response->assertSee('Academic Period Management');
        $response->assertSee('2026-2027');
        $response->assertSee('1st Sem');
        $response->assertSee('2nd Sem');
        $response->assertSee('ACTIVE');
    }

    public function test_activating_first_semester_makes_first_active_and_second_inactive(): void
    {
        $admin = $this->createAdmin();

        $sem1 = Semester::create([
            'school_year' => '2026-2027',
            'semester' => Semester::FIRST_SEMESTER,
            'is_active' => false,
            'closed_at' => now(),
        ]);

        $sem2 = Semester::create([
            'school_year' => '2026-2027',
            'semester' => Semester::SECOND_SEMESTER,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.semesters.activate', $sem1));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertTrue($sem1->fresh()->is_active);
        $this->assertNull($sem1->fresh()->closed_at);

        $this->assertFalse($sem2->fresh()->is_active);
        $this->assertNotNull($sem2->fresh()->closed_at);
    }

    public function test_activating_second_semester_makes_second_active_and_first_inactive(): void
    {
        $admin = $this->createAdmin();

        $sem1 = Semester::create([
            'school_year' => '2026-2027',
            'semester' => Semester::FIRST_SEMESTER,
            'is_active' => true,
            'end_date' => now()->subDay()->toDateString(),
        ]);

        $sem2 = Semester::create([
            'school_year' => '2026-2027',
            'semester' => Semester::SECOND_SEMESTER,
            'is_active' => false,
            'closed_at' => now(),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.semesters.activate', $sem2));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertTrue($sem2->fresh()->is_active);
        $this->assertNull($sem2->fresh()->closed_at);

        $this->assertFalse($sem1->fresh()->is_active);
        $this->assertNotNull($sem1->fresh()->closed_at);
    }

    public function test_activation_synchronizes_enrollment_status(): void
    {
        $admin = $this->createAdmin();

        $sem1 = Semester::create([
            'school_year' => '2026-2027',
            'semester' => Semester::FIRST_SEMESTER,
            'is_active' => true,
            'end_date' => now()->subDay()->toDateString(),
        ]);

        $sem2 = Semester::create([
            'school_year' => '2026-2027',
            'semester' => Semester::SECOND_SEMESTER,
            'is_active' => false,
            'closed_at' => now(),
        ]);

        $user = User::create([
            'name' => 'Student One',
            'email' => 'student1@example.com',
            'password' => bcrypt('Password123!'),
            'role' => 'user',
            'current_semester_id' => $sem1->id,
            'is_active' => true,
            'policy_accepted_at' => now(),
            'policy_version' => $this->policyVersion(),
        ]);

        $enrollment1 = SemesterEnrollment::create([
            'user_id' => $user->id,
            'semester_id' => $sem1->id,
            'status' => SemesterEnrollment::STATUS_ACTIVE,
        ]);

        $enrollment2 = SemesterEnrollment::create([
            'user_id' => $user->id,
            'semester_id' => $sem2->id,
            'status' => SemesterEnrollment::STATUS_ARCHIVED,
        ]);

        // Activate 2nd Semester
        $this->actingAs($admin)->post(route('admin.semesters.activate', $sem2));

        $this->assertSame(SemesterEnrollment::STATUS_ARCHIVED, $enrollment1->fresh()->status);
        $this->assertSame(SemesterEnrollment::STATUS_ACTIVE, $enrollment2->fresh()->status);
    }

    public function test_dean_cannot_activate_semesters(): void
    {
        $dean = $this->createDean();

        $sem = Semester::create([
            'school_year' => '2026-2027',
            'semester' => Semester::FIRST_SEMESTER,
            'is_active' => false,
        ]);

        $response = $this->actingAs($dean)->post(route('admin.semesters.activate', $sem));

        $response->assertForbidden();
    }

    public function test_creating_active_semester_deactivates_existing_active_semester_in_same_school_year(): void
    {
        $admin = $this->createAdmin();

        $sem1 = Semester::create([
            'school_year' => '2026-2027',
            'semester' => Semester::FIRST_SEMESTER,
            'is_active' => true,
            'end_date' => now()->subDay()->toDateString(),
        ]);

        $this->actingAs($admin)->post(route('admin.semesters.store'), [
            'school_year' => '2026-2027',
            'semester' => Semester::SECOND_SEMESTER,
            'is_active' => '1',
        ]);

        $this->assertFalse($sem1->fresh()->is_active);
        $this->assertTrue(Semester::where('school_year', '2026-2027')->where('semester', Semester::SECOND_SEMESTER)->first()->is_active);
    }

    public function test_finished_semester_cannot_be_updated_and_shows_finished_status(): void
    {
        $admin = $this->createAdmin();

        $expiredSem = Semester::create([
            'school_year' => '2025-2026',
            'semester' => Semester::FIRST_SEMESTER,
            'is_active' => false,
            'start_date' => '2025-08-01',
            'end_date' => '2025-12-15',
            'closed_at' => '2025-12-16 00:00:00',
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.semesters.update', $expiredSem), [
            'school_year' => '2025-2026',
            'semester' => Semester::FIRST_SEMESTER,
            'start_date' => '2025-08-01',
            'end_date' => '2025-12-31',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertSame('2025-12-15', $expiredSem->fresh()->end_date->format('Y-m-d'));

        $pageResponse = $this->actingAs($admin)->get(route('admin.semesters'));
        $pageResponse->assertOk();
        $pageResponse->assertSee('FINISHED');
        $pageResponse->assertSee('All Semester Records')->assertSee('Locked', false);
    }

    public function test_switching_academic_years_archives_old_enrollments_without_enrolling_users_in_the_new_term(): void
    {
        $admin = $this->createAdmin();
        $old = Semester::create(['school_year' => '2026-2027', 'semester' => '1st', 'is_active' => true]);
        $next = Semester::create(['school_year' => '2027-2028', 'semester' => '1st', 'is_active' => false]);
        $user = $this->createContinuingMember('user');
        $record = SemesterEnrollment::create(['user_id' => $user->id, 'semester_id' => $old->id, 'status' => 'active']);

        $this->actingAs($admin)->post(route('admin.semesters.activate', $next))->assertSessionHas('success');
        $this->assertSame(1, Semester::where('is_active', true)->count());
        $this->assertFalse($old->fresh()->is_active);
        $this->assertTrue($next->fresh()->is_active);
        $this->assertSame('archived', $record->fresh()->status);
        $this->assertDatabaseMissing('semester_enrollments', ['user_id' => $user->id, 'semester_id' => $next->id]);
    }

    public function test_creating_an_active_term_in_another_year_closes_the_previous_term(): void
    {
        $admin = $this->createAdmin();
        $old = Semester::create(['school_year' => '2026-2027', 'semester' => '1st', 'is_active' => true]);
        $this->actingAs($admin)->post(route('admin.semesters.store'), [
            'school_year' => '2027-2028', 'semester' => '1st', 'is_active' => 1,
        ])->assertSessionHasNoErrors();
        $this->assertFalse($old->fresh()->is_active);
        $this->assertSame(1, Semester::where('is_active', true)->count());
    }

    private function createContinuingMember(string $role, string $department = 'College of Computer Studies'): User
    {
        $number = User::count() + 1;
        return User::create([
            'name' => 'Continuing Member ' . $number, 'email' => "continuing{$number}@example.com",
            'password' => bcrypt('OriginalPassword'), 'role' => $role, 'department' => $department,
            'student_id' => 'ID-' . $number, 'is_approved' => true, 'is_active' => false,
            'policy_accepted_at' => now(), 'policy_version' => $this->policyVersion(),
        ]);
    }

    public function test_dean_activates_only_selected_members_and_repeat_submission_preserves_history_and_credentials(): void
    {
        $dean = $this->createDean();
        $old = Semester::create(['school_year' => '2026-2027', 'semester' => '1st', 'is_active' => false]);
        $current = Semester::create(['school_year' => '2026-2027', 'semester' => '2nd', 'is_active' => true]);
        $student = $this->createContinuingMember('user');
        $faculty = $this->createContinuingMember('researcher');
        $omitted = $this->createContinuingMember('user');
        $foreign = $this->createContinuingMember('user', 'College of Education');
        $password = $student->password;
        $student->update(['current_semester_id' => $old->id]);
        $history = SemesterEnrollment::create([
            'user_id' => $student->id, 'semester_id' => $old->id, 'status' => 'archived',
            'enrolled_at' => now()->subMonths(3), 'enrolled_by' => $dean->id,
        ]);
        $historyData = $history->getAttributes();
        $accountCount = User::count();

        $this->actingAs($dean)->get(route('admin.users.activate-existing'))->assertOk()
            ->assertSee('Activate for Current Semester')->assertSee($current->label)
            ->assertSee($faculty->email)->assertDontSee($foreign->email);
        $selection = ['semester_id' => $current->id, 'user_ids' => [$student->id, $faculty->id]];
        $this->post(route('admin.users.activate-current'), $selection)->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertSame($accountCount, User::count());
        $this->assertSame($password, $student->fresh()->password);
        $this->assertSame($current->id, $student->fresh()->current_semester_id);
        $this->assertTrue($student->fresh()->hasActiveSemesterEnrollment());
        $this->assertTrue($faculty->fresh()->hasActiveSemesterEnrollment());
        $this->assertEquals($historyData, $history->fresh()->getAttributes());
        $this->assertFalse($omitted->fresh()->is_active);
        $record = SemesterEnrollment::where('user_id', $student->id)->where('semester_id', $current->id)->firstOrFail();
        $recordData = $record->getAttributes();
        $this->post(route('admin.users.activate-current'), $selection)->assertSessionHas('success');
        $this->assertSame(3, SemesterEnrollment::count());
        $this->assertEquals($recordData, $record->fresh()->getAttributes());
    }

    public function test_invalid_or_stale_selections_do_not_partially_activate_any_users(): void
    {
        $dean = $this->createDean();
        $current = Semester::create(['school_year' => '2026-2027', 'semester' => '1st', 'is_active' => true]);
        $student = $this->createContinuingMember('user');
        $foreign = $this->createContinuingMember('researcher', 'College of Education');
        $this->actingAs($dean)->post(route('admin.users.activate-current'), [
            'semester_id' => $current->id, 'user_ids' => [$student->id, $foreign->id],
        ])->assertSessionHasErrors('user_ids');
        $this->post(route('admin.users.activate-current'), [
            'semester_id' => $current->id, 'user_ids' => [$student->id, $dean->id],
        ])->assertSessionHasErrors('user_ids');
        $this->post(route('admin.users.activate-current'), ['semester_id' => $current->id])->assertSessionHasErrors('user_ids');
        $current->update(['is_active' => false]);
        $this->post(route('admin.users.activate-current'), [
            'semester_id' => $current->id, 'user_ids' => [$student->id],
        ])->assertSessionHasErrors('semester');
        $this->assertSame(0, SemesterEnrollment::count());
        $this->assertFalse($student->fresh()->is_active);
    }

    public function test_admin_coordinator_and_student_cannot_activate_department_members(): void
    {
        $admin = $this->createAdmin();
        $coordinator = User::create([
            'name' => 'Coordinator', 'email' => 'coordinator@example.com', 'password' => 'password',
            'role' => 'admin', 'is_research_coordinator' => true, 'department' => 'College of Computer Studies',
            'is_active' => true,
            'policy_accepted_at' => now(), 'policy_version' => $this->policyVersion(),
        ]);
        $student = $this->createContinuingMember('user');
        $student->update(['is_active' => true]);
        foreach ([$admin, $coordinator, $student] as $actor) {
            $this->actingAs($actor)->get(route('admin.users.activate-existing'))->assertForbidden();
            $this->post(route('admin.users.activate-current'), [])->assertForbidden();
        }
    }

    public function test_saved_semester_records_cannot_be_deleted_and_duplicate_creation_does_not_overwrite_them(): void
    {
        $admin = $this->createAdmin();
        $old = Semester::create(['school_year' => '2025-2026', 'semester' => '1st', 'is_active' => false, 'end_date' => '2025-12-31']);
        $user = $this->createContinuingMember('user');
        $record = SemesterEnrollment::create(['user_id' => $user->id, 'semester_id' => $old->id, 'status' => 'archived']);
        $this->actingAs($admin)->delete(route('admin.semesters.destroy', $old))->assertSessionHas('error');
        $this->delete(route('admin.user.delete', $user))->assertSessionHas('error');
        $this->post(route('admin.semesters.store'), [
            'school_year' => '2025-2026', 'semester' => '1st', 'is_active' => 1,
        ])->assertSessionHasErrors('semester');
        $this->post(route('admin.semesters.activate', $old))->assertSessionHasErrors('semester');
        $this->assertDatabaseHas('semester_enrollments', ['id' => $record->id, 'status' => 'archived']);
        $this->assertSame('2025-12-31', $old->fresh()->end_date->toDateString());
        $this->assertNotNull($user->fresh());
    }

    public function test_migration_normalizes_legacy_active_terms_and_database_rejects_a_second_active_term(): void
    {
        $old = Semester::create(['school_year' => '2025-2026', 'semester' => '1st', 'is_active' => true]);
        $latest = Semester::create(['school_year' => '2026-2027', 'semester' => '1st', 'is_active' => true]);
        $user = $this->createContinuingMember('user');
        $record = SemesterEnrollment::create(['user_id' => $user->id, 'semester_id' => $old->id, 'status' => 'active']);
        $migration = require database_path('migrations/2026_10_01_000001_enforce_single_active_semester.php');
        $migration->up();
        $this->assertFalse($old->fresh()->is_active);
        $this->assertTrue($latest->fresh()->is_active);
        $this->assertSame('archived', $record->fresh()->status);
        $this->assertSame(2, Semester::count());
        $this->assertSame(1, SemesterEnrollment::count());
        $this->expectException(\Illuminate\Database\QueryException::class);
        Semester::create(['school_year' => '2027-2028', 'semester' => '1st', 'is_active' => true]);
    }

    public function test_admin_can_switch_terms_with_the_database_constraint_installed(): void
    {
        $admin = $this->createAdmin();
        $old = Semester::create(['school_year' => '2026-2027', 'semester' => '1st', 'is_active' => true]);
        $migration = require database_path('migrations/2026_10_01_000001_enforce_single_active_semester.php');
        $migration->up();
        $this->actingAs($admin)->post(route('admin.semesters.store'), [
            'school_year' => '2027-2028', 'semester' => '1st', 'is_active' => 1,
        ])->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertFalse($old->fresh()->is_active);
        $this->post(route('admin.semesters.activate', $old))->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertTrue($old->fresh()->is_active);
        $this->assertSame(1, Semester::where('is_active', true)->count());
        $migration->down();
        $this->assertFalse(Schema::hasColumn('semesters', 'active_slot'));
    }

    public function test_previous_terms_appear_only_in_records_while_active_and_upcoming_terms_keep_their_cards(): void
    {
        $admin = $this->createAdmin();
        $oldYear = Semester::create([
            'school_year' => '2025-2026', 'semester' => '1st', 'is_active' => false,
            'end_date' => '2025-12-31', 'closed_at' => now(),
        ]);
        $previous = Semester::create([
            'school_year' => '2026-2027', 'semester' => '1st', 'is_active' => false,
            'closed_at' => now(),
        ]);
        $active = Semester::create(['school_year' => '2026-2027', 'semester' => '2nd', 'is_active' => true]);
        $upcoming = Semester::create(['school_year' => '2027-2028', 'semester' => '1st', 'is_active' => false]);
        $response = $this->actingAs($admin)->get(route('admin.semesters'))->assertOk();
        $groups = $response->viewData('schoolYearGroups');
        $this->assertSame(['2027-2028', '2026-2027'], $groups->pluck('school_year')->all());
        $currentGroup = $groups->firstWhere('school_year', '2026-2027');
        $this->assertNull($currentGroup['first_semester']);
        $this->assertTrue($currentGroup['has_first_semester']);
        $this->assertSame($active->id, $currentGroup['second_semester']->id);
        $this->assertSame(4, $response->viewData('semesters')->total());
        $cards = explode('<div class="sem-audit-divider">', $response->getContent())[0];
        $this->assertStringNotContainsString('data-semester-id="' . $oldYear->id . '"', $cards);
        $this->assertStringNotContainsString('data-semester-id="' . $previous->id . '"', $cards);
        $this->assertStringContainsString('data-semester-id="' . $active->id . '"', $cards);
        $this->assertStringContainsString('data-semester-id="' . $upcoming->id . '"', $cards);
        $this->assertStringNotContainsString('data-school-year="2026-2027" data-semester="1st"', $cards);
        $this->assertSame(4, Semester::count());
    }

    public function test_current_card_details_work_even_when_records_are_filtered_to_an_old_year(): void
    {
        $admin = $this->createAdmin();
        Semester::create(['school_year' => '2025-2026', 'semester' => '1st', 'is_active' => false, 'closed_at' => now()]);
        $active = Semester::create(['school_year' => '2026-2027', 'semester' => '2nd', 'is_active' => true]);
        $response = $this->actingAs($admin)->get(route('admin.semesters', ['school_year' => '2025-2026']))->assertOk();
        $this->assertSame(1, $response->viewData('semesters')->total());
        $this->assertSame('2025-2026', $response->viewData('semesters')->first()->school_year);
        $this->assertStringContainsString('"updateUrl":"' . str_replace('/', '\\/', route('admin.semesters.update', $active)) . '"', $response->getContent());
    }

    public function test_semester_is_inferred_and_second_semester_waits_until_the_day_after_first_ends(): void
    {
        $admin = $this->createAdmin();
        Carbon::setTestNow(Carbon::parse('2026-10-03 12:00:00', config('app.timezone')));
        try {
            $this->actingAs($admin)->post(route('admin.semesters.store'), [
                'school_year' => '2026-2027', 'start_date' => '2026-10-01',
                'end_date' => '2026-10-03', 'is_active' => 1,
            ])->assertSessionHasNoErrors()->assertSessionHas('success');
            $first = Semester::firstOrFail();
            $this->assertSame('1st', $first->semester);
            $response = $this->get(route('admin.semesters'))->assertOk();
            $plan = $response->viewData('semesterCreationPlans')['2026-2027'];
            $this->assertSame('2nd', $plan['semester']);
            $this->assertFalse($plan['can_create']);
            $response->assertSee('id="add_semester_display"', false)
                ->assertDontSee('<select id="add_semester"', false);
            $this->post(route('admin.semesters.store'), [
                'school_year' => '2026-2027', 'semester' => '2nd', 'is_active' => 0,
            ])->assertSessionHasErrors('semester');
            $this->assertSame(1, Semester::count());
            Carbon::setTestNow(Carbon::parse('2026-10-04 00:01:00', config('app.timezone')));
            $this->post(route('admin.semesters.store'), [
                'school_year' => '2026-2027', 'start_date' => '2026-10-04',
                'end_date' => '2027-03-31', 'is_active' => 1,
            ])->assertSessionHasNoErrors()->assertSessionHas('success');
            $second = Semester::where('semester', '2nd')->firstOrFail();
            $this->assertTrue($second->is_active);
            $this->assertFalse($first->fresh()->is_active);
            $this->assertSame(2, Semester::count());
            $this->post(route('admin.semesters.store'), ['school_year' => '2026-2027'])
                ->assertSessionHasErrors('semester');
            $this->assertSame(2, Semester::count());
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_second_semester_cannot_be_created_first_or_activated_before_first_finishes(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin)->post(route('admin.semesters.store'), [
            'school_year' => '2026-2027', 'semester' => '2nd', 'is_active' => 1,
        ])->assertSessionHasErrors('semester');
        $this->assertSame(0, Semester::count());
        $first = Semester::create([
            'school_year' => '2026-2027', 'semester' => '1st', 'is_active' => true,
            'end_date' => now()->addMonth()->toDateString(),
        ]);
        // Pre-existing terms from older installations also follow the activation rule.
        $second = Semester::create(['school_year' => '2026-2027', 'semester' => '2nd', 'is_active' => false]);
        $this->post(route('admin.semesters.activate', $second))->assertSessionHasErrors('semester');
        $this->assertTrue($first->fresh()->is_active);
        $this->assertFalse($second->fresh()->is_active);
    }

    public function test_closing_first_semester_early_does_not_bypass_its_end_date(): void
    {
        $admin = $this->createAdmin();
        $first = Semester::create([
            'school_year' => '2026-2027', 'semester' => '1st', 'is_active' => false,
            'closed_at' => now(), 'end_date' => now()->addMonth()->toDateString(),
        ]);
        $this->actingAs($admin)->post(route('admin.semesters.store'), [
            'school_year' => '2026-2027', 'semester' => '2nd', 'is_active' => 0,
        ])->assertSessionHasErrors('semester');
        $first->update(['end_date' => null]);
        $this->post(route('admin.semesters.store'), ['school_year' => '2026-2027'])
            ->assertSessionHasErrors('semester');
        $this->assertSame(1, Semester::count());
    }

    public function test_dean_can_load_activation_modal_and_activate_users_without_redirecting(): void
    {
        $dean = $this->createDean();
        $semester = Semester::create(['school_year' => '2026-2027', 'semester' => '1st', 'is_active' => true]);
        $student = $this->createContinuingMember('user');
        $foreign = $this->createContinuingMember('user', 'College of Education');
        $this->actingAs($dean)->get(route('admin.users'))->assertOk()
            ->assertSee('data-open-semester-users', false)
            ->assertSee('id="activateExistingUsersModal"', false);
        $this->get(route('admin.users.activate-existing'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertViewIs('admin.partials.activate-existing-users')
            ->assertSee($student->email)->assertDontSee($foreign->email)
            ->assertDontSee('Back to Manage Users')->assertDontSee('<html', false);
        $this->postJson(route('admin.users.activate-current'), [
            'semester_id' => $semester->id, 'user_ids' => [$student->id],
        ])->assertOk()->assertJsonPath('activated_count', 1);
        $this->assertTrue($student->fresh()->hasActiveSemesterEnrollment());
        $this->postJson(route('admin.users.activate-current'), [
            'semester_id' => $semester->id, 'user_ids' => [$foreign->id],
        ])->assertUnprocessable()->assertJsonValidationErrors('user_ids');
        $this->assertDatabaseMissing('semester_enrollments', ['user_id' => $foreign->id]);
    }

    public function test_activation_users_year_filter_matches_only_selected_students_and_is_ignored_for_faculty(): void
    {
        $dean = $this->createDean();
        $students = collect();
        foreach ([1, 2, 3, 4] as $year) {
            $student = $this->createContinuingMember('user');
            $student->update(['year_level' => $year]);
            $students->push($student);
        }
        $faculty = $this->createContinuingMember('researcher');
        $faculty->update(['year_level' => 2]);
        $foreign = $this->createContinuingMember('user', 'College of Education');
        $foreign->update(['year_level' => 2]);
        $this->actingAs($dean);

        foreach ([1, 2, 3, 4] as $year) {
            $response = $this->get(route('admin.users.activate-existing', ['member_type' => 'student', 'year_level' => $year]),
                ['X-Requested-With' => 'XMLHttpRequest'])->assertOk();
            $expected = $students->firstWhere('year_level', $year);
            $this->assertSame([$expected->id], $response->viewData('users')->pluck('id')->all());
            $response->assertSee('1st Year')->assertSee('4th Year')->assertDontSee('5th Year');
        }
        $this->get(route('admin.users.activate-existing', ['member_type' => 'student', 'year_level' => 5]))
            ->assertSessionHasErrors('year_level');
        $response = $this->get(route('admin.users.activate-existing', ['member_type' => 'faculty', 'year_level' => 2]),
            ['X-Requested-With' => 'XMLHttpRequest'])->assertOk();
        $this->assertSame([$faculty->id], $response->viewData('users')->pluck('id')->all());
        $response->assertSee('data-semester-year-filter  hidden', false);
    }

    public function test_active_period_notifies_only_active_department_deans_and_repeat_activation_does_not_duplicate(): void
    {
        Mail::fake();
        $admin = $this->createAdmin();
        $dean = $this->createDean();
        $otherDean = User::create([
            'name' => 'Education Dean', 'email' => 'education-dean@gmail.com', 'password' => 'password',
            'role' => 'admin', 'is_department_dean' => true, 'is_active' => true,
            'department' => 'College of Education',
        ]);
        foreach ([
            ['is_department_dean' => true, 'is_active' => false],
            ['is_department_dean' => true, 'department' => null],
            ['is_department_dean' => false, 'is_research_coordinator' => true],
            ['role' => 'user'], ['role' => 'researcher'],
        ] as $index => $overrides) {
            User::create(array_replace([
                'name' => 'Excluded Recipient', 'email' => "excluded{$index}@example.com", 'password' => 'password',
                'role' => 'admin', 'department' => $dean->department, 'is_active' => true,
            ], $overrides));
        }

        $this->actingAs($admin)->post(route('admin.semesters.store'), [
            'school_year' => '2026-2027', 'is_active' => 1,
            'start_date' => now()->toDateString(), 'end_date' => now()->addMonths(3)->toDateString(),
        ])->assertSessionHasNoErrors();
        $semester = Semester::firstOrFail();
        $this->assertSame(2, DB::table('notifications')->count());
        foreach ([$dean, $otherDean] as $recipient) {
            $notification = $recipient->notifications()->sole();
            $this->assertSame(AcademicPeriodActivated::class, $notification->type);
            $this->assertSame($semester->id, $notification->data['semester_id']);
            $this->assertSame('AY 2026-2027 — 1st Semester', $notification->data['label']);
            $this->assertSame(route('admin.users'), $notification->data['url']);
            Mail::assertSent(AcademicPeriodActivatedMail::class, function ($mail) use ($recipient, $semester) {
                if (! $mail->hasTo($recipient->email)) return false;
                $this->assertSame($semester->id, $mail->period['semester_id']);
                $mail->assertSeeInHtml('AY 2026-2027 — 1st Semester');
                $mail->assertSeeInHtml('Activate Existing Users');
                return true;
            });
        }
        Mail::assertSentCount(2);
        $this->post(route('admin.semesters.activate', $semester))->assertSessionHasNoErrors();
        $this->assertSame(2, DB::table('notifications')->count());
        Mail::assertSentCount(2);

        Schema::create('research_handoffs', function (Blueprint $table) {
            $table->id();
            $table->string('department');
            $table->unsignedBigInteger('dean_id');
            $table->string('title');
            $table->timestamp('received_at')->nullable();
        });
        $this->actingAs($dean)->getJson(route('admin.dean-notifications'))
            ->assertOk()->assertJsonCount(1, 'notifications')
            ->assertJsonPath('notifications.0.message', $dean->notifications()->sole()->data['message'])
            ->assertJsonPath('notifications.0.url', route('admin.users'));
        $this->actingAs($admin)->getJson(route('admin.dean-notifications'))->assertForbidden();
    }

    public function test_inactive_period_notifies_deans_only_when_it_is_later_activated(): void
    {
        Mail::fake();
        $admin = $this->createAdmin();
        $dean = $this->createDean();
        $this->actingAs($admin)->post(route('admin.semesters.store'), [
            'school_year' => '2026-2027', 'is_active' => 0,
        ])->assertSessionHasNoErrors();
        $this->assertSame(0, $dean->notifications()->count());
        Mail::assertNothingSent();

        $this->post(route('admin.semesters.activate', Semester::firstOrFail()))->assertSessionHasNoErrors();
        $this->assertSame(1, $dean->notifications()->count());
        Mail::assertSentCount(1);
    }

    public function test_rolled_back_activation_does_not_keep_alerts_or_send_email(): void
    {
        Mail::fake();
        $admin = $this->createAdmin();
        $dean = $this->createDean();
        $semester = Semester::create(['school_year' => '2026-2027', 'semester' => '1st', 'is_active' => false]);
        try {
            DB::transaction(function () use ($semester, $admin, $dean) {
                app(SemesterWorkflow::class)->activate($semester, $admin->id);
                $this->assertSame(1, $dean->notifications()->count());
                Mail::assertNothingSent();
                throw new \RuntimeException('Cancel activation');
            });
        } catch (\RuntimeException $exception) {
            $this->assertSame('Cancel activation', $exception->getMessage());
        }
        $this->assertFalse($semester->fresh()->is_active);
        $this->assertSame(0, $dean->notifications()->count());
        Mail::assertNothingSent();
        app(SemesterWorkflow::class)->activate($semester, $admin->id);
        $this->assertSame(1, $dean->notifications()->count());
        Mail::assertSentCount(1);
    }

    public function test_email_failure_keeps_active_period_and_the_dean_system_alert(): void
    {
        $admin = $this->createAdmin();
        $dean = $this->createDean();
        Mail::shouldReceive('mailer')->once()->andThrow(new \RuntimeException('SMTP unavailable'));
        $semester = Semester::create(['school_year' => '2026-2027', 'semester' => '1st', 'is_active' => false]);
        $this->actingAs($admin)->post(route('admin.semesters.activate', $semester))->assertSessionHasNoErrors();
        $this->assertTrue($semester->fresh()->is_active);
        $this->assertSame(1, $dean->notifications()->count());
    }

    public function test_semesters_page_displays_department_user_breakdown(): void
    {
        $admin = $this->createAdmin();

        $sem = Semester::create([
            'school_year' => '2026-2027',
            'semester' => Semester::FIRST_SEMESTER,
            'is_active' => true,
            'start_date' => '2026-08-01',
            'end_date' => '2026-12-31',
        ]);

        $studentCcs = User::create([
            'name' => 'CCS Student',
            'email' => 'ccs@example.com',
            'password' => bcrypt('Pass123!'),
            'role' => 'user',
            'department' => 'College of Computer Studies',
            'current_semester_id' => $sem->id,
            'is_active' => true,
            'policy_accepted_at' => now(),
            'policy_version' => $this->policyVersion(),
        ]);

        $studentCba = User::create([
            'name' => 'CBA Student',
            'email' => 'cba@example.com',
            'password' => bcrypt('Pass123!'),
            'role' => 'user',
            'department' => 'College of Business Administration',
            'current_semester_id' => $sem->id,
            'is_active' => true,
            'policy_accepted_at' => now(),
            'policy_version' => $this->policyVersion(),
        ]);

        SemesterEnrollment::create([
            'user_id' => $studentCcs->id,
            'semester_id' => $sem->id,
            'status' => SemesterEnrollment::STATUS_ACTIVE,
        ]);

        SemesterEnrollment::create([
            'user_id' => $studentCba->id,
            'semester_id' => $sem->id,
            'status' => SemesterEnrollment::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.semesters'));
        $response->assertOk();
        $response->assertSee('College of Computer Studies');
        $response->assertSee('College of Business Admin');

        $detailResponse = $this->actingAs($admin)->get(route('admin.semesters.show', $sem));
        $detailResponse->assertOk();
        $detailResponse->assertSee('Imported Users by Department');
        $detailResponse->assertSee('College of Computer Studies');
        $detailResponse->assertSee('College of Business Administration');
    }
}
