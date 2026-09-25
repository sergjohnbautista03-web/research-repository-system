<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - Ube Repository</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}">
    @stack('styles')
</head>
<body class="admin-body user-dashboard-body">
@php
    $dashboardUser = auth()->user();
    $isFacultyAccount = $dashboardUser->role === 'researcher' && is_null($dashboardUser->graduation_year);
    $isStudentResearcher = $dashboardUser->role === 'researcher' && ! is_null($dashboardUser->graduation_year);
    $dashboardRoleLabel = $isFacultyAccount
        ? 'Faculty'
        : ($isStudentResearcher ? 'Student Researcher' : 'Student');
    $savedResearchCount = isset($pinnedResearches)
        ? $pinnedResearches->count()
        : $dashboardUser->pinnedResearches()->count();
@endphp

<div class="admin-mobile-bar">
    <button type="button" class="admin-mobile-toggle" aria-label="Open dashboard navigation" onclick="toggleUserDashboardSidebar()">
        <span></span><span></span><span></span>
    </button>
    <div class="admin-mobile-brand">
        <strong>PhilCSTian</strong>
        <small>@yield('page-title', 'Dashboard')</small>
    </div>
</div>

<div class="admin-sidebar-backdrop" onclick="toggleUserDashboardSidebar(false)"></div>
<aside class="admin-sidebar">
    <div class="sidebar-brand">
        <span class="sidebar-logo">
            <img src="{{ asset('images/philcstlogologo.png') }}" alt="PhilCST logo">
        </span>
        <div>
            <span class="sidebar-title">PhilCSTian</span>
        </div>
    </div>

    <button type="button" class="sidelink active" data-dashboard-nav="overview">
        <span class="sidelink-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg></span>
        Dashboard
    </button>

    <button type="button" class="sidelink" data-dashboard-nav="saved">
        <span class="sidelink-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"/></svg></span>
        Saved Research
    </button>

    <div class="sidebar-user sidebar-account" data-sidebar-account>
        @if($dashboardUser->profile_photo)
            <img src="{{ asset('storage/' . $dashboardUser->profile_photo) }}" alt="{{ $dashboardUser->name }}" class="user-avatar-sm" style="object-fit:cover;">
        @else
            <span class="user-avatar-sm">{{ strtoupper(substr($dashboardUser->name, 0, 1)) }}</span>
        @endif
        <div style="flex:1; min-width:0;">
            <strong>{{ Str::limit($dashboardUser->name, 18) }}</strong>
            <small>{{ $dashboardRoleLabel }}</small>
        </div>
        <button type="button" class="sidebar-account-toggle" aria-haspopup="menu" aria-expanded="false" aria-label="Open account menu">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="m18 15-6-6-6 6"/></svg>
        </button>
        <div class="sidebar-account-menu" role="menu">
            <button type="button" class="sidebar-account-item" role="menuitem" onclick="toggleUserProfileModal(true)">
                <span class="sidebar-account-item-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/></svg></span>
                My Profile
            </button>
            <a href="{{ route('home') }}" class="sidebar-account-item" role="menuitem">
                <span class="sidebar-account-item-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg></span>
                Browse
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="sidebar-account-item is-danger" role="menuitem">
                    <span class="sidebar-account-item-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/></svg></span>
                    Sign Out
                </button>
            </form>
        </div>
    </div>
</aside>

<main class="admin-main">

    @if(session('success'))
        <div class="flash flash-success">
            <span>OK</span> {{ session('success') }}
            <button type="button" onclick="this.parentElement.remove()">x</button>
        </div>
    @endif
    @if(session('error'))
        <div class="flash flash-error">
            <span>!</span> {{ session('error') }}
            <button type="button" onclick="this.parentElement.remove()">x</button>
        </div>
    @endif

    @yield('content')
</main>

{{-- Student Profile Modal (In-page popup) --}}
<div class="ud-modal" id="userProfileModal" aria-hidden="true">
    <div class="ud-modal-backdrop" onclick="toggleUserProfileModal(false)"></div>
    <div class="ud-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="userProfileTitle">
        <div class="ud-modal-head">
            <div>
                <span class="ra-eyebrow">Student Account</span>
                <h2 id="userProfileTitle">My Profile</h2>
                <p>View your student profile details and manage your account photo</p>
            </div>
            <button type="button" class="ud-modal-close" onclick="toggleUserProfileModal(false)" aria-label="Close profile modal">&times;</button>
        </div>
        <div class="ud-profile-modal-body">
            <div class="ud-profile-photo-card">
                <div class="ud-profile-avatar-wrap">
                    @if($dashboardUser->profile_photo)
                        <img src="{{ asset('storage/' . $dashboardUser->profile_photo) }}" alt="{{ $dashboardUser->name }}" class="ud-profile-avatar-img">
                    @else
                        <span class="ud-profile-avatar-initials">{{ strtoupper(substr($dashboardUser->name, 0, 1)) }}</span>
                    @endif
                </div>
                <div class="ud-profile-photo-info">
                    <strong>{{ $dashboardUser->name }}</strong>
                    <span>{{ $dashboardRoleLabel }} &bull; {{ $dashboardUser->is_active ? 'Active Account' : 'Inactive Account' }}</span>
                    
                    <form method="POST" action="{{ route('profile.photo') }}" enctype="multipart/form-data" class="ud-photo-form" id="studentModalPhotoForm">
                        @csrf
                        @method('PATCH')
                        <div class="ud-photo-actions">
                            <label class="ud-file-picker-btn">
                                <input type="file" name="profile_photo" accept="image/jpeg,image/png,image/jpg,image/gif" onchange="document.getElementById('studentModalPhotoForm').submit()" style="display:none;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17,8 12,3 7,8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                Change Photo
                            </label>
                            @if($dashboardUser->profile_photo)
                                <a href="{{ route('profile.photo.remove') }}" onclick="return confirm('Remove profile photo?')" class="ud-photo-remove-btn">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3,6 5,6 21,6"/><path d="M19,6v14a2,2,0,0,1-2,2H7a2,2,0,0,1-2-2V6m3,0V4a2,2,0,0,1,2-2h4a2,2,0,0,1,2,2v2"/></svg>
                                    Remove
                                </a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

            <div class="ud-profile-grid">
                <div class="ud-profile-item">
                    <span>Full Name</span>
                    <strong>{{ $dashboardUser->name }}</strong>
                </div>
                <div class="ud-profile-item">
                    <span>Email Address</span>
                    <strong>{{ $dashboardUser->email }}</strong>
                </div>
                <div class="ud-profile-item">
                    <span>Student / Employee ID</span>
                    <strong>{{ $dashboardUser->student_id ?: 'N/A' }}</strong>
                </div>
                <div class="ud-profile-item">
                    <span>Department</span>
                    <strong>{{ $dashboardUser->department ?: 'N/A' }}</strong>
                </div>
                <div class="ud-profile-item">
                    <span>Year Level / Role</span>
                    <strong>{{ $dashboardUser->year_level_label ?: $dashboardRoleLabel }}</strong>
                </div>
                <div class="ud-profile-item">
                    <span>Account Status</span>
                    <strong class="ud-status-badge {{ $dashboardUser->is_active ? 'is-active' : 'is-inactive' }}">
                        {{ $dashboardUser->is_active ? 'Active' : 'Inactive' }}
                    </strong>
                </div>
            </div>
        </div>
        <div class="ud-modal-footer">
            <button type="button" class="ra-link-btn" onclick="toggleUserProfileModal(false)">Close</button>
        </div>
    </div>
</div>

<script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>
<script>
    function toggleUserDashboardSidebar(forceState) {
        const body = document.body;
        const shouldOpen = typeof forceState === 'boolean'
            ? forceState
            : !body.classList.contains('admin-sidebar-open');

        body.classList.toggle('admin-sidebar-open', shouldOpen);
    }

    function toggleUserProfileModal(shouldOpen) {
        const modal = document.getElementById('userProfileModal');
        if (!modal) return;
        modal.classList.toggle('is-visible', shouldOpen);
        modal.setAttribute('aria-hidden', shouldOpen ? 'false' : 'true');
        document.body.style.overflow = shouldOpen ? 'hidden' : '';
        document.querySelectorAll('[data-sidebar-account]').forEach(el => el.classList.remove('is-open'));
    }

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const profileModal = document.getElementById('userProfileModal');
            if (profileModal && profileModal.classList.contains('is-visible')) {
                toggleUserProfileModal(false);
            }
        }
    });

    setTimeout(function() {
        const flash = document.querySelector('.flash-success, .flash-error');
        if (flash) {
            flash.style.transition = 'opacity 0.5s';
            flash.style.opacity = '0';
            setTimeout(() => flash.remove(), 500);
        }
    }, 3000);

    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            document.body.classList.remove('admin-sidebar-open');
        }
    });
</script>
@stack('scripts')
</body>
</html>
