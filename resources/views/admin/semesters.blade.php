@extends('layouts.admin')
@section('title', 'Academic Semesters')
@section('page-title', 'Academic Semesters')

@section('content')
@php
    $activeFilters = collect([
        $selectedSchoolYear ? 'School Year: ' . $selectedSchoolYear : null,
        $selectedSemester ? 'Semester: ' . $selectedSemester : null,
        $selectedStatus ? 'Status: ' . ucfirst($selectedStatus) : null,
    ])->filter();
    $shownStart = $semesters->firstItem() ?? 0;
    $shownEnd = $semesters->lastItem() ?? 0;
@endphp

<div class="sem-page-head">
    <div>
        <span>Academic Terms</span>
        <h2>Semester records</h2>
    </div>
    <strong>{{ number_format($shownStart) }}-{{ number_format($shownEnd) }} of {{ number_format($semesters->total()) }}</strong>
</div>

<form method="GET" class="sem-filter-card">
    <div class="sem-filter-grid">
        <div class="sem-filter-field">
            <label for="sem-school-year">School Year</label>
            <select id="sem-school-year" name="school_year">
                <option value="">All School Years</option>
                @foreach($schoolYears as $schoolYear)
                    <option value="{{ $schoolYear }}" {{ $selectedSchoolYear === $schoolYear ? 'selected' : '' }}>{{ $schoolYear }}</option>
                @endforeach
            </select>
        </div>

        <div class="sem-filter-field">
            <label for="sem-semester">Semester</label>
            <select id="sem-semester" name="semester">
                <option value="">All Semesters</option>
                @foreach($semesterOptions as $semesterOption)
                    <option value="{{ $semesterOption }}" {{ $selectedSemester === $semesterOption ? 'selected' : '' }}>{{ $semesterOption }}</option>
                @endforeach
            </select>
        </div>

        <div class="sem-filter-field">
            <label for="sem-status">Status</label>
            <select id="sem-status" name="status">
                <option value="">All Statuses</option>
                <option value="active" {{ $selectedStatus === 'active' ? 'selected' : '' }}>Active</option>
                <option value="archived" {{ $selectedStatus === 'archived' ? 'selected' : '' }}>Archived</option>
            </select>
        </div>

        <div class="sem-filter-actions">
            <button type="submit" class="sem-btn sem-btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                Filter
            </button>
            <a href="{{ route('admin.semesters') }}" class="sem-btn sem-btn-ghost">Clear</a>
        </div>
    </div>

    @if($activeFilters->isNotEmpty())
        <div class="sem-active-filters">
            @foreach($activeFilters as $filter)
                <span>{{ $filter }}</span>
            @endforeach
        </div>
    @endif
</form>

<section class="sem-table-card">
    <div class="sem-table-head">
        <div>
            <span>Records</span>
            <h3>All semesters</h3>
        </div>
        <span class="sem-count-pill">{{ number_format($semesters->total()) }} term{{ $semesters->total() === 1 ? '' : 's' }}</span>
    </div>

    <div class="sem-table-wrap">
        <table class="sem-table">
            <thead>
                <tr>
                    <th>Semester</th>
                    <th>School Year</th>
                    <th>Status</th>
                    <th>Users</th>
                    <th>Researches</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($semesters as $academicSemester)
                    <tr>
                        <td data-label="Semester">
                            <a href="{{ route('admin.semesters.show', $academicSemester) }}" class="sem-title-link">{{ $academicSemester->semester }}</a>
                        </td>
                        <td data-label="School Year"><span class="sem-year">{{ $academicSemester->school_year }}</span></td>
                        <td data-label="Status">
                            <span class="sem-status {{ $academicSemester->isArchived() ? 'is-archived' : 'is-active' }}">
                                {{ $academicSemester->isArchived() ? 'Archived' : 'Active' }}
                            </span>
                        </td>
                        <td data-label="Users">
                            <a href="{{ route('admin.users', ['school_year' => $academicSemester->school_year, 'semester' => $academicSemester->semester]) }}" class="sem-metric-link">
                                {{ number_format($academicSemester->users_count) }}
                            </a>
                        </td>
                        <td data-label="Researches">
                            <a href="{{ route('admin.researches', ['school_year' => $academicSemester->school_year, 'semester' => $academicSemester->semester]) }}" class="sem-metric-link">
                                {{ number_format($academicSemester->researches_count) }}
                            </a>
                        </td>
                        <td data-label="Created">{{ optional($academicSemester->created_at)->format('M d, Y') ?? 'Legacy' }}</td>
                        <td data-label="Actions">
                            <div class="sem-action-row">
                                <a href="{{ route('admin.semesters.show', $academicSemester) }}" class="sem-btn sem-btn-small sem-btn-view">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    View
                                </a>
                                @if($canArchiveSemesters && ! $academicSemester->isArchived())
                                    <form method="POST" action="{{ route('admin.semesters.archive', $academicSemester) }}" onsubmit="return confirm('Archive {{ $academicSemester->label }}?')">
                                        @csrf
                                        <button type="submit" class="sem-btn sem-btn-small sem-btn-archive">Archive</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="sem-empty-cell">
                            <div class="sem-empty-state">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/></svg>
                                <strong>No semesters found</strong>
                                <p>Imported users will create semester records automatically.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($semesters->hasPages())
        <div class="sem-pagination">{{ $semesters->links('vendor.pagination.custom') }}</div>
    @endif
</section>

<style>
.sem-page-head,.sem-table-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px}
.sem-page-head{margin-bottom:18px}
.sem-page-head span,.sem-table-head span:first-child,.sem-filter-field label{display:inline-flex;color:#6b2fa0;font-size:11px;font-weight:800;letter-spacing:.08em;text-transform:uppercase}
.sem-page-head h2,.sem-table-head h3{margin:4px 0 0;color:#2f144f}
.sem-page-head h2{font-size:28px}
.sem-page-head strong{display:inline-flex;align-items:center;min-height:38px;padding:0 13px;border-radius:999px;background:#f3ecff;color:#5f3890;font-size:13px}
.sem-filter-card,.sem-table-card{background:#fff;border:1px solid rgba(107,47,160,.1);box-shadow:0 12px 28px rgba(57,26,101,.06)}
.sem-filter-card{margin-bottom:22px;padding:18px;border-radius:22px}
.sem-filter-grid{display:grid;grid-template-columns:repeat(3,minmax(160px,1fr)) auto;gap:12px;align-items:end}
.sem-filter-field{min-width:0}
.sem-filter-field label{margin:0 0 7px 2px;color:#7b4bb0;font-size:10.5px}
.sem-filter-field select{width:100%;min-height:48px;border:1.5px solid #e4d7f6;border-radius:13px;background:linear-gradient(180deg,#fff,#fcfaff);color:#1d0b3b;font:inherit;font-size:14px;outline:none;padding:0 38px 0 14px}
.sem-filter-field select:focus{border-color:#7c3aed;background:#fff;box-shadow:0 0 0 4px rgba(124,58,237,.11)}
.sem-filter-actions,.sem-action-row{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.sem-filter-actions{min-height:48px}
.sem-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:44px;padding:0 16px;border:0;border-radius:13px;font-size:13px;font-weight:800;text-decoration:none;cursor:pointer;white-space:nowrap}
.sem-btn svg{width:15px;height:15px;flex-shrink:0}
.sem-btn-primary{background:#42127f;color:#fff;box-shadow:0 14px 24px rgba(59,15,122,.16)}
.sem-btn-ghost{background:#f6f1ff;color:#5f3890;border:1px solid #e5d8f7}
.sem-btn-small{min-height:36px;padding:0 12px;border-radius:11px;font-size:12px}
.sem-btn-view{background:#eef6ff;color:#1d4ed8;border:1px solid #cfe3ff}
.sem-btn-archive{background:#fff1f2;color:#be123c;border:1px solid #fecdd3}
.sem-active-filters{display:flex;gap:8px;flex-wrap:wrap;margin-top:14px;padding-top:14px;border-top:1px solid #f0e9fb}
.sem-active-filters span,.sem-count-pill{display:inline-flex;align-items:center;border-radius:999px;font-size:12px;font-weight:800}
.sem-active-filters span{padding:7px 10px;background:#f8f4ff;color:#6b2fa0;border:1px solid #e3d5f5}
.sem-table-card{border-radius:22px;overflow:hidden}
.sem-table-head{padding:18px 20px;border-bottom:1px solid #f0e9fb}
.sem-count-pill{padding:8px 12px;background:#f3ecff;color:#6a35a1;border:1px solid #dfcff8}
.sem-table-wrap{overflow:auto}
.sem-table{width:100%;border-collapse:collapse;font-size:14px}
.sem-table th{padding:13px 16px;text-align:left;color:#6f6189;background:#faf7ff;font-size:11px;text-transform:uppercase;letter-spacing:.06em;white-space:nowrap}
.sem-table td{padding:15px 16px;border-top:1px solid #f0e9fb;color:#2f144f;vertical-align:middle}
.sem-title-link{color:#3b0f7a;font-weight:900;text-decoration:none}
.sem-title-link:hover{text-decoration:underline}
.sem-year{font-weight:800;color:#4c1d95}
.sem-status{display:inline-flex;align-items:center;min-height:28px;padding:0 10px;border-radius:999px;font-size:12px;font-weight:800}
.sem-status.is-active{background:#dcfce7;color:#166534}
.sem-status.is-archived{background:#e5e7eb;color:#4b5563}
.sem-metric-link{display:inline-flex;align-items:center;justify-content:center;min-width:42px;min-height:32px;padding:0 10px;border-radius:999px;background:#f8f4ff;color:#5f3890;font-weight:900;text-decoration:none}
.sem-metric-link:hover{background:#ede2ff}
.sem-action-row form{margin:0}
.sem-empty-cell{padding:46px 18px!important}
.sem-empty-state{display:grid;place-items:center;gap:8px;text-align:center;color:#837596}
.sem-empty-state svg{width:34px;height:34px;color:#9a84bc}
.sem-empty-state strong{color:#2f144f}
.sem-empty-state p{margin:0;font-size:13px}
.sem-pagination{padding:16px 20px;border-top:1px solid #f0e9fb}
@media (max-width: 1040px){.sem-filter-grid{grid-template-columns:repeat(2,minmax(160px,1fr))}.sem-filter-actions{grid-column:1 / -1}}
@media (max-width: 720px){.sem-page-head,.sem-table-head{flex-direction:column}.sem-filter-grid{grid-template-columns:1fr}.sem-table th{display:none}.sem-table,.sem-table tbody,.sem-table tr,.sem-table td{display:block;width:100%}.sem-table tr{border-top:1px solid #f0e9fb}.sem-table td{display:flex;justify-content:space-between;gap:16px;border-top:0;padding:11px 16px}.sem-table td::before{content:attr(data-label);color:#86789a;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.06em}.sem-action-row{justify-content:flex-end}}
</style>
@endsection
