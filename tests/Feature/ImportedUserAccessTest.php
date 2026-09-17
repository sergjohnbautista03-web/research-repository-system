<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ImportedUserAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('users');
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('middle_name')->nullable();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('user');
            $table->string('department')->nullable();
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
    }

    public function test_dean_imported_users_can_submit_and_view_full_research_files(): void
    {
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
            'file' => UploadedFile::fake()->createWithContent('users.csv', $csv),
        ]);

        $response->assertRedirect(route('admin.users', ['imported' => 1]));
        $response->assertSessionHas('success');

        $student = User::query()->where('student_id', '12345678')->firstOrFail();
        $faculty = User::query()->where('student_id', 'FAC-001')->firstOrFail();

        $this->assertSame('College of Computer Studies', $student->department);
        $this->assertSame('user', $student->role);
        $this->assertTrue($student->is_active);
        $this->assertTrue($student->is_approved);
        $this->assertSame($dean->id, $student->student_approved_by);
        $this->assertTrue($student->canSubmitResearch());
        $this->assertTrue($student->canViewFullDocument());

        $this->assertSame('College of Computer Studies', $faculty->department);
        $this->assertSame('researcher', $faculty->role);
        $this->assertTrue($faculty->is_active);
        $this->assertTrue($faculty->is_approved);
        $this->assertSame($dean->id, $faculty->researcher_approved_by);
        $this->assertTrue($faculty->canSubmitResearch());
        $this->assertTrue($faculty->canViewFullDocument());
    }

    public function test_import_skips_existing_approved_users_and_imports_only_new_rows(): void
    {
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
            'file' => UploadedFile::fake()->createWithContent('users.csv', $csv),
        ]);

        $response->assertRedirect(route('admin.users', ['imported' => 1]));
        $response->assertSessionHas('success');
        $response->assertSessionHas('import_skipped', function (array $skipped) {
            return count($skipped) === 1
                && $skipped[0]['login_id'] === '12345678'
                && str_contains($skipped[0]['reason'], 'Existing');
        });

        $this->assertSame(1, User::query()->where('student_id', '12345678')->count());
        $this->assertSame($existing->id, User::query()->where('student_id', '12345678')->value('id'));
        $this->assertSame(3, User::query()->whereIn('student_id', ['87654321', '87654322', '87654323'])->count());
        $this->assertSame(5, User::query()->count());
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
}
