<?php

namespace Tests\Feature;

use App\Mail\PasswordChangeVerificationCode;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PasswordChangeVerificationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('password_reset_tokens');
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
            $table->string('student_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_approved')->default(true);
            $table->boolean('is_department_dean')->default(false);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('policy_accepted_at')->nullable();
            $table->string('policy_version')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function test_user_can_request_password_change_code_and_password_is_not_changed_yet(): void
    {
        Mail::fake();

        $user = $this->createUser([
            'email' => 'student@example.com',
            'password' => Hash::make('CurrentPass1!'),
        ]);

        $response = $this->actingAs($user)->post(route('profile.password.request-code'), [
            'current_password' => 'CurrentPass1!',
            'password' => 'NewSecurePass2@',
            'password_confirmation' => 'NewSecurePass2@',
        ]);

        $response->assertSessionHas('password_code_sent');
        $response->assertSessionHas('password_change_pending', true);

        // Password MUST NOT be updated in database yet
        $user->refresh();
        $this->assertTrue(Hash::check('CurrentPass1!', $user->password));
        $this->assertFalse(Hash::check('NewSecurePass2@', $user->password));

        // Mail must be sent to user's registered email
        Mail::assertSent(PasswordChangeVerificationCode::class, function (PasswordChangeVerificationCode $mail) use ($user) {
            return $mail->hasTo($user->email) && strlen($mail->code) === 6;
        });
    }

    public function test_password_change_fails_when_current_password_is_incorrect(): void
    {
        Mail::fake();

        $user = $this->createUser([
            'password' => Hash::make('CorrectPass1!'),
        ]);

        $response = $this->actingAs($user)->post(route('profile.password.request-code'), [
            'current_password' => 'WrongPass123!',
            'password' => 'NewSecurePass2@',
            'password_confirmation' => 'NewSecurePass2@',
        ]);

        $response->assertSessionHasErrors('current_password');
        Mail::assertNothingSent();
    }

    public function test_password_change_fails_when_new_password_is_same_as_current(): void
    {
        Mail::fake();

        $user = $this->createUser([
            'password' => Hash::make('CurrentPass1!'),
        ]);

        $response = $this->actingAs($user)->post(route('profile.password.request-code'), [
            'current_password' => 'CurrentPass1!',
            'password' => 'CurrentPass1!',
            'password_confirmation' => 'CurrentPass1!',
        ]);

        $response->assertSessionHasErrors('password');
        Mail::assertNothingSent();
    }

    public function test_password_change_fails_when_new_password_is_weak_or_mismatched(): void
    {
        Mail::fake();

        $user = $this->createUser([
            'password' => Hash::make('CurrentPass1!'),
        ]);

        // Weak password (no symbols/numbers)
        $response = $this->actingAs($user)->post(route('profile.password.request-code'), [
            'current_password' => 'CurrentPass1!',
            'password' => 'weakpass',
            'password_confirmation' => 'weakpass',
        ]);
        $response->assertSessionHasErrors('password');

        // Mismatched confirmation
        $response2 = $this->actingAs($user)->post(route('profile.password.request-code'), [
            'current_password' => 'CurrentPass1!',
            'password' => 'NewSecurePass2@',
            'password_confirmation' => 'DifferentPass3#',
        ]);
        $response2->assertSessionHasErrors('password');

        Mail::assertNothingSent();
    }

    public function test_user_can_complete_password_change_with_valid_verification_code(): void
    {
        Mail::fake();

        $user = $this->createUser([
            'email' => 'researcher@example.com',
            'password' => Hash::make('OldPass123!'),
            'role' => 'researcher',
            'remember_token' => 'old-remember-token',
        ]);

        $this->actingAs($user)->post(route('profile.password.request-code'), [
            'current_password' => 'OldPass123!',
            'password' => 'UpdatedPass456#',
            'password_confirmation' => 'UpdatedPass456#',
        ]);

        $code = null;
        Mail::assertSent(PasswordChangeVerificationCode::class, function (PasswordChangeVerificationCode $mail) use (&$code) {
            $code = $mail->code;
            return true;
        });

        $this->assertNotNull($code);

        $response = $this->actingAs($user)->post(route('profile.password.verify'), [
            'verification_code' => $code,
        ]);

        $response->assertSessionHas('success', 'Your password has been changed successfully!');
        $response->assertSessionMissing('password_change_verification');
        $response->assertSessionMissing('password_change_pending');

        $user->refresh();
        $this->assertTrue(Hash::check('UpdatedPass456#', $user->password));
        $this->assertFalse(Hash::check('OldPass123!', $user->password));
        $this->assertNotSame('old-remember-token', $user->remember_token);
    }

    public function test_password_change_fails_with_invalid_verification_code(): void
    {
        Mail::fake();

        $user = $this->createUser([
            'password' => Hash::make('OldPass123!'),
        ]);

        $this->actingAs($user)->post(route('profile.password.request-code'), [
            'current_password' => 'OldPass123!',
            'password' => 'UpdatedPass456#',
            'password_confirmation' => 'UpdatedPass456#',
        ]);

        $response = $this->actingAs($user)->post(route('profile.password.verify'), [
            'verification_code' => '000000', // Invalid code
        ]);

        $response->assertSessionHasErrors('verification_code');

        $user->refresh();
        $this->assertTrue(Hash::check('OldPass123!', $user->password));
    }

    public function test_password_change_fails_with_expired_verification_code(): void
    {
        Mail::fake();

        $user = $this->createUser([
            'password' => Hash::make('OldPass123!'),
        ]);

        $this->actingAs($user)->post(route('profile.password.request-code'), [
            'current_password' => 'OldPass123!',
            'password' => 'UpdatedPass456#',
            'password_confirmation' => 'UpdatedPass456#',
        ]);

        $code = null;
        Mail::assertSent(PasswordChangeVerificationCode::class, function (PasswordChangeVerificationCode $mail) use (&$code) {
            $code = $mail->code;
            return true;
        });

        // Fast-forward session to expired state
        $verification = session('password_change_verification');
        $verification['expires_at'] = now()->subMinutes(1)->timestamp;
        session(['password_change_verification' => $verification]);

        $response = $this->actingAs($user)->post(route('profile.password.verify'), [
            'verification_code' => $code,
        ]);

        $response->assertSessionHasErrors('verification_code');

        $user->refresh();
        $this->assertTrue(Hash::check('OldPass123!', $user->password));
    }

    public function test_user_can_resend_verification_code(): void
    {
        Mail::fake();

        $user = $this->createUser([
            'password' => Hash::make('OldPass123!'),
        ]);

        $this->actingAs($user)->post(route('profile.password.request-code'), [
            'current_password' => 'OldPass123!',
            'password' => 'UpdatedPass456#',
            'password_confirmation' => 'UpdatedPass456#',
        ]);

        $firstCode = null;
        Mail::assertSent(PasswordChangeVerificationCode::class, function (PasswordChangeVerificationCode $mail) use (&$firstCode) {
            $firstCode = $mail->code;
            return true;
        });

        $response = $this->actingAs($user)->post(route('profile.password.resend-code'));
        $response->assertSessionHas('password_code_sent');

        Mail::assertSent(PasswordChangeVerificationCode::class, 2);
    }

    public function test_user_can_cancel_password_change(): void
    {
        Mail::fake();

        $user = $this->createUser([
            'password' => Hash::make('OldPass123!'),
        ]);

        $this->actingAs($user)->post(route('profile.password.request-code'), [
            'current_password' => 'OldPass123!',
            'password' => 'UpdatedPass456#',
            'password_confirmation' => 'UpdatedPass456#',
        ]);

        $response = $this->actingAs($user)->post(route('profile.password.cancel'));
        $response->assertSessionHas('info');
        $this->assertNull(session('password_change_verification'));
        $this->assertNull(session('password_change_pending'));
    }

    public function test_too_many_invalid_attempts_cancels_verification_request(): void
    {
        Mail::fake();

        $user = $this->createUser([
            'password' => Hash::make('OldPass123!'),
        ]);

        $this->actingAs($user)->post(route('profile.password.request-code'), [
            'current_password' => 'OldPass123!',
            'password' => 'UpdatedPass456#',
            'password_confirmation' => 'UpdatedPass456#',
        ]);

        // 5 wrong attempts
        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($user)->post(route('profile.password.verify'), [
                'verification_code' => '111111',
            ]);
        }

        // 6th attempt should lock out and cancel session
        $response = $this->actingAs($user)->post(route('profile.password.verify'), [
            'verification_code' => '111111',
        ]);

        $response->assertSessionHasErrors('verification_code');
        $this->assertNull(session('password_change_verification'));
    }

    public function test_verification_code_cannot_be_reused(): void
    {
        Mail::fake();

        $user = $this->createUser([
            'password' => Hash::make('OldPass123!'),
        ]);

        $this->actingAs($user)->post(route('profile.password.request-code'), [
            'current_password' => 'OldPass123!',
            'password' => 'UpdatedPass456#',
            'password_confirmation' => 'UpdatedPass456#',
        ]);

        $code = null;
        Mail::assertSent(PasswordChangeVerificationCode::class, function (PasswordChangeVerificationCode $mail) use (&$code) {
            $code = $mail->code;
            return true;
        });

        // 1st verify succeeds
        $this->actingAs($user)->post(route('profile.password.verify'), [
            'verification_code' => $code,
        ]);

        // 2nd verify with same code must fail
        $response = $this->actingAs($user)->post(route('profile.password.verify'), [
            'verification_code' => $code,
        ]);

        $response->assertSessionHasErrors('verification_code');
    }

    public function test_dean_can_also_change_password_via_verification_flow(): void
    {
        Mail::fake();

        $dean = $this->createUser([
            'email' => 'dean@example.com',
            'role' => 'admin',
            'is_department_dean' => true,
            'department' => 'College of Computer Studies',
            'password' => Hash::make('DeanPass1!'),
        ]);

        $response = $this->actingAs($dean)->post(route('profile.password.request-code'), [
            'current_password' => 'DeanPass1!',
            'password' => 'NewDeanPass2@',
            'password_confirmation' => 'NewDeanPass2@',
        ]);

        $response->assertSessionHas('password_code_sent');
        Mail::assertSent(PasswordChangeVerificationCode::class);
    }

    private function createUser(array $overrides = []): User
    {
        $policyVersion = config('repository_policy.version', '2026-04-29');

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
            'last_seen_at' => now(),
            'policy_accepted_at' => now(),
            'policy_version' => $policyVersion,
            'remember_token' => null,
        ], $overrides));

        $user->forceFill([
            'email_verified_at' => $overrides['email_verified_at'] ?? now(),
            'last_seen_at' => $overrides['last_seen_at'] ?? now(),
            'policy_accepted_at' => $overrides['policy_accepted_at'] ?? now(),
            'policy_version' => $overrides['policy_version'] ?? $policyVersion,
            'remember_token' => $overrides['remember_token'] ?? null,
        ])->save();

        return $user;
    }
}
