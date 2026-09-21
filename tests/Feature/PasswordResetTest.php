<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role')->default('user');
            $table->string('department')->nullable();
            $table->string('student_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_approved')->default(true);
            $table->boolean('is_department_dean')->default(false);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function test_student_can_request_a_password_reset_link(): void
    {
        Notification::fake();

        $student = $this->createUser([
            'email' => 'student@example.com',
            'role' => 'user',
            'student_id' => '12345678',
        ]);

        $response = $this->post(route('password.email'), [
            'email' => $student->email,
        ]);

        $response->assertSessionHas('status', 'If that email is registered, a password reset link has been sent.');
        Notification::assertSentTo($student, ResetPasswordNotification::class);
    }

    public function test_department_dean_can_request_a_password_reset_link(): void
    {
        Notification::fake();

        $dean = $this->createUser([
            'email' => 'dean@example.com',
            'role' => 'admin',
            'is_department_dean' => true,
            'department' => 'College of Computer Studies',
        ]);

        $response = $this->post(route('password.email'), [
            'email' => $dean->email,
        ]);

        $response->assertSessionHas('status', 'If that email is registered, a password reset link has been sent.');
        Notification::assertSentTo($dean, ResetPasswordNotification::class);
    }

    public function test_password_can_be_reset_from_the_emailed_token(): void
    {
        Notification::fake();

        $student = $this->createUser([
            'email' => 'reset-student@example.com',
            'role' => 'user',
            'student_id' => '87654321',
            'remember_token' => 'old-token',
        ]);

        $this->post(route('password.email'), [
            'email' => $student->email,
        ]);

        $token = null;
        Notification::assertSentTo(
            $student,
            ResetPasswordNotification::class,
            function (ResetPasswordNotification $notification) use (&$token) {
                $token = $notification->token;

                return true;
            }
        );

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => $student->email,
            'password' => 'NewPass1!',
            'password_confirmation' => 'NewPass1!',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success', 'Your password has been reset. You can now sign in.');

        $student->refresh();
        $this->assertTrue(Hash::check('NewPass1!', $student->password));
        $this->assertNotSame('old-token', $student->remember_token);
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => $student->email,
        ]);
    }

    public function test_unknown_email_receives_generic_response_without_sending_notification(): void
    {
        Notification::fake();

        $response = $this->post(route('password.email'), [
            'email' => 'missing@example.com',
        ]);

        $response->assertSessionHas('status', 'If that email is registered, a password reset link has been sent.');
        Notification::assertNothingSent();
    }

    private function createUser(array $overrides = []): User
    {
        $user = User::create(array_merge([
            'name' => 'Test User',
            'email' => 'user@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('OldPass1!'),
            'role' => 'user',
            'department' => null,
            'student_id' => null,
            'is_active' => true,
            'is_approved' => true,
            'is_department_dean' => false,
            'remember_token' => null,
        ], $overrides));

        $user->forceFill([
            'email_verified_at' => $overrides['email_verified_at'] ?? now(),
            'remember_token' => $overrides['remember_token'] ?? null,
        ])->save();

        return $user;
    }
}
