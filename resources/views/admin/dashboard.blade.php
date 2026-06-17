@extends('layouts.admin')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')

<div class="stats-grid">

    <div class="stat-card stat-purple" style="cursor:pointer;" onclick="openModal('researches')">
        <div class="stat-accent acc-purple"></div>
        <div class="stat-info">
            <span class="stat-label">Total Researches</span>
            <span class="stat-number">{{ number_format($stats['total_researches']) }}</span>
        </div>
        <div class="stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="1.8"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414A1 1 0 0 1 19 9.414V19a2 2 0 0 1-2 2z"/></svg>
        </div>
    </div>

    <div class="stat-card stat-amber" style="cursor:pointer;" onclick="openModal('pending')">
        <div class="stat-accent acc-amber"></div>
        <div class="stat-info">
            <span class="stat-label">Pending Review</span>
            <span class="stat-number">{{ number_format($stats['pending']) }}</span>
        </div>
        <div class="stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><polyline points="12,6 12,12 16,14"/></svg>
        </div>
    </div>

    <div class="stat-card stat-green" style="cursor:pointer;" onclick="openModal('approved')">
        <div class="stat-accent acc-green"></div>
        <div class="stat-info">
            <span class="stat-label">Approved</span>
            <span class="stat-number">{{ number_format($stats['approved']) }}</span>
        </div>
        <div class="stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="1.8"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22,4 12,14.01 9,11.01"/></svg>
        </div>
    </div>

    <div class="stat-card stat-red" style="cursor:pointer;" onclick="openModal('rejected')">
        <div class="stat-accent acc-red"></div>
        <div class="stat-info">
            <span class="stat-label">Rejected</span>
            <span class="stat-number">{{ number_format($stats['rejected']) }}</span>
        </div>
        <div class="stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
        </div>
    </div>

    <div class="stat-card stat-blue" style="cursor:pointer;" onclick="openModal('users')">
        <div class="stat-accent acc-blue"></div>
        <div class="stat-info">
            <span class="stat-label">Total Users</span>
            <span class="stat-number">{{ number_format($stats['total_users']) }}</span>
        </div>
        <div class="stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="1.8"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
    </div>

    <div class="stat-card stat-teal">
        <div class="stat-accent acc-teal"></div>
        <div class="stat-info">
            <span class="stat-label">Total Views</span>
            <span class="stat-number">{{ number_format($stats['total_views']) }}</span>
        </div>
        <div class="stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="#0d9488" stroke-width="1.8"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
        </div>
    </div>

</div>

<!-- MAIN GRID -->
<div class="admin-dashboard-grid">
    <div class="admin-card">
        <div class="admin-card-header">
            <h3>By Department</h3>
            <span class="department-card-hint">Click for summary</span>
        </div>
        @php $maxDeptCount = max($researchByDept->pluck('count')->all() ?: [1]); @endphp
        @forelse($researchByDept as $dept)
        <button type="button" class="dept-bar-row dept-bar-button" data-department="{{ $dept->department }}" onclick="openDepartmentAnalyticsModal(this.dataset.department)" aria-label="Open {{ $dept->department }} analytics summary">
            <span class="dept-bar-label" title="{{ $dept->department }}">{{ Str::limit($dept->department, 30) }}</span>
            <span class="dept-bar-wrap" aria-hidden="true">
                <span class="dept-bar" title="{{ $dept->department }}: {{ $dept->count }} research{{ $dept->count == 1 ? '' : 'es' }}" style="width: {{ $maxDeptCount > 0 ? min(100, ($dept->count / $maxDeptCount) * 100) : 0 }}%"></span>
            </span>
            <span class="dept-bar-count">{{ $dept->count }}</span>
        </button>
        @empty
            <div class="empty-sm">No department data yet</div>
        @endforelse
    </div>

    <div class="admin-card">
        <div class="admin-card-header"><h3>Top Viewed</h3></div>
        @foreach($topResearches as $i => $r)
        <div class="top-item">
            <span class="top-rank">{{ $i + 1 }}</span>
            <div class="top-info">
                <a href="{{ route('admin.research.show', $r) }}" class="top-title">{{ Str::limit($r->title, 45) }}</a>
                <span>{{ number_format($r->view_count) }} views</span>
                <small>{{ Str::limit($r->department, 36) }} • {{ $r->year_published }}</small>
            </div>
        </div>
        @endforeach
    </div>

    <div class="admin-card analytics-panel-card">
        <div class="admin-card-header"><h3>Research Trend By Year</h3></div>
        <div class="analytics-panel-body">
            @if($researchByYear->isNotEmpty())
                @php
                    $chartWidth = 720;
                    $chartHeight = 220;
                    $chartPad = 34;
                    $maxYearCount = max($researchByYear->pluck('count')->all() ?: [1]);
                    $yearCount = max($researchByYear->count(), 1);
                    $yearPoints = $researchByYear->values()->map(function ($year, $index) use ($chartWidth, $chartHeight, $chartPad, $maxYearCount, $yearCount) {
                        $x = $yearCount > 1
                            ? $chartPad + ($index * (($chartWidth - ($chartPad * 2)) / ($yearCount - 1)))
                            : $chartWidth / 2;
                        $usableHeight = $chartHeight - ($chartPad * 2);
                        $y = ($chartHeight - $chartPad) - (($year->count / max($maxYearCount, 1)) * $usableHeight);

                        return [
                            'x' => round($x, 2),
                            'y' => round($y, 2),
                            'year' => $year->year_published,
                            'count' => $year->count,
                        ];
                    });
                    $polylinePoints = $yearPoints->map(fn ($point) => $point['x'] . ',' . $point['y'])->implode(' ');
                @endphp
                <div class="trend-line-wrap">
                    <svg class="trend-line-chart" viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}" role="img" aria-label="Research trend by year">
                        <line x1="{{ $chartPad }}" y1="{{ $chartHeight - $chartPad }}" x2="{{ $chartWidth - $chartPad }}" y2="{{ $chartHeight - $chartPad }}" class="trend-axis" />
                        <line x1="{{ $chartPad }}" y1="{{ $chartPad }}" x2="{{ $chartPad }}" y2="{{ $chartHeight - $chartPad }}" class="trend-axis" />
                        @foreach([0.25, 0.5, 0.75, 1] as $guide)
                            @php $guideY = ($chartHeight - $chartPad) - (($chartHeight - ($chartPad * 2)) * $guide); @endphp
                            <line x1="{{ $chartPad }}" y1="{{ $guideY }}" x2="{{ $chartWidth - $chartPad }}" y2="{{ $guideY }}" class="trend-guide" />
                        @endforeach
                        <polyline points="{{ $polylinePoints }}" class="trend-line" />
                        @foreach($yearPoints as $point)
                            <g class="trend-point-group">
                                <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="5" class="trend-point">
                                    <title>{{ $point['year'] }}: {{ $point['count'] }} research{{ $point['count'] == 1 ? '' : 'es' }}</title>
                                </circle>
                                <text x="{{ $point['x'] }}" y="{{ $point['y'] - 12 }}" text-anchor="middle" class="trend-count">{{ $point['count'] }}</text>
                                <text x="{{ $point['x'] }}" y="{{ $chartHeight - 9 }}" text-anchor="middle" class="trend-year">{{ $point['year'] }}</text>
                            </g>
                        @endforeach
                    </svg>
                </div>
            @else
                <div class="empty-sm">No yearly trend data yet</div>
            @endif
        </div>
    </div>

    <div class="admin-card col-span-2">
        <div class="admin-card-header">
            <h3>Recently Approved</h3>
            <a href="{{ route('admin.researches', ['status' => 'approved']) }}" class="btn btn-sm btn-outline">View All</a>
        </div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr><th>Title</th><th>Author</th><th>Department</th><th>Year</th><th>Views</th></tr>
                </thead>
                <tbody>
                    @foreach($recentResearches as $r)
                    <tr>
                        <td><a href="{{ route('admin.research.show', $r) }}" class="table-link">{{ Str::limit($r->title, 50) }}</a></td>
                        <td>{{ $r->author_name }}</td>
                        <td>{{ Str::limit($r->department, 30) }}</td>
                        <td>{{ $r->year_published }}</td>
                        <td>{{ number_format($r->view_count) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.analytics-panel-card{background:linear-gradient(180deg,#ffffff 0%,#fcfaff 100%);border:1px solid #eadff8;box-shadow:0 14px 34px rgba(75,32,125,.06);overflow:hidden}
.analytics-panel-card .admin-card-header{padding:22px 24px 16px;border-bottom:1px solid rgba(125,97,175,.12)}
.analytics-panel-card .admin-card-header h3{margin:0;color:#2d124f;font-size:18px}
.analytics-panel-body{padding:20px 24px 24px}
.top-info small{display:block;margin-top:3px;color:#8f80aa;font-size:11.5px;line-height:1.35}
.trend-line-wrap{width:100%;overflow-x:auto;padding:4px 0}
.trend-line-chart{display:block;width:100%;min-width:520px;height:auto}
.trend-axis{stroke:#d8cbea;stroke-width:2}
.trend-guide{stroke:#efe7fb;stroke-width:1}
.trend-line{fill:none;stroke:#6d28d9;stroke-width:4;stroke-linecap:round;stroke-linejoin:round}
.trend-point{fill:#fff;stroke:#6d28d9;stroke-width:4}
.trend-count{fill:#3b0f7a;font-size:13px;font-weight:800}
.trend-year{fill:#7f7099;font-size:12px;font-weight:700}
.department-card-hint{font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#8f80aa}
.dept-bar-button{width:100%;border:0;background:transparent;text-align:left;font:inherit;cursor:pointer;transition:background .16s ease,box-shadow .16s ease}
.dept-bar-button:hover{background:#fbf9ff;box-shadow:inset 3px 0 0 #7c3aed}
.dept-bar-button:focus-visible{outline:3px solid rgba(124,58,237,.18);outline-offset:-3px;background:#fbf9ff}
.department-analytics-overlay{position:fixed;inset:0;z-index:1300;display:none;align-items:center;justify-content:center;padding:20px;background:rgba(19,8,38,.58);backdrop-filter:blur(4px)}
.department-analytics-overlay.is-open{display:flex}
.department-analytics-dialog{width:min(1120px,96vw);max-height:92vh;display:flex;flex-direction:column;background:#fff;border:1px solid rgba(226,213,244,.95);border-radius:18px;box-shadow:0 28px 90px rgba(36,14,70,.32);overflow:hidden}
.da-header{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;padding:22px 26px;background:linear-gradient(180deg,#fcfbff 0%,#f7f3fd 100%);border-bottom:1px solid #eadff8}
.da-kicker{display:block;margin-bottom:6px;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:#8f80aa}
.da-title{margin:0;color:#2d124f;font-size:24px;font-weight:800;letter-spacing:-.02em;line-height:1.18}
.da-subtitle{display:block;margin-top:7px;color:#746585;font-size:13px}
.da-close{width:36px;height:36px;display:inline-flex;align-items:center;justify-content:center;border:1px solid #e2d5f4;border-radius:8px;background:#fff;color:#6b2fa0;font-size:22px;line-height:1;cursor:pointer}
.da-close:hover{background:#f4f0fc}
.da-body{overflow:auto;padding:22px 26px 26px;background:#fff}
.da-filters{display:grid;grid-template-columns:repeat(4,minmax(0,1fr)) auto;gap:12px;align-items:end;margin-bottom:18px;padding:14px;border:1px solid #eee6fa;border-radius:8px;background:#fcfbff}
.da-filter-field{display:flex;flex-direction:column;gap:6px;min-width:0}
.da-filter-field label{font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#8f80aa}
.da-filter-field input,.da-filter-field select{height:38px;border:1.5px solid #e2d5f4;border-radius:8px;background:#fff;color:#24113f;padding:0 11px;font:inherit;font-size:13px;min-width:0}
.da-filter-field input:focus,.da-filter-field select:focus{outline:none;border-color:#7c3aed;box-shadow:0 0 0 3px rgba(124,58,237,.1)}
.da-reset{height:38px;border:1.5px solid #d7c6ee;border-radius:8px;background:#fff;color:#6b2fa0;font-size:12px;font-weight:800;padding:0 14px;cursor:pointer}
.da-reset:hover{background:#f4f0fc}
.da-metric-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:18px}
.da-metric{padding:14px 15px;border:1px solid #eadff8;border-radius:8px;background:#fff;box-shadow:0 6px 16px rgba(75,32,125,.05)}
.da-metric span{display:block;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#8f80aa;margin-bottom:9px}
.da-metric strong{display:block;color:#2d124f;font-size:30px;line-height:1;font-weight:850;letter-spacing:-.04em}
.da-metric small{display:block;margin-top:7px;color:#746585;font-size:12px}
.da-metric.is-approved{border-color:#bbf7d0;background:#f8fffb}
.da-metric.is-pending{border-color:#fde68a;background:#fffdf4}
.da-content-grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(320px,.78fr);gap:18px}
.da-panel{border:1px solid #eadff8;border-radius:8px;background:#fff;overflow:hidden}
.da-panel-wide{grid-column:1/-1}
.da-panel-header{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 16px;background:#faf8ff;border-bottom:1px solid #eee6fa}
.da-panel-header h4{margin:0;color:#2d124f;font-size:14px;font-weight:800}
.da-panel-header span{color:#8f80aa;font-size:12px;font-weight:700}
.da-panel-body{padding:15px 16px}
.da-status-row{display:grid;grid-template-columns:88px 1fr 42px;gap:12px;align-items:center;margin-bottom:13px}
.da-status-row:last-child{margin-bottom:0}
.da-status-label{font-size:12px;font-weight:800;color:#4b345f}
.da-status-track{height:10px;border-radius:999px;background:#f1ebfa;overflow:hidden}
.da-status-fill{height:100%;min-width:0;border-radius:999px;background:#7c3aed;transition:width .2s ease}
.da-status-fill.is-approved{background:#16a34a}
.da-status-fill.is-pending{background:#d97706}
.da-status-fill.is-rejected{background:#dc2626}
.da-status-fill.is-archived{background:#64748b}
.da-status-count{text-align:right;color:#2d124f;font-size:12px;font-weight:800}
.da-recent-list{display:grid;gap:10px}
.da-recent-item{display:grid;grid-template-columns:1fr auto;gap:10px;padding:11px 0;border-bottom:1px solid #f2ecfa}
.da-recent-item:last-child{border-bottom:none;padding-bottom:0}
.da-recent-title{color:#2d124f;font-size:13px;font-weight:800;text-decoration:none;line-height:1.35}
.da-recent-title:hover{color:#6b2fa0;text-decoration:underline}
.da-recent-meta{display:block;margin-top:4px;color:#746585;font-size:12px;line-height:1.45}
.da-status-pill{display:inline-flex;align-items:center;height:24px;padding:0 9px;border-radius:999px;font-size:10px;font-weight:900;text-transform:uppercase;letter-spacing:.05em;background:#f1ebfa;color:#6b2fa0;white-space:nowrap}
.da-status-pill.is-approved{background:#dcfce7;color:#166534}
.da-status-pill.is-pending{background:#fef3c7;color:#92400e}
.da-status-pill.is-rejected{background:#fee2e2;color:#991b1b}
.da-status-pill.is-archived{background:#e2e8f0;color:#334155}
.da-timeline{position:relative;display:grid;gap:0}
.da-timeline-item{position:relative;display:grid;grid-template-columns:22px 1fr;gap:10px;padding:0 0 15px}
.da-timeline-item::before{content:"";position:absolute;left:6px;top:15px;bottom:0;width:2px;background:#eee6fa}
.da-timeline-item:last-child{padding-bottom:0}
.da-timeline-item:last-child::before{display:none}
.da-timeline-dot{width:14px;height:14px;margin-top:2px;border-radius:50%;background:#7c3aed;box-shadow:0 0 0 4px #f1ebfa}
.da-timeline-dot.is-approved{background:#16a34a;box-shadow:0 0 0 4px #dcfce7}
.da-timeline-dot.is-pending,.da-timeline-dot.is-submitted{background:#d97706;box-shadow:0 0 0 4px #fef3c7}
.da-timeline-dot.is-rejected{background:#dc2626;box-shadow:0 0 0 4px #fee2e2}
.da-timeline-dot.is-archived{background:#64748b;box-shadow:0 0 0 4px #e2e8f0}
.da-timeline-content strong{display:block;color:#2d124f;font-size:13px;line-height:1.35}
.da-timeline-content span{display:block;margin-top:3px;color:#746585;font-size:12px;line-height:1.45}
.da-actions{display:flex;justify-content:flex-end;margin-top:18px}
.da-open-records{display:inline-flex;align-items:center;justify-content:center;height:38px;padding:0 15px;border-radius:8px;background:#5b21b6;color:#fff;text-decoration:none;font-size:12px;font-weight:900}
.da-open-records:hover{background:#4c1d95;color:#fff}
.da-empty{padding:18px;border:1px dashed #e2d5f4;border-radius:8px;background:#fcfbff;color:#8f80aa;text-align:center;font-size:13px;font-weight:700}
@media (max-width: 1024px){.da-filters{grid-template-columns:repeat(2,minmax(0,1fr))}.da-content-grid{grid-template-columns:1fr}.da-metric-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media (max-width: 768px){.analytics-panel-card .admin-card-header{padding:18px 18px 14px}.analytics-panel-body{padding:16px 18px 18px}.department-analytics-overlay{padding:10px}.department-analytics-dialog{max-height:96vh;border-radius:12px}.da-header,.da-body{padding:18px}.da-title{font-size:20px}.da-filters{grid-template-columns:1fr}.da-content-grid{gap:14px}}
@media (max-width: 640px){.da-metric-grid{grid-template-columns:1fr}.da-status-row{grid-template-columns:76px 1fr 34px}.da-recent-item{grid-template-columns:1fr}.da-status-pill{width:max-content}.department-card-hint{display:none}}
</style>

<!-- Stat Detail Modal -->
<div id="statModal" class="modal-overlay" style="display:none;">
    <div class="modal-box" style="width:800px; max-width:95vw; max-height:85vh; overflow-y:auto;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
            <h3 id="modalTitle" style="font-family:var(--font-head); color:var(--purple-deep);">Details</h3>
            <div style="display:flex; gap:8px;">
                <button onclick="printModal()" class="btn btn-outline btn-sm">🖨 Print</button>
                <button onclick="closeModal()" style="background:none; border:none; font-size:22px; cursor:pointer; color:var(--muted);">×</button>
            </div>
        </div>
        <div id="modalBody"></div>
    </div>
</div>

<!-- Department Analytics Modal -->
<div id="departmentAnalyticsModal" class="department-analytics-overlay" aria-hidden="true">
    <div class="department-analytics-dialog" role="dialog" aria-modal="true" aria-labelledby="departmentAnalyticsTitle">
        <div class="da-header">
            <div>
                <span class="da-kicker">Department Analytics</span>
                <h3 id="departmentAnalyticsTitle" class="da-title">Department Summary</h3>
                <span id="departmentAnalyticsSubtitle" class="da-subtitle">0 records</span>
            </div>
            <button type="button" class="da-close" onclick="closeDepartmentAnalyticsModal()" aria-label="Close department analytics">&times;</button>
        </div>
        <div class="da-body">
            <div class="da-filters">
                <div class="da-filter-field">
                    <label for="daDateFrom">Date From</label>
                    <input type="date" id="daDateFrom" data-department-filter>
                </div>
                <div class="da-filter-field">
                    <label for="daDateTo">Date To</label>
                    <input type="date" id="daDateTo" data-department-filter>
                </div>
                <div class="da-filter-field">
                    <label for="daCategory">Category</label>
                    <select id="daCategory" data-department-filter>
                        <option value="">All categories</option>
                    </select>
                </div>
                <div class="da-filter-field">
                    <label for="daStatus">Status</label>
                    <select id="daStatus" data-department-filter>
                        <option value="">All statuses</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                        <option value="archived">Archived</option>
                    </select>
                </div>
                <button type="button" class="da-reset" onclick="resetDepartmentAnalyticsFilters()">Reset</button>
            </div>

            <div class="da-metric-grid" id="departmentMetricGrid"></div>

            <div class="da-content-grid">
                <div class="da-panel">
                    <div class="da-panel-header">
                        <h4>Recent Submissions</h4>
                        <span id="departmentRecentCount">Latest uploads</span>
                    </div>
                    <div class="da-panel-body" id="departmentRecentList"></div>
                </div>

                <div class="da-panel">
                    <div class="da-panel-header">
                        <h4>Status Distribution</h4>
                        <span id="departmentDistributionCount">0 records</span>
                    </div>
                    <div class="da-panel-body" id="departmentStatusDistribution"></div>
                </div>

                <div class="da-panel da-panel-wide">
                    <div class="da-panel-header">
                        <h4>Activity Timeline</h4>
                        <span id="departmentTimelineCount">Recent actions</span>
                    </div>
                    <div class="da-panel-body" id="departmentTimeline"></div>
                </div>
            </div>

            <div class="da-actions">
                <a href="#" id="departmentOpenRecordsLink" class="da-open-records">Open Research Records</a>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="modal-overlay" style="display:none">
    <div class="modal-box">
        <h3>Reject Research</h3>
        <p>Please provide a reason for rejection:</p>
        <form id="rejectForm" method="POST">
            @csrf
            <textarea name="reason" rows="4" placeholder="Explain why this submission is being rejected..." required style="width:100%;padding:10px;border:2px solid #e8dff5;border-radius:6px;margin:12px 0;font-size:14px;"></textarea>
            <div style="display:flex;gap:10px;justify-content:flex-end">
                <button type="button" class="btn btn-ghost" onclick="closeRejectModal()">Cancel</button>
                <button type="submit" class="btn btn-red">Reject</button>
            </div>
        </form>
    </div>
</div>

@php
$allResearchesJson = $allResearches->map(fn($r) => ['title' => $r->title, 'author' => $r->author_name, 'department' => $r->department, 'status' => $r->status, 'year' => $r->year_published]);
$allUsersJson = $allUsers->map(fn($u) => ['name' => $u->name, 'email' => $u->email, 'role' => $u->role, 'department' => $u->department, 'joined' => $u->created_at->format('M d, Y')]);
$pendingJson = $allResearches->where('status','pending')->values()->map(fn($r) => ['title' => $r->title, 'author' => $r->author_name, 'department' => $r->department, 'year' => $r->year_published]);
$approvedJson = $allResearches->where('status','approved')->values()->map(fn($r) => ['title' => $r->title, 'author' => $r->author_name, 'department' => $r->department, 'year' => $r->year_published]);
$rejectedJson = $allResearches->where('status','rejected')->values()->map(fn($r) => ['title' => $r->title, 'author' => $r->author_name, 'department' => $r->department, 'year' => $r->year_published]);
@endphp

@endsection

@push('scripts')
<script>
const departmentAnalyticsData = @json($departmentAnalytics);
const departmentAnalyticsMap = new Map(departmentAnalyticsData.map(item => [item.department, item]));
const adminResearchesUrl = @json(route('admin.researches'));
let activeDepartmentAnalytics = null;

const departmentStatusMeta = {
    pending: { label: 'Pending', className: 'is-pending' },
    approved: { label: 'Approved', className: 'is-approved' },
    rejected: { label: 'Rejected', className: 'is-rejected' },
    archived: { label: 'Archived', className: 'is-archived' },
    submitted: { label: 'Submitted', className: 'is-submitted' },
    updated: { label: 'Updated', className: 'is-updated' },
};

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, function(character) {
        return {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;',
        }[character];
    });
}

function formatNumber(value) {
    return Number(value || 0).toLocaleString();
}

function openDepartmentAnalyticsModal(department) {
    activeDepartmentAnalytics = departmentAnalyticsMap.get(department);

    if (!activeDepartmentAnalytics) {
        return;
    }

    const modal = document.getElementById('departmentAnalyticsModal');
    document.getElementById('departmentAnalyticsTitle').textContent = activeDepartmentAnalytics.department;
    document.getElementById('departmentAnalyticsSubtitle').textContent = `${formatNumber(activeDepartmentAnalytics.total)} research record${activeDepartmentAnalytics.total === 1 ? '' : 's'}`;

    populateDepartmentCategoryFilter(activeDepartmentAnalytics.records || []);
    resetDepartmentAnalyticsFilters(false);
    renderDepartmentAnalytics();

    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
}

function closeDepartmentAnalyticsModal() {
    const modal = document.getElementById('departmentAnalyticsModal');
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    activeDepartmentAnalytics = null;
}

function populateDepartmentCategoryFilter(records) {
    const select = document.getElementById('daCategory');
    const categories = new Map();

    records.forEach(record => {
        if (record.category) {
            categories.set(record.category, record.category_label || record.category);
        }
    });

    const options = Array.from(categories.entries())
        .sort((a, b) => a[1].localeCompare(b[1]))
        .map(([value, label]) => `<option value="${escapeHtml(value)}">${escapeHtml(label)}</option>`)
        .join('');

    select.innerHTML = '<option value="">All categories</option>' + options;
}

function resetDepartmentAnalyticsFilters(shouldRender = true) {
    ['daDateFrom', 'daDateTo', 'daCategory', 'daStatus'].forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.value = '';
        }
    });

    if (shouldRender) {
        renderDepartmentAnalytics();
    }
}

function filteredDepartmentRecords() {
    if (!activeDepartmentAnalytics) {
        return [];
    }

    const fromDate = document.getElementById('daDateFrom').value;
    const toDate = document.getElementById('daDateTo').value;
    const category = document.getElementById('daCategory').value;
    const status = document.getElementById('daStatus').value;

    return (activeDepartmentAnalytics.records || []).filter(record => {
        if (fromDate && (!record.created_date || record.created_date < fromDate)) {
            return false;
        }

        if (toDate && (!record.created_date || record.created_date > toDate)) {
            return false;
        }

        if (category && record.category !== category) {
            return false;
        }

        if (status && record.status !== status) {
            return false;
        }

        return true;
    });
}

function countDepartmentStatuses(records) {
    return records.reduce((counts, record) => {
        const status = record.status || 'pending';
        counts[status] = (counts[status] || 0) + 1;
        return counts;
    }, { pending: 0, approved: 0, rejected: 0, archived: 0 });
}

function renderDepartmentAnalytics() {
    if (!activeDepartmentAnalytics) {
        return;
    }

    const records = filteredDepartmentRecords();
    const counts = countDepartmentStatuses(records);

    renderDepartmentMetrics(records, counts);
    renderDepartmentDistribution(records, counts);
    renderDepartmentRecentSubmissions(records);
    renderDepartmentTimeline(records);
    updateDepartmentRecordsLink();
}

function renderDepartmentMetrics(records, counts) {
    const metrics = [
        { label: 'Total Researches', value: records.length, note: 'Matching records' },
        { label: 'Submitted', value: records.length, note: 'All uploads' },
        { label: 'Approved', value: counts.approved, note: 'Ready for viewing', className: 'is-approved' },
        { label: 'Pending', value: counts.pending, note: 'Waiting for review', className: 'is-pending' },
    ];

    document.getElementById('departmentMetricGrid').innerHTML = metrics.map(metric => `
        <div class="da-metric ${metric.className || ''}">
            <span>${escapeHtml(metric.label)}</span>
            <strong>${formatNumber(metric.value)}</strong>
            <small>${escapeHtml(metric.note)}</small>
        </div>
    `).join('');
}

function renderDepartmentDistribution(records, counts) {
    const total = records.length;
    const maxCount = Math.max(1, ...Object.values(counts));
    const statuses = ['pending', 'approved', 'rejected', 'archived'];

    document.getElementById('departmentDistributionCount').textContent = `${formatNumber(total)} record${total === 1 ? '' : 's'}`;

    if (total === 0) {
        document.getElementById('departmentStatusDistribution').innerHTML = '<div class="da-empty">No records match the selected filters.</div>';
        return;
    }

    document.getElementById('departmentStatusDistribution').innerHTML = statuses.map(status => {
        const meta = departmentStatusMeta[status];
        const count = counts[status] || 0;
        const width = count > 0 ? Math.max(4, (count / maxCount) * 100) : 0;

        return `
            <div class="da-status-row">
                <span class="da-status-label">${meta.label}</span>
                <span class="da-status-track" aria-hidden="true">
                    <span class="da-status-fill ${meta.className}" style="width:${width}%"></span>
                </span>
                <span class="da-status-count">${formatNumber(count)}</span>
            </div>
        `;
    }).join('');
}

function renderDepartmentRecentSubmissions(records) {
    const recent = [...records]
        .sort((a, b) => (b.created_sort || 0) - (a.created_sort || 0))
        .slice(0, 6);

    document.getElementById('departmentRecentCount').textContent = `${formatNumber(recent.length)} shown`;

    if (recent.length === 0) {
        document.getElementById('departmentRecentList').innerHTML = '<div class="da-empty">No recent submissions match the selected filters.</div>';
        return;
    }

    document.getElementById('departmentRecentList').innerHTML = `
        <div class="da-recent-list">
            ${recent.map(record => {
                const statusMeta = departmentStatusMeta[record.status] || departmentStatusMeta.pending;

                return `
                    <div class="da-recent-item">
                        <div>
                            <a href="${escapeHtml(record.url)}" class="da-recent-title">${escapeHtml(record.title)}</a>
                            <span class="da-recent-meta">${escapeHtml(record.author)} | ${escapeHtml(record.category_label)} | ${escapeHtml(record.created_at || 'No date recorded')}</span>
                        </div>
                        <span class="da-status-pill ${statusMeta.className}">${statusMeta.label}</span>
                    </div>
                `;
            }).join('')}
        </div>
    `;
}

function buildDepartmentTimeline(records) {
    const timeline = [];

    records.forEach(record => {
        if (record.created_sort) {
            timeline.push({
                type: 'submitted',
                label: 'Submitted',
                title: record.title,
                time: record.created_at,
                sort: record.created_sort,
            });
        }

        if (record.approved_sort) {
            timeline.push({
                type: 'approved',
                label: 'Approved',
                title: record.title,
                time: record.approved_at,
                sort: record.approved_sort,
            });
        }

        if (['rejected', 'archived'].includes(record.status) && record.updated_sort && record.updated_sort !== record.created_sort) {
            const meta = departmentStatusMeta[record.status];
            timeline.push({
                type: record.status,
                label: meta.label,
                title: record.title,
                time: record.updated_at,
                sort: record.updated_sort,
            });
        }
    });

    return timeline
        .filter(event => event.sort)
        .sort((a, b) => b.sort - a.sort)
        .slice(0, 8);
}

function renderDepartmentTimeline(records) {
    const timeline = buildDepartmentTimeline(records);
    document.getElementById('departmentTimelineCount').textContent = `${formatNumber(timeline.length)} shown`;

    if (timeline.length === 0) {
        document.getElementById('departmentTimeline').innerHTML = '<div class="da-empty">No activity found for the selected filters.</div>';
        return;
    }

    document.getElementById('departmentTimeline').innerHTML = `
        <div class="da-timeline">
            ${timeline.map(event => {
                const meta = departmentStatusMeta[event.type] || departmentStatusMeta.updated;

                return `
                    <div class="da-timeline-item">
                        <span class="da-timeline-dot ${meta.className}" aria-hidden="true"></span>
                        <div class="da-timeline-content">
                            <strong>${escapeHtml(event.label)}: ${escapeHtml(event.title)}</strong>
                            <span>${escapeHtml(event.time || 'No date recorded')}</span>
                        </div>
                    </div>
                `;
            }).join('')}
        </div>
    `;
}

function updateDepartmentRecordsLink() {
    if (!activeDepartmentAnalytics) {
        return;
    }

    const status = document.getElementById('daStatus').value;
    const url = new URL(adminResearchesUrl, window.location.origin);

    if (activeDepartmentAnalytics.department !== 'Unassigned Department') {
        url.searchParams.set('department', activeDepartmentAnalytics.department);
    }

    if (status) {
        url.searchParams.set('status', status);
    }

    document.getElementById('departmentOpenRecordsLink').href = url.toString();
}

document.querySelectorAll('[data-department-filter]').forEach(element => {
    element.addEventListener('change', renderDepartmentAnalytics);
});

document.getElementById('departmentAnalyticsModal').addEventListener('click', function(event) {
    if (event.target === this) {
        closeDepartmentAnalyticsModal();
    }
});

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape' && document.getElementById('departmentAnalyticsModal').classList.contains('is-open')) {
        closeDepartmentAnalyticsModal();
    }
});

const modalData = {
    researches: {
        title: 'All Researches ({{ $stats["total_researches"] }})',
        headers: ['Title','Author','Department','Status','Year'],
        rows: @json($allResearchesJson),
        keys: ['title','author','department','status','year'],
        printable: true
    },
    users: {
        title: 'All Users ({{ $stats["total_users"] }})',
        headers: ['Name','Email','Role','Department','Joined'],
        rows: @json($allUsersJson),
        keys: ['name','email','role','department','joined'],
        printable: true
    },
    pending: {
        title: 'Pending Review ({{ $stats["pending"] }})',
        headers: ['Title','Author','Department','Year'],
        rows: @json($pendingJson),
        keys: ['title','author','department','year'],
        printable: false
    },
    approved: {
        title: 'Approved ({{ $stats["approved"] }})',
        headers: ['Title','Author','Department','Year'],
        rows: @json($approvedJson),
        keys: ['title','author','department','year'],
        printable: false
    },
    rejected: {
        title: 'Rejected ({{ $stats["rejected"] }})',
        headers: ['Title','Author','Department','Year'],
        rows: @json($rejectedJson),
        keys: ['title','author','department','year'],
        printable: false
    },
};

let currentModal = null;

function openModal(type) {
    currentModal = type;
    const data = modalData[type];
    document.getElementById('modalTitle').textContent = data.title;

    let html = '<div style="overflow-x:auto;"><table style="width:100%;border-collapse:collapse;font-size:13px;">';
    html += '<thead><tr>';
    data.headers.forEach(h => {
        html += `<th style="text-align:left;padding:10px 12px;background:var(--purple-ghost);color:var(--purple-deep);font-size:11px;text-transform:uppercase;letter-spacing:.5px;border-bottom:2px solid var(--border);">${h}</th>`;
    });
    html += '</tr></thead><tbody>';

    if (data.rows.length === 0) {
        html += `<tr><td colspan="${data.headers.length}" style="text-align:center;padding:24px;color:var(--muted);">No data found.</td></tr>`;
    } else {
        data.rows.forEach(row => {
            html += '<tr>';
            data.keys.forEach(key => {
                const val = row[key] || '—';
                html += `<td style="padding:10px 12px;border-bottom:1px solid #f3eefb;vertical-align:middle;">${val}</td>`;
            });
            html += '</tr>';
        });
    }

    html += '</tbody></table></div>';
    document.getElementById('modalBody').innerHTML = html;
    document.getElementById('statModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('statModal').style.display = 'none';
    currentModal = null;
}

function printModal() {
    if (!currentModal) return;
    const data = modalData[currentModal];
    let rows = '';
    data.rows.forEach(row => {
        rows += '<tr>';
        data.keys.forEach(key => {
            rows += `<td style="padding:8px 10px;border:1px solid #ddd;">${row[key] || '—'}</td>`;
        });
        rows += '</tr>';
    });

    let headers = '';
    data.headers.forEach(h => {
        headers += `<th style="padding:8px 10px;background:#f0e8f8;border:1px solid #ddd;text-align:left;">${h}</th>`;
    });

    const content = `
        <html><head><title>${data.title}</title>
        <style>
            body{font-family:Arial,sans-serif;padding:30px;}
            h1{color:#52297a;}
            table{width:100%;border-collapse:collapse;font-size:13px;}
            th{background:#f0e8f8;color:#52297a;}
            p{color:#666;margin-bottom:20px;}
        </style>
        </head><body>
        <h1>Ube Repository</h1>
        <h2 style="color:#52297a;">${data.title}</h2>
        <p>Printed on: ${new Date().toLocaleDateString('en-US',{year:'numeric',month:'long',day:'numeric'})}</p>
        <table><thead><tr>${headers}</tr></thead><tbody>${rows}</tbody></table>
        </body></html>
    `;

    const blob = new Blob([content], {type: 'text/html'});
    const url = URL.createObjectURL(blob);
    const iframe = document.createElement('iframe');
    iframe.style.cssText = 'position:fixed;top:0;left:0;width:0;height:0;border:none;visibility:hidden;';
    iframe.src = url;
    document.body.appendChild(iframe);
    iframe.onload = function() {
        iframe.contentWindow.print();
        iframe.contentWindow.onafterprint = function() {
            document.body.removeChild(iframe);
            URL.revokeObjectURL(url);
        };
    };
}

function openRejectModal(id) {
    document.getElementById('rejectForm').action = '/admin/researches/' + id + '/reject';
    document.getElementById('rejectModal').style.display = 'flex';
}
function closeRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
}
</script>
@endpush
