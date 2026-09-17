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
            <label>Year</label>
            <select name="year">
                <option value="">All Years</option>
                @foreach($years as $year)
                    <option value="{{ $year }}" {{ request('year') == $year ? 'selected' : '' }}>{{ $year }}</option>
                @endforeach
            </select>
        </div>

        <div class="rr-filter-group rr-filter-sm">
            <label>Type</label>
            <select name="type">
                <option value="">All Types</option>
                @foreach(\App\Models\Research::typesForCategory(\App\Models\Research::SUBMISSION_CATEGORY_JOURNAL) as $typeOption)
                    <option value="{{ $typeOption }}" {{ request('type') == $typeOption ? 'selected' : '' }}>{{ $typeOption }}</option>
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
            <button type="button" onclick="printReport()" class="rr-btn rr-btn-outline">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                Print Page
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
@media(max-width:640px){.rr-filter-form{flex-direction:column;}.rr-filter-group,.rr-filter-group.rr-filter-sm{flex:none;width:100%;min-width:unset;}.rr-print-wrap{margin-left:0;}.rr-bar-row{grid-template-columns:50px 1fr 34px;}}
</style>

@endsection

@push('scripts')
<script>
function printReport() {
    const filters = {
        department: @json($fixedDepartment ?: (request('department') ?: 'All Departments')),
        year:       @json(request('year') ?: 'All Years'),
        type:       @json(request('type') ?: 'All Types'),
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
        <div class="filters"><strong>Filters:</strong> Department: ${filters.department} | Year: ${filters.year} | Type: ${filters.type} | Page: ${filters.page} | Total: ${filters.total} matching paper(s)</div>`;

    @foreach($grouped as $department => $deptResearches)
    html += `<div class="dept-title"><span>{{ $department }}</span><span>{{ (int) ($departmentTotals[$department] ?? $deptResearches->count()) }} matching paper(s)</span></div>
    <table><thead><tr><th>#</th><th>Title</th><th>Author</th><th>Program</th><th>Type</th><th>Year</th></tr></thead><tbody>`;
    @foreach($deptResearches->sortByDesc('year_published') as $i => $r)
    html += `<tr>
        <td>{{ $i + 1 }}</td>
        <td>{{ addslashes($r->title) }}</td>
        <td>{{ addslashes($r->author_name) }}</td>
        <td>{{ addslashes($r->course ?? $r->program ?? '—') }}</td>
        <td><span class="pill" style="background:${typeColors['{{ $r->type }}'] || '#f3f4f6'}">{{ $r->type }}</span></td>
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
</script>
@endpush
