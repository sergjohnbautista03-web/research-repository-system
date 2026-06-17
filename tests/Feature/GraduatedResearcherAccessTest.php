<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GraduatedResearcherAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('users');
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('user');
            $table->string('department')->nullable();
            $table->string('student_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_approved')->default(true);
            $table->timestamp('policy_accepted_at')->nullable();
            $table->string('policy_version')->nullable();
            $table->unsignedTinyInteger('year_level')->nullable();
            $table->unsignedTinyInteger('course_duration')->nullable();
            $table->unsignedSmallInteger('graduation_year')->nullable();
            $table->date('researcher_end_date')->nullable();
            $table->timestamp('graduated_at')->nullable();
            $table->string('researcher_status')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function test_graduated_researcher_login_is_blocked_and_account_deactivated(): void
    {
        $user = $this->expiredResearcher();

        $response = $this->from('/')->post('/login', [
            'login' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHasErrors('login');

        $user->refresh();

        $this->assertFalse($user->is_active);
        $this->assertNotNull($user->graduated_at);
        $this->assertGuest();
    }

    public function test_graduated_researcher_with_existing_session_is_logged_out(): void
    {
        $user = $this->expiredResearcher();

        $response = $this->actingAs($user)->get('/faq');

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error');

        $user->refresh();

        $this->assertFalse($user->is_active);
        $this->assertNotNull($user->graduated_at);
        $this->assertGuest();
    }

    public function test_researcher_status_label_is_only_active_or_graduated(): void
    {
        $activeResearcher = User::create([
            'name' => 'Active Researcher',
            'email' => 'active@example.com',
            'password' => 'password',
            'role' => 'researcher',
            'department' => 'College of Computer Studies',
            'student_id' => 'STU-0002',
            'is_active' => true,
            'is_approved' => true,
            'year_level' => 2,
            'course_duration' => 4,
            'graduation_year' => (int) date('Y') + 2,
            'researcher_status' => 'legacy_status_a',
        ]);

        $graduatedResearcher = User::create([
            'name' => 'Graduated Researcher',
            'email' => 'graduated@example.com',
            'password' => 'password',
            'role' => 'researcher',
            'department' => 'College of Computer Studies',
            'student_id' => 'STU-0003',
            'is_active' => false,
            'is_approved' => true,
            'year_level' => 4,
            'course_duration' => 4,
            'graduation_year' => (int) date('Y') - 1,
            'graduated_at' => now(),
            'researcher_status' => 'legacy_status_b',
        ]);

        $this->assertSame('Active', $activeResearcher->researcher_status_label);
        $this->assertTrue($activeResearcher->canSubmitResearch());
        $this->assertSame('Graduated', $graduatedResearcher->researcher_status_label);
        $this->assertFalse($graduatedResearcher->canSubmitResearch());
    }

    private function expiredResearcher(): User
    {
        return User::create([
            'name' => 'Expired Researcher',
            'email' => 'expired@example.com',
            'password' => 'password',
            'role' => 'researcher',
            'department' => 'College of Computer Studies',
            'student_id' => 'STU-0001',
            'is_active' => true,
            'is_approved' => true,
            'year_level' => 4,
            'course_duration' => 4,
            'graduation_year' => (int) date('Y') - 1,
            'researcher_end_date' => now()->subDay()->toDateString(),
            'policy_accepted_at' => now(),
            'policy_version' => config('repository_policy.version', '2026-04-29'),
        ]);
    }
}
