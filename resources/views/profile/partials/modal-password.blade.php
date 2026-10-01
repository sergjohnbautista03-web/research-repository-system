<details class="ud-password-section" @if(session('password_change_pending') || session('password_code_sent') || session('info') === 'Password change request cancelled.' || session('success') === 'Your password has been changed successfully!' || $errors->hasAny(['current_password', 'password', 'password_confirmation', 'verification_code'])) open @endif>
    <summary>Change Password <span>Keep your account secure</span></summary>
    <div class="ud-password-content">
        @foreach(['password_code_sent', 'success', 'info'] as $notice)
            @if(session($notice))
                <p class="ud-password-notice" role="status">{{ session($notice) }}</p>
            @endif
        @endforeach
        @foreach(['current_password', 'password', 'password_confirmation', 'verification_code'] as $field)
            @error($field)<p class="ud-password-error" role="alert">{{ $message }}</p>@enderror
        @endforeach

        @if(session('password_change_pending'))
            <p>Enter the 6-digit code sent to <strong>{{ auth()->user()->email }}</strong>. The code expires in 15 minutes.</p>
            <form method="POST" action="{{ route('profile.password.verify') }}">
                @csrf
                <label for="modal-verification-code">Verification Code</label>
                <input id="modal-verification-code" name="verification_code" type="text" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required>
                <button type="submit" class="ud-password-submit">Verify &amp; Update Password</button>
            </form>
            <div class="ud-password-actions">
                <form method="POST" action="{{ route('profile.password.resend-code') }}">@csrf<button type="submit" class="ra-link-btn">Resend Code</button></form>
                <form method="POST" action="{{ route('profile.password.cancel') }}">@csrf<button type="submit" class="ra-link-btn">Cancel</button></form>
            </div>
        @else
            <p>Enter your current and new password. We'll email you a verification code to confirm the change.</p>
            <form method="POST" action="{{ route('profile.password.request-code') }}">
                @csrf
                <label for="modal-current-password">Current Password</label>
                <div class="ud-password-input-wrap">
                    <input id="modal-current-password" type="password" name="current_password" autocomplete="current-password" required>
                    <x-password-eye target="modal-current-password" />
                </div>
                <div class="ud-password-grid">
                    <div><label for="modal-new-password">New Password</label><div class="ud-password-input-wrap"><input id="modal-new-password" type="password" name="password" autocomplete="new-password" minlength="8" aria-describedby="modal-password-hint" required><x-password-eye target="modal-new-password" /></div></div>
                    <div><label for="modal-confirm-password">Confirm New Password</label><div class="ud-password-input-wrap"><input id="modal-confirm-password" type="password" name="password_confirmation" autocomplete="new-password" minlength="8" required><x-password-eye target="modal-confirm-password" /></div></div>
                </div>
                <p id="modal-password-hint" class="ud-password-hint">Use at least 8 characters, including a number and a symbol.</p>
                <button type="submit" class="ud-password-submit">Send Verification Code</button>
            </form>
        @endif
    </div>
</details>
