@extends('layouts.app')
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
            </div>
            <h1>Reset Password</h1>
            <p>Enter your new password below.</p>
        </div>

        <div class="fp-body">
            @if($errors->any())
                <div class="alert alert-error">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('password.update') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" value="{{ old('email', request('email')) }}" required>
                </div>
                <div class="form-group">
                    <label>New Password</label>
                    <div class="password-wrap">
                        <input type="password" name="password" placeholder="Minimum 8 characters" required>
                        <button type="button" class="toggle-pw" onclick="togglePassword('password', this)">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 4.411m0 0L21 21"/></svg>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <div class="password-wrap">
                        <input type="password" name="password_confirmation" placeholder="Repeat new password" required>
                        <button type="button" class="toggle-pw" onclick="togglePassword('password_confirmation', this)">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 4.411m0 0L21 21"/></svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="fp-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    Reset Password
                </button>
            </form>
        </div>
    </div>
</div>

<style>
.fp-page {
    position: relative;
    min-height: calc(100vh - 64px);
    display: flex; align-items: center; justify-content: center;
    padding: 40px 20px; overflow: hidden; background: #1e0540;
}
.fp-page-bg {
    position: absolute; inset: 0;
    background: url('/images/philcstarea.jpg') center/cover no-repeat;
    filter: blur(8px) brightness(0.35); transform: scale(1.04); z-index: 0;
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
    background: #3b0f7a; padding: 32px 36px 26px;
    text-align: center; position: relative; overflow: hidden;
}
.fp-header::before { content:''; position:absolute; width:200px; height:200px; background:rgba(255,255,255,.06); border-radius:50%; top:-80px; right:-60px; }
.fp-header::after  { content:''; position:absolute; width:140px; height:140px; background:rgba(255,255,255,.04); border-radius:50%; bottom:-50px; left:-40px; }
.fp-logo {
    width:52px; height:52px; border-radius:50%;
    background:rgba(255,255,255,.15); border:2px solid rgba(255,255,255,.25);
    display:flex; align-items:center; justify-content:center;
    margin:0 auto 14px; position:relative; z-index:1;
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
}
.fp-body input:focus { border-color:var(--purple-main); background:white; box-shadow:0 0 0 3px rgba(107,47,160,.1); outline:none; }
.fp-btn {
    display:flex; align-items:center; justify-content:center; gap:8px;
    width:100%; padding:13px 24px; border-radius:50px; border:none;
    background:#3b0f7a; color:white; font-size:15px; font-weight:700;
    font-family:var(--font-body); cursor:pointer; margin-top:8px;
    box-shadow:0 4px 20px rgba(59,15,122,.35);
    transition:all .2s; letter-spacing:.2px;
}
.fp-btn:hover { background:#2d0a5e; transform:translateY(-1px); box-shadow:0 8px 28px rgba(59,15,122,.45); }
</style>
@endsection