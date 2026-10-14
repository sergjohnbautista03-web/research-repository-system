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

        Schema::dropIfExists('notifications');
        (require database_path('migrations/2026_10_02_000001_create_notifications_table.php'))->up();
        Schema::dropIfExists('notification_reads');
        (require database_path('migrations/2026_10_02_000002_create_notification_reads_table.php'))->up();

        Schema::dropIfExists('semester_enrollments');
        Schema::dropIfExists('researches');
        Schema::dropIfExists('semesters');
        Schema::dropIfExists('users');
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('middle_name')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role')->default('user');
            $table->string('department')->nullable();
            $table->string('program')->nullable();
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

    public function test_dean_imported_users_can_view_but_cannot_submit_research_files(): void
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
            'firstname,middlename,lastname,department,member_type,student_id,employee_id,year_level,email,password,course',
            'Ana,Santos,Dela Cruz,College of Education,student,12345678,,2,,,Information Technology',
            'Ben,Reyes,Faculty,College of Education,faculty,,FAC-001,,,,Computer Science',
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
        $this->assertSame('Information Technology', $student->program);
        $this->assertSame('Computer Science', $faculty->program);
        $this->assertSame($semester->id, $student->current_semester_id);
        $this->assertSame('user', $student->role);
        $this->assertTrue($student->is_active);
        $this->assertTrue($student->is_approved);
        $this->assertSame($dean->id, $student->student_approved_by);
        $this->assertFalse($student->canSubmitResearch());
        $this->assertTrue($student->canViewFullDocument());

        $this->assertSame('College of Computer Studies', $faculty->department);
        $this->assertSame($semester->id, $faculty->current_semester_id);
        $this->assertSame('researcher', $faculty->role);
        $this->assertTrue($faculty->is_active);
        $this->assertTrue($faculty->is_approved);
        $this->assertSame($dean->id, $faculty->researcher_approved_by);
        $this->assertFalse($faculty->canSubmitResearch());
        $this->assertTrue($faculty->canViewFullDocument());
        foreach ([$student, $faculty] as $member) {
            $member->update([
                'policy_accepted_at' => now(),
                'policy_version' => config('repository_policy.version', '2026-04-29'),
            ]);
            $this->actingAs($member)->get(route('research.submit'))->assertForbidden();
            $this->post(route('research.store'), [])->assertForbidden();
        }
    }

    public function test_dean_can_import_without_an_active_semester_and_activate_users_later(): void
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
            'firstname,middlename,lastname,department,member_type,student_id,employee_id,year_level,email,password,program',
            'Ana,Santos,Dela Cruz,College of Computer Studies,student,12345678,,2,,,Information Technology',
            'Ben,Reyes,Faculty,College of Computer Studies,faculty,,FAC-001,,,,Computer Science',
        ]);

        $response = $this->actingAs($dean)->from(route('admin.users'))->post(route('admin.import-users'), [
            'file' => UploadedFile::fake()->createWithContent('users.csv', $csv),
        ]);

        $response->assertRedirect(route('admin.users', ['imported' => 1]))->assertSessionHasNoErrors()->assertSessionHas('success');
        $student = User::where('student_id', '12345678')->firstOrFail();
        $faculty = User::where('student_id', 'FAC-001')->firstOrFail();
        foreach ([$student, $faculty] as $member) {
            $this->assertNull($member->current_semester_id);
            $this->assertFalse($member->is_active);
            $this->assertTrue($member->is_approved);
            $this->assertFalse($member->canViewFullDocument());
        }
        $this->assertSame('Information Technology', $student->program);
        $this->assertSame('Computer Science', $faculty->program);
        $this->assertDatabaseCount('semester_enrollments', 0);
        $this->assertDatabaseCount('semesters', 0);
        $this->get(route('admin.users'))->assertOk()
            ->assertSee('You can import now and activate users', false)
            ->assertSee('id="iu_submit_btn" class="mu-btn mu-btn-primary">Import Students', false)
            ->assertSee('<button type="submit">Add User</button>', false);

        $semester = Semester::create([
            'school_year' => $this->currentSchoolYear(), 'semester' => Semester::FIRST_SEMESTER,
            'is_active' => true, 'end_date' => now()->addMonths(2),
        ]);
        $this->post(route('admin.users.activate-current'), [
            'semester_id' => $semester->id, 'user_ids' => [$student->id, $faculty->id],
        ])->assertSessionHasNoErrors();
        foreach ([$student, $faculty] as $member) {
            $this->assertTrue($member->fresh()->is_active);
            $this->assertSame($semester->id, $member->fresh()->current_semester_id);
            $this->assertTrue($member->fresh()->hasActiveSemesterEnrollment());
        }
        $this->assertDatabaseCount('semester_enrollments', 2);
    }

    public function test_dean_can_manually_add_student_and_faculty_without_an_active_semester(): void
    {
        $dean = User::create([
            'name' => 'CCS Dean', 'email' => 'no-semester-add@example.com', 'password' => 'password',
            'role' => 'admin', 'department' => 'College of Computer Studies', 'is_department_dean' => true,
            'is_active' => true, 'is_approved' => true, 'policy_accepted_at' => now(),
            'policy_version' => config('repository_policy.version', '2026-04-29'),
        ]);
        $this->actingAs($dean)->get(route('admin.create-user'))->assertOk()
            ->assertSee('<button type="submit">Add User</button>', false);
        foreach (['student', 'faculty'] as $type) {
            $this->post(route('admin.store-user'), [
                'firstname' => 'New', 'lastname' => 'Member', 'member_type' => $type,
                'student_id' => $type . '-001', 'email' => $type . '@example.com', 'year_level' => 2,
                'program' => 'Information Technology',
            ])->assertRedirect(route('admin.users'))->assertSessionHasNoErrors();
            $member = User::where('student_id', $type . '-001')->firstOrFail();
            $this->assertSame($dean->department, $member->department);
            $this->assertSame('Information Technology', $member->program);
            $this->assertSame($dean->id, $member->created_by);
            $this->assertNull($member->current_semester_id);
            $this->assertFalse($member->is_active);
            $this->assertTrue($member->is_approved);
            $this->assertTrue(\Illuminate\Support\Facades\Hash::check($type . '-001_New', $member->password));
        }
        $this->assertDatabaseCount('semester_enrollments', 0);
        $this->assertDatabaseCount('semesters', 0);
    }

    public function test_import_without_active_semester_preserves_previous_enrollment_and_account_status(): void
    {
        $dean = User::create([
            'name' => 'CCS Dean', 'email' => 'history-import@example.com', 'password' => 'password',
            'role' => 'admin', 'department' => 'College of Computer Studies', 'is_department_dean' => true,
            'is_active' => true, 'is_approved' => true, 'policy_accepted_at' => now(),
            'policy_version' => config('repository_policy.version', '2026-04-29'),
        ]);
        $previous = Semester::create(['school_year' => '2025-2026', 'semester' => '1st', 'is_active' => false]);
        $members = collect([true, false])->map(fn ($active, $index) => User::create([
            'name' => 'Existing Student', 'email' => 'existing-' . $index . '@example.com', 'password' => 'password',
            'role' => 'user', 'department' => $dean->department, 'student_id' => '1234567' . $index,
            'current_semester_id' => $previous->id, 'is_active' => $active, 'is_approved' => true,
            'program' => 'Information Technology',
        ]));
        foreach ($members as $member) {
            SemesterEnrollment::create([
                'user_id' => $member->id, 'semester_id' => $previous->id,
                'status' => SemesterEnrollment::STATUS_ARCHIVED, 'enrolled_at' => now()->subYear(),
            ]);
        }
        $history = \Illuminate\Support\Facades\DB::table('semester_enrollments')->get()->toJson();
        $passwords = $members->pluck('password', 'id');
        $csv = "firstname,middlename,lastname,student_id,year_level,program\n"
            . "Existing,Middle,Student,12345670,3,Computer Science\n"
            . "Existing,Middle,Student,12345671,3,Computer Science";
        $this->actingAs($dean)->post(route('admin.import-users'), [
            'file' => UploadedFile::fake()->createWithContent('users.csv', $csv),
        ])->assertSessionHasNoErrors()->assertSessionHas('success');
        foreach ($members as $index => $member) {
            $saved = $member->fresh();
            $this->assertSame($previous->id, $saved->current_semester_id);
            $this->assertSame($index === 0, $saved->is_active);
            $this->assertSame($passwords[$member->id], $saved->password);
            $this->assertSame('Computer Science', $saved->program);
            $this->assertSame(3, (int) $saved->year_level);
        }
        $this->assertSame($history, \Illuminate\Support\Facades\DB::table('semester_enrollments')->get()->toJson());
    }

    public function test_import_preserves_identity_updates_year_and_keeps_omitted_users_unchanged(): void
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

        $originalPassword = $existing->password;
        $existing->update(['is_active' => false, 'year_level' => 1]);
        $omitted = User::create(['name' => 'Omitted Student', 'email' => 'omitted@example.com', 'password' => 'password', 'role' => 'user', 'department' => $dean->department, 'student_id' => '11223344', 'is_active' => true]);
        $otherDepartment = User::create(['name' => 'Other Student', 'email' => 'other@example.com', 'password' => 'password', 'role' => 'user', 'department' => 'College of Education', 'student_id' => '11223345', 'is_active' => true]);
        $faculty = User::create(['name' => 'Faculty Member', 'email' => 'faculty@example.com', 'password' => 'password', 'role' => 'researcher', 'department' => $dean->department, 'student_id' => 'EMP-1', 'is_active' => true]);
        $csv = implode("\n", [
            'firstname,middlename,lastname,department,member_type,student_id,employee_id,year_level,email,password,program',
            'Approved,Existing,User,College of Computer Studies,student,12345678,,2,existing@example.com,changedpassword,Information Technology',
            'Ana,Santos,Dela Cruz,College of Computer Studies,student,87654321,,1,ana@example.com,,Information Technology',
            'Ben,Reyes,Lopez,College of Education,student,87654322,,2,ben@example.com,,Computer Science',
            'Cara,Mendoza,Santos,College of Maritime Studies,student,87654323,,3,cara@example.com,,Computer Science',
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
        $this->assertTrue($existing->fresh()->is_active);
        $this->assertSame(2, (int) $existing->fresh()->year_level);
        $this->assertSame($originalPassword, $existing->fresh()->password);
        $this->assertTrue($omitted->fresh()->is_active);
        $this->assertTrue($otherDepartment->fresh()->is_active);
        $this->assertTrue($faculty->fresh()->is_active);
        $this->assertTrue($dean->fresh()->is_active);

        $conflictCsv = implode("\n", [
            'firstname,middlename,lastname,student_id,year_level,email,password,program',
            'Another,Middle,Student,99887766,3,existing@example.com,password,Information Technology',
        ]);
        $this->actingAs($dean)->post(route('admin.import-users'), [
            'semester' => Semester::FIRST_SEMESTER, 'school_year' => $schoolYear,
            'file' => UploadedFile::fake()->createWithContent('conflict.csv', $conflictCsv),
        ])->assertSessionHas('error');
        $this->assertTrue($existing->fresh()->is_active);
        $this->assertSame('12345678', $existing->fresh()->student_id);
        $this->assertSame(0, User::where('student_id', '99887766')->count());
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

    public function test_only_department_deans_can_manually_create_members(): void
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

        $this->actingAs($admin)->get(route('admin.create-user'))->assertForbidden();
        $this->post(route('admin.store-user'), [])->assertForbidden();
        $this->actingAs($dean)->get(route('admin.create-user'))->assertOk();
        $semester = Semester::create(['school_year' => '2026-2027', 'semester' => Semester::FIRST_SEMESTER, 'is_active' => true, 'end_date' => now()->addMonths(2)]);
        foreach (['student', 'faculty'] as $type) {
            $this->post(route('admin.store-user'), [
                'firstname' => 'New', 'lastname' => 'Member', 'member_type' => $type,
                'student_id' => $type . '-001', 'email' => $type . '@example.com',
                'year_level' => 2,
                'department' => 'Wrong department', 'role' => 'admin',
                'program' => 'Information Technology',
            ])->assertSessionHasNoErrors()->assertRedirect(route('admin.users'));
            $member = User::where('email', $type . '@example.com')->firstOrFail();
            $this->assertSame($dean->department, $member->department);
            $this->assertSame('Information Technology', $member->program);
            $this->assertSame($type === 'student' ? 'user' : 'researcher', $member->role);
            $this->assertTrue($member->hasActiveSemesterEnrollment());
            $this->assertSame($semester->id, $member->current_semester_id);
            $this->assertTrue(\Illuminate\Support\Facades\Hash::check($type . '-001_New', $member->password));
        }
        $this->get(route('admin.users'))->assertOk()
            ->assertDontSee('name="semester_id"', false)
            ->assertSee('id="addUserModal"', false)
            ->assertSee('data-open-add-user', false);
        $this->from(route('admin.users'))->post(route('admin.store-user'), [
            'firstname' => 'Preserved', 'member_type' => 'faculty',
        ])->assertRedirect(route('admin.users'))
            ->assertSessionHasErrors(['lastname', 'email'], null, 'addUser');
        $this->get(route('admin.users'))->assertOk()
            ->assertSee('data-reopen="true"', false)
            ->assertSee('value="Preserved"', false);
        $this->from(route('admin.users'))->post(route('admin.store-user'), [
            'firstname' => 'New123', 'middlename' => '123', 'lastname' => 'Member!',
            'member_type' => 'student', 'student_id' => 'ID@001', 'email' => 'invalid-email',
            'year_level' => 5,
        ])->assertSessionHasErrors([
            'firstname', 'middlename', 'lastname', 'student_id', 'email', 'year_level',
        ], null, 'addUser');
        $this->assertDatabaseMissing('users', ['student_id' => 'ID@001']);
        $this->post(route('admin.store-user'), [
            'firstname' => 'New', 'lastname' => 'Member', 'member_type' => 'faculty',
            'student_id' => 'student-001', 'email' => 'student@example.com',
            'password' => 'Password123!', 'password_confirmation' => 'Password123!',
        ])->assertSessionHasErrors(['student_id', 'email'], null, 'addUser');
        $this->post(route('admin.store-user'), [
            'firstname' => 'SERGIO', 'lastname' => 'Member', 'member_type' => 'student',
            'student_id' => '00037838', 'email' => 'sergio@example.com', 'year_level' => 4,
            'program' => 'Information Technology',
            'password' => 'TamperedPassword',
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.users'));
        $member = User::where('student_id', '00037838')->firstOrFail();
        $this->assertSame(4, (int) $member->year_level);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('00037838_Ser', $member->password));
        $this->assertFalse(\Illuminate\Support\Facades\Hash::check('TamperedPassword', $member->password));
        $this->post(route('logout'));
        $this->post(route('login'), ['login' => '00037838', 'password' => '00037838_Ser'])
            ->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($member);
    }

    public function test_dean_and_coordinator_can_login_without_any_semester(): void
    {
        foreach (['is_department_dean', 'is_research_coordinator'] as $staffFlag) {
            $staff = User::create([
                'name' => 'Department Staff',
                'email' => $staffFlag . '@example.com',
                'password' => 'password',
                'role' => 'admin',
                $staffFlag => true,
                'is_active' => true,
                'is_approved' => true,
                'policy_accepted_at' => now(),
                'policy_version' => config('repository_policy.version', '2026-04-29'),
            ]);

            $this->post('/login', ['login' => $staff->email, 'password' => 'password'])
                ->assertRedirect(route('admin.dashboard'))
                ->assertSessionHasNoErrors();
            $this->assertAuthenticatedAs($staff);
            $this->post('/logout');
        }
    }

    public function test_dean_imported_member_can_login_with_their_own_active_semester_enrollment(): void
    {
        $dean = User::create([
            'name' => 'CCS Dean',
            'email' => 'dean-active-semester@example.com',
            'password' => 'password',
            'role' => 'admin',
            'department' => 'College of Computer Studies',
            'is_department_dean' => true,
            'is_active' => true,
            'is_approved' => true,
        ]);

        $semester = Semester::create([
            'school_year' => $this->currentSchoolYear(),
            'semester' => Semester::FIRST_SEMESTER,
            'is_active' => true,
            'end_date' => now()->addMonths(2),
        ]);

        $student = User::create([
            'name' => 'Imported Student',
            'email' => 'imported-active-semester@example.com',
            'password' => 'password',
            'role' => 'user',
            'department' => $dean->department,
            'student_id' => '24681357',
            'created_by' => $dean->id,
            'current_semester_id' => $semester->id,
            'is_active' => true,
            'is_approved' => true,
            'policy_accepted_at' => now(),
            'policy_version' => config('repository_policy.version', '2026-04-29'),
        ]);

        SemesterEnrollment::create([
            'user_id' => $student->id,
            'semester_id' => $semester->id,
            'status' => SemesterEnrollment::STATUS_ACTIVE,
        ]);

        $this->post('/login', ['login' => $student->student_id, 'password' => 'password'])
            ->assertRedirect(route('user.dashboard'))
            ->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($student);
        $this->assertTrue($student->fresh()->canViewFullDocument());
    }

    private function createSemesterMember(string $role, Semester $semester): User
    {
        $number = User::count() + 1;
        $dean = User::create([
            'name' => 'Department Dean', 'email' => "dean{$number}@example.com",
            'password' => 'password', 'role' => 'admin', 'is_department_dean' => true,
            'department' => 'College of Computer Studies', 'is_active' => true, 'is_approved' => true,
            'policy_accepted_at' => now(), 'policy_version' => config('repository_policy.version', '2026-04-29'),
        ]);

        return User::create([
            'name' => 'Continuing Member', 'email' => "member{$number}@example.com",
            'password' => 'password', 'role' => $role, 'student_id' => "ID-{$number}",
            'department' => $dean->department, 'created_by' => $dean->id,
            'current_semester_id' => $semester->id, 'is_active' => true, 'is_approved' => true,
            'policy_accepted_at' => now(), 'policy_version' => config('repository_policy.version', '2026-04-29'),
        ]);
    }

    public function test_finished_members_cannot_login_until_dean_activates_them_for_the_current_semester(): void
    {
        $old = Semester::create([
            'school_year' => $this->currentSchoolYear(), 'semester' => '1st',
            'is_active' => false, 'end_date' => now()->subDay(), 'closed_at' => now(),
        ]);
        $current = Semester::create([
            'school_year' => $old->school_year, 'semester' => '2nd',
            'is_active' => true, 'end_date' => now()->addMonths(3),
        ]);

        foreach (['user', 'researcher'] as $role) {
            $member = $this->createSemesterMember($role, $old);
            $history = SemesterEnrollment::create([
                'user_id' => $member->id, 'semester_id' => $old->id,
                'status' => SemesterEnrollment::STATUS_ARCHIVED, 'enrolled_at' => now()->subMonths(3),
            ]);
            $historyData = $history->fresh()->getAttributes();
            $originalPassword = $member->password;
            $accountCount = User::count();

            $this->post('/login', ['login' => $member->student_id, 'password' => 'password'])
                ->assertRedirect(route('login'))->assertSessionHasErrors('login');
            $this->assertGuest();
            $this->assertFalse($member->canViewFullDocument());

            $this->actingAs($member->createdBy)->post(route('admin.users.activate-current'), [
                'semester_id' => $current->id, 'user_ids' => [$member->id],
            ])->assertSessionHasNoErrors()->assertSessionHas('success');
            $this->post('/logout');

            $this->post('/login', ['login' => $member->student_id, 'password' => 'password'])
                ->assertSessionHasNoErrors();
            $this->assertAuthenticatedAs($member);
            $this->assertTrue($member->fresh()->canViewFullDocument());
            $this->assertSame($originalPassword, $member->fresh()->password);
            $this->assertSame($accountCount, User::count());
            $this->assertSame($historyData, $history->fresh()->getAttributes());
            $this->post('/logout');
        }
    }

    public function test_current_semester_assignment_alone_does_not_allow_missing_or_inactive_enrollments(): void
    {
        $semester = Semester::create([
            'school_year' => $this->currentSchoolYear(), 'semester' => '1st', 'is_active' => true,
        ]);

        foreach (['user', 'researcher'] as $role) {
            foreach ([null, SemesterEnrollment::STATUS_INACTIVE, SemesterEnrollment::STATUS_ARCHIVED] as $status) {
                $member = $this->createSemesterMember($role, $semester);
                if ($status !== null) {
                    SemesterEnrollment::create([
                        'user_id' => $member->id, 'semester_id' => $semester->id, 'status' => $status,
                    ]);
                }
                $this->post('/login', ['login' => $member->student_id, 'password' => 'password'])
                    ->assertRedirect(route('login'))->assertSessionHasErrors('login');
                $this->assertGuest();
                $this->assertFalse($member->canViewFullDocument());
            }
        }
    }

    public function test_expired_semester_ends_existing_student_and_faculty_sessions(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-02 12:00:00', config('app.timezone')));
        $semester = Semester::create([
            'school_year' => '2026-2027', 'semester' => '1st', 'is_active' => true,
            'end_date' => '2026-10-02',
        ]);
        \Illuminate\Support\Facades\Route::middleware(['web', 'auth'])
            ->get('/member-semester-access-test', fn () => response('Member access available'));

        foreach (['user', 'researcher'] as $role) {
            Carbon::setTestNow(Carbon::parse('2026-10-02 23:59:00', config('app.timezone')));
            $member = $this->createSemesterMember($role, $semester);
            SemesterEnrollment::create([
                'user_id' => $member->id, 'semester_id' => $semester->id,
                'status' => SemesterEnrollment::STATUS_ACTIVE,
            ]);
            $member->load('currentSemester');
            $this->actingAs($member)->get('/member-semester-access-test')->assertOk();
            $this->assertTrue($member->canViewFullDocument());

            Carbon::setTestNow(Carbon::parse('2026-10-03 00:00:00', config('app.timezone')));
            $this->get('/member-semester-access-test')
                ->assertRedirect(route('login'))->assertSessionHasErrors('login');
            $this->assertGuest();
            $this->assertFalse($member->canViewFullDocument());
            $this->post('/login', ['login' => $member->student_id, 'password' => 'password'])
                ->assertRedirect(route('login'))->assertSessionHasErrors('login');
            $this->assertGuest();
        }
    }

    public function test_switching_active_semester_revokes_existing_sessions_without_using_cached_semester(): void
    {
        $old = Semester::create([
            'school_year' => '2026-2027', 'semester' => '1st', 'is_active' => true,
        ]);
        $current = Semester::create([
            'school_year' => '2027-2028', 'semester' => '1st', 'is_active' => false,
        ]);
        $member = $this->createSemesterMember('user', $old);
        SemesterEnrollment::create([
            'user_id' => $member->id, 'semester_id' => $old->id,
            'status' => SemesterEnrollment::STATUS_ACTIVE,
        ]);
        $member->load('currentSemester');
        $this->assertTrue($member->hasActiveSemesterEnrollment());
        app(\App\Services\SemesterWorkflow::class)->activate($current, $member->created_by);

        $this->assertFalse($member->hasActiveSemesterEnrollment());
        $this->assertFalse($member->canViewFullDocument());
        $this->actingAs($member)->get(route('user.dashboard'))
            ->assertRedirect(route('login'))->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_user_overview_shows_student_and_faculty_period_history_with_recorded_times(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-02 12:00:00', config('app.timezone')));
        $old = Semester::create([
            'school_year' => '2026-2027', 'semester' => '1st', 'is_active' => false,
            'end_date' => '2026-09-30',
        ]);
        $current = Semester::create([
            'school_year' => '2026-2027', 'semester' => '2nd', 'is_active' => true,
            'end_date' => '2027-03-31',
        ]);
        $student = $this->createSemesterMember('user', $current);
        $faculty = $this->createSemesterMember('researcher', $current);
        $neverEnrolled = $this->createSemesterMember('user', $current);
        foreach ([$student, $faculty] as $member) {
            SemesterEnrollment::create([
                'user_id' => $member->id, 'semester_id' => $old->id,
                'status' => SemesterEnrollment::STATUS_ARCHIVED,
                'enrolled_at' => Carbon::parse('2026-09-26 09:15:00', config('app.timezone')),
            ]);
            SemesterEnrollment::create([
                'user_id' => $member->id, 'semester_id' => $current->id,
                'status' => SemesterEnrollment::STATUS_ACTIVE,
                'enrolled_at' => Carbon::parse('2026-10-02 08:30:00', config('app.timezone')),
            ]);
        }
        $foreign = $this->createSemesterMember('user', $current);
        $foreign->update(['department' => 'Other Department']);

        $response = $this->actingAs($student->createdBy)->get(route('admin.users'))->assertOk();
        preg_match('/<script type="application\/json" id="mu-user-data">(.*?)<\/script>/s', $response->getContent(), $matches);
        $data = json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);
        foreach ([$student, $faculty] as $member) {
            $records = $data[$member->id]['academic_records'];
            $this->assertCount(2, $records);
            $verb = $member->role === 'researcher' ? 'Activated for' : 'Enrolled in';
            $this->assertSame($verb . ' AY 2026-2027 — 2nd Semester', $records[0]['period']);
            $this->assertSame('Current Semester', $records[0]['status']);
            $this->assertStringContainsString('October 2, 2026 · 8:30 AM', $records[0]['recorded_at']);
            $this->assertSame($verb . ' AY 2026-2027 — 1st Semester', $records[1]['period']);
            $this->assertSame('Previous Semester', $records[1]['status']);
            $this->assertStringContainsString('September 26, 2026 · 9:15 AM', $records[1]['recorded_at']);
            $this->assertSame($member->role === 'researcher' ? 'Activated on' : 'Enrolled on', $records[0]['date_label']);
        }
        $this->assertSame([], $data[$neverEnrolled->id]['academic_records']);
        $this->assertArrayNotHasKey($foreign->id, $data);
    }

    public function test_dean_and_coordinator_sessions_remain_available_without_an_open_semester(): void
    {
        \Illuminate\Support\Facades\Route::middleware(['web', 'auth', 'admin'])
            ->get('/staff-semester-access-test', fn () => response('Staff access available'));

        foreach (['is_department_dean', 'is_research_coordinator'] as $staffFlag) {
            $staff = User::create([
                'name' => 'Department Staff',
                'email' => $staffFlag . '@example.com',
                'password' => 'password',
                'role' => 'admin',
                $staffFlag => true,
                'is_active' => true,
                'is_approved' => true,
            ]);

            $this->actingAs($staff)->get('/staff-semester-access-test')
                ->assertOk()->assertSee('Staff access available');
            $this->assertAuthenticatedAs($staff);
        }
    }

    public function test_user_filters_combine_year_level_search_role_and_department_scope(): void
    {
        $dean = User::factory()->create([
            'role' => 'admin', 'is_department_dean' => true,
            'department' => 'College of Computer Studies',
        ]);
        $this->actingAs($dean);
        $matching = User::factory()->create([
            'name' => 'Alex Match', 'role' => 'user', 'student_id' => '111',
            'year_level' => 2, 'department' => $dean->department, 'program' => 'Information Technology',
        ]);
        User::factory()->create([
            'name' => 'Alex Wrong Year', 'role' => 'user', 'student_id' => '112',
            'year_level' => 1, 'department' => $dean->department, 'program' => 'Information Technology',
        ]);
        User::factory()->create([
            'name' => 'Alex Other Department', 'role' => 'user', 'student_id' => '113',
            'year_level' => 2, 'department' => 'Other Department', 'program' => 'Information Technology',
        ]);
        User::factory()->create([
            'name' => 'Alex Other Program', 'role' => 'user', 'student_id' => '114',
            'year_level' => 2, 'department' => $dean->department, 'program' => 'Computer Science',
        ]);
        $request = \Illuminate\Http\Request::create('/admin/users', 'GET', [
            'search' => 'Alex', 'role' => 'student', 'year_level' => '2', 'program' => 'Information Technology',
            'school_year' => '2025-2026', 'semester' => '2nd',
        ]);
        $view = app(\App\Http\Controllers\AdminController::class)->users($request);
        $this->assertSame([$matching->id], $view->getData()['users']->pluck('id')->all());
        $this->assertSame(['Computer Science', 'Information Technology'], $view->getData()['programs']);
        $html = $view->with('errors', new \Illuminate\Support\ViewErrorBag())->render();
        $this->assertStringContainsString('All Year Levels', $html);
        $this->assertStringContainsString('aria-label="Program"', $html);
        $this->assertStringContainsString('value="Information Technology" selected', $html);
        $this->assertStringNotContainsString('aria-label="Academic Year"', $html);
        $this->assertStringNotContainsString('aria-label="Semester"', $html);
    }

    public function test_program_filter_supports_faculty_and_global_admin_department_filters(): void
    {
        $dean = User::factory()->create([
            'role' => 'admin', 'is_department_dean' => true, 'department' => 'College of Computer Studies',
        ]);
        $faculty = User::factory()->create([
            'role' => 'researcher', 'graduation_year' => null,
            'department' => $dean->department, 'program' => 'Computer Science',
        ]);
        $student = User::factory()->create([
            'role' => 'user', 'student_id' => '001', 'department' => $dean->department, 'program' => 'Computer Science',
        ]);
        User::factory()->create([
            'role' => 'researcher', 'graduation_year' => null,
            'department' => $dean->department, 'program' => 'Information Technology',
        ]);
        User::factory()->create([
            'role' => 'researcher', 'graduation_year' => null,
            'department' => 'Other Department', 'program' => 'Computer Science',
        ]);
        $controller = app(\App\Http\Controllers\AdminController::class);
        $this->actingAs($dean);
        $view = $controller->users(\Illuminate\Http\Request::create('/admin/users', 'GET', [
            'role' => 'faculty', 'program' => 'Computer Science',
        ]));
        $this->assertSame([$faculty->id], $view->getData()['users']->pluck('id')->all());

        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $view = $controller->users(\Illuminate\Http\Request::create('/admin/users', 'GET', [
            'department' => $dean->department, 'program' => 'Computer Science',
        ]));
        $this->assertEqualsCanonicalizing([$faculty->id, $student->id], $view->getData()['users']->pluck('id')->all());
        $this->assertContains('Civil Engineering', $view->getData()['programs']);
    }

    public function test_default_and_cleared_users_include_unassigned_and_archived_members(): void
    {
        $dean = User::factory()->create([
            'role' => 'admin', 'is_department_dean' => true,
            'department' => 'College of Computer Studies',
        ]);
        $this->actingAs($dean);
        $semester = Semester::create([
            'school_year' => $this->currentSchoolYear(), 'semester' => '1st',
            'is_active' => true, 'created_by' => $dean->id,
        ]);
        $student = User::factory()->create([
            'role' => 'user', 'department' => $dean->department,
        ]);
        $archived = User::factory()->create([
            'role' => 'user', 'department' => $dean->department,
        ]);
        SemesterEnrollment::create([
            'user_id' => $archived->id, 'semester_id' => $semester->id,
            'status' => SemesterEnrollment::STATUS_ARCHIVED, 'enrolled_at' => now(),
        ]);
        User::factory()->create(['role' => 'user', 'department' => 'Other Department']);
        $controller = app(\App\Http\Controllers\AdminController::class);
        $default = $controller->users(\Illuminate\Http\Request::create('/admin/users'));
        $this->assertEqualsCanonicalizing([$student->id, $archived->id], $default->getData()['users']->pluck('id')->all());
        $this->assertEmpty($default->getData()['selectedProgram']);
        $all = $controller->users(\Illuminate\Http\Request::create('/admin/users', 'GET', [
            'school_year' => '', 'semester' => '',
        ]));
        $this->assertEqualsCanonicalizing([$student->id, $archived->id], $all->getData()['users']->pluck('id')->all());
        $returnVisit = $controller->users(\Illuminate\Http\Request::create('/admin/users'));
        $this->assertEqualsCanonicalizing([$student->id, $archived->id], $returnVisit->getData()['users']->pluck('id')->all());
        $filtered = $controller->users(\Illuminate\Http\Request::create('/admin/users', 'GET', [
            'school_year' => $semester->school_year, 'semester' => $semester->semester,
        ]));
        $this->assertEqualsCanonicalizing([$student->id, $archived->id], $filtered->getData()['users']->pluck('id')->all());
    }

    public function test_dean_report_filters_counts_and_print_data_remain_department_scoped(): void
    {
        $dean = User::factory()->create(['role' => 'admin', 'is_department_dean' => true, 'department' => 'Assigned Department']);
        $this->actingAs($dean);
        $semester = Semester::create(['school_year' => '2026-2027', 'semester' => '1st', 'is_active' => true]);
        $attributes = ['department' => $dean->department, 'title' => 'Student Study', 'program' => 'BSIT', 'type' => 'Qualitative', 'status' => 'approved', 'semester_id' => $semester->id, 'year_published' => 2026, 'submission_category' => Research::SUBMISSION_CATEGORY_STUDENT_JOURNAL];
        $student = Research::create($attributes);
        foreach (['draft', 'pending', 'rejected', 'archived'] as $status) {
            Research::create(array_merge($attributes, ['title' => 'Excluded ' . $status, 'status' => $status]));
        }
        Research::create(array_merge($attributes, ['title' => 'Faculty Study', 'submission_category' => Research::SUBMISSION_CATEGORY_FACULTY_JOURNAL, 'status' => 'approved']));
        Research::create(array_merge($attributes, ['title' => 'Private Other Department', 'department' => 'Other Department', 'program' => 'Secret Program']));
        $controller = app(\App\Http\Controllers\AdminController::class);
        $all = $controller->reports(\Illuminate\Http\Request::create('/admin/reports', 'GET', ['department' => 'Other Department']));
        $data = $all->getData();
        $this->assertCount(2, $data['allRows']);
        $this->assertSame(1, $data['studentCount']);
        $this->assertSame(1, $data['facultyCount']);
        $this->assertNotContains('Secret Program', $data['programs']);
        $filtered = $controller->reports(\Illuminate\Http\Request::create('/admin/reports', 'GET', [
            'school_year' => '2026-2027', 'semester' => '1st', 'program' => 'BSIT',
            'submission_category' => Research::SUBMISSION_CATEGORY_STUDENT_JOURNAL, 'type' => 'Qualitative', 'status' => 'approved',
        ]));
        $this->assertSame([$student->id], $filtered->getData()['allRows']->pluck('id')->all());
        $this->assertSame(0, $filtered->getData()['facultyCount']);
        $html = $filtered->with('errors', new \Illuminate\Support\ViewErrorBag())->render();
        $this->assertStringContainsString('Assigned Department', $html);
        $this->assertStringContainsString('Author/s', $html);
        $this->assertStringContainsString('Print Report', $html);
        $this->assertStringNotContainsString('name="department"', $html);
        $this->assertStringNotContainsString('Private Other Department', $html);
        $this->assertStringNotContainsString('Excluded ', $html);
        $this->assertStringNotContainsString('value="draft"', $html);
        foreach (['school_year' => '2025-2026', 'semester' => '2nd', 'program' => 'Other', 'type' => 'Thesis', 'status' => 'rejected'] as $key => $value) {
            $none = $controller->reports(\Illuminate\Http\Request::create('/admin/reports', 'GET', [$key => $value]));
            $this->assertCount(0, $none->getData()['allRows']);
        }
    }

    public function test_dean_without_department_cannot_view_report_records(): void
    {
        $dean = User::factory()->create(['role' => 'admin', 'is_department_dean' => true, 'department' => null]);
        $this->actingAs($dean);
        Research::create(['title' => 'Other Research', 'department' => 'Other Department', 'status' => 'approved']);
        $view = app(\App\Http\Controllers\AdminController::class)->reports(\Illuminate\Http\Request::create('/admin/reports'));
        $this->assertCount(0, $view->getData()['allRows']);
        $this->assertCount(0, $view->getData()['programs']);
    }

    public function test_user_program_can_be_assigned_and_validated_for_the_users_department(): void
    {
        $dean = User::create([
            'name' => 'Program Dean', 'email' => 'program-dean@example.com', 'password' => 'password',
            'role' => 'admin', 'is_department_dean' => true,
            'is_active' => true, 'is_approved' => true,
            'department' => 'College of Computer Studies',
            'policy_accepted_at' => now(), 'policy_version' => config('repository_policy.version', '2026-04-29'),
        ]);
        $student = User::create([
            'name' => 'Program Student', 'email' => 'program-student@example.com', 'password' => 'password',
            'role' => 'user', 'department' => $dean->department, 'student_id' => '00078799',
        ]);
        $this->actingAs($dean)->get(route('admin.users'))->assertOk()
            ->assertSee('data-label="Program" title="">Unassigned</td>', false);

        foreach (['Information Technology', 'Computer Science'] as $program) {
            $this->from(route('admin.users'))->patch(route('admin.user.program', $student), ['program' => $program])
                ->assertSessionHasNoErrors()->assertRedirect(route('admin.users'));
            $this->assertSame($program, $student->fresh()->program);
            $this->assertSame($dean->department, $student->fresh()->department);
            $this->get(route('admin.users'))->assertOk()
                ->assertSee('data-label="Program" title="' . $program . '">' . $program . '</td>', false);
        }

        $this->patch(route('admin.user.program', $student), ['program' => 'Civil Engineering'])
            ->assertSessionHasErrors(['program'], null, 'userProgram');
        $this->assertSame('Computer Science', $student->fresh()->program);
        $this->patch(route('admin.user.program', $student), ['program' => ''])
            ->assertSessionHasErrors(['program'], null, 'userProgram');
        $this->assertSame('Computer Science', $student->fresh()->program);

        $student->update(['department' => 'College of Education']);
        $this->patch(route('admin.user.program', $student), ['program' => 'Elementary Education'])->assertForbidden();
        $this->assertSame('Computer Science', $student->fresh()->program);
        $this->patch(route('admin.user.program', $dean), ['program' => 'Information Technology'])->assertForbidden();
    }

    public function test_separate_csv_templates_import_the_selected_member_type_without_a_member_type_column(): void
    {
        $dean = User::create([
            'name' => 'CSV Dean', 'email' => 'csv-dean@example.com', 'password' => 'password',
            'role' => 'admin', 'department' => 'College of Computer Studies', 'is_department_dean' => true,
            'is_active' => true, 'is_approved' => true, 'policy_accepted_at' => now(),
            'policy_version' => config('repository_policy.version', '2026-04-29'),
        ]);
        $this->actingAs($dean)->get(route('admin.users'))->assertOk()
            ->assertSee('Download Student CSV Template')->assertSee('Download Faculty CSV Template');
        foreach (['student' => 'students', 'faculty' => 'faculty'] as $type => $template) {
            $header = trim(file_get_contents(public_path('templates/' . $template . '.csv')));
            $this->assertStringNotContainsString('member_type', $header);
            $this->assertStringNotContainsString('password', $header);
            $this->assertStringContainsString($type === 'student' ? 'student_id' : 'employee_id', $header);
            $row = $type === 'student'
                ? 'Ana,Santos,Student,00078799,2,Information Technology,student-csv@example.com'
                : 'Ben,Reyes,Faculty,FAC-001,Computer Science,faculty-csv@example.com';
            $this->post(route('admin.import-users'), [
                'import_member_type' => $type,
                'file' => UploadedFile::fake()->createWithContent($template . '.csv', $header . "\n" . $row),
            ])->assertRedirect(route('admin.users', ['imported' => 1]))->assertSessionHasNoErrors()->assertSessionHas('success');
            $member = User::where('email', $type . '-csv@example.com')->firstOrFail();
            $this->assertSame($type === 'student' ? 'user' : 'researcher', $member->role);
            $this->assertSame($type === 'student' ? '00078799' : 'FAC-001', $member->student_id);
            $this->assertTrue(\Illuminate\Support\Facades\Hash::check($type === 'student' ? '00078799_Ana' : 'FAC-001_Ben', $member->password));
            $this->assertSame($type === 'student' ? 'Information Technology' : 'Computer Science', $member->program);
            $this->assertSame($type === 'student' ? 2 : null, $member->year_level === null ? null : (int) $member->year_level);
            $this->assertNull($member->graduation_year);
            $this->assertNull($member->current_semester_id);
            $this->assertFalse($member->is_active);
        }
        $this->assertDatabaseCount('semester_enrollments', 0);
        $semester = Semester::create([
            'school_year' => $this->currentSchoolYear(), 'semester' => '1st', 'is_active' => true,
            'end_date' => now()->addMonths(2),
        ]);
        $this->post(route('admin.import-users'), [
            'import_member_type' => 'faculty',
            'file' => UploadedFile::fake()->createWithContent('faculty.csv', "firstname,middlename,lastname,employee_id,program\nCara,Middle,Faculty,FAC-002,Computer Science"),
        ])->assertSessionHasNoErrors()->assertSessionHas('success');
        $faculty = User::where('student_id', 'FAC-002')->firstOrFail();
        $this->assertSame('researcher', $faculty->role);
        $this->assertNull($faculty->graduation_year);
        $this->assertSame($semester->id, $faculty->current_semester_id);
        $this->assertTrue($faculty->hasActiveSemesterEnrollment());
    }

    public function test_separate_import_rejects_mismatched_rows_files_and_existing_account_types(): void
    {
        $dean = User::create([
            'name' => 'CSV Dean', 'email' => 'csv-validation@example.com', 'password' => 'password',
            'role' => 'admin', 'department' => 'College of Computer Studies', 'is_department_dean' => true,
            'is_active' => true, 'is_approved' => true, 'policy_accepted_at' => now(),
            'policy_version' => config('repository_policy.version', '2026-04-29'),
        ]);
        $this->actingAs($dean);
        foreach ([
            ['student', "firstname,middlename,lastname,student_id,member_type,program\nAna,Middle,Student,12345678,faculty,Information Technology"],
            ['faculty', "firstname,middlename,lastname,employee_id,role,program\nBen,Middle,Faculty,FAC-001,user,Computer Science"],
            ['faculty', "firstname,middlename,lastname,student_id,program\nAna,Middle,Student,12345678,Information Technology"],
            ['student', "firstname,middlename,lastname,employee_id,program\nBen,Middle,Faculty,FAC-001,Computer Science"],
        ] as [$type, $csv]) {
            $this->post(route('admin.import-users'), [
                'import_member_type' => $type, 'file' => UploadedFile::fake()->createWithContent('users.csv', $csv),
            ])->assertSessionHas('error')->assertSessionHas('import_errors');
            $this->assertDatabaseCount('users', 1);
        }
        $existingFaculty = User::create([
            'name' => 'Existing Faculty', 'email' => 'existing-faculty-csv@example.com', 'password' => 'password',
            'role' => 'researcher', 'department' => $dean->department, 'student_id' => '12345678',
            'is_active' => false, 'is_approved' => true,
        ]);
        $this->post(route('admin.import-users'), [
            'import_member_type' => 'student',
            'file' => UploadedFile::fake()->createWithContent('students.csv', "firstname,middlename,lastname,student_id,program\nAna,Middle,Student,12345678,Information Technology"),
        ])->assertSessionHas('error')->assertSessionHas('import_errors');
        $this->assertSame('Existing Faculty', $existingFaculty->fresh()->name);
        $this->assertSame('researcher', $existingFaculty->fresh()->role);
        $this->assertFalse($existingFaculty->fresh()->is_active);
        $this->post(route('admin.import-users'), [
            'import_member_type' => 'invalid', 'file' => UploadedFile::fake()->createWithContent('users.csv', 'firstname'),
        ])->assertSessionHasErrors(['import_member_type'], null, 'importUsers');
    }

    public function test_student_and_faculty_imports_require_a_valid_program_for_every_row(): void
    {
        $dean = User::create([
            'name' => 'Required Program Dean', 'email' => 'required-program@example.com', 'password' => 'password',
            'role' => 'admin', 'department' => 'College of Computer Studies', 'is_department_dean' => true,
            'is_active' => true, 'is_approved' => true, 'policy_accepted_at' => now(),
            'policy_version' => config('repository_policy.version', '2026-04-29'),
        ]);
        $this->actingAs($dean);
        foreach (['student', 'faculty'] as $type) {
            $idColumn = $type === 'student' ? 'student_id' : 'employee_id';
            $firstId = $type === 'student' ? '12345678' : 'FAC-001';
            $secondId = $type === 'student' ? '12345679' : 'FAC-002';
            foreach ([null, '', '   ', 'Civil Engineering'] as $program) {
                $csv = "firstname,middlename,lastname,{$idColumn}";
                if ($program === null) {
                    $csv .= "\nAna,Middle,Member,{$firstId}";
                } else {
                    $csv .= ",program\nAna,Middle,Member,{$firstId},Information Technology";
                    $csv .= "\nBen,Middle,Member,{$secondId},{$program}";
                }
                $this->post(route('admin.import-users'), [
                    'import_member_type' => $type,
                    'file' => UploadedFile::fake()->createWithContent($type . '.csv', $csv),
                ])->assertSessionHas('error')->assertSessionHas('import_errors', function ($issues) use ($program) {
                    $message = $program === 'Civil Engineering'
                        ? 'Invalid program for the assigned department.' : 'Program is required.';
                    return collect($issues)->contains(fn ($issue) => str_contains($issue['error'], $message));
                });
                $this->assertDatabaseCount('users', 1);
                $this->assertDatabaseCount('semester_enrollments', 0);
            }
        }
        $this->get(route('admin.users'))->assertOk()->assertSee('Program is required for every Student and Faculty row');
    }

    public function test_manual_student_and_faculty_creation_require_a_valid_program(): void
    {
        $dean = User::create([
            'name' => 'Required Program Dean', 'email' => 'manual-required-program@example.com', 'password' => 'password',
            'role' => 'admin', 'department' => 'College of Computer Studies', 'is_department_dean' => true,
            'is_active' => true, 'is_approved' => true, 'policy_accepted_at' => now(),
            'policy_version' => config('repository_policy.version', '2026-04-29'),
        ]);
        $this->actingAs($dean)->get(route('admin.create-user'))->assertOk()
            ->assertSee('name="program" required', false);
        foreach (['student', 'faculty'] as $type) {
            foreach ([null, '', 'Civil Engineering'] as $program) {
                $data = [
                    'firstname' => 'New', 'lastname' => 'Member', 'member_type' => $type,
                    'student_id' => $type . '-001', 'email' => $type . '@example.com', 'year_level' => 2,
                ];
                if ($program !== null) {
                    $data['program'] = $program;
                }
                $this->post(route('admin.store-user'), $data)->assertSessionHasErrors(['program'], null, 'addUser');
                $this->assertDatabaseCount('users', 1);
            }
        }
    }

    public function test_import_always_generates_the_password_from_id_and_first_name_even_with_a_password_column(): void
    {
        $dean = User::create([
            'name' => 'Login Details Dean', 'email' => 'generated-login@example.com', 'password' => 'password',
            'role' => 'admin', 'department' => 'College of Computer Studies', 'is_department_dean' => true,
            'is_active' => true, 'is_approved' => true, 'policy_accepted_at' => now(),
            'policy_version' => config('repository_policy.version', '2026-04-29'),
        ]);
        $this->actingAs($dean);
        foreach (['student' => '00037838', 'faculty' => 'FAC-003'] as $type => $identifier) {
            $idColumn = $type === 'student' ? 'student_id' : 'employee_id';
            $csv = "firstname,middlename,lastname,{$idColumn},program,password\n"
                . "SE RGIO,Middle,Member,{$identifier},Information Technology,ProvidedPassword123!";
            $this->post(route('admin.import-users'), [
                'import_member_type' => $type,
                'file' => UploadedFile::fake()->createWithContent($type . '.csv', $csv),
            ])->assertSessionHasNoErrors()->assertSessionHas('success');
            $member = User::where('student_id', $identifier)->firstOrFail();
            $this->assertTrue(\Illuminate\Support\Facades\Hash::check($identifier . '_Ser', $member->password));
            $this->assertFalse(\Illuminate\Support\Facades\Hash::check('ProvidedPassword123!', $member->password));
            $originalPassword = $member->password;
            $this->post(route('admin.import-users'), [
                'import_member_type' => $type,
                'file' => UploadedFile::fake()->createWithContent($type . '.csv', str_replace('SE RGIO', 'Changed', $csv)),
            ])->assertSessionHasNoErrors()->assertSessionHas('success');
            $this->assertSame($originalPassword, $member->fresh()->password);
        }
    }

    private function currentSchoolYear(): string
    {
        $startYear = now()->month >= 6 ? now()->year : now()->year - 1;

        return sprintf('%04d-%04d', $startYear, $startYear + 1);
    }
}
