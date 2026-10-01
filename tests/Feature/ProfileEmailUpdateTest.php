<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProfileEmailUpdateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role');
            $table->timestamps();
        });
        $this->withoutMiddleware();
    }

    public function test_student_and_faculty_can_update_only_their_email(): void
    {
        foreach (['user', 'researcher'] as $role) {
            $this->flushSession();
            $user = User::create(['name' => 'Original Name', 'email' => "$role@example.com", 'password' => 'password', 'role' => $role]);
            $this->actingAs($user)->patch(route('profile.update'), [
                'email' => "updated-$role@example.com", 'role' => 'admin', 'student_id' => 'forged',
            ])->assertSessionHasNoErrors()->assertSessionHas('profile_updated', true);
            $this->assertSame("updated-$role@example.com", $user->fresh()->email);
            $this->assertSame($role, $user->fresh()->role);
            $this->assertSame('Original Name', $user->fresh()->name);

            $this->patch(route('profile.update'), ['email' => "updated-$role@example.com"])
                ->assertSessionHasNoErrors();
            $this->patch(route('profile.update'), ['email' => 'invalid'])
                ->assertSessionHasErrors('email');
            $this->patch(route('profile.update'), ['email' => "other-$role@example.com", 'name' => 'Changed Name'])
                ->assertSessionHas('error');
            $this->assertSame('Original Name', $user->fresh()->name);
            $this->assertSame("updated-$role@example.com", $user->fresh()->email);
        }
    }

    public function test_duplicate_email_is_rejected(): void
    {
        User::create(['name' => 'Other User', 'email' => 'taken@example.com', 'password' => 'password', 'role' => 'user']);
        $user = User::create(['name' => 'Student', 'email' => 'student@example.com', 'password' => 'password', 'role' => 'user']);
        $this->actingAs($user)->patch(route('profile.update'), ['email' => 'taken@example.com'])
            ->assertSessionHasErrors('email');
        $this->assertSame('student@example.com', $user->fresh()->email);
    }
}
