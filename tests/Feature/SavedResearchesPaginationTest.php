<?php

namespace Tests\Feature;

use App\Models\Research;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SavedResearchesPaginationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('research_pins');
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
            $table->string('profile_photo')->nullable();
            $table->year('graduation_year')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('semesters', function (Blueprint $table) {
            $table->id();
            $table->string('school_year');
            $table->string('semester');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->enum('status', ['active', 'archived'])->default('active');
            $table->timestamps();
        });

        Schema::create('researches', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('abstract');
            $table->string('author_name');
            $table->json('authors')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->string('submission_category')->default('research');
            $table->string('type')->default('Thesis');
            $table->string('department')->nullable();
            $table->string('course')->nullable();
            $table->string('program')->nullable();
            $table->unsignedInteger('year_published')->default(2024);
            $table->string('keywords')->nullable();
            $table->string('file_path')->default('fake.pdf');
            $table->string('file_name')->default('fake.pdf');
            $table->string('status')->default('approved');
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('citation_copy_count')->default(0);
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->unsignedBigInteger('semester_id')->nullable();
            $table->unsignedBigInteger('academic_semester_id')->nullable();
            $table->string('issn')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('research_pins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('research_id');
            $table->timestamps();
        });
    }

    public function test_dashboard_does_not_render_filter_toolbar_when_saved_records_are_10_or_fewer(): void
    {
        $user = User::create([
            'name' => 'Regular User',
            'email' => 'user@philcst.edu.ph',
            'password' => bcrypt('password123'),
            'role' => 'user',
            'department' => 'College of Computer Studies',
            'policy_accepted_at' => now(),
            'policy_version' => config('repository_policy.version', '2026-04-29'),
        ]);

        for ($i = 1; $i <= 5; $i++) {
            $research = Research::create([
                'title' => "Research Paper $i",
                'abstract' => "Abstract for paper $i",
                'author_name' => "Author $i",
                'user_id' => $user->id,
                'department' => 'College of Computer Studies',
                'year_published' => 2024,
                'status' => 'approved',
            ]);
            $user->pinnedResearches()->attach($research->id);
        }

        $response = $this->actingAs($user)->get(route('user.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Saved Researches');
        $response->assertSee('5 saved research papers in your account.');
        $response->assertDontSee('id="savedFilterToolbar"', false);
    }

    public function test_dashboard_renders_filter_toolbar_and_pagination_when_saved_records_exceed_10(): void
    {
        $user = User::create([
            'name' => 'Active Researcher',
            'email' => 'researcher@philcst.edu.ph',
            'password' => bcrypt('password123'),
            'role' => 'user',
            'department' => 'College of Computer Studies',
            'policy_accepted_at' => now(),
            'policy_version' => config('repository_policy.version', '2026-04-29'),
        ]);

        for ($i = 1; $i <= 14; $i++) {
            $dept = $i % 2 === 0 ? 'College of Computer Studies' : 'College of Education';
            $research = Research::create([
                'title' => "Special Academic Research Paper $i",
                'abstract' => "Abstract description for paper number $i",
                'author_name' => "Dr. Author $i",
                'user_id' => $user->id,
                'department' => $dept,
                'year_published' => 2022 + ($i % 4),
                'status' => 'approved',
            ]);
            $user->pinnedResearches()->attach($research->id);
        }

        $response = $this->actingAs($user)->get(route('user.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('14 saved research papers in your account.');
        $response->assertSee('id="savedFilterToolbar"', false);
        $response->assertSee('id="savedSearchInput"', false);
        $response->assertSee('id="savedDeptFilter"', false);
        $response->assertSee('id="savedYearFilter"', false);
        $response->assertSee('id="savedResetFilters"', false);
        $response->assertSee('id="savedPaginationWrap"', false);
        $response->assertSee('data-saved-card', false);
    }
}
