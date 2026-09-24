@extends('layouts.admin')
@section('title', 'Research Coordinator Dashboard')
@section('page-title', 'Research Coordinator Dashboard')

@push('styles')
<style>
body:has(.rc-dashboard) .admin-main{background:linear-gradient(130deg,#f3eefb,#f8f6fc 65%,#eee8f8)}
body:has(.rc-dashboard) .admin-topbar{border:0;margin-bottom:0;padding-bottom:10px;gap:18px;align-items:flex-start}
body:has(.rc-dashboard) .page-title{font-size:clamp(25px,2.4vw,38px);line-height:1.2;color:#310966}
body:has(.rc-dashboard) .admin-sidebar{background-color:#21063e;background-image:linear-gradient(rgba(163,111,227,.035) 1px,transparent 1px),linear-gradient(90deg,rgba(163,111,227,.035) 1px,transparent 1px);background-size:38px 38px}
.rc-dashboard{color:#351165;max-width:1600px;margin:0 auto}
.rc-welcome{position:relative;isolation:isolate;padding:0 0 28px;min-height:78px}
.rc-welcome:after{content:'';position:absolute;z-index:-1;right:0;top:-28px;width:42%;height:125px;background:linear-gradient(90deg,#f8f6fc00,#f8f6fc33),url('{{ asset('images/philcstarea.jpg') }}') center 45%/cover;opacity:.09;border-radius:20px;pointer-events:none}
.rc-welcome strong{display:block;font-size:16px;margin:0 0 6px;font-weight:600}
.rc-welcome p{margin:0;color:#84709d;font-size:14px;line-height:1.6}
.rc-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:20px}
.rc-stat{display:flex;align-items:flex-start;gap:15px;padding:22px 18px;background:linear-gradient(120deg,#fff,#fcfaff);border:1px solid #eee5f8;border-radius:17px;box-shadow:0 5px 20px #48217806;color:inherit;text-decoration:none;position:relative;min-width:0}
.rc-icon{display:inline-flex;justify-content:center;align-items:center;width:48px;height:52px;border-radius:13px;background:#eee4ff;color:#6017ae;flex-shrink:0}
.rc-icon svg,.rc-section-icon{width:27px;height:27px;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}
.rc-stat-text{min-width:0;padding-right:15px}
.rc-stat h2{font-family:inherit;margin:2px 0 8px;font-size:13px;font-weight:600;line-height:1.4}
.rc-stat strong{display:block;font-size:30px;line-height:1.1;margin-bottom:8px;color:#3a0d72}
.rc-stat p{font-size:12px;color:#81659d;margin:0;line-height:1.5}
.rc-chevron{display:flex;align-items:center;justify-content:center;width:26px;height:26px;border-radius:50%;background:#f1e9ff;color:#651fb0;font-size:22px;flex-shrink:0}
.rc-stat>.rc-chevron{position:absolute;right:12px;top:calc(50% - 13px)}
.rc-stat.is-correction{background:linear-gradient(120deg,#fff,#fff6fa)}
.rc-stat.is-correction .rc-icon{background:#fce0ec;color:#df1557}
.rc-columns{display:grid;grid-template-columns:minmax(0,1.8fr) minmax(320px,1fr);gap:18px;align-items:start}
.rc-panel{background:#fff;border:1px solid #eee6f8;border-radius:18px;box-shadow:0 5px 24px #49247504;min-width:0;overflow:hidden}
.rc-panel-heading{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:20px 20px 14px}
.rc-panel-heading h2{display:flex;align-items:center;gap:10px;font-family:var(--font-head);font-size:21px;color:#3b0d70;margin:0;line-height:1.3}
.rc-section-icon{flex-shrink:0;width:23px;height:23px}
.rc-link{display:inline-flex;align-items:center;justify-content:center;min-height:34px;padding:7px 12px;color:#63259b;background:#fcfaff;border:1px solid #e8dafa;border-radius:10px;text-decoration:none;font-size:12px;white-space:nowrap}
.rc-table-wrap{overflow-x:auto;margin:0 12px 20px;border:1px solid #eee8f6;border-radius:12px}
.rc-table{width:100%;border-collapse:collapse;font-size:12px;min-width:660px}
.rc-table th{background:#f6f1fb;padding:15px 10px;color:#74548e;text-align:left;font-weight:600;font-size:11px}
.rc-table td{padding:19px 10px;border-top:1px solid #f0eaf7;vertical-align:top;color:#826b9b;line-height:1.65}
.rc-table td+td{border-left:1px solid #f5f1f9}
.rc-table td:nth-child(2){width:27%;color:#46216e}
.rc-research-title{display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;color:inherit;text-decoration:none;overflow-wrap:anywhere}
.rc-status{display:inline-block;padding:4px 10px;border-radius:30px;font-size:10px;font-weight:600;line-height:1.5;background:#eee4fc;color:#6525a1;white-space:nowrap}
.rc-status-received{background:#e2edff;color:#155ac4}.rc-status-draft{background:#fff0c7;color:#8a5307}.rc-status-rejected{background:#ffe4ed;color:#c41551}.rc-status-approved{background:#ddf5e8;color:#167348}
.rc-right{display:grid;gap:16px}
.rc-tasks{display:grid;gap:9px;padding:0 14px 14px}
.rc-task{display:flex;align-items:center;gap:13px;padding:13px 11px;border:1px solid #eee6fa;border-radius:12px;text-decoration:none;color:inherit;background:#fefcff}
.rc-task .rc-icon{width:42px;height:47px;background:#f4edff}.rc-task .rc-icon svg{width:23px;height:23px}
.rc-task-info{min-width:0;flex:1}.rc-task strong{display:block;font-size:12px;margin-bottom:4px}.rc-task p{margin:0 0 3px;font-size:12px;color:#806699;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.rc-task small{font-size:11px;color:#806699}
.rc-task.is-correction{background:#fff7fa;border-color:#fbe2ed}.rc-task.is-correction strong,.rc-task.is-correction .rc-icon{color:#d41455}.rc-task.is-correction .rc-icon{background:#fce2ed}
.rc-timeline{list-style:none;margin:0 20px 20px 29px;padding:0 0 0 20px;border-left:2px solid #f0e7fa}
.rc-timeline li{position:relative;padding:10px 0 4px}
.rc-timeline li:before{content:'';position:absolute;width:9px;height:9px;background:#7431b4;border-radius:50%;left:-26px;top:15px;border:2px solid white}
.rc-timeline li[data-kind=received]:before{background:#3979ed}.rc-timeline li[data-kind=rejected]:before{background:#e52267}.rc-timeline li[data-kind=approved]:before{background:#19af7a}
.rc-timeline a{color:inherit;text-decoration:none}.rc-activity-line{display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap}.rc-activity-line strong{font-size:12px;font-weight:600}.rc-activity-line time{font-size:10px;color:#9a88ae}.rc-timeline p{font-size:11px;line-height:1.5;color:#8a739f;margin:4px 0;overflow-wrap:anywhere}
.rc-empty{padding:28px 18px;color:#8b779d;text-align:center;font-size:13px;line-height:1.6}
.rc-dashboard a:focus-visible{outline:2px solid #8b5cf6;outline-offset:3px}.rc-stat:hover,.rc-task:hover{border-color:#cbb1ec}.rc-link:hover{background:#f1e8fb}
@media(min-width:1200px){.rc-submissions{min-height:540px}}
@media(max-width:1250px){.rc-stats{grid-template-columns:repeat(2,minmax(0,1fr))}.rc-columns{grid-template-columns:1fr}.rc-right{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:650px){.rc-stats,.rc-right{grid-template-columns:1fr}.rc-stat{padding:18px}.rc-panel-heading{padding:17px 14px}.rc-panel-heading h2{font-size:18px}.rc-welcome:after{display:none}}
</style>
@endpush

@section('content')
@php
    $icons = [
        'file' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 12h8M8 16h8"/>',
        'edit' => '<path d="M12 4H5a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2h13a2 2 0 0 0 2-2v-7M16 3l5 5M9 15l-1 4 4-1L22 8l-4-4z"/>',
        'send' => '<path d="m22 2-7 20-4-9-9-4 20-7ZM22 2 11 13"/>',
        'alert' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v6M12 17h.01"/>',
    ];
    $cards = [
        ['New Dean Submissions', 'received', 'Recently received files', 'file', route('admin.coordinator.dean-submissions')],
        ['In Processing', 'preparing', 'Draft records being prepared', 'edit', route('admin.coordinator.research-monitoring', ['status' => 'draft'])],
        ['Submitted to Admin', 'pending_reviews', 'For final checking', 'send', route('admin.coordinator.submissions')],
        ['Returned for Correction', 'returned', 'Needs action', 'alert', route('admin.coordinator.returned')],
    ];
@endphp
<div class="rc-dashboard">
    <div class="rc-welcome">
        <strong>Welcome back, Research Coordinator!</strong>
        <p>Manage and prepare research records submitted by the Dean for repository publication.</p>
    </div>
    <section class="rc-stats" aria-label="Research overview">
        @foreach($cards as [$label, $key, $description, $icon, $url])
            <a href="{{ $url }}" class="rc-stat {{ $key === 'returned' ? 'is-correction' : '' }}">
                <span class="rc-icon"><svg viewBox="0 0 24 24" aria-hidden="true">{!! $icons[$icon] !!}</svg></span>
                <div class="rc-stat-text"><h2>{{ $label }}</h2><strong>{{ number_format($stats[$key]) }}</strong><p>{{ $description }}</p></div>
                <span class="rc-chevron" aria-hidden="true">›</span>
            </a>
        @endforeach
    </section>
    <div class="rc-columns">
        <section class="rc-panel rc-submissions">
            <div class="rc-panel-heading">
                <h2><svg class="rc-section-icon" viewBox="0 0 24 24" aria-hidden="true">{!! $icons['file'] !!}</svg>Recent Dean Submissions</h2>
                <a class="rc-link" href="{{ route('admin.coordinator.dean-submissions') }}">View All</a>
            </div>
            <div class="rc-table-wrap">
                <table class="rc-table">
                    <thead><tr><th scope="col">#</th><th scope="col">Research Title</th><th scope="col">Researchers</th><th scope="col">Academic Year</th><th scope="col">Date Received</th><th scope="col">Status</th><th scope="col">Action</th></tr></thead>
                    <tbody>
                        @forelse($recentHandoffs as $handoff)
                            @php $date = $handoff->received_at ?? $handoff->created_at; $research = $handoff->research; @endphp
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td><a class="rc-research-title" href="{{ route('admin.coordinator.dean-submissions', ['search' => $handoff->title]) }}" title="{{ $handoff->title }}">{{ $handoff->title }}</a></td>
                                <td>{{ $research?->author_name ?: 'Not yet recorded' }}</td>
                                <td>{{ $research?->semester?->school_year ?? 'Not yet assigned' }}</td>
                                <td><time datetime="{{ $date->toIso8601String() }}">{{ $date->copy()->timezone('Asia/Manila')->format('M d, Y') }}<br>{{ $date->copy()->timezone('Asia/Manila')->format('h:i A') }}</time></td>
                                <td><span class="rc-status rc-status-{{ $research?->status ?? 'received' }}">{{ $research?->coordinatorStageLabel() ?? 'Received' }}</span></td>
                                <td><a class="rc-link" href="{{ route('admin.coordinator.dean-submissions', ['search' => $handoff->title]) }}" aria-label="View {{ $handoff->title }}">View</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="7"><div class="rc-empty">No Dean submissions yet.<br>Research forwarded by your department’s Dean will appear here.</div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        <div class="rc-right">
            <section class="rc-panel">
                <div class="rc-panel-heading">
                    <h2><svg class="rc-section-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 5h12M9 12h12M9 19h12M3 5h1M3 12h1M3 19h1"/></svg>Tasks Needing Attention</h2>
                    <a class="rc-link" href="{{ route('admin.coordinator.research-monitoring') }}">View All</a>
                </div>
                <div class="rc-tasks">
                    @forelse($tasks as $task)
                        <a class="rc-task {{ $task['kind'] === 'rejected' ? 'is-correction' : '' }}" href="{{ $task['url'] }}">
                            <span class="rc-icon"><svg viewBox="0 0 24 24" aria-hidden="true">{!! $icons['file'] !!}</svg></span>
                            <div class="rc-task-info"><strong>{{ $task['label'] }}</strong><p title="{{ $task['title'] }}">{{ $task['title'] }}</p><small>{{ $task['date']->copy()->timezone('Asia/Manila')->format('M d, Y · h:i A') }}</small></div>
                            <span class="rc-chevron" aria-hidden="true">›</span>
                        </a>
                    @empty
                        <div class="rc-empty">You’re all caught up.<br>No submissions or corrections need attention.</div>
                    @endforelse
                </div>
            </section>
            <section class="rc-panel">
                <div class="rc-panel-heading">
                    <h2><svg class="rc-section-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 6v6l4 3"/></svg>Recent Activity</h2>
                    <a class="rc-link" href="{{ route('admin.coordinator.research-monitoring') }}">View All</a>
                </div>
                <ol class="rc-timeline">
                    @forelse($activities as $activity)
                        <li data-kind="{{ $activity['kind'] }}"><a href="{{ $activity['url'] }}"><div class="rc-activity-line"><strong>{{ $activity['label'] }}</strong><time datetime="{{ $activity['date']->toIso8601String() }}" title="{{ $activity['date']->copy()->timezone('Asia/Manila')->format('M d, Y h:i A') }}">{{ $activity['date']->diffForHumans() }}</time></div><p>{{ $activity['title'] }}</p></a></li>
                    @empty
                        <li><p>No recent activity in your department.</p></li>
                    @endforelse
                </ol>
            </section>
        </div>
    </div>
</div>
@endsection