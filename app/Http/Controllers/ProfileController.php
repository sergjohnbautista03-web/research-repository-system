<?php

namespace App\Http\Controllers;

use App\Mail\PasswordChangeVerificationCode;
use App\Mail\ProfileAccountChangeVerificationCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('profile.edit', [
            'departments' => [
                'College of Accountancy and Business Education',
                'College of Computer Studies',
                'College of Criminal Justice Education',
                'College of Education',
                'College of Engineering and Architecture',
                'College of Maritime Studies',
            ],
        ]);
    }

    public function update(Request $request)
    {
        if ($request->hasAny(['first_name', 'middle_name', 'last_name', 'name'])) {
            return back()->with('error', 'First name and last name cannot be changed from your profile.');
        }

        $user = Auth::user();
        $data = $request->validate($this->profileAccountChangeRules($user));
        $changes = [];
        if (array_key_exists('username', $data)) {
            $username = mb_strtolower(trim($data['username']));
            if ($username !== mb_strtolower((string) $user->username) && $username !== mb_strtolower((string) ($user->student_id ?: $user->email))) {
                $changes['username'] = $username;
            }
        }
        if (array_key_exists('email', $data)) {
            $email = strtolower(trim($data['email']));
            if ($email !== strtolower((string) $user->email)) {
                $changes['email'] = $email;
            }
        }

        if ($user->isAdmin() && $changes) {
            $code = sprintf('%06d', random_int(100000, 999999));
            session([
                'profile_account_change_verification' => [
                    'user_id' => $user->id,
                    'current_email' => $user->email,
                    'changes' => $changes,
                    'code_hash' => Hash::make($code),
                    'expires_at' => now()->addMinutes(5)->timestamp,
                    'attempts' => 0,
                ],
            ]);

            try {
                Mail::to($user->email)->send(new ProfileAccountChangeVerificationCode($user, $code));
            } catch (\Throwable $exception) {
                report($exception);
                session()->forget('profile_account_change_verification');

                return back()
                    ->withErrors(['profile_update' => 'We could not send the verification code to your registered email. Please try again.'])
                    ->withInput($data);
            }

            return back()
                ->with('profile_account_change_sent', true)
                ->withInput($data);
        }

        if ($changes) {
            $user->update($changes);
        }

        return back()->with('success', 'Profile updated successfully!')->with('profile_updated', true);
    }

    private function profileAccountChangeRules(User $user): array
    {
        return [
            'username' => [
                'sometimes', 'required_without:email', 'string', 'max:100', "regex:/^[\pL\pN._@-]+$/u",
                Rule::unique('users', 'username')->ignore($user->id),
                function ($attribute, $value, $fail) use ($user) {
                    $normalizedUsername = mb_strtolower(trim($value));
                    $conflictsWithAnotherLogin = $user->newQuery()
                        ->where('id', '!=', $user->id)
                        ->where(function ($query) use ($normalizedUsername) {
                            $query->whereRaw('LOWER(username) = ?', [$normalizedUsername])
                                ->orWhereRaw('LOWER(email) = ?', [$normalizedUsername])
                                ->orWhereRaw('LOWER(student_id) = ?', [$normalizedUsername]);
                        })
                        ->exists();

                    if ($conflictsWithAnotherLogin) {
                        $fail('This username is already being used as another account login.');
                    }
                },
            ],
            'email' => [
                'sometimes', 'required_without:username', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id),
                function ($attribute, $value, $fail) use ($user) {
                    if (! Schema::hasColumn('users', 'username')) {
                        return;
                    }

                    $normalizedEmail = mb_strtolower(trim($value));
                    $conflictsWithAnotherUsername = $user->newQuery()
                        ->where('id', '!=', $user->id)
                        ->whereRaw('LOWER(username) = ?', [$normalizedEmail])
                        ->exists();

                    if ($conflictsWithAnotherUsername) {
                        $fail('This email address is already being used as another account username.');
                    }
                },
            ],
        ];
    }

    public function verifyProfileAccountChange(Request $request)
    {
        $user = Auth::user();
        abort_unless($user->isAdmin(), 403);

        $request->validate([
            'account_verification_code' => ['required', 'digits:6'],
        ], [
            'account_verification_code.required' => 'Enter the 6-digit verification code.',
            'account_verification_code.digits' => 'The verification code must contain exactly 6 digits.',
        ]);

        $verification = session('profile_account_change_verification');
        if (! $verification || ($verification['user_id'] ?? null) !== $user->id
            || ($verification['current_email'] ?? null) !== $user->email) {
            session()->forget('profile_account_change_verification');

            return back()
                ->withErrors(['account_verification_code' => 'This verification request is no longer valid. Submit your changes again to get a new code.'])
                ->with('profile_account_change_modal_notice', true);
        }

        if (now()->timestamp >= ($verification['expires_at'] ?? 0)) {
            session()->forget('profile_account_change_verification');

            return back()
                ->withErrors(['account_verification_code' => 'The verification code has expired. Submit your changes again to get a new code.'])
                ->with('profile_account_change_modal_notice', true)
                ->withInput($verification['changes'] ?? []);
        }

        $attempts = ($verification['attempts'] ?? 0) + 1;
        if ($attempts > 5) {
            session()->forget('profile_account_change_verification');

            return back()
                ->withErrors(['account_verification_code' => 'Too many incorrect attempts. Submit your changes again to get a new code.'])
                ->with('profile_account_change_modal_notice', true)
                ->withInput($verification['changes'] ?? []);
        }

        if (! Hash::check((string) $request->account_verification_code, $verification['code_hash'])) {
            $verification['attempts'] = $attempts;
            session(['profile_account_change_verification' => $verification]);

            return back()->withErrors([
                'account_verification_code' => 'Incorrect verification code. Please check the code sent to your registered email and try again.',
            ]);
        }

        $changes = $verification['changes'] ?? [];
        $validator = Validator::make($changes, $this->profileAccountChangeRules($user));
        if ($validator->fails()) {
            session()->forget('profile_account_change_verification');

            return back()
                ->withErrors($validator)
                ->withInput($changes);
        }

        $user->update($validator->validated());
        session()->forget('profile_account_change_verification');

        return back()->with('success', 'Your username and email changes have been verified and saved.')->with('profile_updated', true);
    }

    public function cancelProfileAccountChange(Request $request)
    {
        $verification = session('profile_account_change_verification');
        session()->forget('profile_account_change_verification');

        return back()
            ->withInput($verification['changes'] ?? [])
            ->with('info', 'Username and email changes were not saved.');
    }

    public function updatePassword(Request $request)
    {
        return $this->requestPasswordChangeCode($request);
    }

    public function requestPasswordChangeCode(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'string', 'confirmed', Password::min(8)->numbers()->symbols()],
        ], [
            'current_password.required' => 'Please enter your current password.',
            'password.required'         => 'Please enter a new password.',
            'password.confirmed'        => 'The new password confirmation does not match.',
        ]);

        $user = Auth::user();

        if (! Hash::check($request->current_password, $user->password)) {
            return back()
                ->withErrors(['current_password' => 'Current password is incorrect.'])
                ->withInput();
        }

        if (Hash::check($request->password, $user->password)) {
            return back()
                ->withErrors(['password' => 'New password must be different from your current password.'])
                ->withInput();
        }

        $code = sprintf('%06d', random_int(100000, 999999));

        session([
            'password_change_verification' => [
                'user_id'           => $user->id,
                'email'             => $user->email,
                'code_hash'         => Hash::make($code),
                'new_password_hash' => Hash::make($request->password),
                'expires_at'        => now()->addMinutes(15)->timestamp,
                'attempts'          => 0,
            ],
            'password_change_pending' => true,
        ]);

        try {
            Mail::to($user->email)->send(new PasswordChangeVerificationCode($user, $code, 15));
        } catch (\Throwable $e) {
            report($e);
            session()->forget(['password_change_verification', 'password_change_pending']);

            return back()
                ->withErrors(['password' => 'Verification code could not be sent right now. Please check your mail configuration and try again.'])
                ->withInput();
        }

        return back()->with('password_code_sent', 'A 6-digit verification code has been sent to your registered email address (' . $user->email . '). Please enter it below to complete your password change.');
    }

    public function verifyPasswordChangeCode(Request $request)
    {
        $request->validate([
            'verification_code' => ['required', 'digits:6'],
        ], [
            'verification_code.required' => 'Please enter the 6-digit verification code.',
            'verification_code.digits'   => 'The verification code must be exactly 6 digits.',
        ]);

        $verification = session('password_change_verification');
        $user = Auth::user();

        if (! $verification || ($verification['user_id'] ?? null) !== $user->id) {
            session()->forget(['password_change_verification', 'password_change_pending']);

            return back()
                ->withErrors(['verification_code' => 'No pending password change request found or it has expired. Please enter your new password again.']);
        }

        if (now()->timestamp > ($verification['expires_at'] ?? 0)) {
            session()->forget(['password_change_verification', 'password_change_pending']);

            return back()
                ->withErrors(['verification_code' => 'The verification code has expired. Please request a new password change.']);
        }

        $attempts = ($verification['attempts'] ?? 0) + 1;
        if ($attempts > 5) {
            session()->forget(['password_change_verification', 'password_change_pending']);

            return back()
                ->withErrors(['verification_code' => 'Too many invalid attempts. For your security, this verification request has been cancelled.']);
        }

        if (! Hash::check((string) $request->verification_code, $verification['code_hash'])) {
            $verification['attempts'] = $attempts;
            session([
                'password_change_verification' => $verification,
                'password_change_pending'      => true,
            ]);

            $remaining = 5 - $attempts;
            $remainingMsg = $remaining > 0 ? " ({$remaining} attempt(s) remaining)" : '';

            return back()
                ->withErrors(['verification_code' => 'Invalid verification code. Please check the code in your email and try again.' . $remainingMsg]);
        }

        $user->forceFill([
            'password'       => $verification['new_password_hash'],
            'remember_token' => Str::random(60),
        ])->save();
        \App\Services\UserActivity::record($user, 'password_changed', 'Changed account password.');

        session()->forget(['password_change_verification', 'password_change_pending']);

        return back()->with('success', 'Your password has been changed successfully!');
    }

    public function resendPasswordChangeCode(Request $request)
    {
        $verification = session('password_change_verification');
        $user = Auth::user();

        if (! $verification || ($verification['user_id'] ?? null) !== $user->id) {
            session()->forget(['password_change_verification', 'password_change_pending']);

            return back()
                ->withErrors(['verification_code' => 'No pending password change request found. Please start over.']);
        }

        $code = sprintf('%06d', random_int(100000, 999999));

        $verification['code_hash']  = Hash::make($code);
        $verification['expires_at'] = now()->addMinutes(15)->timestamp;
        $verification['attempts']   = 0;

        session([
            'password_change_verification' => $verification,
            'password_change_pending'      => true,
        ]);

        try {
            Mail::to($user->email)->send(new PasswordChangeVerificationCode($user, $code, 15));
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withErrors(['verification_code' => 'Could not resend the verification code. Please try again later.']);
        }

        return back()->with('password_code_sent', 'A new 6-digit verification code has been sent to ' . $user->email . '.');
    }

    public function cancelPasswordChange(Request $request)
    {
        session()->forget(['password_change_verification', 'password_change_pending']);

        return back()->with('info', 'Password change request cancelled.');
    }

    public function updatePhoto(Request $request)
    {
        $request->validate([
            'profile_photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ], [
            'profile_photo.required' => 'Please select a photo.',
            'profile_photo.image'    => 'File must be an image.',
            'profile_photo.mimes'    => 'Only JPG, PNG, GIF are allowed.',
            'profile_photo.max'      => 'Photo must not exceed 2MB.',
        ]);

        $user = Auth::user();

        // Delete old photo if exists
        if ($user->profile_photo) {
            Storage::disk('public')->delete($user->profile_photo);
        }

        // Store new photo
        $path = $request->file('profile_photo')->store('profile-photos', 'public');

        $user->update(['profile_photo' => $path]);
        \App\Services\UserActivity::record($user, 'profile_updated', 'Updated profile photo.');

        return back()->with('success', 'Profile photo updated successfully!');
    }

    public function removePhoto()
    {
        $user = Auth::user();

        if ($user->profile_photo) {
            Storage::disk('public')->delete($user->profile_photo);
            $user->update(['profile_photo' => null]);
            \App\Services\UserActivity::record($user, 'profile_updated', 'Removed profile photo.');
        }

        return back()->with('success', 'Profile photo removed.');
    }
}
