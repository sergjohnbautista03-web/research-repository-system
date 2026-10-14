@extends('layouts.app')

@section('title', 'Reset Password')

@push('styles')
<style>
.rp-page {
    position: relative;
    min-height: calc(100vh - 64px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 48px 20px;
    background: #1b0a2c;
    isolation: isolate;
}

.rp-page::before {
    content: "";
    position: absolute;
    inset: 0;
    z-index: -1;
    background:
        linear-gradient(135deg, rgba(30, 7, 52, .9), rgba(82, 41, 122, .74)),
        url("{{ asset('images/philcstarea.jpg') }}") center / cover no-repeat;
}

.rp-card {
    width: min(100%, 460px);
    padding: 32px;
    border: 1px solid #e4d6f2;
    border-radius: 8px;
    background: #fff;
    box-shadow: 0 26px 70px rgba(20, 8, 36, .38);
}

.rp-header {
    display: grid;
    justify-items: center;
    margin-bottom: 24px;
    text-align: center;
}

.rp-icon {
    width: 54px;
    height: 54px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 16px;
    border-radius: 8px;
    color: #fff;
    background: linear-gradient(135deg, var(--purple-main), var(--teal));
}

.rp-header h1 {
    margin: 0 0 10px;
    color: var(--purple-deep);
    font-family: var(--font-head);
    font-size: clamp(26px, 6vw, 32px);
    line-height: 1.2;
}

.rp-header p {
    margin: 0;
    color: #6f5c82;
    font-size: 14px;
    line-height: 1.6;
}

.rp-alert {
    margin-bottom: 20px;
    padding: 12px 14px;
    border: 1px solid #fecaca;
    border-radius: 8px;
    background: #fff1f2;
    color: #991b1b;
    font-size: 13px;
    line-height: 1.5;
    overflow-wrap: anywhere;
}

.rp-alert p { margin: 0; }
.rp-alert p + p { margin-top: 6px; }
.rp-form { display: grid; gap: 18px; }
.rp-field { display: grid; gap: 8px; }

.rp-field label {
    color: var(--purple-deep);
    font-size: 13px;
    font-weight: 700;
}

.rp-input-wrap { position: relative; }

.rp-input-icon {
    position: absolute;
    top: 50%;
    left: 14px;
    display: inline-flex;
    color: #806b90;
    transform: translateY(-50%);
    pointer-events: none;
}

.rp-input-wrap input {
    width: 100%;
    min-height: 48px;
    padding: 12px 14px 12px 44px;
    border: 2px solid #ddc8ef;
    border-radius: 8px;
    background: #fff;
    font-size: 16px;
}

.rp-input-wrap .rp-password-input { padding-right: 50px; }
.rp-input-wrap input[aria-invalid="true"] { border-color: #dc2626; }

.rp-input-wrap input:focus {
    border-color: var(--purple-main);
    box-shadow: 0 0 0 4px rgba(107, 47, 160, .12);
}

.rp-input-wrap .ud-password-eye {
    position: absolute;
    top: 50%;
    right: 5px;
    width: 40px;
    height: 40px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    border: 0;
    border-radius: 6px;
    background: transparent;
    color: #806b90;
    cursor: pointer;
    transform: translateY(-50%);
}

.rp-input-wrap .ud-password-eye:hover { background: #f7f1fc; color: var(--purple-main); }

.rp-requirements {
    padding: 14px 16px;
    border: 1px solid #eee4f7;
    border-radius: 8px;
    background: #faf7fd;
    color: #6f5c82;
    font-size: 12px;
    line-height: 1.5;
}

.rp-requirements h2 {
    margin: 0 0 8px;
    color: var(--purple-deep);
    font-size: 12px;
    font-weight: 700;
}

.rp-requirements ul { display: grid; gap: 6px; margin: 0; padding: 0; list-style: none; }
.rp-requirements li { display: flex; align-items: center; gap: 8px; }
.rp-requirements li.is-met { color: #166534; }

.rp-requirement-icon {
    flex: 0 0 16px;
    height: 16px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #bba5cc;
    border-radius: 50%;
    font-size: 11px;
}

.is-met .rp-requirement-icon { border-color: #166534; background: #166534; color: #fff; }
.is-met .rp-requirement-icon::after { content: "\2713"; }

.rp-submit {
    min-height: 48px;
    width: 100%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 12px 20px;
    border: 0;
    border-radius: 8px;
    background: var(--purple-main);
    color: #fff;
    font: 700 14px var(--font-body);
    cursor: pointer;
    box-shadow: 0 12px 24px rgba(107, 47, 160, .2);
    transition: background .18s;
}

.rp-submit:hover { background: var(--purple-deep); }

.rp-footer {
    margin-top: 24px;
    padding-top: 20px;
    border-top: 1px solid #eee4f7;
    text-align: center;
}

.rp-back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--purple-main);
    font-size: 14px;
    font-weight: 700;
    text-decoration: none;
}

.rp-back-link:hover { color: var(--purple-deep); text-decoration: underline; }

.rp-card button:focus-visible, .rp-back-link:focus-visible {
    outline: 3px solid var(--purple-main);
    outline-offset: 3px;
}

body:has(.rp-page) .site-footer { margin-top: 0; }

@media (max-width: 520px) {
    .rp-page { align-items: flex-start; padding: 28px 14px; }
    .rp-card { padding: 28px 20px; }
}
</style>
@endpush

@section('content')
<main class="rp-page">
    <section class="rp-card" aria-labelledby="reset-password-title">
        <header class="rp-header">
            <span class="rp-icon" aria-hidden="true">
                <svg width="25" height="25" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="10" rx="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    <path d="m9 16 2 2 4-4"></path>
                </svg>
            </span>
            <h1 id="reset-password-title">Reset Your Password</h1>
            <p>Choose a secure new password to regain access to your account.</p>
        </header>

        @if($errors->any())
            <div class="rp-alert" id="reset-password-errors" role="alert">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}" class="rp-form" id="resetPasswordForm">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="rp-field">
                <label for="reset-email">Email Address</label>
                <div class="rp-input-wrap">
                    <span class="rp-input-icon" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                            <path d="m22 6-10 7L2 6"></path>
                        </svg>
                    </span>
                    <input type="email" id="reset-email" name="email" value="{{ old('email', $email ?? request('email')) }}" placeholder="yourname@philcst.edu.ph" autocomplete="email" required autofocus @error('email') aria-invalid="true" aria-describedby="reset-password-errors" @enderror>
                </div>
            </div>

            <div class="rp-field">
                <label for="reset-password">New Password</label>
                <div class="rp-input-wrap">
                    <span class="rp-input-icon" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="10" rx="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                    </span>
                    <input type="password" id="reset-password" name="password" class="rp-password-input" placeholder="Create a new password" autocomplete="new-password" minlength="8" required aria-describedby="reset-password-requirements @error('password') reset-password-errors @enderror" @error('password') aria-invalid="true" @enderror>
                    <x-password-eye target="reset-password" />
                </div>
            </div>

            <div class="rp-field">
                <label for="reset-password-confirmation">Confirm New Password</label>
                <div class="rp-input-wrap">
                    <span class="rp-input-icon" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                            <path d="m9 12 2 2 4-4"></path>
                        </svg>
                    </span>
                    <input type="password" id="reset-password-confirmation" name="password_confirmation" class="rp-password-input" placeholder="Re-enter your new password" autocomplete="new-password" minlength="8" required aria-describedby="reset-password-requirements">
                    <x-password-eye target="reset-password-confirmation" />
                </div>
            </div>

            <div class="rp-requirements" id="reset-password-requirements">
                <h2>Password requirements</h2>
                <ul>
                    <li id="req-len"><span class="rp-requirement-icon" aria-hidden="true"></span>At least 8 characters</li>
                    <li id="req-num"><span class="rp-requirement-icon" aria-hidden="true"></span>At least one number (0-9)</li>
                    <li id="req-sym"><span class="rp-requirement-icon" aria-hidden="true"></span>At least one special symbol (e.g. !@#$%)</li>
                    <li id="req-match"><span class="rp-requirement-icon" aria-hidden="true"></span>Passwords match</li>
                </ul>
            </div>

            <button type="submit" class="rp-submit">
                <span>Reset Password</span>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m20 6-11 11-5-5"></path>
                </svg>
            </button>
        </form>

        <div class="rp-footer">
            <a href="{{ route('login') }}" class="rp-back-link">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M19 12H5m7 7-7-7 7-7"></path>
                </svg>
                <span>Back to Login</span>
            </a>
        </div>
    </section>
</main>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const password = document.getElementById('reset-password');
    const confirmation = document.getElementById('reset-password-confirmation');

    function updateRequirements() {
        const value = password.value;
        const requirements = {
            'req-len': Array.from(value).length >= 8,
            'req-num': /\p{N}/u.test(value),
            'req-sym': /[\p{Z}\p{S}\p{P}]/u.test(value),
            'req-match': value.length > 0 && value === confirmation.value,
        };

        Object.entries(requirements).forEach(function ([id, met]) {
            document.getElementById(id).classList.toggle('is-met', met);
        });
    }

    password.addEventListener('input', updateRequirements);
    confirmation.addEventListener('input', updateRequirements);
    updateRequirements();
});
</script>
@endpush
