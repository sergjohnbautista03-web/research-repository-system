@extends('layouts.app')
@section('title', 'Reset Password - Ube Repository')
@section('title', 'Reset Password — Ube Repository')

@section('content')
<div class="fp-page">
    <div class="fp-page-bg"></div>
    <div class="fp-glow fp-g1"></div>
    <div class="fp-glow fp-g2"></div>

    <div class="fp-card">
        <div class="fp-header">
            <div class="fp-logo">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
        <div class="fp-card-inner">
            <!-- Icon Badge -->
            <div class="fp-icon-wrap" aria-hidden="true">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    <path d="M9 12l2 2 4-4"/>
                </svg>
            </div>
            <h1>Reset Password</h1>
            <p>Create and confirm your new password.</p>
        </div>

        <div class="fp-body">
            <!-- Title & Subtitle -->
            <h1 class="fp-title">Reset Your Password</h1>
            <p class="fp-subtitle">
                Please enter your registered email and choose a secure new password for your account.
            </p>

            <!-- Error Messages -->
            @if($errors->any())
                <div class="alert alert-error">{{ $errors->first() }}</div>
                <div class="fp-alert fp-alert-error" role="alert">
                    <svg class="fp-alert-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    <div class="fp-alert-text">
                        @foreach($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('password.update') }}">
            <!-- Form -->
            <form method="POST" action="{{ route('password.update') }}" class="fp-form" novalidate id="resetPasswordForm">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div class="form-group">
                    <label for="reset-email">Email Address</label>
                    <input type="email" id="reset-email" name="email" value="{{ old('email', request('email')) }}" autocomplete="email" required autofocus>
                <!-- Email Field -->
                <div class="fp-group">
                    <label for="email" class="fp-label">Email Address</label>
                    <div class="fp-input-wrap">
                        <span class="fp-input-icon" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                <polyline points="22,6 12,13 2,6"/>
                            </svg>
                        </span>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="fp-input @error('email') is-invalid @enderror"
                            value="{{ old('email', $email ?? request('email')) }}"
                            placeholder="e.g. yourname@philcst.edu.ph"
                            autocomplete="email"
                            required
                            autofocus
                        >
                    </div>
                </div>
                <div class="form-group">
                    <label for="reset-password">New Password</label>
                    <div class="password-wrap">
                        <input type="password" id="reset-password" name="password" placeholder="Minimum 8 characters" autocomplete="new-password" required>
                        <button type="button" class="toggle-pw" onclick="togglePassword('reset-password', this)" aria-label="Show or hide password">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 4.411m0 0L21 21"/></svg>

                <!-- New Password Field -->
                <div class="fp-group">
                    <label for="password" class="fp-label">New Password</label>
                    <div class="fp-input-wrap">
                        <span class="fp-input-icon" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                        </span>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="fp-input fp-input-has-toggle @error('password') is-invalid @enderror"
                            placeholder="At least 8 characters"
                            autocomplete="new-password"
                            required
                        >
                        <button
                            type="button"
                            class="fp-toggle-pw"
                            onclick="togglePassword('password', this)"
                            aria-label="Show or hide password"
                            title="Toggle password visibility"
                        >
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                                <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                                <line x1="1" y1="1" x2="23" y2="23"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label for="reset-password-confirmation">Confirm New Password</label>
                    <div class="password-wrap">
                        <input type="password" id="reset-password-confirmation" name="password_confirmation" placeholder="Repeat new password" autocomplete="new-password" required>
                        <button type="button" class="toggle-pw" onclick="togglePassword('reset-password-confirmation', this)" aria-label="Show or hide password confirmation">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 4.411m0 0L21 21"/></svg>

                <!-- Confirm Password Field -->
                <div class="fp-group">
                    <label for="password_confirmation" class="fp-label">Confirm New Password</label>
                    <div class="fp-input-wrap">
                        <span class="fp-input-icon" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                            </svg>
                        </span>
                        <input
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            class="fp-input fp-input-has-toggle"
                            placeholder="Re-enter your new password"
                            autocomplete="new-password"
                            required
                        >
                        <button
                            type="button"
                            class="fp-toggle-pw"
                            onclick="togglePassword('password_confirmation', this)"
                            aria-label="Show or hide password confirmation"
                            title="Toggle confirmation visibility"
                        >
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                                <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                                <line x1="1" y1="1" x2="23" y2="23"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Password Requirements Checklist -->
                <div class="fp-req-box">
                    <div class="fp-req-title">Password Requirements:</div>
                    <ul class="fp-req-list">
                        <li id="req-len" class="fp-req-item">
                            <span class="fp-req-icon">○</span>
                            <span>At least 8 characters</span>
                        </li>
                        <li id="req-num" class="fp-req-item">
                            <span class="fp-req-icon">○</span>
                            <span>At least one number (0–9)</span>
                        </li>
                        <li id="req-sym" class="fp-req-item">
                            <span class="fp-req-icon">○</span>
                            <span>At least one special symbol (!@#$%^&*)</span>
                        </li>
                        <li id="req-match" class="fp-req-item">
                            <span class="fp-req-icon">○</span>
                            <span>Passwords match</span>
                        </li>
                    </ul>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="fp-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    Reset Password
                    <span>Reset Password</span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                </button>
            </form>

            <!-- Back to Login -->
            <div class="fp-footer">
                <a href="{{ route('login') }}" class="fp-back-link">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="19" y1="12" x2="5" y2="12"/>
                        <polyline points="12 19 5 12 12 5"/>
                    </svg>
                    <span>Back to Login</span>
                </a>
            </div>
        </div>
    </div>
</div>

<style>
/* ── Scoped Reset Password Page ───────────────────────────── */
.fp-page {
    position: relative;
    min-height: calc(100vh - 64px);
    display: flex; align-items: center; justify-content: center;
    padding: 40px 20px; overflow: hidden; background: #1e0540;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 48px 20px;
    overflow: hidden;
    background: #19082e;
}

.fp-page-bg {
    position: absolute; inset: 0;
    position: absolute;
    inset: 0;
    background: url('/images/philcstarea.jpg') center/cover no-repeat;
    filter: blur(8px) brightness(0.35); transform: scale(1.04); z-index: 0;
    filter: blur(8px) brightness(0.32);
    transform: scale(1.05);
    z-index: 0;
}
.fp-glow { position: absolute; border-radius: 50%; filter: blur(80px); pointer-events: none; z-index: 1; }
.fp-g1 { width: 500px; height: 500px; background: #7c3aed; opacity: .22; top: -200px; right: -100px; }
.fp-g2 { width: 280px; height: 280px; background: #a855f7; opacity: .15; bottom: -80px; left: 10%; }

.fp-glow {
    position: absolute;
    border-radius: 50%;
    filter: blur(90px);
    pointer-events: none;
    z-index: 1;
}
.fp-g1 {
    width: 480px;
    height: 480px;
    background: #7c3aed;
    opacity: 0.28;
    top: -160px;
    right: -100px;
}
.fp-g2 {
    width: 380px;
    height: 380px;
    background: #a855f7;
    opacity: 0.22;
    bottom: -120px;
    left: -60px;
}

/* ── White Card Container ─────────────────────────────────── */
.fp-card {
    position: relative; z-index: 2;
    width: 100%; max-width: 420px;
    border-radius: 24px; overflow: hidden;
    box-shadow: 0 24px 80px rgba(0,0,0,.5);
    position: relative;
    z-index: 2;
    width: 100%;
    max-width: 470px;
    background: #ffffff;
    border-radius: 24px;
    box-shadow:
        0 24px 60px -12px rgba(35, 10, 65, 0.45),
        0 10px 24px -6px rgba(0, 0, 0, 0.08),
        0 0 0 1px rgba(124, 58, 237, 0.12);
    overflow: hidden;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.fp-header {
    background: #3b0f7a; padding: 32px 36px 26px;
    text-align: center; position: relative; overflow: hidden;

.fp-card-inner {
    padding: 40px 36px 36px;
    text-align: center;
}
.fp-header::before { content:''; position:absolute; width:200px; height:200px; background:rgba(255,255,255,.06); border-radius:50%; top:-80px; right:-60px; }
.fp-header::after  { content:''; position:absolute; width:140px; height:140px; background:rgba(255,255,255,.04); border-radius:50%; bottom:-50px; left:-40px; }
.fp-logo {
    width:52px; height:52px; border-radius:50%;
    background:rgba(255,255,255,.15); border:2px solid rgba(255,255,255,.25);
    display:flex; align-items:center; justify-content:center;
    margin:0 auto 14px; position:relative; z-index:1;

/* ── Icon Badge ───────────────────────────────────────────── */
.fp-icon-wrap {
    width: 60px;
    height: 60px;
    margin: 0 auto 20px;
    border-radius: 18px;
    background: linear-gradient(135deg, #f5effe 0%, #ebdcfc 100%);
    color: #6b2fa0;
    border: 1px solid rgba(107, 47, 160, 0.18);
    box-shadow: 0 8px 20px -4px rgba(107, 47, 160, 0.22);
    display: flex;
    align-items: center;
    justify-content: center;
}
.fp-header h1 { font-family:var(--font-body); font-size:22px; font-weight:700; color:white; margin-bottom:5px; letter-spacing:-.3px; position:relative; z-index:1; }
.fp-header p  { font-size:13px; color:rgba(255,255,255,.55); position:relative; z-index:1; margin:0; }
.fp-body { background:white; padding:28px 36px 32px; }
.fp-body .form-group { margin-bottom: 16px; }
.fp-body .form-group label { font-size:11.5px; font-weight:700; text-transform:uppercase; letter-spacing:.6px; color:var(--purple-deep); display:block; margin-bottom:7px; }
.fp-body input[type="email"],
.fp-body input[type="password"],
.fp-body input[type="text"] {
    border:1.5px solid var(--border); border-radius:10px;
    background:var(--purple-ghost); width:100%; padding:10px 14px;
    font-size:14px; font-family:var(--font-body); color:var(--text);
    transition:border-color .15s, box-shadow .15s;

/* ── Typography ───────────────────────────────────────────── */
.fp-title {
    font-family: var(--font-body, system-ui, sans-serif);
    font-size: 24px;
    font-weight: 800;
    color: #26113b;
    margin-bottom: 8px;
    letter-spacing: -0.4px;
}
.fp-body input:focus { border-color:var(--purple-main); background:white; box-shadow:0 0 0 3px rgba(107,47,160,.1); outline:none; }

.fp-subtitle {
    font-size: 14px;
    line-height: 1.55;
    color: #695d77;
    margin: 0 auto 24px;
    max-width: 370px;
}

/* ── Alerts ───────────────────────────────────────────────── */
.fp-alert {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 14px 16px;
    border-radius: 12px;
    margin-bottom: 22px;
    text-align: left;
    font-size: 13.5px;
    line-height: 1.5;
}

.fp-alert-icon {
    flex-shrink: 0;
    margin-top: 2px;
}

.fp-alert-error {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #991b1b;
}
.fp-alert-error .fp-alert-icon {
    color: #dc2626;
}
.fp-alert-error p {
    margin: 0;
}
.fp-alert-error p + p {
    margin-top: 4px;
}

/* ── Form Controls ────────────────────────────────────────── */
.fp-form {
    text-align: left;
}

.fp-group {
    margin-bottom: 18px;
}

.fp-label {
    display: block;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    color: #4a2d70;
    margin-bottom: 8px;
}

.fp-input-wrap {
    position: relative;
    display: flex;
    align-items: center;
}

.fp-input-icon {
    position: absolute;
    left: 14px;
    color: #8b73a3;
    pointer-events: none;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: color 0.15s ease;
}

.fp-input {
    width: 100%;
    padding: 12px 16px 12px 42px;
    font-size: 14.5px;
    font-family: inherit;
    color: #1f142b;
    background: #faf7fd;
    border: 1.5px solid #dfd3ec;
    border-radius: 12px;
    transition: all 0.2s ease;
}

.fp-input-has-toggle {
    padding-right: 46px;
}

.fp-input:focus {
    background: #ffffff;
    border-color: #6b2fa0;
    box-shadow: 0 0 0 4px rgba(107, 47, 160, 0.12);
    outline: none;
}

.fp-input:focus + .fp-input-icon,
.fp-input-wrap:focus-within .fp-input-icon {
    color: #6b2fa0;
}

.fp-input.is-invalid {
    border-color: #ef4444;
    background: #fffbfa;
}

.fp-input.is-invalid:focus {
    box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.15);
}

.fp-toggle-pw {
    position: absolute;
    right: 12px;
    background: none;
    border: none;
    cursor: pointer;
    color: #8b73a3;
    padding: 6px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: color 0.15s ease, background-color 0.15s ease;
}

.fp-toggle-pw:hover {
    color: #6b2fa0;
    background-color: #f1e9f8;
}

/* ── Password Requirements Checklist ─────────────────────── */
.fp-req-box {
    background: #faf7fd;
    border: 1px solid #ebdcfc;
    border-radius: 12px;
    padding: 14px 16px;
    margin-bottom: 22px;
}

.fp-req-title {
    font-size: 12px;
    font-weight: 700;
    color: #52297a;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}

.fp-req-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.fp-req-item {
    font-size: 12.5px;
    color: #6d5b80;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: color 0.15s ease;
}

.fp-req-icon {
    font-size: 12px;
    line-height: 1;
    font-weight: bold;
    width: 14px;
    text-align: center;
    color: #a491b8;
    transition: all 0.15s ease;
}

.fp-req-item.is-met {
    color: #166534;
    font-weight: 600;
}
.fp-req-item.is-met .fp-req-icon {
    color: #16a34a;
}

/* ── Purple Action Button ─────────────────────────────────── */
.fp-btn {
    display:flex; align-items:center; justify-content:center; gap:8px;
    width:100%; padding:13px 24px; border-radius:50px; border:none;
    background:#3b0f7a; color:white; font-size:15px; font-weight:700;
    font-family:var(--font-body); cursor:pointer; margin-top:8px;
    box-shadow:0 4px 20px rgba(59,15,122,.35);
    transition:all .2s; letter-spacing:.2px;
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 13px 24px;
    border: none;
    border-radius: 12px;
    background: linear-gradient(135deg, #6b2fa0 0%, #52297a 100%);
    color: #ffffff;
    font-size: 15px;
    font-weight: 700;
    font-family: inherit;
    letter-spacing: 0.2px;
    cursor: pointer;
    box-shadow: 0 8px 24px -4px rgba(107, 47, 160, 0.42);
    transition: all 0.2s ease;
    margin-top: 6px;
}
.fp-btn:hover { background:#2d0a5e; transform:translateY(-1px); box-shadow:0 8px 28px rgba(59,15,122,.45); }

.fp-btn:hover {
    background: linear-gradient(135deg, #5b21b6 0%, #431968 100%);
    box-shadow: 0 12px 28px -4px rgba(107, 47, 160, 0.55);
    transform: translateY(-1.5px);
}

.fp-btn:active {
    transform: translateY(0);
    box-shadow: 0 4px 14px -2px rgba(107, 47, 160, 0.4);
}

/* ── Back to Login Footer ─────────────────────────────────── */
.fp-footer {
    margin-top: 26px;
    padding-top: 20px;
    border-top: 1px solid #f1e9f8;
    text-align: center;
}

.fp-back-link {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    color: #6b2fa0;
    font-size: 14px;
    font-weight: 700;
    text-decoration: none;
    transition: all 0.15s ease;
    padding: 4px 8px;
    border-radius: 6px;
}

.fp-back-link:hover {
    color: #4a1f73;
    background: #f7f1fc;
    transform: translateX(-2px);
}

/* ── Mobile Responsiveness ────────────────────────────────── */
@media (max-width: 480px) {
    .fp-page {
        padding: 24px 16px;
    }
    .fp-card-inner {
        padding: 32px 22px 28px;
    }
    .fp-title {
        font-size: 21px;
    }
    .fp-subtitle {
        font-size: 13.5px;
        margin-bottom: 20px;
    }
    .fp-btn {
        padding: 12px 20px;
        font-size: 14.5px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const pw = document.getElementById('password');
    const pwConf = document.getElementById('password_confirmation');

    const reqLen = document.getElementById('req-len');
    const reqNum = document.getElementById('req-num');
    const reqSym = document.getElementById('req-sym');
    const reqMatch = document.getElementById('req-match');

    function updateRequirement(el, isMet) {
        if (!el) return;
        const icon = el.querySelector('.fp-req-icon');
        if (isMet) {
            el.classList.add('is-met');
            if (icon) icon.textContent = '✓';
        } else {
            el.classList.remove('is-met');
            if (icon) icon.textContent = '○';
        }
    }

    function checkPasswordRequirements() {
        const val = pw ? pw.value : '';
        const confVal = pwConf ? pwConf.value : '';

        // Length >= 8
        updateRequirement(reqLen, val.length >= 8);

        // Has number
        updateRequirement(reqNum, /\d/.test(val));

        // Has symbol (anything non-alphanumeric)
        updateRequirement(reqSym, /[^A-Za-z0-9]/.test(val));

        // Passwords match
        const matches = val.length > 0 && confVal.length > 0 && val === confVal;
        updateRequirement(reqMatch, matches);
    }

    if (pw) pw.addEventListener('input', checkPasswordRequirements);
    if (pwConf) pwConf.addEventListener('input', checkPasswordRequirements);

    checkPasswordRequirements();
});
</script>
@endsection
