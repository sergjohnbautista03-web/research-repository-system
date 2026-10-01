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

        Schema::dropIfExists('research_views');
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

        (require database_path('migrations/2026_09_27_000001_create_research_views_table.php'))->up();
    }

    public function test_recent_views_are_personal_ordered_and_deduplicated_for_students_and_faculty(): void
    {
        foreach (['user', 'researcher'] as $role) {
            $user = User::create([
                'name' => 'Reader', 'email' => $role . '@example.com',
                'password' => 'password', 'role' => $role,
                'policy_accepted_at' => now(),
                'policy_version' => config('repository_policy.version', '2026-04-29'),
            ]);
            $papers = collect();
            foreach (['First paper', 'Second paper'] as $title) {
                $papers->push(Research::create([
                    'title' => $title, 'abstract' => 'An abstract', 'author_name' => 'Author',
                    'user_id' => $user->id, 'department' => 'College of Computer Studies',
                    'status' => 'approved',
                ]));
            }

            $this->actingAs($user)->get(route('research.show', $papers[0]))->assertOk();
            $this->travel(1)->minutes();
            $this->get(route('research.show', $papers[1]))->assertOk();
            $this->travel(1)->minutes();
            $this->get(route('research.show', $papers[0]))->assertOk();

            $this->assertSame(2, $user->recentlyViewedResearches()->count());
            $response = $this->get(route('user.dashboard'))->assertOk()->assertSee('Recently Viewed');
            $this->assertSame([$papers[0]->id, $papers[1]->id], $response->viewData('recentlyViewedResearches')->pluck('id')->all());

            $papers[0]->update(['status' => 'archived']);
            $this->assertSame([$papers[1]->id], $user->recentlyViewedResearches()->pluck('researches.id')->all());
            $papers[1]->delete();
            $this->assertSame(0, $user->recentlyViewedResearches()->count());
            $this->travelBack();
        }
    }

    public function test_recent_history_is_limited_to_twenty_and_excludes_another_readers_history(): void
    {
        $reader = User::create([
            'name' => 'Reader', 'email' => 'reader@example.com', 'password' => 'password',
            'policy_accepted_at' => now(),
            'policy_version' => config('repository_policy.version', '2026-04-29'),
        ]);
        $other = User::create([
            'name' => 'Other Reader', 'email' => 'other@example.com', 'password' => 'password',
        ]);
        for ($i = 0; $i < 22; $i++) {
            $paper = Research::create([
                'title' => 'Paper ' . $i, 'abstract' => 'Abstract', 'author_name' => 'Author',
                'user_id' => $reader->id, 'status' => 'approved',
            ]);
            $reader->recentlyViewedResearches()->attach($paper->id, ['last_viewed_at' => now()->subMinutes($i)]);
        }

        $this->assertSame(0, $other->recentlyViewedResearches()->count());
        $response = $this->actingAs($reader)->get(route('user.dashboard'))->assertOk();
        $this->assertCount(20, $response->viewData('recentlyViewedResearches'));
        $this->assertSame('Paper 0', $response->viewData('recentlyViewedResearches')->first()->title);
        $this->assertSame('Paper 19', $response->viewData('recentlyViewedResearches')->last()->title);
    }

    public function test_admin_view_totals_are_grouped_by_publication_year(): void
    {
        $admin = User::create(['name' => 'Admin', 'email' => 'analytics@example.com', 'password' => 'password', 'role' => 'admin']);
        $this->actingAs($admin);
        foreach ([[2022, 3], [2022, 7], [2023, 4]] as [$year, $views]) {
            Research::create([
                'title' => 'Analytics paper', 'abstract' => 'Abstract', 'author_name' => 'Author',
                'user_id' => $admin->id, 'department' => 'College of Computer Studies',
                'status' => 'approved', 'year_published' => $year, 'view_count' => $views,
                'citation_copy_count' => 2,
            ]);
        }
        $method = new \ReflectionMethod(\App\Http\Controllers\AdminController::class, 'buildResearchAnalyticsPayload');
        $payload = $method->invoke(new \App\Http\Controllers\AdminController(), true);
        $summary = $payload['analyticsSummary'];
        $this->assertEquals(14, $summary['total_views']);
        $this->assertEquals(10, $summary['views_by_year'][2022]);
        $this->assertEquals(4, $summary['views_by_year'][2023]);
        $this->assertEquals(0, $summary['views_by_year'][2024] ?? 0);
        $this->assertEquals(2, $summary['papers_by_year'][2022]);
        $this->assertEquals(1, $summary['papers_by_year'][2023]);
        $this->assertEquals(0, $summary['papers_by_year'][2024] ?? 0);
        $this->assertEquals(4, $summary['citations_by_year'][2022]);
        $this->assertEquals(2, $summary['citations_by_year'][2023]);
        $this->assertEquals(6, $summary['total_copy_citations']);
        $withoutCitations = $method->invoke(new \App\Http\Controllers\AdminController(), false);
        $this->assertSame([], $withoutCitations['analyticsSummary']['citations_by_year']);
    }

    public function test_guest_and_denied_research_visits_do_not_create_history(): void
    {
        $user = User::create([
            'name' => 'Reader', 'email' => 'reader@example.com', 'password' => 'password',
        ]);
        $paper = Research::create([
            'title' => 'Public paper', 'abstract' => 'Abstract', 'author_name' => 'Author',
            'department' => 'College of Computer Studies', 'user_id' => $user->id,
            'status' => 'approved',
        ]);
        $this->get(route('research.show', $paper))->assertOk();
        $this->assertDatabaseCount('research_views', 0);

        $paper->update(['status' => 'pending', 'user_id' => $user->id + 1]);
        $this->actingAs($user)->get(route('research.show', $paper))->assertNotFound();
        $this->assertDatabaseCount('research_views', 0);
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
        $response->assertSee('id="modal-current-password"', false);
        $response->assertSee('id="modal-new-password"', false);
        $response->assertSee('id="modal-confirm-password"', false);
        $this->get(route('profile.edit'))->assertOk()
            ->assertViewIs('profile.edit')
            ->assertSee('Profile Settings')
            ->assertSee('Change Password');
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
