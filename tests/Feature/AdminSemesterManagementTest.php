<?php

namespace Tests\Feature;

use App\Models\Semester;
use App\Models\SemesterEnrollment;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminSemesterManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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
        $response->assertSee('Semester Management');
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
        $pageResponse->assertSee('Finished & Locked', false);
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
