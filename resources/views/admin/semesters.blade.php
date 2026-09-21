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
    $semesterUpdateErrorId = (string) old('semester_id', session('semester_update_id', ''));
    $semesterUpdateOldInput = $errors->semesterUpdate->any() ? [
        'schoolYear' => old('school_year'),
        'semester' => old('semester'),
        'startDateValue' => old('start_date'),
        'endDateValue' => old('end_date'),
        'isActive' => old('is_active', '0'),
    ] : null;
    $semesterUpdateErrorMessage = $errors->semesterUpdate->first();
    $shownStart = $semesters->firstItem() ?? 0;
    $shownEnd = $semesters->lastItem() ?? 0;
    $semesterModalRecords = $semesters->getCollection()->mapWithKeys(function ($academicSemester) {
        return [
            (string) $academicSemester->id => [
                'id' => (string) $academicSemester->id,
                'title' => $academicSemester->label,
                'semester' => $academicSemester->semester,
                'schoolYear' => $academicSemester->school_year,
                'startDate' => optional($academicSemester->start_date)->format('M d, Y') ?? 'Not set',
                'startDateValue' => optional($academicSemester->start_date)->format('Y-m-d') ?? '',
                'endDate' => optional($academicSemester->end_date)->format('M d, Y') ?? 'Not set',
                'endDateValue' => optional($academicSemester->end_date)->format('Y-m-d') ?? '',
                'status' => $academicSemester->isArchived() ? 'Archived' : 'Active',
                'statusClass' => $academicSemester->isArchived() ? 'is-archived' : 'is-active',
                'isActive' => (bool) $academicSemester->is_active,
                'usersCount' => number_format($academicSemester->users_count),
                'researchesCount' => number_format($academicSemester->researches_count),
                'createdDate' => optional($academicSemester->created_at)->format('M d, Y') ?? 'Legacy',
                'updateUrl' => route('admin.semesters.update', $academicSemester),
                'usersUrl' => route('admin.users', ['school_year' => $academicSemester->school_year, 'semester' => $academicSemester->semester]),
                'researchesUrl' => route('admin.researches', ['school_year' => $academicSemester->school_year, 'semester' => $academicSemester->semester]),
            ],
        ];
    });
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
                    <th>End Date</th>
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
                            <button type="button" class="sem-title-link sem-modal-trigger" data-semester-id="{{ $academicSemester->id }}">{{ $academicSemester->semester }}</button>
                        </td>
                        <td data-label="School Year"><span class="sem-year">{{ $academicSemester->school_year }}</span></td>
                        <td data-label="End Date">{{ optional($academicSemester->end_date)->format('M d, Y') ?? 'Not set' }}</td>
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
                                <button type="button" class="sem-btn sem-btn-small sem-btn-view sem-modal-trigger" data-semester-id="{{ $academicSemester->id }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    View
                                </button>
                                @if($canManageSemesters)
                                    <button type="button" class="sem-btn sem-btn-small sem-btn-edit sem-edit-trigger" data-semester-id="{{ $academicSemester->id }}">Edit</button>
                                @endif
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
                        <td colspan="8" class="sem-empty-cell">
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

<div class="sem-modal-backdrop" id="semesterDetailsModal" aria-hidden="true">
    <section class="sem-modal" role="dialog" aria-modal="true" aria-labelledby="semesterModalTitle">
        <div class="sem-modal-head">
            <div>
                <span>Semester Details</span>
                <h3 id="semesterModalTitle">Semester record</h3>
                <p id="semesterModalSubtitle">Academic term information</p>
            </div>
            <button type="button" class="sem-modal-close" data-semester-modal-close aria-label="Close semester details">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>

        <div class="sem-modal-status-row">
            <span id="semesterModalStatus" class="sem-status is-active">Active</span>
            <small>Created <strong id="semesterModalCreated">Legacy</strong></small>
        </div>

        <div class="sem-modal-info-grid">
            <div class="sem-modal-info">
                <span>School Year</span>
                <strong id="semesterModalSchoolYear">-</strong>
            </div>
            <div class="sem-modal-info">
                <span>Semester</span>
                <strong id="semesterModalSemester">-</strong>
            </div>
            <div class="sem-modal-info">
                <span>Start Date</span>
                <strong id="semesterModalStartDate">Not set</strong>
            </div>
            <div class="sem-modal-info">
                <span>End Date</span>
                <strong id="semesterModalEndDate">Not set</strong>
            </div>
            <div class="sem-modal-info">
                <span>Active Status</span>
                <strong id="semesterModalStatusText">Active</strong>
            </div>
        </div>

        <div class="sem-modal-metrics">
            <a href="#" id="semesterModalUsersLink">
                <span>Assigned Users</span>
                <strong id="semesterModalUsers">0</strong>
            </a>
            <a href="#" id="semesterModalResearchesLink">
                <span>Research Records</span>
                <strong id="semesterModalResearches">0</strong>
            </a>
        </div>

        <div class="sem-modal-actions">
            @if($canManageSemesters)
                <button type="button" id="semesterModalEditButton" class="sem-btn sem-btn-primary">Edit Record</button>
            @endif
            <button type="button" class="sem-btn sem-btn-ghost" data-semester-modal-close>Close</button>
        </div>
    </section>
</div>

@if($canManageSemesters)
<div class="sem-modal-backdrop" id="semesterEditModal" aria-hidden="true">
    <section class="sem-modal sem-edit-modal" role="dialog" aria-modal="true" aria-labelledby="semesterEditModalTitle">
        <div class="sem-modal-head">
            <div>
                <span>Edit Semester</span>
                <h3 id="semesterEditModalTitle">Semester record</h3>
                <p id="semesterEditModalSubtitle">Academic term information</p>
            </div>
            <button type="button" class="sem-modal-close" data-semester-edit-close aria-label="Cancel editing semester">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>

        <form method="POST" id="semesterEditForm" class="sem-edit-form">
            @csrf
            @method('PATCH')
            <input type="hidden" name="semester_id" id="semesterEditId">

            <div class="sem-form-alert" id="semesterEditAlert" role="alert" @if(! $errors->semesterUpdate->any()) hidden @endif>
                {{ $errors->semesterUpdate->first() }}
            </div>

            <div class="sem-edit-grid">
                <label class="sem-edit-field" for="semesterEditSchoolYear">
                    <span>School Year</span>
                    <input type="text" id="semesterEditSchoolYear" name="school_year" placeholder="2025-2026" required>
                </label>

                <label class="sem-edit-field" for="semesterEditSemester">
                    <span>Semester</span>
                    <select id="semesterEditSemester" name="semester" required>
                        @foreach($semesterOptions as $semesterOption)
                            <option value="{{ $semesterOption }}">{{ $semesterOption }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="sem-edit-field" for="semesterEditStartDate">
                    <span>Start Date</span>
                    <input type="date" id="semesterEditStartDate" name="start_date" required>
                </label>

                <label class="sem-edit-field" for="semesterEditEndDate">
                    <span>End Date</span>
                    <input type="date" id="semesterEditEndDate" name="end_date" required>
                </label>

                <label class="sem-active-toggle" for="semesterEditIsActive">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" id="semesterEditIsActive" name="is_active" value="1">
                    <span>
                        <strong>Active Status</strong>
                        <small id="semesterEditActiveText">Active</small>
                    </span>
                </label>
            </div>

            <div class="sem-modal-actions">
                <button type="submit" class="sem-btn sem-btn-primary">Save Changes</button>
                <button type="button" class="sem-btn sem-btn-ghost" data-semester-edit-close>Cancel</button>
            </div>
        </form>
    </section>
</div>
@endif

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
.sem-btn-edit{background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0}
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
.sem-title-link{display:inline-flex;padding:0;border:0;background:transparent;color:#3b0f7a;font:inherit;font-weight:900;text-decoration:none;cursor:pointer}
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
.sem-modal-backdrop{position:fixed;inset:0;z-index:1000;display:none;align-items:center;justify-content:center;padding:24px;background:rgba(23,8,45,.52);backdrop-filter:blur(10px)}
.sem-modal-backdrop.is-open{display:flex}
body.sem-modal-open{overflow:hidden}
.sem-modal{width:min(680px,100%);max-height:min(720px,calc(100vh - 48px));overflow:auto;border-radius:22px;background:#fff;border:1px solid rgba(107,47,160,.12);box-shadow:0 28px 80px rgba(23,8,45,.28)}
.sem-modal-head{display:flex;align-items:flex-start;justify-content:space-between;gap:18px;padding:24px 24px 18px;border-bottom:1px solid #f0e9fb;background:linear-gradient(180deg,#fff,#fbf8ff)}
.sem-modal-head span,.sem-modal-info span,.sem-modal-metrics span{display:inline-flex;color:#6b2fa0;font-size:11px;font-weight:900;letter-spacing:.08em;text-transform:uppercase}
.sem-modal-head h3{margin:5px 0 4px;color:#2f144f;font-size:26px;line-height:1.15}
.sem-modal-head p{margin:0;color:#6b5b80;font-size:14px;font-weight:700}
.sem-modal-close{display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;border:1px solid #e5d8f7;border-radius:12px;background:#fff;color:#5f3890;cursor:pointer;flex-shrink:0}
.sem-modal-close svg{width:18px;height:18px}
.sem-modal-close:hover{background:#f6f1ff}
.sem-modal-status-row{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:18px 24px 0}
.sem-modal-status-row small{color:#837596;font-size:13px;font-weight:700}
.sem-modal-status-row small strong{color:#2f144f}
.sem-modal-info-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;padding:18px 24px}
.sem-modal-info{min-width:0;padding:0 0 13px;border-bottom:1px solid #efe7fb}
.sem-modal-info strong{display:block;margin-top:6px;color:#2f144f;font-size:18px;line-height:1.25}
.sem-modal-metrics{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;padding:0 24px 20px}
.sem-modal-metrics a{display:flex;align-items:center;justify-content:space-between;gap:16px;min-height:58px;padding:0 16px;border-radius:14px;background:#f8f4ff;border:1px solid #eadff8;text-decoration:none}
.sem-modal-metrics a:hover{background:#f0e7ff}
.sem-modal-metrics strong{color:#42127f;font-size:22px}
.sem-modal-actions{display:flex;align-items:center;justify-content:flex-end;gap:10px;padding:18px 24px 24px;border-top:1px solid #f0e9fb}
.sem-edit-modal{width:min(720px,100%)}
.sem-edit-form{display:grid}
.sem-form-alert{margin:20px 24px 0;padding:12px 14px;border-radius:12px;background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;font-size:13px;font-weight:800}
.sem-edit-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;padding:20px 24px}
.sem-edit-field{display:grid;gap:7px;min-width:0}
.sem-edit-field span,.sem-active-toggle strong{color:#6b2fa0;font-size:11px;font-weight:900;letter-spacing:.08em;text-transform:uppercase}
.sem-edit-field input,.sem-edit-field select{width:100%;min-height:48px;border:1.5px solid #e4d7f6;border-radius:13px;background:linear-gradient(180deg,#fff,#fcfaff);color:#1d0b3b;font:inherit;font-size:14px;outline:none;padding:0 13px}
.sem-edit-field input:focus,.sem-edit-field select:focus{border-color:#7c3aed;background:#fff;box-shadow:0 0 0 4px rgba(124,58,237,.11)}
.sem-active-toggle{grid-column:1 / -1;display:flex;align-items:center;gap:12px;min-height:58px;padding:0 14px;border:1.5px solid #d9f7df;border-radius:14px;background:#f7fef8;cursor:pointer}
.sem-active-toggle input[type="checkbox"]{width:19px;height:19px;accent-color:#16a34a;flex-shrink:0}
.sem-active-toggle span{display:grid;gap:3px}
.sem-active-toggle small{color:#476a52;font-size:13px;font-weight:800}
@media (max-width: 1040px){.sem-filter-grid{grid-template-columns:repeat(2,minmax(160px,1fr))}.sem-filter-actions{grid-column:1 / -1}}
@media (max-width: 720px){.sem-page-head,.sem-table-head{flex-direction:column}.sem-filter-grid,.sem-modal-info-grid,.sem-modal-metrics,.sem-edit-grid{grid-template-columns:1fr}.sem-table th{display:none}.sem-table,.sem-table tbody,.sem-table tr,.sem-table td{display:block;width:100%}.sem-table tr{border-top:1px solid #f0e9fb}.sem-table td{display:flex;justify-content:space-between;gap:16px;border-top:0;padding:11px 16px}.sem-table td::before{content:attr(data-label);color:#86789a;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.06em}.sem-action-row{justify-content:flex-end}.sem-modal-backdrop{padding:14px}.sem-modal-head,.sem-modal-status-row,.sem-modal-info-grid,.sem-modal-metrics,.sem-edit-grid,.sem-modal-actions{padding-left:18px;padding-right:18px}.sem-form-alert{margin-left:18px;margin-right:18px}.sem-modal-actions{justify-content:stretch}.sem-modal-actions .sem-btn{flex:1}}
</style>
@endsection

@push('scripts')
<script>
(function() {
    const semesterRecords = @json($semesterModalRecords);
    const semesterUpdateErrorId = @json($semesterUpdateErrorId);
    const semesterUpdateOldInput = @json($semesterUpdateOldInput);
    const semesterUpdateErrorMessage = @json($semesterUpdateErrorMessage);
    const modal = document.getElementById('semesterDetailsModal');
    const editModal = document.getElementById('semesterEditModal');

    if (!modal) {
        return;
    }

    const closeButtons = modal.querySelectorAll('[data-semester-modal-close]');
    const focusTarget = modal.querySelector('.sem-modal-close');
    const editCloseButtons = editModal ? editModal.querySelectorAll('[data-semester-edit-close]') : [];
    const editForm = document.getElementById('semesterEditForm');
    const editFocusTarget = document.getElementById('semesterEditSchoolYear');
    const editAlert = document.getElementById('semesterEditAlert');
    const editActiveCheckbox = document.getElementById('semesterEditIsActive');
    const editActiveText = document.getElementById('semesterEditActiveText');
    const modalEditButton = document.getElementById('semesterModalEditButton');
    let previousFocus = null;
    let previousEditFocus = null;

    function setText(id, value) {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = value || '-';
        }
    }

    function setLink(id, href) {
        const element = document.getElementById(id);
        if (element) {
            element.href = href || '#';
        }
    }

    function setValue(id, value) {
        const element = document.getElementById(id);
        if (element) {
            element.value = value || '';
        }
    }

    function getRecord(id) {
        return semesterRecords[String(id)];
    }

    function valueFrom(values, key, fallback) {
        if (values && Object.prototype.hasOwnProperty.call(values, key) && values[key] !== null) {
            return values[key];
        }

        return fallback;
    }

    function isTruthy(value) {
        return value === true || value === 1 || value === '1' || value === 'true' || value === 'on';
    }

    function setActiveState(value) {
        const active = isTruthy(value);

        if (editActiveCheckbox) {
            editActiveCheckbox.checked = active;
        }

        if (editActiveText) {
            editActiveText.textContent = active ? 'Active' : 'Inactive';
        }
    }

    function setEditAlert(message) {
        if (!editAlert) {
            return;
        }

        editAlert.textContent = message || '';
        editAlert.hidden = !message;
    }

    function syncModalBodyState() {
        const detailsOpen = modal.classList.contains('is-open');
        const editOpen = editModal && editModal.classList.contains('is-open');
        document.body.classList.toggle('sem-modal-open', detailsOpen || editOpen);
    }

    function openSemesterModal(record) {
        if (!record) {
            return;
        }

        previousFocus = document.activeElement;
        setText('semesterModalTitle', record.title);
        setText('semesterModalSubtitle', record.schoolYear + ' - ' + record.semester);
        setText('semesterModalSchoolYear', record.schoolYear);
        setText('semesterModalSemester', record.semester);
        setText('semesterModalStartDate', record.startDate);
        setText('semesterModalEndDate', record.endDate);
        setText('semesterModalStatusText', record.status);
        setText('semesterModalCreated', record.createdDate);
        setText('semesterModalUsers', record.usersCount);
        setText('semesterModalResearches', record.researchesCount);
        setLink('semesterModalUsersLink', record.usersUrl);
        setLink('semesterModalResearchesLink', record.researchesUrl);

        if (modalEditButton) {
            modalEditButton.dataset.semesterId = record.id;
        }

        const status = document.getElementById('semesterModalStatus');
        if (status) {
            status.textContent = record.status;
            status.className = 'sem-status ' + record.statusClass;
        }

        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        syncModalBodyState();

        if (focusTarget) {
            focusTarget.focus();
        }
    }

    function closeSemesterModal(restoreFocus = true) {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        syncModalBodyState();

        if (restoreFocus && previousFocus && typeof previousFocus.focus === 'function') {
            previousFocus.focus();
        }
    }

    function openSemesterEditModal(record, options = {}) {
        if (!record || !editModal || !editForm) {
            return;
        }

        const detailsWasOpen = modal.classList.contains('is-open');
        previousEditFocus = detailsWasOpen ? previousFocus : document.activeElement;
        closeSemesterModal(false);

        const values = options.useOld && semesterUpdateOldInput ? semesterUpdateOldInput : record;
        editForm.action = record.updateUrl;
        setValue('semesterEditId', record.id);
        setText('semesterEditModalTitle', 'Edit ' + record.title);
        setText('semesterEditModalSubtitle', record.schoolYear + ' - ' + record.semester);
        setValue('semesterEditSchoolYear', valueFrom(values, 'schoolYear', record.schoolYear));
        setValue('semesterEditSemester', valueFrom(values, 'semester', record.semester));
        setValue('semesterEditStartDate', valueFrom(values, 'startDateValue', record.startDateValue));
        setValue('semesterEditEndDate', valueFrom(values, 'endDateValue', record.endDateValue));
        setActiveState(valueFrom(values, 'isActive', record.isActive));
        setEditAlert(options.useOld ? semesterUpdateErrorMessage : '');

        editModal.classList.add('is-open');
        editModal.setAttribute('aria-hidden', 'false');
        syncModalBodyState();

        if (editFocusTarget) {
            editFocusTarget.focus();
        }
    }

    function closeSemesterEditModal() {
        if (!editModal) {
            return;
        }

        editModal.classList.remove('is-open');
        editModal.setAttribute('aria-hidden', 'true');
        setEditAlert('');
        syncModalBodyState();

        if (previousEditFocus && typeof previousEditFocus.focus === 'function') {
            previousEditFocus.focus();
        }
    }

    document.querySelectorAll('.sem-modal-trigger').forEach(function(trigger) {
        trigger.addEventListener('click', function() {
            openSemesterModal(getRecord(trigger.dataset.semesterId));
        });
    });

    document.querySelectorAll('.sem-edit-trigger').forEach(function(trigger) {
        trigger.addEventListener('click', function() {
            openSemesterEditModal(getRecord(trigger.dataset.semesterId));
        });
    });

    if (modalEditButton) {
        modalEditButton.addEventListener('click', function() {
            openSemesterEditModal(getRecord(modalEditButton.dataset.semesterId));
        });
    }

    if (editActiveCheckbox) {
        editActiveCheckbox.addEventListener('change', function() {
            setActiveState(editActiveCheckbox.checked);
        });
    }

    closeButtons.forEach(function(button) {
        button.addEventListener('click', closeSemesterModal);
    });

    editCloseButtons.forEach(function(button) {
        button.addEventListener('click', closeSemesterEditModal);
    });

    modal.addEventListener('click', function(event) {
        if (event.target === modal) {
            closeSemesterModal();
        }
    });

    if (editModal) {
        editModal.addEventListener('click', function(event) {
            if (event.target === editModal) {
                closeSemesterEditModal();
            }
        });
    }

    window.addEventListener('keydown', function(event) {
        if (event.key !== 'Escape') {
            return;
        }

        if (editModal && editModal.classList.contains('is-open')) {
            closeSemesterEditModal();

            return;
        }

        if (modal.classList.contains('is-open')) {
            closeSemesterModal();
        }
    });

    if (semesterUpdateErrorId && getRecord(semesterUpdateErrorId)) {
        openSemesterEditModal(getRecord(semesterUpdateErrorId), { useOld: true });
    }
})();
</script>
@endpush
