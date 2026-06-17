@extends('layouts.admin')
@section('title', 'Capture Logs')
@section('page-title', 'Capture Logs')

@section('content')
@php
    $parseDevice = function (?string $userAgent) {
        $ua = $userAgent ?: '';
        $browser = 'Unknown browser';
        $os = 'Unknown OS';
        $device = 'Unknown device';

        if (stripos($ua, 'Edg/') !== false) {
            $browser = 'Microsoft Edge';
        } elseif (stripos($ua, 'Chrome/') !== false && stripos($ua, 'Chromium') === false) {
            $browser = 'Google Chrome';
        } elseif (stripos($ua, 'Firefox/') !== false) {
            $browser = 'Mozilla Firefox';
        } elseif (stripos($ua, 'Safari/') !== false) {
            $browser = 'Safari';
        }

        if (stripos($ua, 'Windows NT') !== false) {
            $os = 'Windows';
        } elseif (stripos($ua, 'Android') !== false) {
            $os = 'Android';
        } elseif (stripos($ua, 'iPhone') !== false || stripos($ua, 'iPad') !== false) {
            $os = 'iOS / iPadOS';
        } elseif (stripos($ua, 'Mac OS X') !== false) {
            $os = 'macOS';
        } elseif (stripos($ua, 'Linux') !== false) {
            $os = 'Linux';
        }

        if (stripos($ua, 'Mobile') !== false || stripos($ua, 'Android') !== false || stripos($ua, 'iPhone') !== false) {
            $device = 'Mobile';
        } elseif (stripos($ua, 'iPad') !== false || stripos($ua, 'Tablet') !== false) {
            $device = 'Tablet';
        } elseif ($ua !== '') {
            $device = 'Desktop/Laptop';
        }

        return compact('device', 'browser', 'os');
    };

    $eventLabel = function (?string $eventType) {
        return match ($eventType) {
            'login_success' => 'Login',
            'protected_view_opened' => 'Protected View Opened',
            'printscreen' => 'Screenshot Attempt',
            'print_blocked' => 'Print Blocked',
            'save_blocked' => 'Save Blocked',
            'copy_blocked' => 'Copy Blocked',
            'source_view_blocked' => 'Source View Blocked',
            'window_blur' => 'Window Switched',
            'tab_hidden' => 'Tab Hidden',
            'context_menu_blocked' => 'Right Click Blocked',
            default => str_replace('_', ' ', ucwords((string) $eventType, '_')),
        };
    };

    $activeFilters = collect([
        request('search') ? 'Search: ' . request('search') : null,
        request('event_type') ? 'Event: ' . $eventLabel(request('event_type')) : null,
        request('department') ? 'Department: ' . request('department') : null,
        request('date') ? 'Date: ' . \Carbon\Carbon::parse(request('date'))->format('M d, Y') : null,
    ])->filter()->values();

    $readLogIdLookup = collect($readLogIds ?? [])->map(fn ($id) => (int) $id)->flip();
    $securityEventTypes = $securityEventTypes ?? \App\Models\CaptureAttemptLog::securityEventTypes();
    $riskScore = [
        'protected_view_opened' => 0,
        'printscreen' => 5,
        'print_blocked' => 3,
        'save_blocked' => 3,
        'source_view_blocked' => 3,
        'copy_blocked' => 2,
        'context_menu_blocked' => 2,
        'window_blur' => 1,
        'tab_hidden' => 1,
    ];

    $viewerGroups = $activityLogs
        ->groupBy(fn ($log) => $log->user_id ? 'user:' . $log->user_id : 'email:' . strtolower($log->viewer_email ?: $log->viewer_name ?: 'unknown'))
        ->map(function ($group) use ($parseDevice, $eventLabel, $readLogIdLookup, $securityEventTypes, $riskScore) {
            $latest = $group->sortByDesc('created_at')->first();
            $device = $parseDevice($latest?->user_agent);
            $securityLogs = $group->filter(fn ($log) => in_array($log->event_type, $securityEventTypes, true));
            $unreadSecurityLogs = $securityLogs->filter(fn ($log) => ! $readLogIdLookup->has((int) $log->id));
            $accountRiskScore = $unreadSecurityLogs->sum(fn ($log) => $riskScore[$log->event_type] ?? 0);
            $priorityLabel = match (true) {
                $unreadSecurityLogs->contains(fn ($log) => $log->event_type === 'printscreen') => 'Screenshot attempt',
                $accountRiskScore >= 6 => 'High activity',
                $unreadSecurityLogs->contains(fn ($log) => $log->event_type !== 'protected_view_opened') => 'Needs review',
                default => null,
            };

            return [
                'name' => $latest?->viewer_name ?: $latest?->user?->name ?: 'Unknown Account',
                'email' => $latest?->viewer_email ?: $latest?->user?->email ?: 'No email recorded',
                'department' => $latest?->viewer_department ?: $latest?->research?->department ?: 'No department',
                'attempts' => $group->count(),
                'security_count' => $securityLogs->count(),
                'unread_count' => $unreadSecurityLogs->count(),
                'risk_score' => $accountRiskScore,
                'priority_label' => $priorityLabel,
                'log_ids' => $securityLogs->pluck('id')->map(fn ($id) => (int) $id)->values(),
                'latest_at' => $latest?->created_at?->copy()->timezone('Asia/Manila')->format('M d, Y h:i A') ?: 'Unknown time',
                'device' => $device['device'],
                'browser' => $device['browser'],
                'os' => $device['os'],
                'logs' => $group->sortByDesc('created_at')->values()->map(function ($log) use ($parseDevice, $eventLabel, $readLogIdLookup, $securityEventTypes) {
                    $details = $log->details ?? [];
                    $device = $parseDevice($log->user_agent);
                    $isSecurityEvent = in_array($log->event_type, $securityEventTypes, true);

                    return [
                        'id' => (int) $log->id,
                        'event' => $eventLabel($log->event_type),
                        'research' => $log->research?->title ?: 'Account login',
                        'scope' => ucfirst($log->viewer_scope),
                        'time' => $log->created_at?->copy()->timezone('Asia/Manila')->format('M d, Y h:i A'),
                        'ip' => $log->ip_address ?: 'No IP recorded',
                        'reason' => $details['reason'] ?? 'n/a',
                        'key' => $details['key'] ?? 'n/a',
                        'location' => $details['location_estimate'] ?? 'n/a',
                        'device' => $device['device'],
                        'browser' => $device['browser'],
                        'os' => $device['os'],
                        'agent' => $log->user_agent ?: 'Unavailable',
                        'unread' => $isSecurityEvent && ! $readLogIdLookup->has((int) $log->id),
                        'security_event' => $isSecurityEvent,
                    ];
                }),
            ];
        })
        ->sortByDesc('risk_score')
        ->sortByDesc('unread_count')
        ->values();
@endphp

<section class="cal-shell">
    <div class="cal-hero">
        <div class="cal-hero-copy">
            <span class="cal-kicker">Security Audit Trail</span>
            <h2>Security activity at a glance</h2>
            <p>Review protected viewer events, devices, IP addresses, and suspicious behavior without losing account context. Login events are available through the Event filter.</p>
        </div>

        @if($activeFilters->isNotEmpty())
            <div class="cal-active-filters">
                @foreach($activeFilters as $filter)
                    <span class="cal-filter-chip">{{ $filter }}</span>
                @endforeach
            </div>
        @endif
    </div>

    <div class="cal-filter-card">
        <form method="GET" action="{{ route('admin.capture-attempt-logs') }}" class="cal-filter-form">
            <div class="cal-filter-group cal-filter-group-search">
                <label>Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="User, email, paper title, event">
            </div>

            <div class="cal-filter-group">
                <label>Event</label>
                <select name="event_type">
                    <option value="">All Events</option>
                    @foreach($eventTypes as $eventType)
                        <option value="{{ $eventType }}" {{ request('event_type') === $eventType ? 'selected' : '' }}>
                            {{ $eventLabel($eventType) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="cal-filter-group">
                <label>Department</label>
                <select name="department">
                    <option value="">All Departments</option>
                    @foreach($departments as $department)
                        <option value="{{ $department }}" {{ request('department') === $department ? 'selected' : '' }}>
                            {{ $department }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="cal-filter-group">
                <label>Date</label>
                <input type="date" name="date" value="{{ request('date') }}">
            </div>

            <div class="cal-filter-actions">
                <button type="submit" class="cal-btn cal-btn-primary">Apply Filters</button>
                <a href="{{ route('admin.capture-attempt-logs') }}" class="cal-btn cal-btn-ghost">Reset</a>
            </div>
        </form>
    </div>

    <div class="cal-summary-grid">
        <article class="cal-summary-card cal-summary-card-primary">
            <div class="cal-summary-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M3 12h18"/>
                    <path d="M7 8h10"/>
                    <path d="M9 16h6"/>
                </svg>
            </div>
            <div>
                <strong>{{ $logs->total() }}</strong>
                <span>Total security events</span>
            </div>
        </article>

        <article class="cal-summary-card">
            <div class="cal-summary-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
            </div>
            <div>
                <strong>{{ $logs->getCollection()->pluck('viewer_name')->filter()->unique()->count() }}</strong>
                <span>Unique accounts on this page</span>
            </div>
        </article>

        <article class="cal-summary-card">
            <div class="cal-summary-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M4 6h16"/>
                    <path d="M4 12h16"/>
                    <path d="M4 18h10"/>
                </svg>
            </div>
            <div>
                <strong>{{ $logs->getCollection()->pluck('event_type')->filter()->unique()->count() }}</strong>
                <span>Event types on this page</span>
            </div>
        </article>
    </div>

    @if($viewerGroups->isEmpty())
        <div class="cal-card">
            <div class="cal-empty">
                <div class="cal-empty-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M12 8v4l3 3"/>
                        <circle cx="12" cy="12" r="9"/>
                    </svg>
                </div>
                <strong>No capture logs found.</strong>
                <span>Once protected viewer events are recorded, the audit trail will appear here.</span>
            </div>
        </div>
    @else
        <div class="cal-card cal-account-card">
            <div class="cal-section-head">
                <div>
                    <h3>Account Activity View</h3>
                    <p>Grouped by account/email so login and protected viewer behavior are easier to inspect.</p>
                </div>
                <span>{{ $viewerGroups->count() }} account(s)</span>
            </div>

            <div class="cal-account-grid">
                @foreach($viewerGroups as $index => $viewer)
                    <article class="cal-account-item {{ $viewer['unread_count'] > 0 ? 'is-unread' : '' }} {{ $viewer['risk_score'] >= 5 ? 'is-suspicious' : '' }}" data-account-row>
                        <div>
                            <div class="cal-viewer-name">
                                {{ $viewer['name'] }}
                                @if($viewer['unread_count'] > 0)
                                    <span class="cal-review-dot" title="Unread suspicious activity"></span>
                                @endif
                            </div>
                            <div class="cal-viewer-meta">{{ $viewer['email'] }}</div>
                            <div class="cal-subline">{{ $viewer['department'] }}</div>
                            @if($viewer['priority_label'])
                                <div class="cal-review-line">
                                    <span>{{ $viewer['priority_label'] }}</span>
                                    <small>{{ $viewer['unread_count'] }} unread security event{{ $viewer['unread_count'] === 1 ? '' : 's' }}</small>
                                </div>
                            @endif
                        </div>
                        <div class="cal-account-meta">
                            <span class="cal-account-count">{{ $viewer['attempts'] }} event{{ $viewer['attempts'] === 1 ? '' : 's' }}</span>
                            @if($viewer['security_count'] > 0)
                                <span class="cal-security-count">{{ $viewer['security_count'] }} protected viewer event{{ $viewer['security_count'] === 1 ? '' : 's' }}</span>
                            @endif
                            <span>{{ $viewer['latest_at'] }}</span>
                            <span>{{ $viewer['device'] }} • {{ $viewer['browser'] }} • {{ $viewer['os'] }}</span>
                        </div>
                        <button type="button" class="cal-btn {{ $viewer['unread_count'] > 0 ? 'cal-btn-alert' : 'cal-btn-ghost' }} cal-account-btn" data-activity='@json($viewer)'>
                            View Activity
                        </button>
                    </article>
                @endforeach
            </div>
        </div>
    @endif
</section>

<div class="cal-activity-modal" id="activityModal" aria-hidden="true">
    <div class="cal-activity-backdrop" data-close-activity></div>
    <div class="cal-activity-dialog" role="dialog" aria-modal="true" aria-labelledby="activityModalTitle">
        <div class="cal-activity-head">
            <div>
                <span class="cal-kicker">Security Timeline</span>
                <h3 id="activityModalTitle">Account Activity</h3>
                <p id="activityModalMeta"></p>
            </div>
            <button type="button" class="cal-activity-close" aria-label="Close activity view" data-close-activity>&times;</button>
        </div>
        <div class="cal-activity-summary" id="activityModalSummary"></div>
        <div class="cal-activity-list" id="activityModalList"></div>
    </div>
</div>

<style>
.cal-shell{
    display:grid;
    gap:18px;
}

.cal-hero{
    position:relative;
    overflow:hidden;
    padding:28px 30px;
    border-radius:26px;
    background:
        radial-gradient(circle at top right, rgba(102,126,234,.18), transparent 30%),
        radial-gradient(circle at left bottom, rgba(236,72,153,.12), transparent 28%),
        linear-gradient(135deg, #ffffff 0%, #f8f4ff 52%, #f2edff 100%);
    border:1px solid rgba(132,92,214,.14);
    box-shadow:0 18px 44px rgba(69,26,122,.08);
}

.cal-kicker{
    display:inline-flex;
    align-items:center;
    gap:8px;
    margin-bottom:12px;
    font-size:11px;
    font-weight:800;
    letter-spacing:.18em;
    text-transform:uppercase;
    color:#8d78bb;
}

.cal-kicker::before{
    content:"";
    width:30px;
    height:1px;
    background:linear-gradient(90deg, #6d28d9, transparent);
}

.cal-hero h2{
    margin:0 0 8px;
    font-size:30px;
    line-height:1.1;
    letter-spacing:-.04em;
    color:#1f123e;
}

.cal-hero p{
    max-width:720px;
    margin:0;
    color:#7f71a7;
    line-height:1.7;
    font-size:14px;
}

.cal-active-filters{
    display:flex;
    flex-wrap:wrap;
    gap:10px;
    margin-top:20px;
}

.cal-filter-chip{
    display:inline-flex;
    align-items:center;
    padding:8px 13px;
    border-radius:999px;
    background:rgba(255,255,255,.84);
    border:1px solid rgba(157,128,220,.2);
    color:#5b3d8a;
    font-size:12px;
    font-weight:600;
    backdrop-filter:blur(8px);
}

.cal-filter-card,
.cal-card{
    background:#fff;
    border-radius:24px;
    border:1px solid rgba(120,87,183,.12);
    box-shadow:0 14px 34px rgba(51,23,92,.06);
}

.cal-filter-card{
    padding:22px;
}

.cal-filter-form{
    display:grid;
    grid-template-columns:minmax(240px, 1.8fr) repeat(3, minmax(150px, .9fr)) auto;
    gap:14px;
    align-items:end;
}

.cal-filter-group{
    display:flex;
    flex-direction:column;
    gap:8px;
}

.cal-filter-group label{
    font-size:11px;
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:.15em;
    color:#9a87bf;
}

.cal-filter-group input,
.cal-filter-group select{
    height:52px;
    padding:0 16px;
    border:1.5px solid #e8dcfb;
    border-radius:16px;
    background:linear-gradient(180deg, #fefcff 0%, #f8f4ff 100%);
    font-size:14px;
    color:#221248;
    font-family:inherit;
    width:100%;
    box-sizing:border-box;
    transition:border-color .16s ease, box-shadow .16s ease, transform .16s ease;
}

.cal-filter-group input:focus,
.cal-filter-group select:focus{
    outline:none;
    border-color:#7c3aed;
    box-shadow:0 0 0 4px rgba(124,58,237,.12);
    transform:translateY(-1px);
}

.cal-filter-actions{
    display:flex;
    gap:10px;
    align-items:center;
}

.cal-btn{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-width:132px;
    height:52px;
    padding:0 20px;
    border-radius:18px;
    font-size:13px;
    font-weight:800;
    border:none;
    text-decoration:none;
    cursor:pointer;
    font-family:inherit;
    transition:transform .16s ease, box-shadow .16s ease, background .16s ease, color .16s ease;
}

.cal-btn:hover{
    transform:translateY(-1px);
}

.cal-btn-primary{
    background:linear-gradient(135deg, #5b21b6 0%, #43188e 100%);
    color:#fff;
    box-shadow:0 14px 24px rgba(91,33,182,.24);
}

.cal-btn-ghost{
    background:#fff;
    color:#7d69a8;
    border:1.5px solid #e9ddfb;
}

.cal-btn-alert{
    background:linear-gradient(135deg, #dc2626 0%, #7f1d1d 100%);
    color:#fff;
    box-shadow:0 14px 24px rgba(220,38,38,.2);
}

.cal-summary-grid{
    display:grid;
    grid-template-columns:repeat(3, minmax(0, 1fr));
    gap:14px;
}

.cal-summary-card{
    display:flex;
    align-items:center;
    gap:16px;
    padding:20px 22px;
    border-radius:22px;
    background:#fff;
    border:1px solid rgba(124,58,237,.1);
    box-shadow:0 12px 28px rgba(57,26,101,.05);
}

.cal-summary-card-primary{
    background:linear-gradient(135deg, #1f1146 0%, #34166c 100%);
    color:#fff;
}

.cal-summary-icon{
    display:grid;
    place-items:center;
    width:52px;
    height:52px;
    border-radius:18px;
    background:#f4edff;
    color:#6d28d9;
    flex-shrink:0;
}

.cal-summary-card-primary .cal-summary-icon{
    background:rgba(255,255,255,.14);
    color:#fff;
}

.cal-summary-card strong{
    display:block;
    margin-bottom:4px;
    font-size:32px;
    line-height:1;
    letter-spacing:-.05em;
    color:#1f123e;
}

.cal-summary-card span{
    font-size:13px;
    color:#8576a9;
}

.cal-summary-card-primary strong,
.cal-summary-card-primary span{
    color:#fff;
}

.cal-card{
    overflow:hidden;
}

.cal-account-card{
    padding:22px;
}

.cal-section-head{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:16px;
    margin-bottom:16px;
}

.cal-section-head h3{
    margin:0 0 4px;
    color:#1f123e;
    font-size:20px;
}

.cal-section-head p,
.cal-section-head span{
    margin:0;
    color:#8576a9;
    font-size:13px;
}

.cal-account-grid{
    display:grid;
    gap:12px;
}

.cal-account-item{
    display:grid;
    grid-template-columns:minmax(220px,1.2fr) minmax(220px,1fr) auto;
    gap:16px;
    align-items:center;
    padding:16px;
    border-radius:18px;
    border:1px solid #efe7fb;
    background:#fcfaff;
}

.cal-account-item.is-unread{
    border-color:#fecaca;
    background:linear-gradient(135deg,#fff7f7 0%,#fff 58%,#faf7ff 100%);
    box-shadow:0 16px 32px rgba(127,29,29,.08);
}

.cal-account-item.is-suspicious{
    box-shadow:0 16px 36px rgba(127,29,29,.12);
}

.cal-account-meta{
    display:grid;
    gap:5px;
    color:#7f6ca7;
    font-size:12px;
    line-height:1.45;
}

.cal-account-count{
    color:#5b21b6;
    font-weight:800;
}

.cal-security-count{
    color:#9f1239;
    font-weight:800;
}

.cal-account-btn{
    min-width:130px;
    height:44px;
}

.cal-viewer-name,
.cal-cell-title,
.cal-research-title a{
    font-weight:800;
    color:#1f123e;
    text-decoration:none;
    line-height:1.5;
}

.cal-review-dot{
    display:inline-block;
    width:9px;
    height:9px;
    margin-left:8px;
    border-radius:999px;
    background:#ef4444;
    box-shadow:0 0 0 5px rgba(239,68,68,.12);
    vertical-align:middle;
}

.cal-review-line{
    display:flex;
    align-items:center;
    flex-wrap:wrap;
    gap:8px;
    margin-top:10px;
}

.cal-review-line span{
    display:inline-flex;
    align-items:center;
    padding:5px 9px;
    border-radius:999px;
    background:#fee2e2;
    color:#991b1b;
    font-size:11px;
    font-weight:900;
    letter-spacing:.02em;
}

.cal-review-line small{
    color:#9f1239;
    font-size:11.5px;
    font-weight:700;
}

.cal-research-title a:hover{
    color:#6d28d9;
}

.cal-viewer-meta{
    margin-top:5px;
    color:#8e7faf;
    font-size:12px;
    line-height:1.55;
}

.cal-subline{
    margin-top:7px;
    color:#695489;
    font-size:12px;
    line-height:1.55;
}

.cal-pill{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-width:92px;
    min-height:56px;
    padding:8px 14px;
    border-radius:999px;
    background:linear-gradient(135deg, #ede4ff 0%, #e6dcfb 100%);
    color:#6b21c9;
    font-size:12px;
    font-weight:800;
    line-height:1.35;
    text-align:center;
    box-shadow:inset 0 0 0 1px rgba(124,58,237,.08);
}

.cal-context-line{
    color:#816fa6;
    font-size:12px;
    line-height:1.6;
    word-break:break-word;
}

.cal-context-line + .cal-context-line{
    margin-top:4px;
}

.cal-empty{
    display:grid;
    place-items:center;
    gap:10px;
    padding:72px 24px;
    text-align:center;
    color:#8475a7;
}

.cal-empty-icon{
    display:grid;
    place-items:center;
    width:68px;
    height:68px;
    border-radius:22px;
    background:linear-gradient(135deg, #f4ecff 0%, #ece4ff 100%);
    color:#6d28d9;
}

.cal-empty strong{
    color:#241248;
    font-size:18px;
}

.cal-empty span{
    max-width:480px;
    font-size:14px;
    line-height:1.7;
}

.cal-pagination{
    padding:18px 22px 22px;
    background:#fff;
}

.cal-activity-modal{
    position:fixed;
    inset:0;
    display:none;
    align-items:center;
    justify-content:center;
    padding:22px;
    z-index:1400;
}

.cal-activity-modal.is-open{
    display:flex;
}

.cal-activity-backdrop{
    position:absolute;
    inset:0;
    background:rgba(31,18,62,.58);
    backdrop-filter:blur(4px);
}

.cal-activity-dialog{
    position:relative;
    width:min(880px,100%);
    max-height:calc(100vh - 44px);
    overflow:auto;
    border-radius:24px;
    background:#fff;
    box-shadow:0 26px 70px rgba(31,18,62,.28);
}

.cal-activity-head{
    display:flex;
    justify-content:space-between;
    gap:16px;
    padding:22px 24px 18px;
    border-bottom:1px solid #efe7fb;
    background:linear-gradient(180deg,#fdfbff,#f8f4ff);
}

.cal-activity-head h3{
    margin:0 0 6px;
    color:#1f123e;
    font-size:24px;
}

.cal-activity-head p{
    margin:0;
    color:#7f71a7;
    line-height:1.5;
}

.cal-activity-close{
    width:42px;
    height:42px;
    border:none;
    border-radius:14px;
    background:#f4edff;
    color:#5b21b6;
    font-size:28px;
    cursor:pointer;
}

.cal-activity-summary{
    display:grid;
    grid-template-columns:repeat(4,minmax(0,1fr));
    gap:12px;
    padding:18px 24px 0;
}

.cal-activity-summary div{
    padding:14px;
    border-radius:16px;
    background:#faf7ff;
    border:1px solid #efe7fb;
}

.cal-activity-summary strong{
    display:block;
    color:#1f123e;
    margin-bottom:5px;
}

.cal-activity-summary span{
    color:#7f6ca7;
    font-size:12px;
    line-height:1.5;
}

.cal-activity-list{
    display:grid;
    gap:12px;
    padding:18px 24px 24px;
}

.cal-activity-entry{
    padding:16px;
    border-radius:18px;
    border:1px solid #efe7fb;
    background:#fff;
}

.cal-activity-entry.is-unread{
    border-color:#fecaca;
    background:#fff7f7;
}

.cal-activity-entry.is-security .cal-activity-event{
    background:#fee2e2;
    color:#991b1b;
}

.cal-unread-tag{
    display:inline-flex;
    align-items:center;
    padding:7px 10px;
    border-radius:999px;
    background:#fee2e2;
    color:#991b1b;
    font-size:12px;
    font-weight:900;
}

.cal-activity-entry-top{
    display:flex;
    justify-content:space-between;
    gap:12px;
    flex-wrap:wrap;
    margin-bottom:10px;
}

.cal-activity-event{
    display:inline-flex;
    padding:7px 11px;
    border-radius:999px;
    background:#ede4ff;
    color:#5b21b6;
    font-size:12px;
    font-weight:800;
}

.cal-activity-time{
    color:#8e7faf;
    font-size:12px;
    font-weight:700;
}

.cal-activity-entry p{
    margin:0 0 8px;
    color:#1f123e;
    font-weight:800;
    line-height:1.5;
}

.cal-activity-entry small{
    display:block;
    color:#7f6ca7;
    line-height:1.55;
}

@media (max-width: 1180px){
    .cal-filter-form{
        grid-template-columns:repeat(2, minmax(0, 1fr));
    }

    .cal-filter-group-search,
    .cal-filter-actions{
        grid-column:auto;
    }

    .cal-summary-grid{
        grid-template-columns:1fr;
    }
}

@media (max-width: 700px){
    .cal-hero{
        padding:22px 20px;
        border-radius:22px;
    }

    .cal-hero h2{
        font-size:24px;
    }

    .cal-filter-card,
    .cal-card{
        border-radius:20px;
    }

    .cal-filter-card{
        padding:18px;
    }

    .cal-filter-form{
        grid-template-columns:1fr;
    }

    .cal-filter-actions{
        flex-direction:column;
    }

    .cal-btn{
        width:100%;
    }

    .cal-account-item,
    .cal-activity-summary{
        grid-template-columns:1fr;
    }
}
</style>

<script>
function escapeCalHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, function(character) {
        return {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[character];
    });
}

const activityModal = document.getElementById('activityModal');
const activityModalTitle = document.getElementById('activityModalTitle');
const activityModalMeta = document.getElementById('activityModalMeta');
const activityModalSummary = document.getElementById('activityModalSummary');
const activityModalList = document.getElementById('activityModalList');
const markViewedUrl = @json(route('admin.capture-attempt-logs.mark-viewed'));
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

function updateSidebarCaptureBadge(count) {
    const badge = document.getElementById('captureLogBadge');

    if (!badge) {
        return;
    }

    const safeCount = Number(count || 0);
    badge.textContent = safeCount > 99 ? '99+' : String(safeCount);
    badge.classList.toggle('is-hidden', safeCount <= 0);
}

function markAccountRowRead(button, payload) {
    const row = button.closest('[data-account-row]');

    if (!row) {
        return;
    }

    row.classList.remove('is-unread', 'is-suspicious');
    row.querySelector('.cal-review-dot')?.remove();
    row.querySelector('.cal-review-line')?.remove();
    button.classList.remove('cal-btn-alert');
    button.classList.add('cal-btn-ghost');

    payload.unread_count = 0;
    payload.priority_label = null;
    payload.logs = Array.isArray(payload.logs)
        ? payload.logs.map(function(log) {
            return { ...log, unread: false };
        })
        : [];
    button.dataset.activity = JSON.stringify(payload);
}

async function markActivityViewed(payload, button) {
    const logIds = Array.isArray(payload.log_ids) ? payload.log_ids : [];

    if (logIds.length === 0) {
        markAccountRowRead(button, payload);
        return;
    }

    try {
        const response = await fetch(markViewedUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            credentials: 'same-origin',
            body: JSON.stringify({ log_ids: logIds }),
        });

        if (!response.ok) {
            return;
        }

        const result = await response.json();
        updateSidebarCaptureBadge(result.unread_count);
        markAccountRowRead(button, payload);
    } catch (error) {
        // Keep the row unread if the read marker could not be saved.
    }
}

function openActivityModal(payload, button) {
    activityModalTitle.textContent = payload.name || 'Account Activity';
    activityModalMeta.textContent = `${payload.email || 'No email recorded'} • ${payload.department || 'No department'}`;
    activityModalSummary.innerHTML = `
        <div><strong>${escapeCalHtml(payload.attempts)} event(s)</strong><span>Latest: ${escapeCalHtml(payload.latest_at)}</span></div>
        <div><strong>${escapeCalHtml(payload.unread_count || 0)} unread</strong><span>${escapeCalHtml(payload.priority_label || 'Reviewed activity')}</span></div>
        <div><strong>${escapeCalHtml(payload.device)}</strong><span>${escapeCalHtml(payload.os)}</span></div>
        <div><strong>${escapeCalHtml(payload.browser)}</strong><span>Detected from browser user-agent</span></div>
    `;

    const logs = Array.isArray(payload.logs) ? payload.logs : [];
    activityModalList.innerHTML = logs.length
        ? logs.map(function(log) {
            return `
                <article class="cal-activity-entry ${log.unread ? 'is-unread' : ''} ${log.security_event ? 'is-security' : ''}">
                    <div class="cal-activity-entry-top">
                        <span class="cal-activity-event">${escapeCalHtml(log.event)}</span>
                        ${log.unread ? '<span class="cal-unread-tag">Needs review</span>' : ''}
                        <span class="cal-activity-time">${escapeCalHtml(log.time)}</span>
                    </div>
                    <p>${escapeCalHtml(log.research)}</p>
                    <small>Scope: ${escapeCalHtml(log.scope)} • IP: ${escapeCalHtml(log.ip)}</small>
                    <small>Device: ${escapeCalHtml(log.device)} / ${escapeCalHtml(log.os)} • Browser: ${escapeCalHtml(log.browser)}</small>
                    <small>Key: ${escapeCalHtml(log.key)} • Reason: ${escapeCalHtml(log.reason)}</small>
                    <small>Location: ${escapeCalHtml(log.location)}</small>
                    <small>Agent: ${escapeCalHtml(log.agent)}</small>
                </article>
            `;
        }).join('')
        : '<div class="cal-empty"><strong>No activity details available.</strong></div>';

    activityModal.classList.add('is-open');
    activityModal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';

    markActivityViewed(payload, button);
}

function closeActivityModal() {
    activityModal.classList.remove('is-open');
    activityModal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
}

document.querySelectorAll('[data-activity]').forEach(function(button) {
    button.addEventListener('click', function() {
        openActivityModal(JSON.parse(this.dataset.activity), this);
    });
});

document.querySelectorAll('[data-close-activity]').forEach(function(button) {
    button.addEventListener('click', closeActivityModal);
});

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape' && activityModal.classList.contains('is-open')) {
        closeActivityModal();
    }
});
</script>
@endsection
