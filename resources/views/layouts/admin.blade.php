<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') - Ube Repository Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    @stack('styles')
</head>
<body class="admin-body">
@php
    $adminUser = auth()->user();
    $isGlobalAdmin = $adminUser->isGlobalAdmin();
    $isDepartmentDean = $adminUser->isDepartmentDean();
    $isResearchCoordinator = $adminUser->isResearchCoordinator();
    $showCaptureLogs = $isGlobalAdmin;
    $portalTitle = $isResearchCoordinator ? 'Research Coordinator' : ($isDepartmentDean ? 'Ube Dean' : 'Ube Admin');
    $accountRoleLabel = $isResearchCoordinator ? 'Research Coordinator' : ($isDepartmentDean ? 'Department Dean' : 'Administrator');
    $captureLogAlertCount = 0;
    $latestCaptureLogId = 0;
    $reviewNotificationTokens = $isGlobalAdmin
        ? \App\Models\Research::pending()->whereNotNull('coordinator_id')->get(['id', 'updated_at'])
            ->map(fn ($research) => $research->id . ':' . $research->updated_at->format('Y-m-d H:i:s'))->values()
        : collect();

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
        <div>
            <span class="sidebar-title">{{ $portalTitle }}</span>
            <span class="sidebar-sub">Research Portal</span>
        </div>
    </div>

@if($isResearchCoordinator)
    @php
        $coordinatorPendingHandoffIds = \App\Models\ResearchHandoff::where('department', $adminUser->department)
            ->where('status', \App\Models\ResearchHandoff::STATUS_PENDING)
            ->pluck('id');
        $coordinatorPendingHandoffs = $coordinatorPendingHandoffIds->count();
        $coordinatorReturned = \App\Models\Research::where('department', $adminUser->department)
            ->where('status', \App\Models\Research::STATUS_REJECTED)
            ->count();
    @endphp
    <a href="{{ route('admin.coordinator.dashboard') }}" class="sidelink {{ request()->routeIs('admin.dashboard') || request()->routeIs('admin.coordinator.dashboard') ? 'active' : '' }}">
        <span class="sidelink-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg></span>
        Dashboard
    </a>
    <a href="{{ route('admin.coordinator.research-monitoring') }}" class="sidelink {{ request()->routeIs('admin.coordinator.research-monitoring') ? 'active' : '' }}">
        <span class="sidelink-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12h6"/><path d="M9 16h6"/><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg></span>
        Research Monitoring
    </a>
    <a href="{{ route('admin.coordinator.dean-submissions') }}" class="sidelink {{ request()->routeIs('admin.coordinator.dean-submissions') ? 'active' : '' }}">
        <span class="sidelink-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12.5V19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-6.5"/><path d="M16 6l-4-4-4 4"/><path d="M12 2v13"/><path d="m7 10-5 2.5L12 18l10-5.5L17 10"/></svg></span>
        Dean Submissions
        @if($coordinatorPendingHandoffs > 0)<span class="badge-count">{{ $coordinatorPendingHandoffs }}</span>@endif
    </a>
    <a href="{{ route('admin.coordinator.submissions') }}" class="sidelink {{ request()->routeIs('admin.coordinator.submissions') ? 'active' : '' }}">
        <span class="sidelink-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2 11 13"/><path d="m22 2-7 20-4-9-9-4Z"/></svg></span>
        Submission to Admin
    </a>
    <a href="{{ route('admin.coordinator.returned') }}" class="sidelink {{ request()->routeIs('admin.coordinator.returned') ? 'active' : '' }}">
        <span class="sidelink-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 14-4-4 4-4"/><path d="M5 10h11a4 4 0 0 1 0 8h-1"/></svg></span>
        Returned Researches
        @if($coordinatorReturned > 0)<span class="badge-count">{{ $coordinatorReturned }}</span>@endif
    </a>
    <a href="{{ route('admin.coordinator.reports') }}" class="sidelink {{ request()->routeIs('admin.coordinator.reports') ? 'active' : '' }}">
        <span class="sidelink-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></span>
        Reports
    </a>
@endif

@if(! $isResearchCoordinator)
    {{-- Dashboard --}}
<a href="{{ route('admin.dashboard') }}" class="sidelink {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
    <span class="sidelink-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg></span>
    Dashboard
</a>
@endif

@if(! $isResearchCoordinator && ($isDepartmentDean || $isGlobalAdmin))
<a href="{{ route('admin.research-handoffs') }}" class="sidelink {{ request()->routeIs('admin.research-handoffs*') ? 'active' : '' }}">
    <span class="sidelink-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12.5V19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-6.5"/><path d="M16 6l-4-4-4 4"/><path d="M12 2v13"/><path d="m7 10-5 2.5L12 18l10-5.5L17 10"/></svg></span>
    @if($isResearchCoordinator)
        Research Inbox
    @elseif($isDepartmentDean)
        Research Handoffs
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
    <span class="sidelink-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414A1 1 0 0 1 19 9.414V19a2 2 0 0 1-2 2z"/></svg></span>
    Researches
    @if($isGlobalAdmin)
        @php $pending = \App\Models\Research::pending()->count(); @endphp
        @if($pending > 0)<span class="badge-count">{{ $pending }}</span>@endif
    @endif
</a>
@endif

{{-- Users --}}
@if(! $isResearchCoordinator)
<a href="{{ route('admin.users') }}" class="sidelink {{ request()->routeIs('admin.users*') ? 'active' : '' }}">
    <span class="sidelink-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></span>
    Users
</a>
@endif

{{-- Semesters --}}
@if($isGlobalAdmin)
<a href="{{ route('admin.semesters') }}" class="sidelink {{ request()->routeIs('admin.semesters*') ? 'active' : '' }}">
    <span class="sidelink-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/><path d="M8 14h3"/><path d="M13 14h3"/><path d="M8 18h3"/></svg></span>
    Semesters
</a>
@endif

<hr class="sidebar-divider">

{{-- Reports --}}
@if(! $isResearchCoordinator)
<a href="{{ route('admin.reports') }}" class="sidelink {{ request()->routeIs('admin.reports') ? 'active' : '' }}">
    <span class="sidelink-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></span>
    Reports
</a>
@endif

@if($showCaptureLogs)
<a href="{{ route('admin.capture-attempt-logs') }}" class="sidelink {{ request()->routeIs('admin.capture-attempt-logs') ? 'active' : '' }}">
    <span class="sidelink-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 8v4l3 3"/><circle cx="12" cy="12" r="9"/></svg></span>
    Capture Logs
    <span id="captureLogBadge" class="badge-count capture-log-badge {{ $captureLogAlertCount > 0 ? '' : 'is-hidden' }}" data-latest-id="{{ $latestCaptureLogId }}">
        {{ $captureLogAlertCount > 99 ? '99+' : $captureLogAlertCount }}
    </span>
</a>
@endif

    <div class="sidebar-user sidebar-account" data-sidebar-account>
    <span class="user-avatar-sm">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
    <div style="flex:1; min-width:0;">
        <strong>{{ Str::limit(auth()->user()->name, 18) }}</strong>
        <small>
            {{ $accountRoleLabel }}
            @if(($isDepartmentDean || $isResearchCoordinator) && $adminUser->department)
                &middot; {{ $adminUser->department }}
            @endif
        </small>
    </div>
    <button type="button" class="sidebar-account-toggle" aria-haspopup="menu" aria-expanded="false" aria-label="Open account menu">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="m18 15-6-6-6 6"/></svg>
    </button>
    <div class="sidebar-account-menu" role="menu">
        <a href="{{ route('profile.edit') }}" class="sidebar-account-item" role="menuitem">
            <span class="sidebar-account-item-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/></svg></span>
            My Profile
        </a>
        <a href="{{ route('home') }}" class="sidebar-account-item" role="menuitem">
            <span class="sidebar-account-item-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg></span>
            Browse
        </a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="sidebar-account-item is-danger" role="menuitem">
                <span class="sidebar-account-item-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/></svg></span>
                Sign Out
            </button>
        </form>
    </div>
</div>
</aside>

<main class="admin-main">
    <div class="admin-topbar">
        <h1 class="page-title">@yield('page-title', 'Dashboard')</h1>
        <div class="topbar-right">
            <span class="topbar-date">{{ now('Asia/Manila')->format('F j, Y') }}</span>
            @if($isResearchCoordinator || $isGlobalAdmin)
                @php $notificationCount = $isResearchCoordinator ? $coordinatorPendingHandoffs : $reviewNotificationTokens->count(); @endphp
                <button type="button" id="notificationToggle"
                   class="topbar-notifications"
                   aria-label="Notifications{{ $notificationCount > 0 ? ' (' . $notificationCount . ')' : '' }}"
                   aria-haspopup="dialog" aria-controls="notificationPanel" aria-expanded="false"
                   title="Notifications">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                    @if($notificationCount > 0)
                        <span class="topbar-notification-count" aria-hidden="true">{{ $notificationCount > 99 ? '99+' : $notificationCount }}</span>
                    @endif
                </button>
            @endif
        </div>
    </div>

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

    @if($showCaptureLogs)
    <div class="capture-log-toast" id="captureLogToast" aria-live="polite" aria-hidden="true">
        <strong id="captureLogToastTitle">New capture log</strong>
        <span id="captureLogToastBody">A protected viewer event was recorded.</span>
        <a href="{{ route('admin.capture-attempt-logs') }}">Open logs</a>
    </div>
    @endif

    @yield('content')
</main>

@if($isResearchCoordinator || $isGlobalAdmin)
<dialog id="notificationPanel" class="notification-panel" aria-labelledby="notificationPanelTitle">
    <div class="notification-panel-header">
        <h2 id="notificationPanelTitle">Notifications</h2>
        <button type="button" id="notificationClose" aria-label="Close notifications" autofocus>&times;</button>
    </div>
    <div id="notificationPanelContent" class="notification-panel-content" aria-live="polite"></div>
</dialog>
<script>
    (() => {
        const toggle = document.getElementById('notificationToggle');
        const panel = document.getElementById('notificationPanel');
        const content = document.getElementById('notificationPanelContent');
        const badge = toggle.querySelector('.topbar-notification-count');
        const pendingIds = @json($coordinatorPendingHandoffIds ?? []);
        const reviewTokens = @json($reviewNotificationTokens);
        const isGlobalAdmin = @json($isGlobalAdmin);
        const reviewSeenKey = @json('review-notifications-seen:' . $adminUser->id);
        let seenReviews = [];
        try {
            const stored = JSON.parse(localStorage.getItem(reviewSeenKey) || '[]');
            if (Array.isArray(stored)) seenReviews = stored;
        } catch (error) {}
        const seenKey = @json('dean-notifications-seen:' . $adminUser->id . ':' . $adminUser->department);
        let seenId = 0;
        try {
            const storedId = Number(localStorage.getItem(seenKey));
            if (Number.isSafeInteger(storedId) && storedId > 0) seenId = storedId;
        } catch (error) {
            // The badge still clears for this page if browser storage is unavailable.
        }

        function updateNotificationBadge() {
            const count = isGlobalAdmin
                ? reviewTokens.filter(token => !seenReviews.includes(token)).length
                : pendingIds.filter(id => Number(id) > seenId).length;
            if (badge) {
                badge.hidden = count === 0;
                badge.textContent = count > 99 ? '99+' : String(count);
            }
            toggle.setAttribute('aria-label', count > 0 ? `Notifications (${count})` : 'Notifications');
        }

        updateNotificationBadge();
        let requestController;

        async function loadNotifications() {
            requestController?.abort();
            requestController = new AbortController();
            content.textContent = 'Loading notifications…';
            content.setAttribute('aria-busy', 'true');
            try {
                const response = await fetch(@json($isGlobalAdmin ? route('admin.review-notifications') : route('admin.coordinator.notifications', ['panel' => 1])), {
                    headers: { 'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                    signal: requestController.signal,
                });
                if (!response.ok || response.redirected) throw new Error('Unable to load notifications');
                content.innerHTML = await response.text();
            } catch (error) {
                if (error.name === 'AbortError') return;
                content.textContent = 'Unable to load notifications. ';
                const retry = document.createElement('button');
                retry.type = 'button';
                retry.className = 'notification-retry';
                retry.textContent = 'Try again';
                retry.addEventListener('click', loadNotifications);
                content.append(retry);
            } finally {
                content.removeAttribute('aria-busy');
            }
        }

        toggle.addEventListener('click', () => {
            panel.showModal();
            seenId = pendingIds.reduce((latest, id) => Math.max(latest, Number(id)), seenId);
            seenReviews = reviewTokens;
            updateNotificationBadge();
            try {
                localStorage.setItem(seenKey, String(seenId));
                if (isGlobalAdmin) localStorage.setItem(reviewSeenKey, JSON.stringify(seenReviews));
            } catch (error) {
                // Storage can be disabled by the browser.
            }
            toggle.setAttribute('aria-expanded', 'true');
            document.body.classList.add('notifications-open');
            loadNotifications();
        });
        document.getElementById('notificationClose').addEventListener('click', () => panel.close());
        panel.addEventListener('click', (event) => {
            const bounds = panel.getBoundingClientRect();
            if (event.target === panel && (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom)) panel.close();
        });
        panel.addEventListener('close', () => {
            requestController?.abort();
            toggle.setAttribute('aria-expanded', 'false');
            document.body.classList.remove('notifications-open');
            toggle.focus();
        });
    })();
</script>
@endif
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
