@extends('layouts.admin')
@section('title', 'Research Reports')
@section('page-title', 'Research Reports')

@section('content')
@php
    $adminUser = auth()->user();
    $isDepartmentScoped = $adminUser?->isDepartmentDean();
    $fixedDepartment = $isDepartmentScoped ? $adminUser->department : null;
@endphp

{{-- FILTER BAR --}}
<div class="rr-filter-card">
    <form method="GET" action="{{ route('admin.reports') }}" class="rr-filter-form">

        <div class="rr-filter-group">
            <label>Department</label>
            @if($isDepartmentScoped)
                <div class="rr-fixed-department">{{ $fixedDepartment ?: 'Assigned Department' }}</div>
            @else
                <select name="department">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept }}" {{ request('department') == $dept ? 'selected' : '' }}>{{ $dept }}</option>
                    @endforeach
                </select>
            @endif
        </div>

        <div class="rr-filter-group rr-filter-sm">
            <label>Academic Year / Year</label>
            <select name="year">
                <option value="">All Years</option>
                @foreach($years as $year)
                    <option value="{{ $year }}" {{ request('year') == $year ? 'selected' : '' }}>{{ $year }}</option>
                @endforeach
            </select>
        </div>

        <div class="rr-filter-actions">
            <label class="rr-filter-label-hidden">Action</label>
            <div style="display:flex; gap:8px;">
                <button type="submit" class="rr-btn rr-btn-primary">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                    Filter
                </button>
                <a href="{{ route('admin.reports') }}" class="rr-btn rr-btn-ghost">Clear</a>
            </div>
        </div>

        <div class="rr-print-wrap">
            <label class="rr-filter-label-hidden">Print</label>
            <button type="button" onclick="openReportPrintModal()" class="rr-btn rr-btn-outline">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                Print Report
            </button>
        </div>

    </form>
</div>

@if(method_exists($researches, 'total'))
    <div class="rr-results-meta">
        Showing {{ number_format($researches->firstItem() ?? 0) }}-{{ number_format($researches->lastItem() ?? 0) }} of {{ number_format($researches->total()) }} matching papers
    </div>
@endif

@php
    $typeColors = [
        'Thesis'                 => ['bg'=>'#f0eaf9','border'=>'#e2d5f4','color'=>'#6b2fa0'],
        'Feasibility Study'      => ['bg'=>'#dbeafe','border'=>'#bfdbfe','color'=>'#1d4ed8'],
        'Descriptive Research'   => ['bg'=>'#dcfce7','border'=>'#bbf7d0','color'=>'#15803d'],
        'Correlational Research' => ['bg'=>'#fef9c3','border'=>'#fde68a','color'=>'#92400e'],
        'Quantitative Research'  => ['bg'=>'#ffe4e6','border'=>'#fecdd3','color'=>'#be123c'],
        'Capstone 1'             => ['bg'=>'#ede9fe','border'=>'#ddd6fe','color'=>'#5b21b6'],
        'Capstone 2'             => ['bg'=>'#e0f2fe','border'=>'#bae6fd','color'=>'#0369a1'],
        'Applied Research'       => ['bg'=>'#fce7f3','border'=>'#fbcfe8','color'=>'#9d174d'],
        'Qualitative Research'   => ['bg'=>'#f0fdf4','border'=>'#bbf7d0','color'=>'#166534'],
        'Mixed Methods Research' => ['bg'=>'#fff7ed','border'=>'#fed7aa','color'=>'#92400e'],
        'Action Research'        => ['bg'=>'#faf5ff','border'=>'#e9d5ff','color'=>'#7e22ce'],
        'Experimental Research'  => ['bg'=>'#f0f9ff','border'=>'#bae6fd','color'=>'#075985'],
        'Journal Article'        => ['bg'=>'#e0f2fe','border'=>'#bae6fd','color'=>'#075985'],
        'Review Article'         => ['bg'=>'#ecfccb','border'=>'#d9f99d','color'=>'#3f6212'],
        'Case Report'            => ['bg'=>'#fee2e2','border'=>'#fecaca','color'=>'#b91c1c'],
        'Short Communication'    => ['bg'=>'#f5f3ff','border'=>'#ddd6fe','color'=>'#6d28d9'],
    ];
@endphp

{{-- GROUPED TABLE --}}
@php
    $researchRows = method_exists($researches, 'getCollection') ? $researches->getCollection() : collect($researches);
    $grouped = $researchRows->groupBy(fn ($research) => trim((string) $research->department) !== '' ? $research->department : 'Unassigned Department');
@endphp

@if($researchRows->isEmpty())
    <div class="rr-card">
        <div class="rr-empty">
            <div class="rr-empty-icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            </div>
            <p>No research found matching your filters.</p>
            <a href="{{ route('admin.reports') }}" class="rr-btn rr-btn-primary" style="margin-top:14px;">Clear Filters</a>
        </div>
    </div>
@else
    @foreach($grouped as $department => $deptResearches)
    @php
        $departmentTotal = (int) ($departmentTotals[$department] ?? $deptResearches->count());
    @endphp
    <div class="rr-card">
        <div class="rr-dept-header">
            <div class="rr-dept-header-left">
                <div class="rr-dept-icon">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                </div>
                <span class="rr-dept-name">{{ $department }}</span>
            </div>
            <span class="rr-dept-count">{{ number_format($departmentTotal) }} {{ $departmentTotal == 1 ? 'paper' : 'papers' }}</span>
        </div>

        <div class="rr-table-wrap">
            <table class="rr-table">
                <thead>
                    <tr>
                        <th style="width:40px;">#</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Program</th>
                        <th>Type</th>
                        <th>Year</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($deptResearches->sortByDesc('year_published') as $i => $r)
                    <tr>
                        <td class="rr-row-num">{{ $i + 1 }}</td>
                        <td>
                            <a href="{{ route('admin.research.show', $r) }}" class="rr-title-link">{{ $r->title }}</a>
                        </td>
                        <td class="rr-author">{{ $r->author_name }}</td>
                        <td class="rr-program">{{ $r->course ?? $r->program ?? '—' }}</td>
                        <td>
                            <span class="rr-type-pill" style="
                                background:{{ ($typeColors[$r->type] ?? ['bg'=>'#f3f4f6'])['bg'] }};
                                border:1px solid {{ ($typeColors[$r->type] ?? ['border'=>'#e5e7eb'])['border'] }};
                                color:{{ ($typeColors[$r->type] ?? ['color'=>'#374151'])['color'] }};
                            ">{{ $r->type }}</span>
                        </td>
                        <td class="rr-year">{{ $r->year_published }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endforeach

    @if(method_exists($researches, 'links'))
        <div class="rr-pagination">
            {{ $researches->links('vendor.pagination.custom') }}
        </div>
    @endif
@endif

<div id="reportPrintModal" class="rr-modal" aria-hidden="true">
    <div class="rr-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="reportPrintModalTitle">
        <div class="rr-modal-header">
            <div>
                <span>Print Report</span>
                <h3 id="reportPrintModalTitle">Generate filtered report</h3>
            </div>
            <button type="button" class="rr-modal-close" onclick="closeReportPrintModal()" aria-label="Close print options">&times;</button>
        </div>

        <div class="rr-modal-body">
            <div class="rr-export-grid">
                <div class="rr-export-field">
                    <label for="reportExportDepartment">Department</label>
                    @if($isDepartmentScoped)
                        <input id="reportExportDepartment" type="text" value="{{ $fixedDepartment ?: 'Assigned Department' }}" data-value="{{ $fixedDepartment }}" readonly>
                    @else
                        <select id="reportExportDepartment">
                            <option value="">All Departments</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept }}" {{ request('department') === $dept ? 'selected' : '' }}>{{ $dept }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>
            </div>

            @php
                $initialReportYear = request('year');
                $initialAllReportYears = $initialReportYear === null || $initialReportYear === '';
            @endphp
            <div class="rr-export-field">
                <label id="reportYearDropdownFieldLabel">Academic Year / Year</label>
                <div class="rr-year-dropdown" id="reportYearDropdown">
                    <button
                        type="button"
                        class="rr-year-trigger"
                        id="reportYearDropdownButton"
                        aria-haspopup="true"
                        aria-expanded="false"
                        aria-labelledby="reportYearDropdownFieldLabel reportYearDropdownValue"
                        onclick="toggleReportYearDropdown()"
                    >
                        <span class="rr-year-trigger-text" id="reportYearDropdownValue">All Years</span>
                        <span class="rr-year-trigger-icon" aria-hidden="true"></span>
                    </button>
                    <div class="rr-year-menu" id="reportYearDropdownMenu">
                        <label class="rr-year-check rr-year-check-all">
                            <input type="checkbox" id="reportExportAllYears" value="" {{ $initialAllReportYears ? 'checked' : '' }}>
                            <span>
                                <strong>All Years</strong>
                                <small>2022 onwards, based on available records</small>
                            </span>
                        </label>
                        @forelse($years as $year)
                            <label class="rr-year-check">
                                <input type="checkbox" class="report-export-year" value="{{ $year }}" {{ $initialAllReportYears || (string) $initialReportYear === (string) $year ? 'checked' : '' }}>
                                <span>{{ $year }}</span>
                            </label>
                        @empty
                            <div class="rr-year-empty">No report years available yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="rr-export-result">
                <span>Matching Records</span>
                <strong id="reportExportCount">0</strong>
                <small id="reportExportSummary">Select filters to preview the report count.</small>
            </div>

            <p id="reportPrintValidation" class="rr-print-validation" aria-live="polite"></p>
        </div>

        <div class="rr-modal-actions">
            <button type="button" class="rr-btn rr-btn-ghost" onclick="closeReportPrintModal()">Cancel</button>
            <button type="button" class="rr-btn rr-btn-outline" id="reportExportPdfBtn" onclick="exportFilteredReport('pdf')">Export as PDF</button>
            <button type="button" class="rr-btn rr-btn-primary" id="reportExportExcelBtn" onclick="exportFilteredReport('excel')">Export as Excel</button>
        </div>
    </div>
</div>

<style>
.rr-filter-card{background:#fff;border-radius:16px;border:1px solid rgba(107,47,160,.1);box-shadow:0 2px 16px rgba(59,15,122,.05);padding:20px 24px;margin-bottom:20px;}
.rr-filter-form{display:flex;align-items:flex-end;gap:14px;flex-wrap:wrap;}
.rr-filter-group{display:flex;flex-direction:column;gap:5px;flex:1;min-width:160px;}
.rr-filter-group.rr-filter-sm{flex:0 0 150px;min-width:150px;}
.rr-filter-group label,.rr-filter-actions label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:#a090bc;}
.rr-filter-label-hidden{visibility:hidden;display:block;font-size:11px;margin-bottom:5px;}
.rr-filter-group select,.rr-fixed-department{padding:9px 13px;border:1.5px solid #e8dff5;border-radius:10px;background:#faf8ff;font-size:13.5px;color:#1a0638;box-sizing:border-box;font-family:inherit;width:100%;min-height:39px;}
.rr-fixed-department{display:flex;align-items:center;color:#6b2fa0;font-weight:800;}
.rr-filter-group select:focus{outline:none;border-color:#7c3aed;box-shadow:0 0 0 3px rgba(124,58,237,.1);}
.rr-results-meta{margin:-8px 0 14px;color:#7d6c98;font-size:12.5px;font-weight:700;}
.rr-filter-actions{display:flex;flex-direction:column;gap:4px;}
.rr-print-wrap{display:flex;flex-direction:column;gap:4px;margin-left:auto;}
.rr-btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;border-radius:50px;font-size:13px;font-weight:700;cursor:pointer;border:none;text-decoration:none;transition:all .14s ease;font-family:inherit;white-space:nowrap;}
.rr-btn-primary{background:#3b0f7a;color:#fff;box-shadow:0 3px 12px rgba(59,15,122,.25);}
.rr-btn-primary:hover{background:#2d0a5e;transform:translateY(-1px);color:#fff;}
.rr-btn-ghost{background:#fff;color:#8b7aaa;border:1.5px solid #e8dff5;}
.rr-btn-ghost:hover{background:#f4f0fc;color:#3b0f7a;}
.rr-btn-outline{background:#faf8ff;color:#6b2fa0;border:1.5px solid #e2d5f4;}
.rr-btn-outline:hover{background:#f0eaf9;}
.rr-card{background:#fff;border-radius:18px;border:1px solid rgba(107,47,160,.1);box-shadow:0 4px 24px rgba(59,15,122,.05),0 1px 4px rgba(0,0,0,.03);overflow:hidden;margin-bottom:18px;}
.rr-dept-header{display:flex;align-items:center;justify-content:space-between;padding:16px 24px;background:linear-gradient(160deg,#fdfbff 0%,#f4f0fc 100%);border-bottom:1px solid #f0eaf9;}
.rr-dept-header-left{display:flex;align-items:center;gap:10px;}
.rr-dept-icon{width:30px;height:30px;border-radius:8px;background:#f0eaf9;border:1px solid #e2d5f4;display:flex;align-items:center;justify-content:center;color:#6b2fa0;flex-shrink:0;}
.rr-dept-name{font-size:14px;font-weight:800;color:#1a0638;letter-spacing:-.2px;}
.rr-dept-count{padding:4px 12px;background:#f0eaf9;border:1px solid #e2d5f4;border-radius:50px;font-size:12px;font-weight:700;color:#6b2fa0;}
.rr-table-wrap{overflow-x:auto;}
.rr-table{width:100%;border-collapse:collapse;}
.rr-table thead tr{border-bottom:1px solid #f0eaf9;}
.rr-table th{padding:10px 18px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:#a090bc;text-align:left;background:#fdfbff;}
.rr-table tbody tr{border-bottom:1px solid #f9f6fe;transition:background .12s;}
.rr-table tbody tr:last-child{border-bottom:none;}
.rr-table tbody tr:hover{background:#fdfbff;}
.rr-table td{padding:13px 18px;vertical-align:middle;}
.rr-row-num{font-size:12px;color:#c0aee0;font-weight:600;}
.rr-title-link{font-size:13.5px;font-weight:700;color:#1a0638;text-decoration:none;transition:color .12s;}
.rr-title-link:hover{color:#7c3aed;}
.rr-author{font-size:13px;color:#5b3d8a;font-weight:600;}
.rr-program{font-size:12.5px;color:#a090bc;}
.rr-year{font-size:13.5px;font-weight:800;color:#3b0f7a;}
.rr-type-pill{display:inline-flex;align-items:center;padding:3px 10px;border-radius:6px;font-size:.7rem;font-weight:700;white-space:nowrap;}
.rr-empty{text-align:center;padding:48px 20px;color:#c0aee0;}
.rr-empty-icon{width:56px;height:56px;border-radius:16px;background:#f4f0fc;border:1px solid #e8dff5;display:flex;align-items:center;justify-content:center;color:#c0aee0;margin:0 auto 14px;}
.rr-empty p{font-size:14px;margin:0;}
.rr-pagination{margin-top:20px;}
.rr-modal{position:fixed;inset:0;background:rgba(26,6,56,.46);z-index:80;display:none;align-items:center;justify-content:center;padding:18px;}
.rr-modal.is-open{display:flex;}
.rr-modal-dialog{width:min(720px,100%);max-height:90vh;background:#fff;border-radius:18px;box-shadow:0 28px 70px rgba(26,6,56,.28);overflow:hidden;border:1px solid rgba(107,47,160,.16);display:flex;flex-direction:column;}
.rr-modal-header{display:flex;align-items:flex-start;justify-content:space-between;gap:14px;padding:22px 24px 16px;border-bottom:1px solid #f0eaf9;}
.rr-modal-header span{display:block;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.9px;color:#7c3aed;margin-bottom:6px;}
.rr-modal-header h3{margin:0;color:#1a0638;font-size:22px;line-height:1.15;}
.rr-modal-close{width:34px;height:34px;border-radius:10px;border:1px solid #e8dff5;background:#faf8ff;color:#6b2fa0;font-size:24px;line-height:1;cursor:pointer;}
.rr-modal-close:hover{background:#f0eaf9;}
.rr-modal-body{padding:20px 24px;overflow-y:auto;}
.rr-print-context{padding:12px 14px;border:1px solid #e8dff5;background:#faf8ff;border-radius:12px;display:grid;gap:4px;margin-bottom:14px;}
.rr-print-context strong{font-size:12px;color:#3b0f7a;text-transform:uppercase;letter-spacing:.06em;}
.rr-print-context span{font-size:13px;color:#6b5b82;line-height:1.5;}
.rr-print-options{display:grid;gap:10px;}
.rr-print-option{display:flex;align-items:flex-start;gap:12px;border:1.5px solid #e8dff5;border-radius:12px;padding:14px;background:#fff;cursor:pointer;transition:border-color .14s ease,background .14s ease,box-shadow .14s ease;}
.rr-print-option:hover{border-color:#cbb6eb;background:#fdfbff;}
.rr-print-option:has(input:checked){border-color:#6b2fa0;background:#f8f2ff;box-shadow:0 0 0 3px rgba(107,47,160,.08);}
.rr-print-option input{margin-top:3px;accent-color:#6b2fa0;}
.rr-print-option span{display:grid;gap:3px;}
.rr-print-option strong{color:#1a0638;font-size:14px;}
.rr-print-option small{color:#7d6c98;font-size:12.5px;line-height:1.35;}
.rr-export-grid{display:grid;grid-template-columns:1fr;gap:14px;margin-bottom:14px;}
.rr-export-field{display:grid;gap:7px;margin-bottom:14px;}
.rr-export-field label{font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#6b2fa0;}
.rr-export-field select,.rr-export-field input[type="text"]{width:100%;min-height:42px;border:1.5px solid #e8dff5;border-radius:12px;background:#faf8ff;color:#1a0638;font-family:inherit;font-size:13.5px;font-weight:700;padding:9px 12px;box-sizing:border-box;}
.rr-export-field select:focus{outline:none;border-color:#7c3aed;box-shadow:0 0 0 3px rgba(124,58,237,.1);}
.rr-year-dropdown{position:relative;}
.rr-year-trigger{width:100%;min-height:44px;display:flex;align-items:center;justify-content:space-between;gap:12px;border:1.5px solid #e8dff5;border-radius:12px;background:#faf8ff;color:#1a0638;font-family:inherit;font-size:13.5px;font-weight:800;padding:10px 13px;box-sizing:border-box;cursor:pointer;transition:border-color .14s ease,background .14s ease,box-shadow .14s ease;}
.rr-year-trigger:hover,.rr-year-dropdown.is-open .rr-year-trigger{border-color:#cbb6eb;background:#fff;box-shadow:0 0 0 3px rgba(124,58,237,.08);}
.rr-year-trigger:focus{outline:none;border-color:#7c3aed;box-shadow:0 0 0 3px rgba(124,58,237,.1);}
.rr-year-trigger-text{min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;text-align:left;}
.rr-year-trigger-icon{width:8px;height:8px;border-right:2px solid #6b2fa0;border-bottom:2px solid #6b2fa0;transform:rotate(45deg);transition:transform .14s ease;margin-right:3px;flex:0 0 auto;}
.rr-year-dropdown.is-open .rr-year-trigger-icon{transform:rotate(225deg);margin-top:5px;}
.rr-year-menu{display:none;max-height:280px;overflow-y:auto;overscroll-behavior:contain;box-sizing:border-box;margin-top:8px;padding:8px;border:1px solid #e2d5f4;border-radius:14px;background:#fff;box-shadow:0 14px 32px rgba(26,6,56,.12);}
.rr-year-dropdown.is-open .rr-year-menu{display:grid;gap:4px;}
.rr-export-field .rr-year-check{display:flex;align-items:center;gap:10px;min-height:38px;padding:9px 10px;border-radius:10px;color:#1a0638;font-size:13px;font-weight:800;letter-spacing:0;text-transform:none;cursor:pointer;transition:background .14s ease,color .14s ease;}
.rr-export-field .rr-year-check:hover{background:#f8f2ff;}
.rr-export-field .rr-year-check:has(input:checked){background:#f0eaf9;color:#2d0a5e;}
.rr-year-check input{width:16px;height:16px;margin:0;accent-color:#6b2fa0;flex:0 0 auto;}
.rr-export-field .rr-year-check span{display:grid;gap:2px;line-height:1.2;}
.rr-export-field .rr-year-check strong{font-size:13px;}
.rr-export-field .rr-year-check small{color:#85749f;font-size:11px;font-weight:700;letter-spacing:0;text-transform:none;}
.rr-year-empty{width:100%;padding:10px;color:#8c7e9f;font-size:13px;text-align:center;}
.rr-export-result{display:flex;align-items:center;justify-content:space-between;gap:16px;background:#f8f2ff;border:1px solid #e2d5f4;border-radius:14px;padding:14px 16px;}
.rr-export-result span{font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#6b2fa0;}
.rr-export-result strong{font-size:28px;color:#1a0638;line-height:1;}
.rr-export-result small{margin-left:auto;color:#6b5b82;font-size:12.5px;text-align:right;}
.rr-btn:disabled{opacity:.55;cursor:not-allowed;transform:none;box-shadow:none;}
.rr-print-validation{min-height:18px;margin:12px 0 0;color:#b91c1c;font-size:12.5px;font-weight:700;}
.rr-modal-actions{display:flex;justify-content:flex-end;gap:10px;padding:16px 24px 22px;border-top:1px solid #f0eaf9;background:#fdfbff;}
@media(max-width:640px){.rr-filter-form{flex-direction:column;}.rr-filter-group,.rr-filter-group.rr-filter-sm{flex:none;width:100%;min-width:unset;}.rr-print-wrap{margin-left:0;}.rr-bar-row{grid-template-columns:50px 1fr 34px;}}
@media(max-width:640px){.rr-modal{padding:12px;align-items:flex-end;}.rr-modal-dialog{border-radius:16px 16px 0 0;max-height:92vh;}.rr-year-menu{max-height:250px;}.rr-export-grid{grid-template-columns:1fr}.rr-export-result{align-items:flex-start;flex-direction:column}.rr-export-result small{text-align:left;margin-left:0}.rr-modal-actions{flex-direction:column-reverse;}.rr-modal-actions .rr-btn{justify-content:center;width:100%;}}
</style>

@endsection

@push('scripts')
@php
    $formatReportRow = function ($research) {
        return [
            'department' => trim((string) $research->department) !== '' ? $research->department : 'Unassigned Department',
            'title' => $research->title ?: 'Untitled Research',
            'author' => $research->author_name ?: 'Unknown Author',
            'program' => $research->course ?? $research->program ?? '-',
            'type' => $research->type ?: 'N/A',
            'year' => $research->year_published ?: 'N/A',
        ];
    };

    $pagePrintRows = $researchRows->map($formatReportRow)->values();
    $filteredPrintRows = $printResearches->map($formatReportRow)->values();
    $departmentSummaryRows = $departmentTotals
        ->map(fn ($count, $department) => [
            'department' => $department,
            'count' => (int) $count,
        ])
        ->sortBy('department')
        ->values();
@endphp
<script>
function printReport() {
    const filters = {
        department: @json($fixedDepartment ?: (request('department') ?: 'All Departments')),
        year:       @json(request('year') ?: 'All Years'),
        page:       @json(method_exists($researches, 'currentPage') ? $researches->currentPage() : 1),
        total:      @json(method_exists($researches, 'total') ? $researches->total() : $researchRows->count()),
    };

    const typeColors = {
        'Thesis':                 '#ede9fe',
        'Feasibility Study':      '#dbeafe',
        'Descriptive Research':   '#dcfce7',
        'Correlational Research': '#fef9c3',
        'Quantitative Research':  '#ffe4e6',
        'Capstone 1':             '#ede9fe',
        'Capstone 2':             '#e0f2fe',
        'Applied Research':       '#fce7f3',
        'Qualitative Research':   '#f0fdf4',
        'Mixed Methods Research': '#fff7ed',
        'Action Research':        '#faf5ff',
        'Experimental Research':  '#f0f9ff',
    };

    let html = `<html><head><title>Research Inventory Report</title><style>
        body{font-family:Arial,sans-serif;padding:30px;font-size:13px;}
        h1{color:#52297a;margin-bottom:4px;}
        .subtitle{color:#888;font-size:13px;margin-bottom:6px;}
        .filters{color:#666;font-size:12px;margin-bottom:24px;padding:10px 14px;background:#f9f5ff;border-left:4px solid #6b2fa0;border-radius:4px;}
        .dept-title{background:#f0e8f8;color:#52297a;padding:10px 14px;font-weight:700;font-size:14px;margin-top:24px;border-radius:4px;display:flex;justify-content:space-between;}
        table{width:100%;border-collapse:collapse;margin-top:8px;}
        th{background:#f9f5ff;color:#52297a;padding:8px 10px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.5px;border-bottom:2px solid #e2d6f0;}
        td{padding:8px 10px;border-bottom:1px solid #f3eefb;vertical-align:top;}
        .pill{padding:2px 8px;border-radius:6px;font-size:11px;font-weight:700;}
        @media print{body{padding:15px;}}
    </style></head><body>
        <h1>Ube Repository — Research Inventory Report</h1>
        <div class="subtitle">Philippine College of Science and Technology</div>
        <div class="subtitle">Printed on: ${new Date().toLocaleDateString('en-US',{year:'numeric',month:'long',day:'numeric'})}</div>
        <div class="filters"><strong>Filters:</strong> Department: ${filters.department} | Year: ${filters.year} | Page: ${filters.page} | Total: ${filters.total} matching paper(s)</div>`;

    @foreach($grouped as $department => $deptResearches)
    html += `<div class="dept-title"><span>{{ $department }}</span><span>{{ (int) ($departmentTotals[$department] ?? $deptResearches->count()) }} matching paper(s)</span></div>
    <table><thead><tr><th>#</th><th>Title</th><th>Author</th><th>Program</th><th>Year</th></tr></thead><tbody>`;
    @foreach($deptResearches->sortByDesc('year_published') as $i => $r)
    html += `<tr>
        <td>{{ $i + 1 }}</td>
        <td>{{ addslashes($r->title) }}</td>
        <td>{{ addslashes($r->author_name) }}</td>
        <td>{{ addslashes($r->course ?? $r->program ?? '—') }}</td>
        <td><strong>{{ $r->year_published }}</strong></td>
    </tr>`;
    @endforeach
    html += `</tbody></table>`;
    @endforeach

    html += `</body></html>`;

    const blob  = new Blob([html], {type:'text/html'});
    const url   = URL.createObjectURL(blob);
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

const reportPrintData = {
    filters: {
        department: @json($fixedDepartment ?: (request('department') ?: 'All Departments')),
        year: @json(request('year') ?: 'All Years'),
        page: @json(method_exists($researches, 'currentPage') ? $researches->currentPage() : 1),
        total: @json(method_exists($researches, 'total') ? $researches->total() : $researchRows->count()),
    },
    rows: {
        filtered: @json($filteredPrintRows),
        page: @json($pagePrintRows),
    },
    summary: @json($departmentSummaryRows),
};

const reportPrintLabels = {
    filtered: 'Complete Filtered Report',
    page: 'Current Page Report',
    summary: 'Department Summary Report',
};

const reportTypeColors = {
    'Thesis': '#ede9fe',
    'Feasibility Study': '#dbeafe',
    'Descriptive Research': '#dcfce7',
    'Correlational Research': '#fef9c3',
    'Quantitative Research': '#ffe4e6',
    'Capstone 1': '#ede9fe',
    'Capstone 2': '#e0f2fe',
    'Applied Research': '#fce7f3',
    'Qualitative Research': '#f0fdf4',
    'Mixed Methods Research': '#fff7ed',
    'Action Research': '#faf5ff',
    'Experimental Research': '#f0f9ff',
    'Journal Article': '#e0f2fe',
    'Review Article': '#ecfccb',
    'Case Report': '#fee2e2',
    'Short Communication': '#f5f3ff',
};

function openReportPrintModal() {
    const modal = document.getElementById('reportPrintModal');
    const validation = document.getElementById('reportPrintValidation');

    document.querySelectorAll('input[name="report_print_scope"]').forEach(input => {
        input.checked = false;
    });

    validation.textContent = '';
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    modal.querySelector('input[name="report_print_scope"]')?.focus();
}

function closeReportPrintModal() {
    const modal = document.getElementById('reportPrintModal');

    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
}

function confirmReportPrint() {
    const selected = document.querySelector('input[name="report_print_scope"]:checked');
    const validation = document.getElementById('reportPrintValidation');

    if (!selected) {
        validation.textContent = 'Please choose what you want to print.';
        return;
    }

    if (selected.value === 'summary' && reportPrintData.summary.length === 0) {
        validation.textContent = 'There is no department summary to print.';
        return;
    }

    if (selected.value !== 'summary' && reportPrintData.rows[selected.value].length === 0) {
        validation.textContent = 'There are no research papers to print for this option.';
        return;
    }

    validation.textContent = '';
    closeReportPrintModal();
    printReport(selected.value);
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

function groupRowsByDepartment(rows) {
    return rows.reduce((groups, row) => {
        const department = row.department || 'Unassigned Department';

        if (!groups[department]) {
            groups[department] = [];
        }

        groups[department].push(row);
        return groups;
    }, {});
}

function buildSummaryTable() {
    const rows = reportPrintData.summary.map((entry, index) => `
        <tr>
            <td>${index + 1}</td>
            <td>${escapeHtml(entry.department)}</td>
            <td><strong>${Number(entry.count || 0).toLocaleString()}</strong></td>
        </tr>
    `).join('');

    return `
        <h2>Department Summary</h2>
        <table>
            <thead><tr><th>#</th><th>Department</th><th>Total Papers</th></tr></thead>
            <tbody>${rows || '<tr><td colspan="3">No department totals available.</td></tr>'}</tbody>
        </table>
    `;
}

function buildResearchTables(rows) {
    const groups = groupRowsByDepartment(rows);
    const departments = Object.keys(groups);

    if (departments.length === 0) {
        return '<p class="empty">No research papers found for this print option.</p>';
    }

    return departments.map(department => {
        const body = groups[department].map((row, index) => `
            <tr>
                <td>${index + 1}</td>
                <td>${escapeHtml(row.title)}</td>
                <td>${escapeHtml(row.author)}</td>
                <td>${escapeHtml(row.program)}</td>
                <td><strong>${escapeHtml(row.year)}</strong></td>
            </tr>
        `).join('');

        return `
            <div class="dept-title">
                <span>${escapeHtml(department)}</span>
                <span>${groups[department].length.toLocaleString()} paper(s)</span>
            </div>
            <table>
                <thead><tr><th>#</th><th>Title</th><th>Author</th><th>Program</th><th>Year</th></tr></thead>
                <tbody>${body}</tbody>
            </table>
        `;
    }).join('');
}

function printReport(scope) {
    const rows = scope === 'summary' ? [] : reportPrintData.rows[scope];
    const generatedAt = new Date().toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
    const totalLabel = scope === 'page'
        ? `${rows.length.toLocaleString()} paper(s) on page ${reportPrintData.filters.page}`
        : `${reportPrintData.filters.total.toLocaleString()} matching paper(s)`;
    const bodyContent = scope === 'summary'
        ? buildSummaryTable()
        : buildResearchTables(rows);

    const html = `<html><head><title>${escapeHtml(reportPrintLabels[scope])}</title><style>
        body{font-family:Arial,sans-serif;padding:30px;font-size:13px;color:#1f1235;}
        h1{color:#52297a;margin:0 0 4px;font-size:24px;}
        h2{color:#52297a;margin:24px 0 8px;font-size:16px;}
        .subtitle{color:#666;font-size:13px;margin-bottom:6px;}
        .filters{color:#555;font-size:12px;margin:18px 0 24px;padding:10px 14px;background:#f9f5ff;border-left:4px solid #6b2fa0;border-radius:4px;line-height:1.5;}
        .dept-title{background:#f0e8f8;color:#52297a;padding:10px 14px;font-weight:700;font-size:14px;margin-top:24px;border-radius:4px;display:flex;justify-content:space-between;gap:16px;}
        table{width:100%;border-collapse:collapse;margin-top:8px;}
        th{background:#f9f5ff;color:#52297a;padding:8px 10px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.5px;border-bottom:2px solid #e2d6f0;}
        td{padding:8px 10px;border-bottom:1px solid #f3eefb;vertical-align:top;}
        .pill{padding:2px 8px;border-radius:6px;font-size:11px;font-weight:700;display:inline-block;}
        .empty{padding:16px;background:#f9f5ff;border-radius:6px;color:#666;}
        @media print{body{padding:15px;} h2,.dept-title{break-after:avoid;} tr{break-inside:avoid;}}
    </style></head><body>
        <h1>Ube Repository - ${escapeHtml(reportPrintLabels[scope])}</h1>
        <div class="subtitle">Philippine College of Science and Technology</div>
        <div class="subtitle">Printed on: ${escapeHtml(generatedAt)}</div>
        <div class="filters">
            <strong>Filters:</strong>
            Department: ${escapeHtml(reportPrintData.filters.department)} |
            Year: ${escapeHtml(reportPrintData.filters.year)} |
            ${escapeHtml(totalLabel)}
        </div>
        ${bodyContent}
    </body></html>`;

    printHtml(html);
}

function printHtml(html) {
    const blob = new Blob([html], { type: 'text/html' });
    const url = URL.createObjectURL(blob);
    const iframe = document.createElement('iframe');
    iframe.style.cssText = 'position:fixed;top:0;left:0;width:0;height:0;border:none;visibility:hidden;';
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

document.getElementById('reportPrintModal')?.addEventListener('click', event => {
    if (event.target === event.currentTarget) {
        closeReportPrintModal();
    }
});

document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && document.getElementById('reportPrintModal')?.classList.contains('is-open')) {
        if (document.getElementById('reportYearDropdown')?.classList.contains('is-open')) {
            toggleReportYearDropdown(false);
            return;
        }

        closeReportPrintModal();
    }
});

const reportExportRows = @json($reportFilterRows);
const reportExportUrls = {
    pdf: @json(route('admin.reports.export.pdf')),
    excel: @json(route('admin.reports.export.excel')),
};

function reportYearInputs() {
    return Array.from(document.querySelectorAll('.report-export-year'));
}

function checkedReportYearValues() {
    return reportYearInputs()
        .filter(input => input.checked)
        .map(input => Number(input.value))
        .filter(year => Number.isInteger(year))
        .sort((a, b) => a - b);
}

function toggleReportYearDropdown(forceOpen = null) {
    const dropdown = document.getElementById('reportYearDropdown');
    const button = document.getElementById('reportYearDropdownButton');

    if (!dropdown) {
        return;
    }

    const shouldOpen = forceOpen === null
        ? !dropdown.classList.contains('is-open')
        : Boolean(forceOpen);

    dropdown.classList.toggle('is-open', shouldOpen);
    button?.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
}

function updateReportYearLabel() {
    const label = document.getElementById('reportYearDropdownValue');
    const allYears = document.getElementById('reportExportAllYears');
    const years = checkedReportYearValues();

    if (!label) {
        return;
    }

    if (allYears?.checked) {
        label.textContent = 'All Years';
        return;
    }

    label.textContent = years.length ? years.join(', ') : 'Select years';
}

function selectedReportYears() {
    const allYears = document.getElementById('reportExportAllYears');

    if (allYears?.checked) {
        return [];
    }

    return checkedReportYearValues();
}

function reportExportDepartmentValue() {
    const field = document.getElementById('reportExportDepartment');

    return field?.dataset.value ?? field?.value ?? '';
}

function matchingReportExportRows() {
    const department = reportExportDepartmentValue();
    const years = selectedReportYears();
    const allYears = document.getElementById('reportExportAllYears')?.checked ?? true;

    if (!allYears && years.length === 0) {
        return [];
    }

    return reportExportRows.filter(row => {
        const matchesDepartment = !department || row.department === department;
        const matchesYear = allYears || years.includes(Number(row.year));

        return matchesDepartment && matchesYear;
    });
}

function reportExportSummaryText(count) {
    const department = reportExportDepartmentValue() || 'All Departments';
    const years = selectedReportYears();
    const yearText = document.getElementById('reportExportAllYears')?.checked
        ? 'All Years'
        : (years.length ? years.join(', ') : 'No year selected');

    return `${department} | ${yearText} | ${count.toLocaleString()} record${count === 1 ? '' : 's'}`;
}

function updateReportExportCount() {
    const rows = matchingReportExportRows();
    const count = rows.length;
    const countEl = document.getElementById('reportExportCount');
    const summaryEl = document.getElementById('reportExportSummary');
    const pdfBtn = document.getElementById('reportExportPdfBtn');
    const excelBtn = document.getElementById('reportExportExcelBtn');
    const hasSelectedYear = document.getElementById('reportExportAllYears')?.checked
        || document.querySelectorAll('.report-export-year:checked').length > 0;
    const canExport = count > 0 && hasSelectedYear;

    if (countEl) {
        countEl.textContent = count.toLocaleString();
    }

    if (summaryEl) {
        summaryEl.textContent = hasSelectedYear
            ? reportExportSummaryText(count)
            : 'Select at least one year or choose All Years.';
    }

    if (pdfBtn) {
        pdfBtn.disabled = !canExport;
    }

    if (excelBtn) {
        excelBtn.disabled = !canExport;
    }

    updateReportYearLabel();

    return count;
}

function syncReportYearSelection(changedInput = null) {
    const allYears = document.getElementById('reportExportAllYears');
    const yearInputs = reportYearInputs();

    if (changedInput === allYears) {
        yearInputs.forEach(input => {
            input.checked = Boolean(allYears?.checked);
        });
    }

    if (!changedInput && allYears?.checked) {
        yearInputs.forEach(input => {
            input.checked = true;
        });
    }

    if (changedInput && changedInput !== allYears && allYears) {
        const checkedCount = yearInputs.filter(input => input.checked).length;
        allYears.checked = yearInputs.length > 0 && checkedCount === yearInputs.length;
    }

    updateReportExportCount();
}

function openReportPrintModal() {
    const modal = document.getElementById('reportPrintModal');
    const validation = document.getElementById('reportPrintValidation');

    if (validation) {
        validation.textContent = '';
    }

    syncReportYearSelection();
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    document.getElementById('reportExportDepartment')?.focus();
}

function closeReportPrintModal() {
    const modal = document.getElementById('reportPrintModal');

    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    toggleReportYearDropdown(false);
}

function exportFilteredReport(format) {
    const validation = document.getElementById('reportPrintValidation');
    const allYears = document.getElementById('reportExportAllYears')?.checked ?? true;
    const years = selectedReportYears();
    const count = updateReportExportCount();

    if (validation) {
        validation.textContent = '';
    }

    if (!allYears && years.length === 0) {
        if (validation) {
            validation.textContent = 'Select at least one year or choose All Years.';
        }
        return;
    }

    if (count === 0) {
        if (validation) {
            validation.textContent = 'No research records match the selected filters.';
        }
        return;
    }

    const url = reportExportUrls[format];

    if (!url) {
        return;
    }

    const params = new URLSearchParams();
    const department = reportExportDepartmentValue();

    if (department) {
        params.set('department', department);
    }

    years.forEach(year => params.append('years[]', year));

    window.location.href = `${url}?${params.toString()}`;
}

document.getElementById('reportExportDepartment')?.addEventListener('change', updateReportExportCount);
document.getElementById('reportExportAllYears')?.addEventListener('change', event => syncReportYearSelection(event.currentTarget));
reportYearInputs().forEach(input => {
    input.addEventListener('change', event => syncReportYearSelection(event.currentTarget));
});
document.addEventListener('click', event => {
    const dropdown = document.getElementById('reportYearDropdown');

    if (dropdown?.classList.contains('is-open') && !dropdown.contains(event.target)) {
        toggleReportYearDropdown(false);
    }
});
updateReportExportCount();
</script>
@endpush
