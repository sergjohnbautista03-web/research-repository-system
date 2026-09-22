@extends('layouts.app')

@section('title', 'Forgot Password')

@push('styles')
<style>
.fp-page {
    position: relative;
    min-height: calc(100vh - 64px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 56px 20px;
    overflow: hidden;
    background: #1b0a2c;
}

.fp-page::before {
    content: "";
    position: absolute;
    inset: 0;
    background:
        linear-gradient(135deg, rgba(30, 7, 52, .9), rgba(82, 41, 122, .74)),
        url("{{ asset('images/philcstarea.jpg') }}") center / cover no-repeat;
    filter: saturate(.9);
    transform: scale(1.02);
}

.fp-page::after {
    content: "";
    position: absolute;
    inset: 0;
    background:
        radial-gradient(circle at 22% 20%, rgba(255, 255, 255, .14), transparent 30%),
        radial-gradient(circle at 78% 82%, rgba(15, 122, 115, .2), transparent 34%);
    pointer-events: none;
}

.fp-card {
    position: relative;
    z-index: 1;
    width: min(100%, 440px);
    padding: 36px;
    border: 1px solid rgba(228, 214, 242, .86);
    border-radius: 8px;
    background: rgba(255, 255, 255, .97);
    box-shadow: 0 26px 70px rgba(20, 8, 36, .38);
}

.fp-header {
    display: grid;
    justify-items: center;
    text-align: center;
    margin-bottom: 28px;
}

.fp-icon {
    width: 54px;
    height: 54px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 18px;
    border-radius: 8px;
    color: #fff;
    background: linear-gradient(135deg, var(--purple-main), var(--teal));
    box-shadow: 0 14px 28px rgba(107, 47, 160, .24);
}

.fp-header h1 {
    margin: 0 0 8px;
    color: var(--purple-deep);
    font-family: var(--font-head);
    font-size: clamp(30px, 7vw, 38px);
    line-height: 1.05;
    letter-spacing: 0;
}

.fp-header p {
    max-width: 320px;
    margin: 0;
    color: #6f5c82;
    font-size: 14px;
    line-height: 1.6;
}

.fp-alert {
    display: flex;
    gap: 12px;
    margin-bottom: 20px;
    padding: 13px 14px;
    border-radius: 8px;
    font-size: 13px;
    line-height: 1.5;
}

.fp-alert svg {
    flex: 0 0 auto;
    margin-top: 2px;
}

.fp-alert p {
    margin: 0;
}

.fp-alert-success {
    border: 1px solid #bbf7d0;
    background: #f0fdf4;
    color: #166534;
}

.fp-alert-error {
    border: 1px solid #fecaca;
    background: #fff1f2;
    color: #991b1b;
}

.fp-form {
    display: grid;
    gap: 18px;
}

.fp-field {
    display: grid;
    gap: 8px;
}

.fp-field label {
    color: var(--purple-deep);
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .7px;
    text-transform: uppercase;
}

.fp-input-wrap {
    position: relative;
}

.fp-input-icon {
    position: absolute;
    top: 50%;
    left: 14px;
    display: inline-flex;
    color: #806b90;
    transform: translateY(-50%);
    pointer-events: none;
}

.fp-input-wrap input {
    min-height: 46px;
    padding-left: 44px;
    border: 2px solid #ddc8ef;
    border-radius: 8px;
    background: #fff;
}

.fp-input-wrap input:focus {
    border-color: var(--purple-main);
    box-shadow: 0 0 0 4px rgba(107, 47, 160, .12);
}

.fp-field-error {
    color: #991b1b;
    font-size: 12px;
    font-weight: 700;
}

.fp-submit {
    min-height: 48px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    width: 100%;
    border: 0;
    border-radius: 8px;
    background: var(--purple-main);
    color: #fff;
    font-family: var(--font-body);
    font-size: 14px;
    font-weight: 800;
    cursor: pointer;
    box-shadow: 0 14px 28px rgba(107, 47, 160, .22);
    transition: background .18s, transform .18s, box-shadow .18s;
}

.fp-submit:hover {
    background: var(--purple-deep);
    box-shadow: 0 18px 32px rgba(82, 41, 122, .26);
    transform: translateY(-1px);
}

.fp-card-footer {
    display: flex;
    justify-content: center;
    margin-top: 24px;
    padding-top: 20px;
    border-top: 1px solid #eee4f7;
}

.fp-back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--purple-main);
    font-size: 14px;
    font-weight: 800;
    text-decoration: none;
}

.fp-back-link:hover {
    color: var(--purple-deep);
    text-decoration: underline;
}

body:has(.fp-page) .site-footer {
    margin-top: 0;
}

@media (max-width: 520px) {
    .fp-page {
        align-items: flex-start;
        padding: 32px 14px;
    }

    .fp-card {
        padding: 28px 20px;
    }
}
</style>
@endpush

@section('content')
<main class="fp-page">
    <section class="fp-card" aria-labelledby="forgot-password-title">
        <header class="fp-header">
            <span class="fp-icon" aria-hidden="true">
                <svg width="25" height="25" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="10" rx="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    <path d="M12 16h.01"></path>
                </svg>
            </span>
            <h1 id="forgot-password-title">Forgot Password?</h1>
            <p>Enter your registered email and we will send a secure link to reset your password.</p>
        </header>

        @if(session('status'))
            <div class="fp-alert fp-alert-success" role="status">
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M20 6 9 17l-5-5"></path>
                </svg>
                <p>{{ session('status') }}</p>
            </div>
        @endif

        @if($errors->any())
            <div class="fp-alert fp-alert-error" role="alert">
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="M12 8v4"></path>
                    <path d="M12 16h.01"></path>
                </svg>
                <div>
                    @foreach($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="fp-form" novalidate>
            @csrf

            <div class="fp-field">
                <label for="email">Registered Email Address</label>
                <div class="fp-input-wrap">
                    <span class="fp-input-icon" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"></path>
                            <path d="m22 6-10 7L2 6"></path>
                        </svg>
                    </span>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="yourname@philcst.edu.ph"
                        autocomplete="email"
                        required
                        autofocus
                    >
                </div>
                @error('email')
                    <span class="fp-field-error">{{ $message }}</span>
                @enderror
            </div>

            <button type="submit" class="fp-submit">
                <span>Send Reset Link</span>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M22 2 11 13"></path>
                    <path d="m22 2-7 20-4-9-9-4 20-7Z"></path>
                </svg>
            </button>
        </form>

        <div class="fp-card-footer">
            <a href="{{ route('login') }}" class="fp-back-link">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M19 12H5"></path>
                    <path d="m12 19-7-7 7-7"></path>
                </svg>
                <span>Back to Login</span>
            </a>
        </div>
    </section>
</main>
@endsection
