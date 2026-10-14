<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') - Ube Repository Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/notifications.css') }}?v={{ filemtime(public_path('css/notifications.css')) }}">
    @stack('styles')
    <link rel="stylesheet" href="{{ asset('css/report-print.css') }}?v={{ filemtime(public_path('css/report-print.css')) }}">
    <script src="{{ asset('js/report-print.js') }}?v={{ filemtime(public_path('js/report-print.js')) }}" data-stylesheet="{{ asset('css/report-print.css') }}?v={{ filemtime(public_path('css/report-print.css')) }}" data-letterhead="{{ asset('images/report-letterhead.jpeg') }}"></script>
</head>
<body class="admin-body @yield('body-class')">
@php
    $adminUser = auth()->user();
    $isGlobalAdmin = $adminUser->isGlobalAdmin();
    $isDepartmentDean = $adminUser->isDepartmentDean();
    $isResearchCoordinator = $adminUser->isResearchCoordinator();
    $portalTitle = $isResearchCoordinator ? 'Research Coordinator' : ($isDepartmentDean ? 'Ube Dean' : 'Ube Admin');
    $accountRoleLabel = $isResearchCoordinator ? 'Research Coordinator' : ($isDepartmentDean ? 'Department Dean' : 'Administrator');
    $profileNameParts = preg_split('/\s+/', trim($adminUser->name), 2);
    $profileFirstName = old('first_name', $profileNameParts[0] ?? '');
    $profileNameRemainder = $profileNameParts[1] ?? '';
    if ($adminUser->middle_name && str_starts_with($profileNameRemainder, $adminUser->middle_name . ' ')) {
        $profileNameRemainder = trim(substr($profileNameRemainder, strlen($adminUser->middle_name)));
    }
    $profileMiddleName = old('middle_name', $adminUser->middle_name);
    $profileLastName = old('last_name', $profileNameRemainder);

@endphp

<div class="admin-mobile-bar">
    <button type="button" class="admin-mobile-toggle" aria-label="Open admin navigation" onclick="toggleAdminSidebar()">
        <span></span><span></span><span></span>
    </button>
    <div class="admin-mobile-brand">
        <strong>
            {{ $portalTitle }}
        </strong>
        <small>@yield('page-title', 'Dashboard')</small>
    </div>
</div>

<div class="admin-sidebar-backdrop" onclick="toggleAdminSidebar(false)"></div>
<aside class="admin-sidebar">
    <div class="sidebar-brand">
        <span class="sidebar-logo">
            <img src="{{ asset('images/philcstlogologo.png') }}" alt="PhilCST logo">
        </span>
        <div class="sidebar-brand-text">
            <span class="sidebar-title">{{ $portalTitle }}</span>
        </div>
        <a href="{{ route('home') }}" class="sidebar-home-link" aria-label="Go to home page" title="Home">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 10 9-7 9 7"/><path d="M5 9v12h14V9"/><path d="M9 21v-8h6v8"/></svg>
        </a>
    </div>

<a href="{{ route($isResearchCoordinator ? 'admin.coordinator.dashboard' : 'admin.dashboard') }}" class="sidelink {{ request()->routeIs('admin.dashboard') || ($isResearchCoordinator && request()->routeIs('admin.coordinator.dashboard')) ? 'active' : '' }}">
    <span class="sidelink-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg></span>
    Dashboard
</a>
<a href="{{ route('admin.journal-batches') }}" class="sidelink {{ request()->routeIs('admin.journal-batches*') ? 'active' : '' }}">
    <span class="sidelink-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="6" width="18" height="15" rx="2"/><path d="M7 3v6M17 3v6M3 11h18M7 15h4M7 18h8"/></svg></span>
    {{ $isGlobalAdmin ? 'Journal Batches for Publication' : 'Journal Batches' }}
</a>
@if($isResearchCoordinator)
    @php
        $coordinatorPendingHandoffIds = \App\Models\ResearchHandoff::where('department', $adminUser->department)
            ->where('status', \App\Models\ResearchHandoff::STATUS_PENDING)
            ->pluck('id');
        $coordinatorPendingHandoffs = $coordinatorPendingHandoffIds->count();
    @endphp
    <a href="{{ route('admin.coordinator.dean-submissions') }}" class="sidelink {{ request()->routeIs('admin.coordinator.dean-submissions') ? 'active' : '' }}">
        <span class="sidelink-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12.5V19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-6.5"/><path d="M16 6l-4-4-4 4"/><path d="M12 2v13"/><path d="m7 10-5 2.5L12 18l10-5.5L17 10"/></svg></span>
        Assigned Journal Tasks
        @if($coordinatorPendingHandoffs > 0)<span class="badge-count">{{ $coordinatorPendingHandoffs }}</span>@endif
    </a>
    <a href="{{ route('admin.coordinator.reports') }}" class="sidelink {{ request()->routeIs('admin.coordinator.reports') ? 'active' : '' }}">
        <span class="sidelink-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></span>
        Reports
    </a>
@endif

@if(! $isResearchCoordinator && $isDepartmentDean)
<a href="{{ route('admin.research-handoffs') }}" class="sidelink {{ request()->routeIs('admin.research-handoffs*') ? 'active' : '' }}">
    <span class="sidelink-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12.5V19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-6.5"/><path d="M16 6l-4-4-4 4"/><path d="M12 2v13"/><path d="m7 10-5 2.5L12 18l10-5.5L17 10"/></svg></span>
    @if($isResearchCoordinator)
        Research Inbox
    @elseif($isDepartmentDean)
        Journal Preparation Task
    @else
        Handoffs
    @endif
    @if($isResearchCoordinator)
        @php $handoffPending = \App\Models\ResearchHandoff::where('department', $adminUser->department)->where('status', \App\Models\ResearchHandoff::STATUS_PENDING)->count(); @endphp
        @if($handoffPending > 0)<span class="badge-count">{{ $handoffPending }}</span>@endif
    @endif
</a>
@endif

{{-- Researches --}}
@if(! $isResearchCoordinator)
<a href="{{ route('admin.researches') }}" class="sidelink {{ request()->routeIs('admin.researches*') ? 'active' : '' }}">
    <span class="sidelink-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414A1 1 0 0 1 19 9.414V19a2 2 0 0 1-2 2z"/></svg></span>
    Researches
</a>
@endif

{{-- Users --}}
@if(! $isResearchCoordinator)
<a href="{{ route('admin.users') }}" class="sidelink {{ request()->routeIs('admin.users*') ? 'active' : '' }}">
    <span class="sidelink-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></span>
    Users
</a>
@if($isDepartmentDean)
<a href="{{ route('admin.user-activity-logs') }}" class="sidelink {{ request()->routeIs('admin.user-activity-logs') ? 'active' : '' }}">
    <span class="sidelink-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"/><path d="M7 16l4-4 3 3 5-7"/></svg></span>
    Department Logs
</a>
@endif
@endif

{{-- Semesters --}}
@if($isGlobalAdmin)
<a href="{{ route('admin.semesters') }}" class="sidelink {{ request()->routeIs('admin.semesters*') ? 'active' : '' }}">
    <span class="sidelink-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/><path d="M8 14h3"/><path d="M13 14h3"/><path d="M8 18h3"/></svg></span>
    Academic Period
</a>
@endif

<hr class="sidebar-divider">

{{-- Reports --}}
@if(! $isResearchCoordinator)
<a href="{{ route('admin.reports') }}" class="sidelink {{ request()->routeIs('admin.reports') ? 'active' : '' }}">
    <span class="sidelink-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></span>
    Reports
</a>
@endif

@if($isGlobalAdmin)
<a href="{{ route('admin.activity-logs') }}" class="sidelink {{ request()->routeIs('admin.activity-logs') ? 'active' : '' }}">
    <span class="sidelink-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 8v4l3 3"/><circle cx="12" cy="12" r="9"/></svg></span>
    System Logs
</a>
@endif

    <div class="sidebar-user">
    <span class="user-avatar-sm">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
    <div style="flex:1; min-width:0;">
        <strong>{{ ($isDepartmentDean || $isResearchCoordinator) ? $adminUser->name : Str::limit($adminUser->name, 18) }}</strong>
        <small>
            @if($isDepartmentDean || $isResearchCoordinator)
                {{ $adminUser->department }}
            @else
                {{ $accountRoleLabel }}
            @endif
        </small>
    </div>
</div>
</aside>

<main class="admin-main">
    <div class="admin-topbar">
        <h1 class="page-title">@yield('page-title', 'Dashboard')</h1>
        <div class="topbar-right">
            <span class="topbar-date">{{ now('Asia/Manila')->format('F j, Y') }}</span>
            @include('components.notification-bell')
        </div>
    </div>

    @yield('dashboard-backdrop')

    @if($errors->any())
        <div class="flash flash-error" role="alert">{{ $errors->first() }}</div>
    @endif

    @if(session('success'))
        <div class="flash flash-success">
            <span>✓</span> {{ session('success') }}
            <button onclick="this.parentElement.remove()">×</button>
        </div>
    @endif
    @if(session('error'))
        <div class="flash flash-error">
            <span>✕</span> {{ session('error') }}
            <button onclick="this.parentElement.remove()">×</button>
        </div>
    @endif


    @yield('content')
</main>

<div class="ud-modal" id="adminProfileModal" aria-hidden="true">
    <div class="ud-modal-backdrop" onclick="toggleAdminProfileModal(false)"></div>
    <div class="ud-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="adminProfileTitle">
        <div class="ud-modal-head">
            <div><span class="ra-eyebrow">{{ $accountRoleLabel }} Account</span><h2 id="adminProfileTitle">My Profile</h2><p>Manage your profile photo and account security</p></div>
            <button type="button" class="ud-modal-close" onclick="toggleAdminProfileModal(false)" aria-label="Close profile modal">&times;</button>
        </div>
        <div class="ud-profile-modal-body">
            <div class="ud-profile-photo-card">
                <div class="ud-profile-avatar-wrap">
                    @if($adminUser->profile_photo)<img src="{{ asset('storage/' . $adminUser->profile_photo) }}" alt="{{ $adminUser->name }}" class="ud-profile-avatar-img">@else<span class="ud-profile-avatar-initials">{{ strtoupper(substr($adminUser->name, 0, 1)) }}</span>@endif
                </div>
                <div class="ud-profile-photo-info">
                    <strong>{{ $adminUser->name }}</strong><span>{{ $accountRoleLabel }} &bull; {{ $adminUser->is_active ? 'Active Account' : 'Inactive Account' }}</span>
                    <form method="POST" action="{{ route('profile.photo') }}" enctype="multipart/form-data" class="ud-photo-form" id="deanModalPhotoForm">@csrf @method('PATCH')
                        <div class="ud-photo-actions"><label class="ud-file-picker-btn"><input type="file" name="profile_photo" accept="image/jpeg,image/png,image/jpg,image/gif" onchange="document.getElementById('deanModalPhotoForm').submit()" hidden><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17,8 12,3 7,8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>Change Photo</label>@if($adminUser->profile_photo)<a href="{{ route('profile.photo.remove') }}" onclick="return confirm('Remove profile photo?')" class="ud-photo-remove-btn">Remove</a>@endif</div>
                    </form>
                </div>
            </div>
            <form method="POST" action="{{ route('profile.update') }}" class="ud-profile-edit-form">@csrf @method('PATCH')
                <div class="ud-profile-edit-grid">
                    <div class="ud-profile-readonly"><span>First Name</span><strong>{{ $profileFirstName ?: 'N/A' }}</strong></div>
                    <div class="ud-profile-readonly"><span>Middle Name</span><strong>{{ $profileMiddleName ?: 'N/A' }}</strong></div>
                    <div class="ud-profile-readonly"><span>Last Name</span><strong>{{ $profileLastName ?: 'N/A' }}</strong></div>
                    <label><span>Email Address</span><input type="email" name="email" value="{{ old('email', $adminUser->email) }}" required></label>
                    <div class="ud-profile-readonly"><span>{{ $isDepartmentDean ? 'Dean ID' : ($isResearchCoordinator ? 'Coordinator ID' : 'Administrator ID') }}</span><strong>{{ $adminUser->student_id ?: 'N/A' }}</strong></div>
                </div>
                @error('email')<p class="ud-profile-form-error">{{ $message }}</p>@enderror
                <div class="ud-profile-save-row"><button type="submit">Save Changes</button></div>
            </form>
        </div>
        @include('profile.partials.modal-password')
        <div class="ud-modal-footer"><button type="button" class="ra-link-btn" onclick="toggleAdminProfileModal(false)">Close</button></div>
    </div>
</div>

@include('admin.partials.notifications')
<script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>
<script>
    function toggleAdminProfileModal(shouldOpen) {
        const modal = document.getElementById('adminProfileModal');
        if (!modal) return;
        modal.classList.toggle('is-visible', shouldOpen);
        modal.setAttribute('aria-hidden', shouldOpen ? 'false' : 'true');
        document.body.classList.toggle('admin-profile-open', shouldOpen);
        if (shouldOpen) {
            document.querySelector('[data-sidebar-account]')?.classList.remove('is-open');
            window.setTimeout(() => modal.querySelector('.ud-modal-close')?.focus(), 0);
        }
    }

    @php
        $reopenAdminProfile = (bool) (session('profile_updated') || session('password_change_pending') || session('password_code_sent') || session('info') === 'Password change request cancelled.' || session('success') === 'Your password has been changed successfully!' || $errors->hasAny(['email', 'current_password', 'password', 'password_confirmation', 'verification_code']));
    @endphp
    if (window.location.hash === '#profile' || @json($reopenAdminProfile)) {
        toggleAdminProfileModal(true);
    }

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && document.getElementById('adminProfileModal')?.classList.contains('is-visible')) {
            toggleAdminProfileModal(false);
        }
    });

    function toggleAdminSidebar(forceState) {
        const body = document.body;
        const shouldOpen = typeof forceState === 'boolean'
            ? forceState
            : !body.classList.contains('admin-sidebar-open');

        body.classList.toggle('admin-sidebar-open', shouldOpen);
    }

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
@include('components.live-search')
</body>
</html>
