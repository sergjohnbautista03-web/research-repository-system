<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — Ube Repository Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    @stack('styles')
</head>
<body class="admin-body">
@php
    $adminUser = auth()->user();
    $showCaptureLogs = ! $adminUser->isDepartmentDean();
    $captureLogAlertCount = 0;
    $latestCaptureLogId = 0;

    if ($showCaptureLogs) {
        $captureLogNotificationQuery = \App\Models\CaptureAttemptLog::query()
            ->whereIn('event_type', \App\Models\CaptureAttemptLog::securityEventTypes());

        $captureLogAlertCount = (clone $captureLogNotificationQuery)
            ->whereDoesntHave('reads', fn ($readQuery) => $readQuery->where('user_id', $adminUser->id))
            ->count();
        $latestCaptureLogId = (clone $captureLogNotificationQuery)
            ->latest('id')
            ->value('id') ?? 0;
    }
@endphp

<div class="admin-mobile-bar">
    <button type="button" class="admin-mobile-toggle" aria-label="Open admin navigation" onclick="toggleAdminSidebar()">
        <span></span><span></span><span></span>
    </button>
    <div class="admin-mobile-brand">
        <strong>
            {{ $adminUser->isDepartmentDean() ? 'Ube Dean' : 'Ube Admin' }}
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
        <div>
            <span class="sidebar-title">{{ $adminUser->isDepartmentDean() ? 'Ube Dean' : 'Ube Admin' }}</span>
            <span class="sidebar-sub">Research Portal</span>
        </div>
    </div>

    {{-- Dashboard --}}
<a href="{{ route('admin.dashboard') }}" class="sidelink {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
    <span class="sidelink-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg></span>
    Dashboard
</a>

{{-- Researches --}}
<a href="{{ route('admin.researches') }}" class="sidelink {{ request()->routeIs('admin.researches*') ? 'active' : '' }}">
    <span class="sidelink-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414A1 1 0 0 1 19 9.414V19a2 2 0 0 1-2 2z"/></svg></span>
    Researches
    @if(! $adminUser->isDepartmentDean())
        @php $pending = \App\Models\Research::pending()->count(); @endphp
        @if($pending > 0)<span class="badge-count">{{ $pending }}</span>@endif
    @endif
</a>

{{-- Users --}}
<a href="{{ route('admin.users') }}" class="sidelink {{ request()->routeIs('admin.users*') ? 'active' : '' }}">
    <span class="sidelink-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></span>
    Users
</a>

{{-- Graduated Users --}}
<a href="{{ route('admin.graduated-researchers') }}" class="sidelink {{ request()->routeIs('admin.graduated-researchers') ? 'active' : '' }}">
    <span class="sidelink-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M22 10v6M2 10l10-5 10 5-10 5-10-5z"/>
            <path d="M6 12v5c0 2 2 3 6 3s6-1 6-3v-5"/>
        </svg>
    </span>
    Graduated Users
</a>

@if($adminUser->canManageDepartmentKeys())
<a href="{{ route('admin.create-admin') }}" class="sidelink {{ request()->routeIs('admin.create-admin') ? 'active' : '' }}">
    <span class="sidelink-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="10" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="17" y1="11" x2="23" y2="11"/></svg></span>
    Create Dean
</a>
@endif

<hr class="sidebar-divider">

{{-- View Site --}}
<a href="{{ route('home') }}" class="sidelink">
    <span class="sidelink-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9,22 9,12 15,12 15,22"/></svg></span>
    View Site
</a>

{{-- Reports --}}
<a href="{{ route('admin.reports') }}" class="sidelink {{ request()->routeIs('admin.reports') ? 'active' : '' }}">
    <span class="sidelink-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></span>
    Reports
</a>

@if($showCaptureLogs)
<a href="{{ route('admin.capture-attempt-logs') }}" class="sidelink {{ request()->routeIs('admin.capture-attempt-logs') ? 'active' : '' }}">
    <span class="sidelink-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 8v4l3 3"/><circle cx="12" cy="12" r="9"/></svg></span>
    Capture Logs
    <span id="captureLogBadge" class="badge-count capture-log-badge {{ $captureLogAlertCount > 0 ? '' : 'is-hidden' }}" data-latest-id="{{ $latestCaptureLogId }}">
        {{ $captureLogAlertCount > 99 ? '99+' : $captureLogAlertCount }}
    </span>
</a>
@endif

    <div class="sidebar-user">
    <span class="user-avatar-sm">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
    <div style="flex:1; min-width:0;">
        <strong>{{ Str::limit(auth()->user()->name, 18) }}</strong>
        <small>
            {{ $adminUser->isDepartmentDean() ? 'Department Dean' : 'Administrator' }}
            @if($adminUser->isDepartmentDean() && $adminUser->department)
                • {{ $adminUser->department }}
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
            <form method="POST" action="{{ route('logout') }}" class="topbar-logout-form">
                @csrf
                <button type="submit" class="topbar-logout-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16,17 21,12 16,7"/>
                        <line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                    <span>Sign Out</span>
                </button>
            </form>
        </div>
    </div>

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

    @if($showCaptureLogs)
    <div class="capture-log-toast" id="captureLogToast" aria-live="polite" aria-hidden="true">
        <strong id="captureLogToastTitle">New capture log</strong>
        <span id="captureLogToastBody">A protected viewer event was recorded.</span>
        <a href="{{ route('admin.capture-attempt-logs') }}">Open logs</a>
    </div>
    @endif

    @yield('content')
</main>

<script src="{{ asset('js/app.js') }}"></script>
<script>
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

    @if($showCaptureLogs)
    (function() {
        const summaryUrl = @json(route('admin.capture-attempt-logs.summary'));
        const onCaptureLogsPage = @json(request()->routeIs('admin.capture-attempt-logs'));
        const renderedLatestId = Number(@json($latestCaptureLogId));
        const badge = document.getElementById('captureLogBadge');
        const toast = document.getElementById('captureLogToast');
        const toastTitle = document.getElementById('captureLogToastTitle');
        const toastBody = document.getElementById('captureLogToastBody');
        let knownLatestId = renderedLatestId;
        let toastTimer = null;

        function updateCaptureBadge(count) {
            if (!badge) {
                return;
            }

            const safeCount = Number(count || 0);
            badge.textContent = safeCount > 99 ? '99+' : String(safeCount);
            badge.classList.toggle('is-hidden', safeCount <= 0);
        }

        function showCaptureToast(payload) {
            if (!toast || onCaptureLogsPage) {
                return;
            }

            window.clearTimeout(toastTimer);
            toastTitle.textContent = payload.event || 'New capture log';
            toastBody.textContent = (payload.viewer || 'Unknown viewer') + ' - ' + (payload.research || 'Protected viewer');
            toast.classList.add('is-visible');
            toast.setAttribute('aria-hidden', 'false');

            toastTimer = window.setTimeout(function() {
                toast.classList.remove('is-visible');
                toast.setAttribute('aria-hidden', 'true');
            }, 7000);
        }

        async function pollCaptureLogs() {
            try {
                const response = await fetch(summaryUrl, {
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    return;
                }

                const payload = await response.json();
                const latestId = Number(payload.latest_id || 0);

                updateCaptureBadge(payload.unread_count);

                if (latestId > knownLatestId) {
                    showCaptureToast(payload);
                    knownLatestId = latestId;
                }
            } catch (error) {
                // Quietly retry on the next poll.
            }
        }

        window.setTimeout(pollCaptureLogs, 10000);
        window.setInterval(pollCaptureLogs, 30000);
    })();
    @endif
</script>
@stack('scripts')
</body>
</html>
