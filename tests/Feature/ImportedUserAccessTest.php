<?php

namespace Tests\Feature;

use App\Models\Research;
use App\Models\Semester;
use App\Models\SemesterEnrollment;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ImportedUserAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_dean_imported_users_can_submit_and_view_full_research_files(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password',
            'role' => 'admin',
            'is_department_dean' => false,
            'is_active' => true,
            'is_approved' => true,
        ]);

        $schoolYear = $this->currentSchoolYear();
        $semester = Semester::create([
            'school_year' => $schoolYear,
            'semester' => Semester::FIRST_SEMESTER,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(5)->toDateString(),
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        $dean = User::create([
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

        $csv = implode("\n", [
            'firstname,middlename,lastname,department,member_type,student_id,employee_id,year_level,email,password',
            'Ana,Santos,Dela Cruz,College of Education,student,12345678,,2,,',
            'Ben,Reyes,Faculty,College of Education,faculty,,FAC-001,,,',
        ]);

        $response = $this->actingAs($dean)->post(route('admin.import-users'), [
            'semester' => Semester::FIRST_SEMESTER,
            'school_year' => $schoolYear,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(5)->toDateString(),
            'file' => UploadedFile::fake()->createWithContent('users.csv', $csv),
        ]);

        $response->assertRedirect(route('admin.users', ['imported' => 1]));
        $response->assertSessionHas('success');

        $student = User::query()->where('student_id', '12345678')->firstOrFail();
        $faculty = User::query()->where('student_id', 'FAC-001')->firstOrFail();

        $this->assertSame('College of Computer Studies', $student->department);
        $this->assertSame($semester->id, $student->current_semester_id);
        $this->assertSame('user', $student->role);
        $this->assertTrue($student->is_active);
        $this->assertTrue($student->is_approved);
        $this->assertSame($dean->id, $student->student_approved_by);
        $this->assertTrue($student->canSubmitResearch());
        $this->assertTrue($student->canViewFullDocument());

        $this->assertSame('College of Computer Studies', $faculty->department);
        $this->assertSame($semester->id, $faculty->current_semester_id);
        $this->assertSame('researcher', $faculty->role);
        $this->assertTrue($faculty->is_active);
        $this->assertTrue($faculty->is_approved);
        $this->assertSame($dean->id, $faculty->researcher_approved_by);
        $this->assertTrue($faculty->canSubmitResearch());
        $this->assertTrue($faculty->canViewFullDocument());
    }

    public function test_dean_import_fails_with_invalid_school_year(): void
    {
        $dean = User::create([
            'name' => 'CCS Dean',
            'email' => 'ccs-dean-no-sem@example.com',
            'password' => 'password',
            'role' => 'admin',
            'department' => 'College of Computer Studies',
            'is_department_dean' => true,
            'is_active' => true,
            'is_approved' => true,
            'policy_accepted_at' => now(),
            'policy_version' => config('repository_policy.version', '2026-04-29'),
        ]);

        $csv = implode("\n", [
            'firstname,middlename,lastname,department,member_type,student_id,employee_id,year_level,email,password',
            'Ana,Santos,Dela Cruz,College of Computer Studies,student,12345678,,2,,',
        ]);

        $response = $this->actingAs($dean)->from(route('admin.users'))->post(route('admin.import-users'), [
            'semester' => Semester::FIRST_SEMESTER,
            'school_year' => 'invalid-year',
            'file' => UploadedFile::fake()->createWithContent('users.csv', $csv),
        ]);

        $response->assertRedirect(route('admin.users'));
        $response->assertSessionHasErrors(['school_year'], errorBag: 'importUsers');
        $this->assertNull(User::query()->where('student_id', '12345678')->first());
    }

    public function test_import_skips_existing_approved_users_and_imports_only_new_rows(): void
    {
        $schoolYear = $this->currentSchoolYear();
        $semester = Semester::create([
            'school_year' => $schoolYear,
            'semester' => Semester::FIRST_SEMESTER,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(5)->toDateString(),
            'is_active' => true,
        ]);

        $dean = User::create([
            'name' => 'CCS Dean',
            'email' => 'ccs-dean-duplicates@example.com',
            'password' => 'password',
            'role' => 'admin',
            'department' => 'College of Computer Studies',
            'is_department_dean' => true,
            'is_active' => true,
            'is_approved' => true,
            'policy_accepted_at' => now(),
            'policy_version' => config('repository_policy.version', '2026-04-29'),
        ]);

        $existing = User::create([
            'name' => 'Approved Existing User',
            'email' => 'existing@example.com',
            'password' => 'password',
            'role' => 'user',
            'department' => 'College of Computer Studies',
            'student_id' => '12345678',
            'is_active' => true,
            'is_approved' => true,
            'student_approved_by' => $dean->id,
            'student_approved_at' => now(),
        ]);

        $csv = implode("\n", [
            'firstname,middlename,lastname,department,member_type,student_id,employee_id,year_level,email,password',
            'Approved,Existing,User,College of Computer Studies,student,12345678,,2,existing@example.com,',
            'Ana,Santos,Dela Cruz,College of Computer Studies,student,87654321,,1,ana@example.com,',
            'Ben,Reyes,Lopez,College of Education,student,87654322,,2,ben@example.com,',
            'Cara,Mendoza,Santos,College of Maritime Studies,student,87654323,,3,cara@example.com,',
        ]);

        $response = $this->actingAs($dean)->post(route('admin.import-users'), [
            'semester' => Semester::FIRST_SEMESTER,
            'school_year' => $schoolYear,
            'file' => UploadedFile::fake()->createWithContent('users.csv', $csv),
        ]);

        $response->assertRedirect(route('admin.users', ['imported' => 1]));
        $response->assertSessionHas('success');
        $response->assertSessionHas('import_preview', function (array $preview) {
            return collect($preview)->contains(fn ($row) => $row['login_id'] === '12345678' && $row['action'] === 'Updated');
        });

        $this->assertSame(1, User::query()->where('student_id', '12345678')->count());
        $this->assertSame($existing->id, User::query()->where('student_id', '12345678')->value('id'));
        $this->assertDatabaseHas('semester_enrollments', [
            'user_id' => $existing->id,
            'semester_id' => $semester->id,
            'status' => SemesterEnrollment::STATUS_ACTIVE,
        ]);
        $this->assertSame(3, User::query()->whereIn('student_id', ['87654321', '87654322', '87654323'])->count());
    }

    public function test_dean_cannot_add_research_directly(): void
    {
        $dean = User::create([
            'name' => 'CCS Dean',
            'email' => 'ccs-dean-add-research@example.com',
            'password' => 'password',
            'role' => 'admin',
            'department' => 'College of Computer Studies',
            'is_department_dean' => true,
            'is_active' => true,
            'is_approved' => true,
            'policy_accepted_at' => now(),
            'policy_version' => config('repository_policy.version', '2026-04-29'),
        ]);

        $this->actingAs($dean)->get(route('admin.add-research'))->assertForbidden();
        $this->actingAs($dean)->post(route('admin.store-research'), [])->assertForbidden();
    }

    public function test_dean_cannot_approve_reject_archive_publish_or_delete_research(): void
    {
        $dean = User::create([
            'name' => 'CCS Dean',
            'email' => 'ccs-dean-research-actions@example.com',
            'password' => 'password',
            'role' => 'admin',
            'department' => 'College of Computer Studies',
            'is_department_dean' => true,
            'is_active' => true,
            'is_approved' => true,
            'policy_accepted_at' => now(),
            'policy_version' => config('repository_policy.version', '2026-04-29'),
        ]);

        $research = Research::create([
            'title' => 'Sample Research',
            'abstract' => 'Sample abstract.',
            'author_name' => 'John Doe',
            'department' => 'College of Computer Studies',
            'type' => 'Thesis',
            'year_published' => 2026,
            'status' => 'pending',
        ]);

        $this->actingAs($dean)->post(route('admin.research.approve', $research))->assertForbidden();
        $this->actingAs($dean)->post(route('admin.research.reject', $research), ['reason' => 'Testing'])->assertForbidden();
        $this->actingAs($dean)->post(route('admin.research.archive', $research))->assertForbidden();
        $this->actingAs($dean)->post(route('admin.research.publish', $research))->assertForbidden();
        $this->actingAs($dean)->delete(route('admin.research.delete', $research))->assertForbidden();
    }

    public function test_dean_cannot_create_update_archive_or_delete_semesters(): void
    {
        $dean = User::create([
            'name' => 'CCS Dean',
            'email' => 'ccs-dean-semester-block@example.com',
            'password' => 'password',
            'role' => 'admin',
            'department' => 'College of Computer Studies',
            'is_department_dean' => true,
            'is_active' => true,
            'is_approved' => true,
            'policy_accepted_at' => now(),
            'policy_version' => config('repository_policy.version', '2026-04-29'),
        ]);

        $semester = Semester::create([
            'school_year' => '2026-2027',
            'semester' => Semester::FIRST_SEMESTER,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(5)->toDateString(),
            'is_active' => true,
        ]);

        $this->actingAs($dean)->post(route('admin.semesters.store'), [
            'school_year' => '2027-2028',
            'semester' => Semester::FIRST_SEMESTER,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(5)->toDateString(),
        ])->assertForbidden();

        $this->actingAs($dean)->patch(route('admin.semesters.update', $semester), [
            'school_year' => '2026-2027',
            'semester' => Semester::FIRST_SEMESTER,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(6)->toDateString(),
        ])->assertForbidden();

        $this->actingAs($dean)->post(route('admin.semesters.archive', $semester))->assertForbidden();
        $this->actingAs($dean)->delete(route('admin.semesters.destroy', $semester))->assertForbidden();
    }

    public function test_global_admin_cannot_import_users(): void
    {
        $admin = User::create([
            'name' => 'Global Admin',
            'email' => 'global-admin@example.com',
            'password' => 'password',
            'role' => 'admin',
            'is_active' => true,
            'is_approved' => true,
            'policy_accepted_at' => now(),
            'policy_version' => config('repository_policy.version', '2026-04-29'),
        ]);

        $csv = implode("\n", [
            'firstname,middlename,lastname,department,member_type,student_id,employee_id,year_level,email,password',
            'Ana,Santos,Dela Cruz,College of Computer Studies,student,87654321,,1,ana@example.com,',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.import-users'), [
            'file' => UploadedFile::fake()->createWithContent('users.csv', $csv),
        ]);

        $response->assertForbidden();
        $this->assertNull(User::query()->where('student_id', '87654321')->first());
    }

    public function test_manual_user_creation_is_disabled_for_admins_and_deans(): void
    {
        $admin = User::create([
            'name' => 'Global Admin',
            'email' => 'global-admin-create@example.com',
            'password' => 'password',
            'role' => 'admin',
            'is_active' => true,
            'is_approved' => true,
            'policy_accepted_at' => now(),
            'policy_version' => config('repository_policy.version', '2026-04-29'),
        ]);

        $dean = User::create([
            'name' => 'CCS Dean',
            'email' => 'ccs-dean-create@example.com',
            'password' => 'password',
            'role' => 'admin',
            'department' => 'College of Computer Studies',
            'is_department_dean' => true,
            'is_active' => true,
            'is_approved' => true,
            'policy_accepted_at' => now(),
            'policy_version' => config('repository_policy.version', '2026-04-29'),
        ]);

        foreach ([$admin, $dean] as $actor) {
            $this->actingAs($actor)->get(route('admin.create-user'))->assertForbidden();
            $this->actingAs($actor)->post(route('admin.store-user'), [])->assertForbidden();
        }
    }

    private function currentSchoolYear(): string
    {
        $startYear = now()->month >= 6 ? now()->year : now()->year - 1;

        return sprintf('%04d-%04d', $startYear, $startYear + 1);
    }
}
