@extends('layouts.app')
@section('title', 'Forgot Password — Ube Repository')

@section('content')
<div class="fp-page">
    <div class="fp-page-bg"></div>
    <div class="fp-glow fp-g1"></div>
    <div class="fp-glow fp-g2"></div>

    <div class="fp-card">
        <div class="fp-header">
            <div class="fp-logo">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
            </div>
            <h1>Forgot Password</h1>
            <p>Enter your email and we'll send you a password reset link.</p>
        </div>

        <div class="fp-body">
            @if(session('status'))
                <div class="alert alert-success">✅ {{ session('status') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-error">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('password.email') }}">
                @csrf
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="your@email.com" required>
                </div>
                <button type="submit" class="fp-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22,2 15,22 11,13 2,9"/></svg>
                    Send Reset Link
                </button>
            </form>

            <div class="fp-footer">
                <a href="{{ route('login') }}">← Back to Login</a>
            </div>
        </div>
    </div>
</div>

<style>
.fp-page {
    position: relative;
    min-height: calc(100vh - 64px);
    display: flex; align-items: center; justify-content: center;
    padding: 40px 20px;
    overflow: hidden;
    background: #1e0540;
}
.fp-page-bg {
    position: absolute; inset: 0;
    filter: blur(8px) brightness(0.35);
    transform: scale(1.04);
    z-index: 0;
}
.fp-glow { position: absolute; border-radius: 50%; filter: blur(80px); pointer-events: none; z-index: 1; }
.fp-g1 { width: 500px; height: 500px; background: #7c3aed; opacity: .22; top: -200px; right: -100px; }
.fp-g2 { width: 280px; height: 280px; background: #a855f7; opacity: .15; bottom: -80px; left: 10%; }

.fp-card {
    position: relative; z-index: 2;
    width: 100%; max-width: 420px;
    border-radius: 24px; overflow: hidden;
    box-shadow: 0 24px 80px rgba(0,0,0,.5);
}
.fp-header {
    background: #3b0f7a;
    padding: 32px 36px 26px;
    text-align: center;
    position: relative; overflow: hidden;
}
.fp-header::before {
    content: '';
    position: absolute; width: 200px; height: 200px;
    background: rgba(255,255,255,.06);
    border-radius: 50%; top: -80px; right: -60px;
}
.fp-header::after {
    content: '';
    position: absolute; width: 140px; height: 140px;
    background: rgba(255,255,255,.04);
    border-radius: 50%; bottom: -50px; left: -40px;
}
.fp-logo {
    width: 52px; height: 52px; border-radius: 50%;
    background: rgba(255,255,255,.15);
    border: 2px solid rgba(255,255,255,.25);
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 14px;
    position: relative; z-index: 1;
}
.fp-header h1 {
    font-family: var(--font-body);
    font-size: 22px; font-weight: 700;
    color: white; margin-bottom: 5px;
    letter-spacing: -.3px;
    position: relative; z-index: 1;
}
.fp-header p {
    font-size: 13px; color: rgba(255,255,255,.55);
    position: relative; z-index: 1; margin: 0;
}
.fp-body {
    background: white;
    padding: 28px 36px 32px;
}
.fp-body .form-group label {
    font-size: 11.5px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .6px;
    color: var(--purple-deep); display: block; margin-bottom: 7px;
}
.fp-body input[type="email"] {
    border: 1.5px solid var(--border);
    border-radius: 10px;
    background: var(--purple-ghost);
    transition: border-color .15s, box-shadow .15s;
}
.fp-body input:focus {
    border-color: var(--purple-main);
    background: white;
    box-shadow: 0 0 0 3px rgba(107,47,160,.1);
    outline: none;
}
.fp-btn {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    width: 100%; padding: 13px 24px;
    border-radius: 50px; border: none;
    background: #3b0f7a; color: white;
    font-size: 15px; font-weight: 700;
    font-family: var(--font-body);
    cursor: pointer; margin-top: 6px;
    box-shadow: 0 4px 20px rgba(59,15,122,.35);
    transition: all .2s; letter-spacing: .2px;
}
.fp-btn:hover { background: #2d0a5e; transform: translateY(-1px); box-shadow: 0 8px 28px rgba(59,15,122,.45); }
.fp-footer {
    text-align: center; margin-top: 20px;
    padding-top: 18px; border-top: 1px solid var(--border);
}
.fp-footer a { font-size: 13.5px; color: var(--purple-main); font-weight: 600; text-decoration: none; }
.fp-footer a:hover { text-decoration: underline; }
</style>
@endsection