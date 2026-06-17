@extends('layouts.app')
@section('title', 'My Profile — Ube Repository')

@section('content')
@php
    $profileUser = auth()->user();
    $profileRoleLabel = $profileUser->isDepartmentDean() ? 'Dean' : ucfirst($profileUser->role);
    $profileIdLabel = $profileUser->isDepartmentDean()
        ? 'Dean ID'
        : ($profileUser->isAdmin() ? 'Admin ID' : 'Student / Employee ID');
@endphp

<div class="prof-root">

    {{-- ══ HERO ══ --}}
    <div class="prof-hero">
        <div class="prof-hero-grid"></div>
        <div class="prof-glow prof-g1"></div>
        <div class="prof-glow prof-g2"></div>
        <div class="prof-hero-inner">
            <div class="prof-eyebrow"><span class="prof-eyebrow-dot"></span> Account</div>
            <h1>My Profile</h1>
            <p>Manage your personal information and account settings</p>
        </div>
    </div>

    {{-- ══ BODY ══ --}}
    <div class="prof-body">

        @if($errors->any())
            <div class="alert alert-error" style="margin-bottom:20px; border-radius:12px;">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <div class="prof-top-grid">
        {{-- Profile Photo --}}
        <div class="prof-card prof-photo-card">
            <div class="prof-card-label">Profile Photo</div>
            <div class="prof-photo-row">
                <div class="prof-avatar-wrap">
                    @if(auth()->user()->profile_photo)
                        <img src="{{ asset('storage/' . auth()->user()->profile_photo) }}" alt="Profile Photo" class="prof-avatar-img">
                    @else
                        <div class="prof-avatar-initials">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                    @endif
                    <div class="prof-avatar-ring"></div>
                </div>

                <form method="POST" action="{{ route('profile.photo') }}" enctype="multipart/form-data" class="prof-photo-form">
                    @csrf
                    @method('PATCH')
                    <div class="prof-photo-info">
                        <strong>{{ auth()->user()->name }}</strong>
                        <span>{{ $profileRoleLabel }}</span>
                    </div>
                    <label class="prof-field-label">Upload New Photo</label>
                    <label class="prof-file-picker">
                        <input type="file" name="profile_photo" accept="image/jpeg,image/png,image/jpg,image/gif" class="prof-file-input" data-prof-file-input>
                        <span class="prof-file-action">Choose Image</span>
                        <span class="prof-file-name" data-prof-file-name>No file chosen</span>
                    </label>
                    <small class="prof-hint">JPG, PNG, GIF — max 2MB</small>
                    <div class="prof-photo-actions">
                        <button type="submit" class="prof-btn prof-btn--primary">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17,8 12,3 7,8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                            Upload Photo
                        </button>
                        @if(auth()->user()->profile_photo)
                            <a href="{{ route('profile.photo.remove') }}" onclick="return confirm('Remove profile photo?')" class="prof-btn prof-btn--ghost">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3,6 5,6 21,6"/><path d="M19,6v14a2,2,0,0,1-2,2H7a2,2,0,0,1-2-2V6m3,0V4a2,2,0,0,1,2-2h4a2,2,0,0,1,2,2v2"/></svg>
                                Remove
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        {{-- Personal Information --}}
        <div class="prof-card prof-details-card">
            <div class="prof-card-label">Personal Information</div>
            <h2 class="prof-card-title">Account Details</h2>

            <form method="POST" action="{{ route('profile.update') }}">
                @csrf
                @method('PATCH')

                <div class="prof-form-grid">
                    <div class="prof-field">
                        <label class="prof-field-label">Full Name</label>
                        <input type="text" name="name" value="{{ old('name', auth()->user()->name) }}" required class="prof-input">
                    </div>
                    <div class="prof-field">
                        <label class="prof-field-label">Email Address</label>
                        <input type="email" value="{{ auth()->user()->email }}" disabled class="prof-input prof-input--disabled">
                        <small class="prof-hint">Email cannot be changed.</small>
                        </div>
                        <div class="prof-field">
                            <label class="prof-field-label">Department</label>
                            <input type="text" value="{{ auth()->user()->department }}" disabled class="prof-input prof-input--disabled">
                            <small class="prof-hint">Department cannot be changed.</small>
                        </div>
                    @if(auth()->user()->student_id)
                    <div class="prof-field">
                        <label class="prof-field-label">{{ $profileIdLabel }}</label>
                        <input type="text" value="{{ auth()->user()->student_id }}" disabled class="prof-input prof-input--disabled">
                    </div>
                    @endif
                </div>

                <div class="prof-form-actions">
                    <button type="submit" class="prof-btn prof-btn--primary">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17,21 17,13 7,13 7,21"/><polyline points="7,3 7,8 15,8"/></svg>
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
        </div>

    </div>
</div>

<style>
.prof-root { font-family: var(--font-body); background: #eeeaf6; min-height: 100vh; }

/* HERO */
.prof-hero {
    position: relative; background: #3b0f7a;
    overflow: hidden; padding: 44px 44px 48px; text-align: center;
}
.prof-hero-grid {
    position: absolute; inset: 0; pointer-events: none;
    background-image: linear-gradient(rgba(255,255,255,.04) 1px,transparent 1px), linear-gradient(90deg,rgba(255,255,255,.04) 1px,transparent 1px);
    background-size: 38px 38px;
}
.prof-glow { position: absolute; border-radius: 50%; filter: blur(80px); pointer-events: none; }
.prof-g1 { width: 500px; height: 500px; background: #7c3aed; opacity: .22; top: -200px; right: -100px; }
.prof-g2 { width: 280px; height: 280px; background: #a855f7; opacity: .15; bottom: -80px; left: 18%; }
.prof-hero-inner { position: relative; z-index: 2; }
.prof-eyebrow {
    display: inline-flex; align-items: center; gap: 7px;
    font-size: 11px; font-weight: 600;
    color: rgba(255,255,255,.5); text-transform: uppercase; letter-spacing: 1.2px;
    margin-bottom: 12px;
}
.prof-eyebrow-dot { width: 7px; height: 7px; border-radius: 50%; background: #c084fc; }
.prof-hero h1 {
    font-family: var(--font-body); font-size: 38px; font-weight: 700;
    color: white; margin-bottom: 8px; letter-spacing: -.5px;
}
.prof-hero p { font-size: 14px; color: rgba(255,255,255,.5); }

/* BODY */
.prof-body { max-width: 1320px; margin: 0 auto; padding: 32px 40px 64px; display: flex; flex-direction: column; gap: 24px; }
.prof-top-grid {
    display: grid;
    grid-template-columns: minmax(360px, .72fr) minmax(0, 1.28fr);
    gap: 24px;
    align-items: stretch;
}

/* CARD */
.prof-card {
    background: linear-gradient(180deg, #fff, #fff 72%, #fdfbff);
    border-radius: 18px;
    border: 1px solid rgba(109,40,217,.08);
    padding: 34px 38px;
    box-shadow: 0 10px 30px rgba(46,16,101,.08);
    position: relative; overflow: hidden;
}
.prof-card::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
    background: linear-gradient(90deg, #7c3aed, #a855f7, #c084fc);
}
.prof-card-label {
    display: inline-flex; align-items: center;
    font-size: 10.5px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 1px;
    color: var(--purple-main); background: var(--purple-ghost);
    border: 1px solid var(--purple-pale);
    padding: 5px 13px; border-radius: 50px; margin-bottom: 18px;
    align-self: flex-start;
}
.prof-card-title {
    font-family: var(--font-head); font-size: 22px;
    color: var(--purple-deep); margin-bottom: 28px;
}

/* PHOTO SECTION */
.prof-photo-card { display: flex; flex-direction: column; align-items: flex-start; }
.prof-photo-row { display: flex; align-items: flex-start; gap: 28px; flex-wrap: wrap; }
.prof-photo-card .prof-photo-row {
    flex: 1;
    width: 100%;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 22px;
    text-align: center;
}
.prof-avatar-wrap {
    position: relative; width: 124px; height: 124px; flex-shrink: 0;
}
.prof-avatar-img {
    width: 124px; height: 124px; border-radius: 50%;
    object-fit: cover; position: relative; z-index: 1;
    border: 4px solid white;
    box-shadow: 0 12px 26px rgba(107,47,160,.22);
}
.prof-avatar-initials {
    width: 124px; height: 124px; border-radius: 50%;
    background: linear-gradient(135deg, #7c3aed, #a855f7);
    display: flex; align-items: center; justify-content: center;
    font-size: 48px; font-weight: 800; color: white;
    border: 4px solid white;
    box-shadow: 0 12px 26px rgba(107,47,160,.25);
    position: relative; z-index: 1;
}
.prof-avatar-ring {
    position: absolute; inset: -7px; border-radius: 50%;
    border: 2px dashed #c4b5fd; animation: prof-spin 12s linear infinite;
}
@keyframes prof-spin { to { transform: rotate(360deg); } }

.prof-photo-form { flex: 1; width: 100%; max-width: 360px; }
.prof-photo-info { margin-bottom: 20px; }
.prof-photo-info strong { display: block; font-size: 18px; font-weight: 800; color: var(--purple-deep); margin-bottom: 7px; }
.prof-photo-info span { font-size: 12.5px; color: var(--muted); background: var(--purple-ghost); border: 1px solid var(--purple-pale); padding: 2px 10px; border-radius: 50px; }

.prof-file-picker {
    position: relative;
    display: grid;
    grid-template-columns: auto minmax(0, 1fr);
    align-items: center;
    gap: 10px;
    width: 100%;
    padding: 9px;
    border: 1.5px dashed #d8c7f3;
    border-radius: 14px;
    background: #fbf8ff;
    cursor: pointer;
    text-align: left;
    transition: border-color .15s, background .15s, box-shadow .15s;
}
.prof-file-picker:hover,
.prof-file-picker:focus-within {
    border-color: var(--purple-main);
    background: #fff;
    box-shadow: 0 0 0 3px rgba(107,47,160,.08);
}
.prof-file-input {
    position: absolute;
    width: 1px;
    height: 1px;
    opacity: 0;
    pointer-events: none;
}
.prof-file-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 34px;
    padding: 0 13px;
    border-radius: 10px;
    background: #fff;
    border: 1px solid var(--purple-pale);
    color: var(--purple-deep);
    font-size: 12.5px;
    font-weight: 800;
}
.prof-file-name {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    color: var(--muted);
    font-size: 12.5px;
}
.prof-hint { font-size: 12px; color: var(--muted); display: block; margin-bottom: 14px; }
.prof-photo-actions { display: flex; justify-content: center; gap: 10px; flex-wrap: wrap; }

/* FORM */
.prof-field-label {
    display: block; font-size: 11.5px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .6px;
    color: var(--purple-deep); margin-bottom: 7px;
}
.prof-form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 22px 24px; margin-bottom: 24px; }
.prof-field { display: flex; flex-direction: column; }
.prof-field--full { grid-column: span 2; }
.prof-input {
    width: 100%; min-height: 52px; padding: 13px 16px;
    border: 1.5px solid var(--border);
    border-radius: 12px; background: #fff;
    font-family: var(--font-body); font-size: 15px; color: var(--text);
    transition: border-color .15s, box-shadow .15s;
}
.prof-input:focus {
    border-color: var(--purple-main); background: white;
    box-shadow: 0 0 0 3px rgba(107,47,160,.1); outline: none;
}
.prof-input--disabled { opacity: .62; cursor: not-allowed; background: #f7f4fb; }
.prof-form-actions { display: flex; }
.prof-access-status {
    border-radius: 14px;
    padding: 14px 16px;
    margin-bottom: 18px;
    border: 1px solid #e8dff5;
    background: #faf8ff;
}
.prof-access-status strong {
    display: block;
    margin-bottom: 4px;
    color: var(--purple-deep);
}
.prof-access-status p {
    margin: 0;
    font-size: 13px;
    line-height: 1.6;
    color: var(--muted);
}
.prof-access-status.is-active {
    background: #ecfdf5;
    border-color: #bbf7d0;
}
.prof-access-status.is-inactive {
    background: #fff7ed;
    border-color: #fed7aa;
}

/* BUTTONS */
.prof-btn {
    display: inline-flex; align-items: center; gap: 7px;
    min-height: 46px;
    padding: 11px 24px; border-radius: 50px;
    font-size: 13.5px; font-weight: 700;
    font-family: var(--font-body);
    cursor: pointer; border: none; transition: all .2s;
    letter-spacing: .2px; text-decoration: none;
}
.prof-btn--primary {
    background: linear-gradient(135deg, #4b168f, #3b0f7a);
    color: white;
    box-shadow: 0 10px 22px rgba(59,15,122,.28);
}
.prof-btn--primary:hover {
    background: #2d0a5e; transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(59,15,122,.4);
}
.prof-btn--ghost {
    background: var(--purple-ghost); color: var(--purple-main);
    border: 1px solid var(--purple-light);
}
.prof-btn--ghost:hover { background: var(--purple-pale); }

/* password-wrap override */
.prof-card .password-wrap input { min-height: 52px; border-radius: 12px; background: #fff; border: 1.5px solid var(--border); }
.prof-card .password-wrap input:focus { border-color: var(--purple-main); background: white; box-shadow: 0 0 0 3px rgba(107,47,160,.1); }

@media(max-width: 768px) {
    .prof-hero  { padding: 32px 20px 36px; }
    .prof-hero h1 { font-size: 28px; }
    .prof-body  { padding: 24px 16px 48px; gap: 16px; }
    .prof-card  { padding: 22px 20px; }
    .prof-card-title { font-size: 20px; margin-bottom: 22px; }
    .prof-form-grid { grid-template-columns: 1fr; }
    .prof-field--full { grid-column: span 1; }
    .prof-photo-row { flex-direction: column; align-items: center; text-align: center; }
    .prof-avatar-wrap,
    .prof-avatar-img,
    .prof-avatar-initials { width: 108px; height: 108px; }
    .prof-avatar-initials { font-size: 40px; }
}

@media(max-width: 980px) {
    .prof-top-grid { grid-template-columns: 1fr; }
}
</style>

@push('scripts')
<script>
document.querySelectorAll('[data-prof-file-input]').forEach(function(input) {
    input.addEventListener('change', function() {
        const picker = input.closest('.prof-file-picker');
        const fileName = picker ? picker.querySelector('[data-prof-file-name]') : null;

        if (!fileName) {
            return;
        }

        fileName.textContent = input.files && input.files.length ? input.files[0].name : 'No file chosen';
    });
});
</script>
@endpush

@endsection
