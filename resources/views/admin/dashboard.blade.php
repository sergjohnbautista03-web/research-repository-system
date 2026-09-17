@extends('layouts.admin')
@section('title', 'Research Analytics')
@section('page-title', 'Research Analytics')

@section('content')
@php
    $summaryCards = [
        [
            'key' => 'papers',
            'label' => 'Total Research Papers',
            'value' => $analyticsSummary['total_research_papers'],
            'note' => $analyticsSummary['report_scope'],
            'class' => 'is-paper',
            'icon' => 'paper',
            'description' => 'Review all research records behind this total, including approvals, department filters, authors, and publication details.',
            'action_label' => 'Open Research Papers',
            'action_url' => route('admin.researches'),
        ],
        [
            'key' => 'views',
            'label' => 'Total Views',
            'value' => $analyticsSummary['total_views'],
            'note' => $analyticsSummary['year_range'],
            'class' => 'is-view',
            'icon' => 'view',
            'description' => 'See how repository engagement is distributed across departments and publication years for the current analytics range.',
            'action_label' => 'Review Analytics Chart',
            'action_url' => route('admin.dashboard') . '#research-views-panel',
        ],
        [
            'key' => 'citations',
            'label' => 'Total Copy Citations',
            'value' => $analyticsSummary['total_copy_citations'],
            'note' => 'Citation activity',
            'class' => 'is-copy',
            'icon' => 'copy',
            'description' => 'Track citation-copy activity to understand which research papers are being referenced by readers.',
            'action_label' => 'Open Citation Reports',
            'action_url' => route('admin.reports'),
        ],
        [
            'key' => 'researchers',
            'label' => 'Total Researchers',
            'value' => $analyticsSummary['total_researchers'],
            'note' => 'Researcher accounts',
            'class' => 'is-researcher',
            'icon' => 'researcher',
            'description' => 'Manage faculty and student researcher accounts that can submit and maintain scholarly work in the repository.',
            'action_label' => 'Manage Researchers',
            'action_url' => route('admin.users', ['role' => 'researcher']),
        ],
    ];

    $summaryCardDetails = collect($summaryCards)->mapWithKeys(fn ($card) => [
        $card['key'] => [
            'label' => $card['label'],
            'value' => $card['value'],
            'note' => $card['note'],
            'description' => $card['description'],
            'actionLabel' => $card['action_label'],
            'actionUrl' => $card['action_url'],
        ],
    ]);
@endphp

<div class="ra-toolbar">
    <div class="ra-toolbar-copy">
        <span class="ra-eyebrow">Admin Analytics</span>
        <h2>Research Repository Dashboard</h2>
        <p>{{ $analyticsSummary['report_scope'] }} | {{ $analyticsSummary['year_range'] }}</p>
    </div>
    <button type="button" class="ra-print-btn" onclick="printAnalyticsReport()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="6 9 6 2 18 2 18 9"></polyline>
            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
            <rect x="6" y="14" width="12" height="8"></rect>
        </svg>
        Print Report
    </button>
</div>

<div class="ra-summary-grid">
    @foreach($summaryCards as $card)
        <button
            type="button"
            class="ra-summary-card {{ $card['class'] }}"
            data-summary-key="{{ $card['key'] }}"
            onclick="openSummaryCardModal('{{ $card['key'] }}')"
            aria-haspopup="dialog"
            aria-controls="summaryCardModal"
            aria-label="Open {{ $card['label'] }} details">
            <div class="ra-summary-copy">
                <span>{{ $card['label'] }}</span>
                <strong>{{ number_format($card['value']) }}</strong>
                <small>{{ $card['note'] }}</small>
                <span class="ra-summary-action">
                    View details
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M5 12h14"></path>
                        <path d="m12 5 7 7-7 7"></path>
                    </svg>
                </span>
            </div>
            <div class="ra-summary-icon" aria-hidden="true">
                @if($card['icon'] === 'paper')
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                @elseif($card['icon'] === 'view')
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                @elseif($card['icon'] === 'copy')
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="9" y="9" width="13" height="13" rx="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                @else
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                @endif
            </div>
        </button>
    @endforeach
</div>

<div class="ra-dashboard-grid">
    <section id="research-views-panel" class="ra-panel ra-chart-panel" tabindex="-1">
        <div class="ra-panel-header">
            <div>
                <h3>Yearly Research Views per Department</h3>
                <span>X-axis: years | Y-axis: views</span>
            </div>
        </div>

        <div class="ra-chart-wrap">
            <div class="ra-y-title">Views</div>
            <div class="ra-y-axis" aria-hidden="true">
                @foreach($analyticsYAxisLabels as $label)
                    <span>{{ number_format($label) }}</span>
                @endforeach
            </div>

            <div class="ra-plot">
                <div class="ra-grid-lines" aria-hidden="true">
                    <span></span><span></span><span></span><span></span><span></span>
                </div>

                <div class="ra-year-groups">
                    @foreach($analyticsChartData as $yearGroup)
                        <div class="ra-year-group">
                            <div class="ra-bars">
                                @foreach($yearGroup['departments'] as $departmentData)
                                    @php
                                        $barHeight = $analyticsChartMaxViews > 0
                                            ? (($departmentData['views'] / $analyticsChartMaxViews) * 100)
                                            : 0;
                                        $barHeight = $departmentData['views'] > 0 ? max(4, $barHeight) : 2;
                                    @endphp
                                    <button
                                        type="button"
                                        class="ra-bar {{ $departmentData['views'] == 0 ? 'is-empty' : '' }}"
                                        style="height: {{ round($barHeight, 2) }}%; --bar-color: {{ $departmentData['color'] }};"
                                        data-department="{{ $departmentData['department'] }}"
                                        data-year="{{ $yearGroup['year'] }}"
                                        onclick="openAnalyticsDetail(this.dataset.department, this.dataset.year)"
                                        title="{{ $departmentData['code'] }} {{ $yearGroup['year'] }}: {{ number_format($departmentData['views']) }} views">
                                        <span class="ra-sr-only">{{ $departmentData['department'] }} {{ $yearGroup['year'] }} {{ number_format($departmentData['views']) }} views</span>
                                    </button>
                                @endforeach
                            </div>
                            <span class="ra-year-label">{{ $yearGroup['year'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="ra-x-title">Years</div>
    </section>

    <aside class="ra-panel ra-side-panel">
        <div class="ra-panel-header">
            <div>
                <h3>Departments</h3>
                <span>{{ $analyticsDepartments->count() }} tracked</span>
            </div>
        </div>
        <div class="ra-legend-list">
            @foreach($analyticsDepartments as $department)
                <div class="ra-legend-row">
                    <span class="ra-legend-swatch" style="background: {{ $department['color'] }}"></span>
                    <strong>{{ $department['code'] }}</strong>
                    <span>{{ $department['name'] }}</span>
                </div>
            @endforeach
        </div>
    </aside>

    <section class="ra-panel ra-wide-panel">
        <div class="ra-panel-header">
            <div>
                <h3>Top Viewed Research Papers</h3>
                <span>Approved records</span>
            </div>
            <a href="{{ route('admin.researches', ['status' => 'approved']) }}" class="ra-link-btn">View All</a>
        </div>
        <div class="ra-table-wrap">
            <table class="ra-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Department</th>
                        <th>Year</th>
                        <th>Views</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topResearches as $research)
                        <tr>
                            <td>
                                <a href="{{ route('admin.research.show', $research) }}" class="ra-table-link">{{ $research->title }}</a>
                                <span>{{ $research->author_name }}</span>
                            </td>
                            <td>{{ $research->department ?: 'Unassigned Department' }}</td>
                            <td>{{ $research->year_published ?: 'N/A' }}</td>
                            <td>{{ number_format((int) $research->view_count) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="ra-empty-cell">No approved research activity yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

<div id="summaryCardModal" class="ra-modal ra-summary-modal" aria-hidden="true">
    <div class="ra-modal-dialog ra-summary-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="summaryCardModalTitle" aria-describedby="summaryCardModalDescription" tabindex="-1">
        <div class="ra-modal-header">
            <div>
                <span class="ra-eyebrow">Dashboard Summary</span>
                <h3 id="summaryCardModalTitle">Summary Detail</h3>
                <p id="summaryCardModalDescription">Metric description</p>
            </div>
            <button type="button" class="ra-close-btn" onclick="closeSummaryCardModal()" aria-label="Close summary detail">
                &times;
            </button>
        </div>
        <div class="ra-summary-modal-body">
            <div class="ra-summary-modal-metric">
                <span id="summaryCardModalNote">Current scope</span>
                <strong id="summaryCardModalValue">0</strong>
            </div>
            <div class="ra-summary-modal-actions">
                <button type="button" class="ra-modal-secondary-btn" onclick="closeSummaryCardModal()">Close</button>
                <a id="summaryCardModalAction" href="#" class="ra-modal-primary-btn">Open Page</a>
            </div>
        </div>
    </div>
</div>

<div id="analyticsDetailModal" class="ra-modal" aria-hidden="true">
    <div class="ra-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="analyticsDetailTitle" tabindex="-1">
        <div class="ra-modal-header">
            <div>
                <span class="ra-eyebrow">Department-Year Detail</span>
                <h3 id="analyticsDetailTitle">Analytics Detail</h3>
                <p id="analyticsDetailSubtitle">Department and year</p>
            </div>
            <button type="button" class="ra-close-btn" onclick="closeAnalyticsDetailModal()" aria-label="Close analytics detail">
                &times;
            </button>
        </div>
        <div class="ra-modal-body">
            <div id="analyticsDetailMetrics" class="ra-detail-metrics"></div>
            <div class="ra-modal-table-wrap">
                <table class="ra-table ra-modal-table">
                    <thead>
                        <tr>
                            <th>Research Paper</th>
                            <th>Author</th>
                            <th>Type</th>
                            <th>Views</th>
                        </tr>
                    </thead>
                    <tbody id="analyticsDetailPapers"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.ra-toolbar{display:flex;align-items:flex-end;justify-content:space-between;gap:16px;margin-bottom:20px;padding:20px 22px;background:#fff;border:1px solid rgba(109,40,217,.1);border-radius:16px;box-shadow:0 8px 28px rgba(46,16,101,.06)}
.ra-toolbar-copy{min-width:0}
.ra-eyebrow{display:block;margin-bottom:6px;color:#7a5ca8;font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:.09em}
.ra-toolbar h2{margin:0;color:#1f1235;font-size:24px;font-weight:850;letter-spacing:0;line-height:1.2}
.ra-toolbar p{margin:7px 0 0;color:#6d5d85;font-size:13px}
.ra-print-btn,.ra-link-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;height:40px;border-radius:10px;border:1px solid #d8c8ef;background:#fff;color:#4f1d7a;font:inherit;font-size:13px;font-weight:850;text-decoration:none;white-space:nowrap;cursor:pointer;transition:background .16s ease,border-color .16s ease,transform .16s ease}
.ra-print-btn{padding:0 16px;background:#43216f;color:#fff;border-color:#43216f;box-shadow:0 10px 22px rgba(67,33,111,.18)}
.ra-print-btn:hover{background:#331653;transform:translateY(-1px)}
.ra-print-btn svg,.ra-link-btn svg{width:16px;height:16px}
.ra-link-btn{height:34px;padding:0 13px}
.ra-link-btn:hover{background:#f5f1fb;border-color:#bda6df;color:#3b0f63}
.ra-summary-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:18px}
.ra-summary-card{appearance:none;width:100%;position:relative;min-height:142px;display:flex;align-items:flex-start;justify-content:space-between;gap:14px;padding:20px;border-radius:16px;background:#fff;border:1px solid rgba(109,40,217,.1);box-shadow:0 10px 26px rgba(46,16,101,.07);overflow:hidden;text-align:left;font:inherit;color:inherit;cursor:pointer;transition:transform .18s ease,box-shadow .18s ease,border-color .18s ease,background .18s ease}
.ra-summary-card::before{content:"";position:absolute;inset:0 0 auto;height:4px;background:var(--accent,#6d28d9)}
.ra-summary-card:hover,.ra-summary-card:focus-visible{transform:translateY(-4px);border-color:color-mix(in srgb,var(--accent,#6d28d9) 34%,#fff);box-shadow:0 18px 40px rgba(46,16,101,.12)}
.ra-summary-card:focus-visible{outline:3px solid rgba(109,40,217,.22);outline-offset:3px}
.ra-summary-card:active{transform:translateY(-1px)}
.ra-summary-card.is-paper{--accent:#6d28d9}
.ra-summary-card.is-view{--accent:#0f766e}
.ra-summary-card.is-copy{--accent:#b45309}
.ra-summary-card.is-researcher{--accent:#2563eb}
.ra-summary-copy{position:relative;z-index:1;min-width:0}
.ra-summary-copy > span:first-child{display:block;color:#746585;font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:.08em;line-height:1.35}
.ra-summary-copy strong{display:block;margin-top:12px;color:#1f1235;font-size:34px;font-weight:900;line-height:1;letter-spacing:0}
.ra-summary-copy small{display:block;margin-top:9px;color:#786890;font-size:12px;line-height:1.35}
.ra-summary-action{display:inline-flex;align-items:center;gap:6px;margin-top:14px;color:var(--accent,#6d28d9);font-size:12px;font-weight:900;line-height:1.2}
.ra-summary-action svg{width:14px;height:14px;transition:transform .18s ease}
.ra-summary-card:hover .ra-summary-action svg,.ra-summary-card:focus-visible .ra-summary-action svg{transform:translateX(3px)}
.ra-summary-icon{width:46px;height:46px;display:flex;align-items:center;justify-content:center;flex:0 0 auto;border-radius:12px;color:var(--accent,#6d28d9);background:color-mix(in srgb,var(--accent,#6d28d9) 12%,#fff);border:1px solid color-mix(in srgb,var(--accent,#6d28d9) 18%,#fff)}
.ra-summary-icon svg{width:22px;height:22px}
.ra-dashboard-grid{display:grid;grid-template-columns:minmax(0,1fr) 320px;gap:18px}
.ra-panel{background:#fff;border:1px solid rgba(109,40,217,.1);border-radius:16px;box-shadow:0 8px 28px rgba(46,16,101,.06);overflow:hidden}
.ra-panel:focus{outline:3px solid rgba(109,40,217,.18);outline-offset:3px}
.ra-panel-header{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:17px 20px;background:#fbf9ff;border-bottom:1px solid rgba(109,40,217,.08)}
.ra-panel-header h3{margin:0;color:#1f1235;font-size:16px;font-weight:850;letter-spacing:0;line-height:1.25}
.ra-panel-header span{display:block;margin-top:4px;color:#7d6c98;font-size:12px;font-weight:700}
.ra-chart-panel{min-width:0}
.ra-chart-wrap{display:grid;grid-template-columns:24px 54px minmax(0,1fr);gap:8px;padding:22px 22px 8px;min-height:390px}
.ra-y-title{writing-mode:vertical-rl;transform:rotate(180deg);align-self:center;justify-self:center;color:#5b3d8a;font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:.08em}
.ra-y-axis{display:flex;flex-direction:column;justify-content:space-between;align-items:flex-end;padding:2px 0 42px;color:#7d6c98;font-size:11px;font-weight:800}
.ra-plot{position:relative;min-width:0;border-left:1px solid #ddd2ed;border-bottom:1px solid #ddd2ed}
.ra-grid-lines{position:absolute;inset:0 0 42px 0;display:flex;flex-direction:column;justify-content:space-between;pointer-events:none}
.ra-grid-lines span{height:1px;background:#efe9f7}
.ra-year-groups{position:relative;z-index:1;display:grid;grid-template-columns:repeat({{ max(1, $analyticsChartData->count()) }},minmax(86px,1fr));align-items:end;gap:12px;height:100%;min-height:318px;padding:0 12px 0}
.ra-year-group{height:100%;display:grid;grid-template-rows:minmax(0,1fr) 42px;align-items:end;min-width:0}
.ra-bars{height:100%;display:flex;align-items:flex-end;justify-content:center;gap:5px;min-width:0}
.ra-bar{width:14px;min-width:8px;max-width:18px;border:0;border-radius:7px 7px 2px 2px;background:var(--bar-color);box-shadow:0 7px 14px color-mix(in srgb,var(--bar-color) 28%,transparent);cursor:pointer;transition:opacity .16s ease,transform .16s ease,filter .16s ease}
.ra-bar:hover,.ra-bar:focus-visible{opacity:.88;transform:translateY(-2px);filter:saturate(1.1)}
.ra-bar:focus-visible{outline:3px solid rgba(67,33,111,.18);outline-offset:2px}
.ra-bar.is-empty{opacity:.28;box-shadow:none}
.ra-year-label{align-self:start;justify-self:center;padding-top:10px;color:#4f3a68;font-size:12px;font-weight:900}
.ra-x-title{text-align:center;padding:0 20px 18px;color:#5b3d8a;font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:.08em}
.ra-side-panel{min-width:0}
.ra-legend-list{display:grid;gap:0;padding:8px 0}
.ra-legend-row{display:grid;grid-template-columns:14px 48px minmax(0,1fr);gap:10px;align-items:center;padding:11px 18px;border-bottom:1px solid #f1ebfa}
.ra-legend-row:last-child{border-bottom:none}
.ra-legend-swatch{width:12px;height:12px;border-radius:4px}
.ra-legend-row strong{color:#1f1235;font-size:12px;font-weight:900}
.ra-legend-row span:last-child{min-width:0;color:#665276;font-size:12.5px;font-weight:650;line-height:1.35}
.ra-wide-panel{grid-column:1/-1}
.ra-table-wrap,.ra-modal-table-wrap{overflow-x:auto}
.ra-table{width:100%;border-collapse:collapse;font-size:13px}
.ra-table th{text-align:left;padding:11px 16px;background:#fbf9ff;color:#5b3d8a;font-size:10.5px;font-weight:900;text-transform:uppercase;letter-spacing:.06em;border-bottom:1px solid #eadff8;white-space:nowrap}
.ra-table td{padding:13px 16px;border-bottom:1px solid #f1ebfa;color:#2d2440;vertical-align:top}
.ra-table tbody tr:last-child td{border-bottom:none}
.ra-table tbody tr:hover td{background:#fdfbff}
.ra-table-link{display:block;color:#24113f;font-weight:850;text-decoration:none;line-height:1.35;max-width:720px}
.ra-table-link:hover{color:#6d28d9;text-decoration:underline}
.ra-table td span{display:block;margin-top:4px;color:#7d6c98;font-size:12px;line-height:1.35}
.ra-empty-cell{text-align:center;color:#8b7aaa!important;padding:26px!important}
.ra-modal{position:fixed;inset:0;z-index:1400;display:none;align-items:center;justify-content:center;padding:20px;background:rgba(19,8,38,.58);backdrop-filter:blur(4px)}
.ra-modal.is-open{display:flex;animation:ra-modal-fade .18s ease both}
.ra-modal-dialog{width:min(980px,96vw);max-height:92vh;display:flex;flex-direction:column;background:#fff;border:1px solid #e7daf7;border-radius:16px;box-shadow:0 30px 90px rgba(26,6,56,.32);overflow:hidden}
.ra-modal.is-open .ra-modal-dialog{animation:ra-modal-rise .22s cubic-bezier(.2,.8,.2,1) both}
.ra-modal-header{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;padding:22px 24px;background:#fbf9ff;border-bottom:1px solid #eadff8}
.ra-modal-header h3{margin:0;color:#1f1235;font-size:22px;font-weight:900;letter-spacing:0;line-height:1.2}
.ra-modal-header p{margin:7px 0 0;color:#6d5d85;font-size:13px;line-height:1.4}
.ra-close-btn{width:38px;height:38px;display:inline-flex;align-items:center;justify-content:center;border:1px solid #e2d5f4;border-radius:10px;background:#fff;color:#4f1d7a;font-size:24px;line-height:1;cursor:pointer}
.ra-close-btn:hover,.ra-close-btn:focus-visible{background:#f5f1fb;outline:none;box-shadow:0 0 0 3px rgba(109,40,217,.14)}
.ra-modal-body{overflow:auto;padding:20px 24px 24px}
.ra-summary-modal-dialog{width:min(540px,94vw)}
.ra-summary-modal-body{display:grid;gap:18px;padding:20px 24px 24px}
.ra-summary-modal-metric{padding:18px;border:1px solid #eadff8;border-radius:14px;background:linear-gradient(135deg,#fff 0%,#fbf8ff 100%)}
.ra-summary-modal-metric span{display:block;color:#7d6c98;font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:.07em}
.ra-summary-modal-metric strong{display:block;margin-top:9px;color:#1f1235;font-size:36px;font-weight:900;line-height:1}
.ra-summary-modal-actions{display:flex;justify-content:flex-end;gap:10px;flex-wrap:wrap}
.ra-modal-primary-btn,.ra-modal-secondary-btn{min-height:40px;display:inline-flex;align-items:center;justify-content:center;border-radius:10px;padding:0 15px;font:inherit;font-size:13px;font-weight:850;text-decoration:none;cursor:pointer;transition:background .16s ease,border-color .16s ease,transform .16s ease,box-shadow .16s ease}
.ra-modal-primary-btn{border:1px solid #43216f;background:#43216f;color:#fff;box-shadow:0 10px 22px rgba(67,33,111,.18)}
.ra-modal-primary-btn:hover,.ra-modal-primary-btn:focus-visible{background:#331653;transform:translateY(-1px);outline:none;box-shadow:0 14px 28px rgba(67,33,111,.22)}
.ra-modal-secondary-btn{border:1px solid #d8c8ef;background:#fff;color:#4f1d7a}
.ra-modal-secondary-btn:hover,.ra-modal-secondary-btn:focus-visible{background:#f5f1fb;border-color:#bda6df;outline:none}
.ra-detail-metrics{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:18px}
.ra-detail-metric{padding:13px 14px;border:1px solid #eadff8;border-radius:12px;background:#fcfbff}
.ra-detail-metric span{display:block;color:#7d6c98;font-size:10px;font-weight:900;text-transform:uppercase;letter-spacing:.07em}
.ra-detail-metric strong{display:block;margin-top:8px;color:#1f1235;font-size:24px;font-weight:900;line-height:1}
.ra-modal-table .ra-table-link{max-width:430px}
.ra-sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
@keyframes ra-modal-fade{from{opacity:0}to{opacity:1}}
@keyframes ra-modal-rise{from{opacity:0;transform:translateY(14px) scale(.98)}to{opacity:1;transform:translateY(0) scale(1)}}
@media (max-width:1200px){.ra-summary-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.ra-dashboard-grid{grid-template-columns:1fr}.ra-side-panel{order:2}.ra-wide-panel{order:3}.ra-chart-wrap{min-height:360px}.ra-year-groups{overflow-x:auto}}
@media (max-width:760px){.ra-toolbar{align-items:stretch;flex-direction:column}.ra-print-btn{width:100%}.ra-summary-grid{grid-template-columns:1fr}.ra-chart-wrap{grid-template-columns:18px 44px minmax(0,1fr);padding:18px 14px 6px;min-height:330px}.ra-year-groups{grid-template-columns:repeat({{ max(1, $analyticsChartData->count()) }},minmax(76px,1fr));gap:8px;padding:0 8px}.ra-bar{width:10px}.ra-detail-metrics{grid-template-columns:repeat(2,minmax(0,1fr))}.ra-modal{padding:10px}.ra-modal-header,.ra-modal-body,.ra-summary-modal-body{padding:18px}.ra-modal-header h3{font-size:19px}.ra-summary-modal-actions{flex-direction:column}.ra-modal-primary-btn,.ra-modal-secondary-btn{width:100%}}
@media (max-width:520px){.ra-summary-card{min-height:112px}.ra-summary-copy strong{font-size:30px}.ra-panel-header{align-items:flex-start;flex-direction:column}.ra-detail-metrics{grid-template-columns:1fr}.ra-legend-row{grid-template-columns:14px 42px minmax(0,1fr)}}
@media (prefers-reduced-motion:reduce){.ra-summary-card,.ra-summary-action svg,.ra-bar,.ra-print-btn,.ra-link-btn,.ra-modal-primary-btn,.ra-modal-secondary-btn{transition:none}.ra-modal.is-open,.ra-modal.is-open .ra-modal-dialog{animation:none}}
</style>

@endsection

@push('scripts')
<script>
const analyticsChartData = @json($analyticsChartData);
const analyticsSummary = @json($analyticsSummary);
const analyticsDepartments = @json($analyticsDepartments);
const summaryCardData = @json($summaryCardDetails);
const analyticsLookup = new Map();
let activeRaModal = null;
let previousRaFocus = null;

analyticsChartData.forEach(yearGroup => {
    yearGroup.departments.forEach(entry => {
        analyticsLookup.set(analyticsKey(entry.department, entry.year), entry);
    });
});

function analyticsKey(department, year) {
    return `${year}::${department}`;
}

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, character => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    }[character]));
}

function formatNumber(value) {
    return Number(value || 0).toLocaleString();
}

function openRaModal(modal, focusTarget = null) {
    previousRaFocus = document.activeElement;
    activeRaModal = modal;
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';

    window.requestAnimationFrame(() => {
        (focusTarget || modal.querySelector('[role="dialog"]') || modal).focus();
    });
}

function closeRaModal(modal) {
    if (!modal) {
        return;
    }

    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');

    if (activeRaModal === modal) {
        activeRaModal = null;
    }

    if (!document.querySelector('.ra-modal.is-open')) {
        document.body.style.overflow = '';
    }

    if (previousRaFocus && typeof previousRaFocus.focus === 'function') {
        previousRaFocus.focus();
    }
}

function focusableElements(modal) {
    return Array.from(modal.querySelectorAll([
        'a[href]',
        'button:not([disabled])',
        'textarea:not([disabled])',
        'input:not([disabled])',
        'select:not([disabled])',
        '[tabindex]:not([tabindex="-1"])',
    ].join(','))).filter(element => element.offsetParent !== null);
}

function keepFocusInModal(event) {
    if (!activeRaModal || event.key !== 'Tab') {
        return;
    }

    const focusable = focusableElements(activeRaModal);

    if (!focusable.length) {
        event.preventDefault();
        activeRaModal.querySelector('[role="dialog"]')?.focus();
        return;
    }

    const first = focusable[0];
    const last = focusable[focusable.length - 1];

    if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
    }
}

function openSummaryCardModal(key) {
    const card = summaryCardData[key];

    if (!card) {
        return;
    }

    document.getElementById('summaryCardModalTitle').textContent = card.label;
    document.getElementById('summaryCardModalDescription').textContent = card.description;
    document.getElementById('summaryCardModalNote').textContent = card.note;
    document.getElementById('summaryCardModalValue').textContent = formatNumber(card.value);

    const action = document.getElementById('summaryCardModalAction');
    action.textContent = card.actionLabel;
    action.href = card.actionUrl;

    const modal = document.getElementById('summaryCardModal');
    openRaModal(modal, action);
}

function closeSummaryCardModal() {
    closeRaModal(document.getElementById('summaryCardModal'));
}

function openAnalyticsDetail(department, year) {
    const entry = analyticsLookup.get(analyticsKey(department, Number(year)));

    if (!entry) {
        return;
    }

    document.getElementById('analyticsDetailTitle').textContent = `${entry.code} - ${entry.year}`;
    document.getElementById('analyticsDetailSubtitle').textContent = entry.department;
    document.getElementById('analyticsDetailMetrics').innerHTML = [
        ['Department', entry.code],
        ['Year', entry.year],
        ['Total Views', formatNumber(entry.views)],
        ['Papers', formatNumber(entry.paper_count)],
    ].map(metric => `
        <div class="ra-detail-metric">
            <span>${escapeHtml(metric[0])}</span>
            <strong>${escapeHtml(metric[1])}</strong>
        </div>
    `).join('');

    const papers = entry.papers || [];
    document.getElementById('analyticsDetailPapers').innerHTML = papers.length
        ? papers.map(paper => `
            <tr>
                <td><a href="${escapeHtml(paper.url)}" class="ra-table-link">${escapeHtml(paper.title)}</a></td>
                <td>${escapeHtml(paper.author)}</td>
                <td>${escapeHtml(paper.type)}</td>
                <td>${formatNumber(paper.views)}</td>
            </tr>
        `).join('')
        : '<tr><td colspan="5" class="ra-empty-cell">No approved research papers for this department and year.</td></tr>';

    const modal = document.getElementById('analyticsDetailModal');
    openRaModal(modal, modal.querySelector('.ra-close-btn'));
}

function closeAnalyticsDetailModal() {
    closeRaModal(document.getElementById('analyticsDetailModal'));
}

document.getElementById('summaryCardModal').addEventListener('click', event => {
    if (event.target === event.currentTarget) {
        closeSummaryCardModal();
    }
});

document.getElementById('summaryCardModalAction').addEventListener('click', event => {
    const hash = event.currentTarget.hash;

    if (hash && document.querySelector(hash)) {
        closeSummaryCardModal();

        window.setTimeout(() => {
            document.querySelector(hash)?.focus();
        }, 80);
    }
});

document.getElementById('analyticsDetailModal').addEventListener('click', event => {
    if (event.target === event.currentTarget) {
        closeAnalyticsDetailModal();
    }
});

document.addEventListener('keydown', event => {
    keepFocusInModal(event);

    if (event.key !== 'Escape') {
        return;
    }

    if (document.getElementById('summaryCardModal').classList.contains('is-open')) {
        closeSummaryCardModal();
    } else if (document.getElementById('analyticsDetailModal').classList.contains('is-open')) {
        closeAnalyticsDetailModal();
    }
});

function flattenAnalyticsRows() {
    const rows = [];

    analyticsChartData.forEach(yearGroup => {
        yearGroup.departments.forEach(entry => {
            rows.push({
                department: entry.department,
                code: entry.code,
                year: entry.year,
                views: entry.views,
                citationCopies: entry.citation_copies,
                paperCount: entry.paper_count,
            });
        });
    });

    return rows;
}

function flattenPaperRows() {
    const rows = [];

    analyticsChartData.forEach(yearGroup => {
        yearGroup.departments.forEach(entry => {
            (entry.papers || []).forEach(paper => {
                rows.push({
                    department: entry.department,
                    code: entry.code,
                    year: entry.year,
                    title: paper.title,
                    author: paper.author,
                    views: paper.views,
                    citationCopies: paper.citation_copies,
                });
            });
        });
    });

    return rows.sort((a, b) => (b.views || 0) - (a.views || 0));
}

function printAnalyticsReport() {
    const generatedAt = new Date().toLocaleString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });

    const summaryRows = [
        ['Total Research Papers', analyticsSummary.total_research_papers],
        ['Total Views', analyticsSummary.total_views],
        ['Total Copy Citations', analyticsSummary.total_copy_citations],
        ['Total Researchers', analyticsSummary.total_researchers],
    ].map(row => `<tr><td>${escapeHtml(row[0])}</td><td>${formatNumber(row[1])}</td></tr>`).join('');

    const analyticsRows = flattenAnalyticsRows().map(row => `
        <tr>
            <td>${escapeHtml(row.code)}</td>
            <td>${escapeHtml(row.department)}</td>
            <td>${escapeHtml(row.year)}</td>
            <td>${formatNumber(row.views)}</td>
            <td>${formatNumber(row.citationCopies)}</td>
            <td>${formatNumber(row.paperCount)}</td>
        </tr>
    `).join('');

    const paperRows = flattenPaperRows().map(row => `
        <tr>
            <td>${escapeHtml(row.title)}</td>
            <td>${escapeHtml(row.author)}</td>
            <td>${escapeHtml(row.code)}</td>
            <td>${escapeHtml(row.year)}</td>
            <td>${formatNumber(row.views)}</td>
            <td>${formatNumber(row.citationCopies)}</td>
        </tr>
    `).join('');

    const html = `
        <html>
        <head>
            <title>Research Analytics Report</title>
            <style>
                body{font-family:Arial,sans-serif;padding:30px;color:#1f1235;font-size:12px}
                h1{margin:0 0 4px;color:#3b0f63;font-size:24px}
                h2{margin:24px 0 8px;color:#3b0f63;font-size:16px}
                .meta{color:#65536f;margin-bottom:4px}
                .scope{margin:14px 0 20px;padding:10px 12px;border-left:4px solid #6d28d9;background:#f7f2ff;color:#3b0f63}
                table{width:100%;border-collapse:collapse;margin-top:8px}
                th{padding:8px 9px;border:1px solid #d9cce9;background:#f4effb;color:#3b0f63;text-align:left;text-transform:uppercase;font-size:10px;letter-spacing:.04em}
                td{padding:8px 9px;border:1px solid #e8e0f2;vertical-align:top}
                .summary{max-width:520px}
                @media print{body{padding:16px} h2{break-after:avoid} tr{break-inside:avoid}}
            </style>
        </head>
        <body>
            <h1>Research Analytics Report</h1>
            <div class="meta">Philippine College of Science and Technology</div>
            <div class="meta">Generated: ${escapeHtml(generatedAt)}</div>
            <div class="scope"><strong>Scope:</strong> ${escapeHtml(analyticsSummary.report_scope)} | <strong>Years:</strong> ${escapeHtml(analyticsSummary.year_range)}</div>

            <h2>Summary</h2>
            <table class="summary"><tbody>${summaryRows}</tbody></table>

            <h2>Yearly Department Views</h2>
            <table>
                <thead><tr><th>Code</th><th>Department</th><th>Year</th><th>Views</th><th>Copy Citations</th><th>Papers</th></tr></thead>
                <tbody>${analyticsRows || '<tr><td colspan="6">No analytics data available.</td></tr>'}</tbody>
            </table>

            <h2>Top Research Paper List</h2>
            <table>
                <thead><tr><th>Title</th><th>Author</th><th>Department</th><th>Year</th><th>Views</th><th>Copy Citations</th></tr></thead>
                <tbody>${paperRows || '<tr><td colspan="6">No approved research papers available.</td></tr>'}</tbody>
            </table>
        </body>
        </html>
    `;

    printHtml(html);
}

function printHtml(html) {
    const blob = new Blob([html], { type: 'text/html' });
    const url = URL.createObjectURL(blob);
    const iframe = document.createElement('iframe');
    iframe.style.cssText = 'position:fixed;top:0;left:0;width:0;height:0;border:0;visibility:hidden;';
    iframe.src = url;
    document.body.appendChild(iframe);

    iframe.onload = function() {
        iframe.contentWindow.focus();
        iframe.contentWindow.print();
        iframe.contentWindow.onafterprint = function() {
            document.body.removeChild(iframe);
            URL.revokeObjectURL(url);
        };
    };
}
</script>
@endpush
