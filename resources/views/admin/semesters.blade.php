@extends('layouts.admin')
@section('title', 'Semester Management')
@section('page-title', 'Semester Management')

@section('content')
@php
    $activeFilters = collect([
        $selectedSchoolYear ? 'Academic Year: ' . $selectedSchoolYear : null,
        $selectedSemester ? 'Semester: ' . $selectedSemester : null,
        $selectedStatus ? 'Status: ' . ucfirst($selectedStatus) : null,
    ])->filter();

    $shownStart = $semesters->firstItem() ?? 0;
    $shownEnd = $semesters->lastItem() ?? 0;

    $semesterModalRecords = $semesters->getCollection()->mapWithKeys(function ($academicSemester) use ($departmentBreakdowns) {
        $isFinished = $academicSemester->hasExpired();
        $isOpen = $academicSemester->isOpen();
        $statusText = $isOpen ? 'Active' : ($isFinished ? 'Finished' : 'Inactive');
        $statusClass = $isOpen ? 'is-active' : ($isFinished ? 'is-finished' : 'is-inactive');
        $deptBreakdown = $academicSemester->department_breakdown ?? ($departmentBreakdowns[$academicSemester->id] ?? []);

        return [
            (string) $academicSemester->id => [
                'id' => (string) $academicSemester->id,
                'title' => $academicSemester->label,
                'semester' => $academicSemester->semester,
                'semesterLabel' => $academicSemester->semester_label,
                'schoolYear' => $academicSemester->school_year,
                'startDate' => optional($academicSemester->start_date)->format('M d, Y') ?? 'Not set',
                'rawStartDate' => optional($academicSemester->start_date)->format('Y-m-d') ?? '',
                'endDate' => optional($academicSemester->end_date)->format('M d, Y') ?? 'Not set',
                'rawEndDate' => optional($academicSemester->end_date)->format('Y-m-d') ?? '',
                'status' => $statusText,
                'statusClass' => $statusClass,
                'isActive' => (bool) $isOpen,
                'isFinished' => (bool) $isFinished,
                'usersCount' => number_format($academicSemester->users_count),
                'researchesCount' => number_format($academicSemester->researches_count),
                'departmentBreakdown' => $deptBreakdown,
                'createdDate' => optional($academicSemester->created_at)->format('M d, Y') ?? 'Legacy',
                'updateUrl' => route('admin.semesters.update', $academicSemester),
                'usersUrl' => route('admin.users', ['school_year' => $academicSemester->school_year, 'semester' => $academicSemester->semester]),
                'researchesUrl' => route('admin.researches', ['school_year' => $academicSemester->school_year, 'semester' => $academicSemester->semester]),
            ],
        ];
    });
@endphp

@if(session('success'))
    <div class="sem-alert sem-alert-success" role="alert">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        <span>{{ session('success') }}</span>
    </div>
@endif

@if(session('error'))
    <div class="sem-alert sem-alert-danger" role="alert">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span>{{ session('error') }}</span>
    </div>
@endif

@if($errors->any())
    <div class="sem-alert sem-alert-danger" role="alert">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <div>
            <strong>Please correct the following errors:</strong>
            <ul style="margin:4px 0 0 16px; padding:0;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

@if(! auth()->user()?->isDepartmentDean())
    <div class="sem-page-head">
        <div>
            <span>Academic Terms & Periods</span>
            <h2>Semester Management</h2>
            <p class="sem-page-sub">Manage Academic Years, 1st Semester, and 2nd Semester. Expired semesters are automatically finished and locked for historical integrity. Only one semester in the same Academic Year is active at a time.</p>
        </div>
        @if($canManageSemesters)
            <button type="button" class="sem-btn sem-btn-primary" id="openAddSemesterBtn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add Academic Year / Semester
            </button>
        @endif
    </div>
@endif

{{-- ── Primary Academic Years & Semesters Section ─────────────────────────────── --}}
<section class="sem-sy-section">
    <div class="sem-section-head">
        <div>
            <span class="sem-badge-label">Active Term Control</span>
            <h3>Academic Year Management</h3>
        </div>
        <span class="sem-count-pill">{{ count($schoolYearGroups) }} Academic Year{{ count($schoolYearGroups) === 1 ? '' : 's' }}</span>
    </div>

    @if($schoolYearGroups->isEmpty())
        <div class="sem-empty-card">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/></svg>
            <strong>No Academic Years Found</strong>
            <p>Get started by creating a Academic Year and configuring 1st & 2nd Semesters.</p>
            @if($canManageSemesters)
                <button type="button" class="sem-btn sem-btn-primary" style="margin-top:12px;" onclick="document.getElementById('openAddSemesterBtn').click()">
                    + Create First Academic Year
                </button>
            @endif
        </div>
    @else
        <div class="sem-sy-list">
            @foreach($schoolYearGroups as $group)
                @php
                    $firstSem = $group['first_semester'];
                    $secondSem = $group['second_semester'];
                    $activeSem = $group['active_semester'];

                    $firstSemFinished = $firstSem ? $firstSem->hasExpired() : false;
                    $firstSemOpen = $firstSem ? $firstSem->isOpen() : false;

                    $secondSemFinished = $secondSem ? $secondSem->hasExpired() : false;
                    $secondSemOpen = $secondSem ? $secondSem->isOpen() : false;
                @endphp
                <div class="sem-sy-card {{ $activeSem ? 'has-active' : '' }}">
                    <div class="sem-sy-card-head">
                        <div class="sem-sy-title-wrap">
                            <span class="sem-sy-label">Academic Year</span>
                            <h4 class="sem-sy-title">{{ $group['school_year'] }}</h4>
                        </div>
                        <div class="sem-sy-status-badge {{ $activeSem ? 'is-active-sy' : 'is-inactive-sy' }}">
                            @if($activeSem)
                                <span class="sem-pulse-dot"></span>
                                {{ $activeSem->semester_label }} Active
                            @else
                                <span class="sem-inactive-dot"></span>
                                No Active Semester
                            @endif
                        </div>
                    </div>

                    <div class="sem-terms-grid">
                        {{-- ── 1st Semester Box ─────────────────────────────── --}}
                        <div class="sem-term-box {{ $firstSemOpen ? 'is-term-active' : ($firstSemFinished ? 'is-term-finished' : ($firstSem ? 'is-term-inactive' : 'is-term-missing')) }}">
                            <div class="sem-term-box-head">
                                <div>
                                    <span class="sem-term-tag">1st Semester</span>
                                    <h5 class="sem-term-name">{{ $group['school_year'] }} — 1st Sem</h5>
                                </div>
                                @if($firstSem)
                                    <span class="sem-pill-badge {{ $firstSemOpen ? 'is-active' : ($firstSemFinished ? 'is-finished' : 'is-inactive') }}">
                                        @if($firstSemOpen)
                                            <span class="sem-pulse-dot"></span> ACTIVE
                                        @elseif($firstSemFinished)
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" style="width:12px;height:12px;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg> FINISHED
                                        @else
                                            INACTIVE
                                        @endif
                                    </span>
                                @else
                                    <span class="sem-pill-badge is-missing">NOT CREATED</span>
                                @endif
                            </div>

                            @if($firstSem)
                                <div class="sem-term-info-row">
                                    <div class="sem-term-meta">
                                        <span>Date Range:</span>
                                        <strong>
                                            @if($firstSem->start_date && $firstSem->end_date)
                                                {{ $firstSem->start_date->format('M d, Y') }} – {{ $firstSem->end_date->format('M d, Y') }}
                                            @elseif($firstSem->start_date)
                                                From {{ $firstSem->start_date->format('M d, Y') }}
                                            @elseif($firstSem->end_date)
                                                Until {{ $firstSem->end_date->format('M d, Y') }}
                                            @else
                                                Dates not specified
                                            @endif
                                        </strong>
                                    </div>
                                    <div class="sem-term-stats">
                                        <a href="{{ route('admin.users', ['school_year' => $group['school_year'], 'semester' => '1st']) }}" title="Enrolled Users">
                                            <strong>{{ number_format($firstSem->users_count) }}</strong> Users
                                        </a>
                                        <a href="{{ route('admin.researches', ['school_year' => $group['school_year'], 'semester' => '1st']) }}" title="Research Papers">
                                            <strong>{{ number_format($firstSem->researches_count) }}</strong> Researches
                                        </a>
                                    </div>

                                    @if(!empty($firstSem->department_breakdown))
                                        <div class="sem-dept-breakdown-row">
                                            <span class="sem-dept-breakdown-title">Imported by Department:</span>
                                            <div class="sem-dept-chips">
                                                @foreach($firstSem->department_breakdown as $deptName => $deptCount)
                                                    <span class="sem-dept-chip" title="{{ $deptName }}: {{ $deptCount }} user{{ $deptCount == 1 ? '' : 's' }}">
                                                        <span class="sem-dept-chip-name">{{ \Illuminate\Support\Str::limit($deptName, 22) }}:</span>
                                                        <strong class="sem-dept-chip-count">{{ number_format($deptCount) }}</strong>
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <div class="sem-term-actions">
                                    @if($firstSemOpen)
                                        <button type="button" class="sem-btn sem-btn-current-active" disabled>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                            1st Sem is ACTIVE
                                        </button>
                                        @if($canManageSemesters)
                                            <button type="button" class="sem-btn sem-btn-edit-sm js-open-edit-modal" data-semester-id="{{ $firstSem->id }}">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                                                Edit
                                            </button>
                                        @endif
                                        <button type="button" class="sem-btn sem-btn-view-sm sem-modal-trigger" data-semester-id="{{ $firstSem->id }}">
                                            Details
                                        </button>
                                    @elseif($firstSemFinished)
                                        <span class="sem-locked-status-pill" title="This semester has ended and is locked for historical records.">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                            <span>Finished & Locked</span>
                                        </span>
                                        <button type="button" class="sem-btn sem-btn-view-sm sem-modal-trigger" data-semester-id="{{ $firstSem->id }}" style="margin-left:auto;">
                                            View Details
                                        </button>
                                    @else
                                        @if($canManageSemesters)
                                            <button type="button" class="sem-btn sem-btn-activate js-open-activate-modal"
                                                data-semester-id="{{ $firstSem->id }}"
                                                data-semester-name="1st Semester"
                                                data-school-year="{{ $group['school_year'] }}"
                                                data-other-semester="2nd Semester"
                                                data-activate-url="{{ route('admin.semesters.activate', $firstSem) }}">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18.36 6.64a9 9 0 1 1-12.73 0"/><line x1="12" y1="2" x2="12" y2="12"/></svg>
                                                Activate 1st Semester
                                            </button>
                                            <button type="button" class="sem-btn sem-btn-edit-sm js-open-edit-modal" data-semester-id="{{ $firstSem->id }}">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                                                Edit
                                            </button>
                                        @endif
                                        <button type="button" class="sem-btn sem-btn-view-sm sem-modal-trigger" data-semester-id="{{ $firstSem->id }}">
                                            Details
                                        </button>
                                    @endif
                                </div>
                            @else
                                <div class="sem-missing-state">
                                    <p>1st Semester is not yet registered for {{ $group['school_year'] }}.</p>
                                    @if($canManageSemesters)
                                        <button type="button" class="sem-btn sem-btn-create-missing js-create-missing" data-school-year="{{ $group['school_year'] }}" data-semester="1st">
                                            + Initialize 1st Semester
                                        </button>
                                    @endif
                                </div>
                            @endif
                        </div>

                        {{-- ── 2nd Semester Box ─────────────────────────────── --}}
                        <div class="sem-term-box {{ $secondSemOpen ? 'is-term-active' : ($secondSemFinished ? 'is-term-finished' : ($secondSem ? 'is-term-inactive' : 'is-term-missing')) }}">
                            <div class="sem-term-box-head">
                                <div>
                                    <span class="sem-term-tag">2nd Semester</span>
                                    <h5 class="sem-term-name">{{ $group['school_year'] }} — 2nd Sem</h5>
                                </div>
                                @if($secondSem)
                                    <span class="sem-pill-badge {{ $secondSemOpen ? 'is-active' : ($secondSemFinished ? 'is-finished' : 'is-inactive') }}">
                                        @if($secondSemOpen)
                                            <span class="sem-pulse-dot"></span> ACTIVE
                                        @elseif($secondSemFinished)
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" style="width:12px;height:12px;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg> FINISHED
                                        @else
                                            INACTIVE
                                        @endif
                                    </span>
                                @else
                                    <span class="sem-pill-badge is-missing">NOT CREATED</span>
                                @endif
                            </div>

                            @if($secondSem)
                                <div class="sem-term-info-row">
                                    <div class="sem-term-meta">
                                        <span>Date Range:</span>
                                        <strong>
                                            @if($secondSem->start_date && $secondSem->end_date)
                                                {{ $secondSem->start_date->format('M d, Y') }} – {{ $secondSem->end_date->format('M d, Y') }}
                                            @elseif($secondSem->start_date)
                                                From {{ $secondSem->start_date->format('M d, Y') }}
                                            @elseif($secondSem->end_date)
                                                Until {{ $secondSem->end_date->format('M d, Y') }}
                                            @else
                                                Dates not specified
                                            @endif
                                        </strong>
                                    </div>
                                    <div class="sem-term-stats">
                                        <a href="{{ route('admin.users', ['school_year' => $group['school_year'], 'semester' => '2nd']) }}" title="Enrolled Users">
                                            <strong>{{ number_format($secondSem->users_count) }}</strong> Users
                                        </a>
                                        <a href="{{ route('admin.researches', ['school_year' => $group['school_year'], 'semester' => '2nd']) }}" title="Research Papers">
                                            <strong>{{ number_format($secondSem->researches_count) }}</strong> Researches
                                        </a>
                                    </div>

                                    @if(!empty($secondSem->department_breakdown))
                                        <div class="sem-dept-breakdown-row">
                                            <span class="sem-dept-breakdown-title">Imported by Department:</span>
                                            <div class="sem-dept-chips">
                                                @foreach($secondSem->department_breakdown as $deptName => $deptCount)
                                                    <span class="sem-dept-chip" title="{{ $deptName }}: {{ $deptCount }} user{{ $deptCount == 1 ? '' : 's' }}">
                                                        <span class="sem-dept-chip-name">{{ \Illuminate\Support\Str::limit($deptName, 22) }}:</span>
                                                        <strong class="sem-dept-chip-count">{{ number_format($deptCount) }}</strong>
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <div class="sem-term-actions">
                                    @if($secondSemOpen)
                                        <button type="button" class="sem-btn sem-btn-current-active" disabled>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                            2nd Sem is ACTIVE
                                        </button>
                                        @if($canManageSemesters)
                                            <button type="button" class="sem-btn sem-btn-edit-sm js-open-edit-modal" data-semester-id="{{ $secondSem->id }}">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                                                Edit
                                            </button>
                                        @endif
                                        <button type="button" class="sem-btn sem-btn-view-sm sem-modal-trigger" data-semester-id="{{ $secondSem->id }}">
                                            Details
                                        </button>
                                    @elseif($secondSemFinished)
                                        <span class="sem-locked-status-pill" title="This semester has ended and is locked for historical records.">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                            <span>Finished & Locked</span>
                                        </span>
                                        <button type="button" class="sem-btn sem-btn-view-sm sem-modal-trigger" data-semester-id="{{ $secondSem->id }}" style="margin-left:auto;">
                                            View Details
                                        </button>
                                    @else
                                        @if($canManageSemesters)
                                            <button type="button" class="sem-btn sem-btn-activate js-open-activate-modal"
                                                data-semester-id="{{ $secondSem->id }}"
                                                data-semester-name="2nd Semester"
                                                data-school-year="{{ $group['school_year'] }}"
                                                data-other-semester="1st Semester"
                                                data-activate-url="{{ route('admin.semesters.activate', $secondSem) }}">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18.36 6.64a9 9 0 1 1-12.73 0"/><line x1="12" y1="2" x2="12" y2="12"/></svg>
                                                Activate 2nd Semester
                                            </button>
                                            <button type="button" class="sem-btn sem-btn-edit-sm js-open-edit-modal" data-semester-id="{{ $secondSem->id }}">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                                                Edit
                                            </button>
                                        @endif
                                        <button type="button" class="sem-btn sem-btn-view-sm sem-modal-trigger" data-semester-id="{{ $secondSem->id }}">
                                            Details
                                        </button>
                                    @endif
                                </div>
                            @else
                                <div class="sem-missing-state">
                                    <p>2nd Semester is not yet registered for {{ $group['school_year'] }}.</p>
                                    @if($canManageSemesters)
                                        <button type="button" class="sem-btn sem-btn-create-missing js-create-missing" data-school-year="{{ $group['school_year'] }}" data-semester="2nd">
                                            + Initialize 2nd Semester
                                        </button>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</section>

{{-- ── All Semester Records Filter & Table ─────────────────────────────────────── --}}
<div class="sem-audit-divider">
    <span>Filter & Audit All Records</span>
</div>

<form method="GET" class="sem-filter-card">
    <div class="sem-filter-grid">
        <div class="sem-filter-field">
            <label for="sem-school-year">Academic Year</label>
            <select id="sem-school-year" name="school_year">
                <option value="">All Academic Years</option>
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
                    <option value="{{ $semesterOption }}" {{ $selectedSemester === $semesterOption ? 'selected' : '' }}>{{ $semesterOption }} Sem</option>
                @endforeach
            </select>
        </div>

        <div class="sem-filter-field">
            <label for="sem-status">Status</label>
            <select id="sem-status" name="status">
                <option value="">All Statuses</option>
                <option value="active" {{ $selectedStatus === 'active' ? 'selected' : '' }}>Active</option>
                <option value="closed" {{ $selectedStatus === 'closed' ? 'selected' : '' }}>Inactive / Finished</option>
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
            <h3>All Semester Records</h3>
        </div>
        <span class="sem-count-pill">{{ number_format($semesters->total()) }} term{{ $semesters->total() === 1 ? '' : 's' }}</span>
    </div>

    <div class="sem-table-wrap">
        <table class="sem-table">
            <thead>
                <tr>
                    <th>Semester</th>
                    <th>Academic Year</th>
                    <th>Dates</th>
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
                            <button type="button" class="sem-title-link sem-modal-trigger" data-semester-id="{{ $academicSemester->id }}">{{ $academicSemester->semester_label }}</button>
                        </td>
                        <td data-label="Academic Year"><span class="sem-year">{{ $academicSemester->school_year }}</span></td>
                        <td data-label="Dates">
                            @if($academicSemester->start_date && $academicSemester->end_date)
                                {{ $academicSemester->start_date->format('M d, Y') }} – {{ $academicSemester->end_date->format('M d, Y') }}
                            @elseif($academicSemester->end_date)
                                Ends {{ $academicSemester->end_date->format('M d, Y') }}
                            @else
                                Not set
                            @endif
                        </td>
                        <td data-label="Status">
                            @if($academicSemester->isOpen())
                                <span class="sem-pill-badge is-active">
                                    <span class="sem-pulse-dot"></span> ACTIVE
                                </span>
                            @elseif($academicSemester->hasExpired())
                                <span class="sem-pill-badge is-finished">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" style="width:12px;height:12px;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg> FINISHED
                                </span>
                            @else
                                <span class="sem-pill-badge is-inactive">
                                    INACTIVE
                                </span>
                            @endif
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
                                    View
                                </button>
                                @if($academicSemester->hasExpired())
                                    <span class="sem-table-lock-tag" title="Finished semester is locked from editing.">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg> Locked
                                    </span>
                                @elseif($canManageSemesters)
                                    <button type="button" class="sem-btn sem-btn-small sem-btn-ghost js-open-edit-modal" data-semester-id="{{ $academicSemester->id }}">
                                        Edit
                                    </button>
                                    @if(! $academicSemester->isOpen())
                                        <button type="button" class="sem-btn sem-btn-small sem-btn-activate-sm js-open-activate-modal"
                                            data-semester-id="{{ $academicSemester->id }}"
                                            data-semester-name="{{ $academicSemester->semester_label }}"
                                            data-school-year="{{ $academicSemester->school_year }}"
                                            data-other-semester="{{ $academicSemester->semester === '1st' ? '2nd Sem' : '1st Sem' }}"
                                            data-activate-url="{{ route('admin.semesters.activate', $academicSemester) }}">
                                            Activate
                                        </button>
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="sem-empty-cell">
                            <div class="sem-empty-state">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/></svg>
                                <strong>No semester records found</strong>
                                <p>Click the "Add Academic Year / Semester" button above to add new academic terms.</p>
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

{{-- ── Activation Confirmation Modal ─────────────────────────────────────────── --}}
<div class="sem-modal-backdrop" id="activateConfirmationModal" aria-hidden="true">
    <section class="sem-modal sem-modal-confirm" role="dialog" aria-modal="true" aria-labelledby="activateConfirmTitle">
        <div class="sem-modal-head sem-confirm-head">
            <div class="sem-confirm-icon-wrap">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18.36 6.64a9 9 0 1 1-12.73 0"/><line x1="12" y1="2" x2="12" y2="12"/></svg>
            </div>
            <div>
                <span>Confirmation</span>
                <h3 id="activateConfirmTitle">Activate Semester</h3>
            </div>
            <button type="button" class="sem-modal-close" data-modal-close="activateConfirmationModal" aria-label="Close modal">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>

        <div class="sem-confirm-body">
            <p class="sem-confirm-prompt">Are you sure you want to change the active semester for <strong id="confirmSchoolYearText">-</strong>?</p>

            <div class="sem-confirm-transition-card">
                <div class="sem-transition-item is-target">
                    <span class="sem-trans-label">Will Become Active</span>
                    <strong id="confirmTargetSemesterText">-</strong>
                    <span class="sem-pill-badge is-active"><span class="sem-pulse-dot"></span> ACTIVE</span>
                </div>

                <div class="sem-transition-divider">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </div>

                <div class="sem-transition-item is-sibling">
                    <span class="sem-trans-label">Will Become Inactive</span>
                    <strong id="confirmOtherSemesterText">-</strong>
                    <span class="sem-pill-badge is-inactive">INACTIVE</span>
                </div>
            </div>

            <div class="sem-confirm-note">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                <span>Only one semester for the same academic year can be active at a time. Active user enrollments will be linked to the newly activated semester.</span>
            </div>
        </div>

        <form id="activateSemesterForm" method="POST" action="" class="sem-modal-actions">
            @csrf
            <button type="button" class="sem-btn sem-btn-ghost" data-modal-close="activateConfirmationModal">Cancel</button>
            <button type="submit" class="sem-btn sem-btn-activate-confirm">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                Yes, Activate Semester
            </button>
        </form>
    </section>
</div>

{{-- ── Add Semester / Academic Year Modal ──────────────────────────────────────── --}}
@if($canManageSemesters)
<div class="sem-modal-backdrop" id="addSemesterModal" aria-hidden="true">
    <section class="sem-modal" role="dialog" aria-modal="true" aria-labelledby="addSemesterTitle">
        <div class="sem-modal-head">
            <div>
                <span>Academic Setup</span>
                <h3 id="addSemesterTitle">Add Academic Year / Semester</h3>
                <p>Create a semester term for a academic year.</p>
            </div>
            <button type="button" class="sem-modal-close" data-modal-close="addSemesterModal" aria-label="Close modal">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>

        <form method="POST" action="{{ route('admin.semesters.store') }}">
            @csrf
            <div class="sem-form-body">
                <div class="sem-form-group">
                    <label for="add_school_year">Academic Year <span class="req">*</span></label>
                    <input type="text" id="add_school_year" name="school_year" placeholder="e.g. 2026-2027" required class="sem-input" pattern="\d{4}-\d{4}">
                    <small class="sem-help">Format: YYYY-YYYY (e.g. 2026-2027)</small>
                </div>

                <div class="sem-form-group">
                    <label for="add_semester">Semester <span class="req">*</span></label>
                    <select id="add_semester" name="semester" required class="sem-input">
                        <option value="1st">1st Semester</option>
                        <option value="2nd">2nd Semester</option>
                    </select>
                </div>

                <div class="sem-form-row">
                    <div class="sem-form-group">
                        <label for="add_start_date">Start Date</label>
                        <input type="date" id="add_start_date" name="start_date" class="sem-input">
                    </div>
                    <div class="sem-form-group">
                        <label for="add_end_date">End Date</label>
                        <input type="date" id="add_end_date" name="end_date" class="sem-input">
                    </div>
                </div>

                <div class="sem-checkbox-wrap">
                    <label class="sem-checkbox-label">
                        <input type="checkbox" name="is_active" value="1" checked>
                        <span>Set as ACTIVE semester for this academic year</span>
                    </label>
                    <small class="sem-help" style="margin-left:24px;">If checked, any other semester in this academic year will automatically become Inactive.</small>
                </div>
            </div>

            <div class="sem-modal-actions">
                <button type="button" class="sem-btn sem-btn-ghost" data-modal-close="addSemesterModal">Cancel</button>
                <button type="submit" class="sem-btn sem-btn-primary">Save Semester</button>
            </div>
        </form>
    </section>
</div>

{{-- ── Edit Semester Dates Modal ─────────────────────────────────────────────── --}}
<div class="sem-modal-backdrop" id="editSemesterModal" aria-hidden="true">
    <section class="sem-modal" role="dialog" aria-modal="true" aria-labelledby="editSemesterTitle">
        <div class="sem-modal-head">
            <div>
                <span>Academic Period</span>
                <h3 id="editSemesterTitle">Edit Semester Dates</h3>
                <p id="editSemesterSubtitle">Adjust start and end dates for this academic term.</p>
            </div>
            <button type="button" class="sem-modal-close" data-modal-close="editSemesterModal" aria-label="Close modal">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>

        <form id="editSemesterForm" method="POST" action="">
            @csrf
            @method('PATCH')
            <div class="sem-form-body">
                <div class="sem-edit-term-summary">
                    <div class="sem-edit-term-pill">
                        <span class="sem-edit-term-label">Academic Year (Locked)</span>
                        <strong id="edit_school_year_display">-</strong>
                    </div>
                    <div class="sem-edit-term-pill">
                        <span class="sem-edit-term-label">Semester Term (Locked)</span>
                        <strong id="edit_semester_display">-</strong>
                    </div>
                </div>

                <div class="sem-form-row">
                    <div class="sem-form-group">
                        <label for="edit_start_date">Start Date</label>
                        <input type="date" id="edit_start_date" name="start_date" class="sem-input">
                    </div>
                    <div class="sem-form-group">
                        <label for="edit_end_date">End Date</label>
                        <input type="date" id="edit_end_date" name="end_date" class="sem-input">
                    </div>
                </div>
            </div>

            <div class="sem-modal-actions">
                <button type="button" class="sem-btn sem-btn-ghost" data-modal-close="editSemesterModal">Cancel</button>
                <button type="submit" class="sem-btn sem-btn-primary">Save Dates</button>
            </div>
        </form>
    </section>
</div>
@endif

{{-- ── View Details Modal ─────────────────────────────────────────────────────── --}}
<div class="sem-modal-backdrop" id="semesterDetailsModal" aria-hidden="true">
    <section class="sem-modal" role="dialog" aria-modal="true" aria-labelledby="semesterModalTitle">
        <div class="sem-modal-head">
            <div>
                <span>Semester Details</span>
                <h3 id="semesterModalTitle">Semester record</h3>
                <p id="semesterModalSubtitle">Academic term information</p>
            </div>
            <button type="button" class="sem-modal-close" data-modal-close="semesterDetailsModal" aria-label="Close semester details">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>

        <div id="semesterModalLockedBanner" class="sem-modal-locked-banner" style="display:none;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            <span>This semester is <strong>FINISHED & LOCKED</strong>. Historical records are preserved and cannot be modified.</span>
        </div>

        <div class="sem-modal-status-row">
            <span id="semesterModalStatus" class="sem-pill-badge is-active">Active</span>
            <small>Created <strong id="semesterModalCreated">Legacy</strong></small>
        </div>

        <div class="sem-modal-info-grid">
            <div class="sem-modal-info">
                <span>Academic Year</span>
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
                <span>Status</span>
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

        {{-- Department Breakdown Section in Modal --}}
        <div class="sem-modal-dept-wrap">
            <div class="sem-modal-dept-head">
                <span class="sem-dept-modal-tag">Department Breakdown</span>
                <h4>Imported Users by Department</h4>
            </div>
            <div id="semesterModalDeptList" class="sem-modal-dept-grid">
                <!-- Dynamically populated via JS -->
            </div>
        </div>

        <div class="sem-modal-actions">
            <button type="button" class="sem-btn sem-btn-ghost" data-modal-close="semesterDetailsModal">Close</button>
        </div>
    </section>
</div>

<style>
/* ── Alerts ── */
.sem-alert{display:flex;align-items:flex-start;gap:12px;padding:14px 18px;border-radius:14px;font-size:13.5px;margin-bottom:20px;font-weight:600;line-height:1.4}
.sem-alert svg{width:20px;height:20px;flex-shrink:0;margin-top:1px}
.sem-alert-success{background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0}
.sem-alert-danger{background:#fff1f2;color:#9f1239;border:1px solid #fecdd3}

/* ── Headings & Layout ── */
.sem-page-head{display:flex;align-items:flex-start;justify-content:space-between;gap:20px;margin-bottom:24px;flex-wrap:wrap}
.sem-page-head span,.sem-badge-label,.sem-table-head span:first-child,.sem-filter-field label,.sem-modal-head span,.sem-trans-label,.sem-dept-modal-tag{display:inline-flex;color:#6b2fa0;font-size:11px;font-weight:800;letter-spacing:.08em;text-transform:uppercase}
.sem-page-head h2{margin:4px 0 0;color:#2f144f;font-size:28px;font-weight:900}
.sem-page-sub{margin:4px 0 0;color:#6f5f84;font-size:14px;max-width:720px;line-height:1.45}

/* ── Primary Academic Year Management Section ── */
.sem-sy-section{margin-bottom:34px}
.sem-section-head{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:16px}
.sem-section-head h3{margin:4px 0 0;color:#2f144f;font-size:20px;font-weight:900}
.sem-count-pill{padding:6px 14px;background:#f3ecff;color:#6a35a1;border:1px solid #dfcff8;border-radius:999px;font-size:12px;font-weight:800}

.sem-sy-list{display:flex;flex-direction:column;gap:20px}
.sem-sy-card{background:#fff;border-radius:20px;border:1.5px solid #e9dff7;box-shadow:0 10px 26px rgba(59,15,122,.05);padding:22px 24px;transition:box-shadow .2s, border-color .2s}
.sem-sy-card.has-active{border-color:#bbf7d0;box-shadow:0 12px 30px rgba(16,185,129,.08)}

.sem-sy-card-head{display:flex;align-items:center;justify-content:space-between;gap:16px;padding-bottom:16px;margin-bottom:18px;border-bottom:1px solid #f1e9fc;flex-wrap:wrap}
.sem-sy-label{font-size:11px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#7c3aed}
.sem-sy-title{margin:2px 0 0;color:#1e0d38;font-size:22px;font-weight:900}

.sem-sy-status-badge{display:inline-flex;align-items:center;gap:8px;padding:6px 14px;border-radius:999px;font-size:12.5px;font-weight:800}
.sem-sy-status-badge.is-active-sy{background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0}
.sem-sy-status-badge.is-inactive-sy{background:#f3f4f6;color:#6b7280;border:1px solid #e5e7eb}

.sem-pulse-dot{width:8px;height:8px;border-radius:50%;background:#10b981;box-shadow:0 0 0 3px rgba(16,185,129,.25);display:inline-block}
.sem-inactive-dot{width:8px;height:8px;border-radius:50%;background:#9ca3af;display:inline-block}

/* ── 1st & 2nd Term Grid ── */
.sem-terms-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
.sem-term-box{display:flex;flex-direction:column;justify-content:space-between;background:linear-gradient(180deg,#faf8fe,#fff);border:1.5px solid #ebdff8;border-radius:16px;padding:18px 20px;transition:all .2s ease}
.sem-term-box.is-term-active{background:linear-gradient(180deg,#f0fdf4,#fff);border-color:#86efac;box-shadow:0 8px 20px rgba(34,197,94,.09)}
.sem-term-box.is-term-finished{background:linear-gradient(180deg,#fffbeb,#fff);border-color:#fde68a}
.sem-term-box.is-term-missing{background:#fafafa;border:1.5px dashed #d1d5db;justify-content:center}

.sem-term-box-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:14px}
.sem-term-tag{font-size:10.5px;font-weight:900;letter-spacing:.08em;text-transform:uppercase;color:#6d28d9}
.sem-term-name{margin:2px 0 0;color:#281045;font-size:17px;font-weight:800}

.sem-pill-badge{display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:999px;font-size:11.5px;font-weight:800;letter-spacing:.04em;text-transform:uppercase}
.sem-pill-badge.is-active{background:#dcfce7;color:#15803d;border:1px solid #86efac}
.sem-pill-badge.is-finished{background:#fef3c7;color:#92400e;border:1px solid #fde68a}
.sem-pill-badge.is-inactive{background:#f3f4f6;color:#6b7280;border:1px solid #e5e7eb}
.sem-pill-badge.is-missing{background:#fef3c7;color:#92400e;border:1px solid #fde68a}

.sem-term-info-row{display:flex;flex-direction:column;gap:10px;margin-bottom:16px}
.sem-term-meta{font-size:12.5px;color:#69587f}
.sem-term-meta strong{color:#1e0c38;display:block;margin-top:2px;font-size:13.5px}

.sem-term-stats{display:flex;align-items:center;gap:12px;flex-wrap:wrap}
.sem-term-stats a{display:inline-flex;align-items:center;gap:6px;padding:5px 12px;background:#f3ecff;border:1px solid #e3d3f9;border-radius:10px;color:#5a2b8e;font-size:12px;font-weight:700;text-decoration:none}
.sem-term-stats a strong{color:#3a0b73;font-size:13px}
.sem-term-stats a:hover{background:#ebdfff}

/* ── Department Breakdown Chips ── */
.sem-dept-breakdown-row{margin-top:4px;padding-top:10px;border-top:1px dashed #eeddfc;display:flex;flex-direction:column;gap:6px}
.sem-dept-breakdown-title{font-size:10.5px;font-weight:800;color:#7c3aed;text-transform:uppercase;letter-spacing:.05em}
.sem-dept-chips{display:flex;flex-wrap:wrap;gap:6px}
.sem-dept-chip{display:inline-flex;align-items:center;gap:4px;padding:3px 8px;background:#f5f0ff;border:1px solid #e0d0f7;border-radius:8px;font-size:11px;color:#4c1d95}
.sem-dept-chip-name{color:#6b21a8}
.sem-dept-chip-count{color:#1e1b4b;font-weight:800}

.sem-term-actions{display:flex;align-items:center;gap:8px;flex-wrap:wrap;padding-top:14px;border-top:1px solid #f1e9fc;margin-top:auto}

/* ── Buttons ── */
.sem-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:42px;padding:0 16px;border:0;border-radius:12px;font-size:13px;font-weight:800;text-decoration:none;cursor:pointer;white-space:nowrap;transition:all .15s ease}
.sem-btn svg{width:16px;height:16px;flex-shrink:0}
.sem-btn-primary{background:#42127f;color:#fff;box-shadow:0 10px 22px rgba(59,15,122,.18)}
.sem-btn-primary:hover{background:#320a65}
.sem-btn-ghost{background:#f6f1ff;color:#5f3890;border:1px solid #e5d8f7}
.sem-btn-ghost:hover{background:#ede3fc}

.sem-btn-activate{background:linear-gradient(180deg,#16a34a,#15803d);color:#fff;box-shadow:0 8px 18px rgba(22,163,74,.24);flex:1}
.sem-btn-activate:hover{background:#15803d}
.sem-btn-activate-confirm{background:#16a34a;color:#fff;box-shadow:0 10px 24px rgba(22,163,74,.25)}
.sem-btn-activate-confirm:hover{background:#15803d}
.sem-btn-activate-sm{background:#ecfdf5;color:#047857;border:1px solid #a7f3d0;min-height:34px;padding:0 10px;border-radius:9px;font-size:12px}
.sem-btn-activate-sm:hover{background:#d1fae5}

.sem-btn-current-active{background:#dcfce7;color:#166534;border:1px solid #86efac;cursor:default;flex:1;opacity:1}
.sem-btn-edit-sm,.sem-btn-view-sm{min-height:36px;padding:0 12px;border-radius:10px;font-size:12px;font-weight:800}
.sem-btn-edit-sm{background:#fbf8ff;color:#6b2fa0;border:1px solid #e2d2f7}
.sem-btn-edit-sm:hover{background:#f2e8fd}
.sem-btn-view-sm{background:#f8fafc;color:#475569;border:1px solid #e2e8f0}
.sem-btn-view-sm:hover{background:#f1f5f9}

.sem-locked-status-pill{display:inline-flex;align-items:center;gap:6px;padding:8px 12px;background:#fef3c7;color:#92400e;border:1px solid #fde68a;border-radius:10px;font-size:12px;font-weight:800}
.sem-locked-status-pill svg{width:14px;height:14px}
.sem-table-lock-tag{display:inline-flex;align-items:center;gap:4px;padding:4px 8px;background:#f3f4f6;color:#6b7280;border:1px solid #e5e7eb;border-radius:8px;font-size:11.5px;font-weight:700}

.sem-btn-create-missing{width:100%;min-height:40px;background:#fff;border:1.5px dashed #c084fc;color:#7c3aed;font-size:13px;font-weight:800;border-radius:12px;cursor:pointer;margin-top:10px}
.sem-btn-create-missing:hover{background:#f8f3ff;border-color:#7c3aed}

.sem-missing-state{text-align:center;padding:12px 0}
.sem-missing-state p{color:#8c7e9f;font-size:13px;margin:0}

/* ── Audit Table & Divider ── */
.sem-audit-divider{display:flex;align-items:center;text-align:center;margin:32px 0 18px}
.sem-audit-divider::before,.sem-audit-divider::after{content:'';flex:1;border-bottom:1px solid #e7dcf5}
.sem-audit-divider span{padding:0 14px;color:#87759f;font-size:12px;font-weight:800;letter-spacing:.06em;text-transform:uppercase}

.sem-filter-card,.sem-table-card{background:#fff;border:1px solid rgba(107,47,160,.1);box-shadow:0 12px 28px rgba(57,26,101,.06)}
.sem-filter-card{margin-bottom:20px;padding:18px;border-radius:20px}
.sem-filter-grid{display:grid;grid-template-columns:repeat(3,minmax(160px,1fr)) auto;gap:12px;align-items:end}
.sem-filter-field{min-width:0}
.sem-filter-field label{margin:0 0 7px 2px;color:#7b4bb0;font-size:10.5px}
.sem-filter-field select{width:100%;min-height:46px;border:1.5px solid #e4d7f6;border-radius:12px;background:linear-gradient(180deg,#fff,#fcfaff);color:#1d0b3b;font:inherit;font-size:13.5px;outline:none;padding:0 34px 0 14px}
.sem-filter-field select:focus{border-color:#7c3aed;background:#fff;box-shadow:0 0 0 4px rgba(124,58,237,.11)}
.sem-filter-actions,.sem-action-row{display:flex;align-items:center;gap:8px;flex-wrap:wrap}

.sem-active-filters{display:flex;gap:8px;flex-wrap:wrap;margin-top:14px;padding-top:14px;border-top:1px solid #f0e9fb}
.sem-active-filters span{display:inline-flex;align-items:center;padding:6px 10px;background:#f8f4ff;color:#6b2fa0;border:1px solid #e3d5f5;border-radius:999px;font-size:11.5px;font-weight:800}

.sem-table-card{border-radius:20px;overflow:hidden}
.sem-table-head{display:flex;align-items:center;justify-content:space-between;padding:18px 20px;border-bottom:1px solid #f0e9fb}
.sem-table-head h3{margin:4px 0 0;color:#2f144f;font-size:18px;font-weight:800}
.sem-table-wrap{overflow:auto}
.sem-table{width:100%;border-collapse:collapse;font-size:13.5px}
.sem-table th{padding:12px 16px;text-align:left;color:#6f6189;background:#faf7ff;font-size:11px;text-transform:uppercase;letter-spacing:.06em;white-space:nowrap}
.sem-table td{padding:14px 16px;border-top:1px solid #f0e9fb;color:#2f144f;vertical-align:middle}

.sem-title-link{display:inline-flex;padding:0;border:0;background:transparent;color:#3b0f7a;font:inherit;font-weight:800;text-decoration:none;cursor:pointer}
.sem-title-link:hover{text-decoration:underline}
.sem-year{font-weight:800;color:#4c1d95}
.sem-metric-link{display:inline-flex;align-items:center;justify-content:center;min-width:40px;min-height:30px;padding:0 10px;border-radius:999px;background:#f8f4ff;color:#5f3890;font-weight:800;text-decoration:none;font-size:12px}
.sem-metric-link:hover{background:#ede2ff}

.sem-empty-card{padding:48px 24px;text-align:center;background:#fff;border-radius:20px;border:1.5px dashed #d6c6ea;margin-bottom:24px}
.sem-empty-card svg{width:40px;height:40px;color:#9b86bd;margin-bottom:8px}
.sem-empty-card strong{display:block;color:#2a0f4e;font-size:18px}
.sem-empty-card p{color:#77678e;font-size:14px;margin:4px 0 0}

.sem-empty-cell{padding:46px 18px!important}
.sem-empty-state{display:grid;place-items:center;gap:8px;text-align:center;color:#837596}
.sem-empty-state svg{width:34px;height:34px;color:#9a84bc}
.sem-empty-state strong{color:#2f144f}
.sem-empty-state p{margin:0;font-size:13px}
.sem-pagination{padding:16px 20px;border-top:1px solid #f0e9fb}

/* ── Modals ── */
.sem-modal-backdrop{position:fixed;inset:0;z-index:1000;display:none;align-items:center;justify-content:center;padding:24px;background:rgba(23,8,45,.56);backdrop-filter:blur(10px)}
.sem-modal-backdrop.is-open{display:flex}
body.sem-modal-open{overflow:hidden}

.sem-modal{width:min(640px,100%);max-height:min(720px,calc(100vh - 48px));overflow:auto;border-radius:22px;background:#fff;border:1px solid rgba(107,47,160,.12);box-shadow:0 28px 80px rgba(23,8,45,.28)}
.sem-modal-head{display:flex;align-items:flex-start;justify-content:space-between;gap:18px;padding:22px 24px 18px;border-bottom:1px solid #f0e9fb;background:linear-gradient(180deg,#fff,#fbf8ff)}
.sem-modal-head h3{margin:4px 0 2px;color:#2f144f;font-size:22px;line-height:1.2}
.sem-modal-head p{margin:0;color:#6b5b80;font-size:13px;font-weight:600}
.sem-modal-close{display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;border:1px solid #e5d8f7;border-radius:12px;background:#fff;color:#5f3890;cursor:pointer;flex-shrink:0}
.sem-modal-close svg{width:18px;height:18px}
.sem-modal-close:hover{background:#f6f1ff}

.sem-modal-locked-banner{display:flex;align-items:center;gap:10px;margin:16px 24px 0;padding:12px 16px;background:#fffbeb;border:1px solid #fef08a;border-radius:14px;color:#854d0e;font-size:13px;line-height:1.4}
.sem-modal-locked-banner svg{width:18px;height:18px;flex-shrink:0;color:#d97706}

.sem-modal-status-row{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:18px 24px 0}
.sem-modal-status-row small{color:#837596;font-size:13px;font-weight:700}
.sem-modal-info-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;padding:18px 24px}
.sem-modal-info{min-width:0;padding:0 0 12px;border-bottom:1px solid #efe7fb}
.sem-modal-info span{display:inline-flex;color:#6b2fa0;font-size:10.5px;font-weight:800;letter-spacing:.08em;text-transform:uppercase}
.sem-modal-info strong{display:block;margin-top:4px;color:#2f144f;font-size:16px}
.sem-modal-metrics{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;padding:0 24px 18px}
.sem-modal-metrics a{display:flex;align-items:center;justify-content:space-between;gap:16px;min-height:54px;padding:0 16px;border-radius:14px;background:#f8f4ff;border:1px solid #eadff8;text-decoration:none}
.sem-modal-metrics a:hover{background:#f0e7ff}
.sem-modal-metrics strong{color:#42127f;font-size:20px}

/* ── Department Breakdown in Modal ── */
.sem-modal-dept-wrap{padding:0 24px 18px}
.sem-modal-dept-head{margin-bottom:10px}
.sem-modal-dept-head h4{margin:2px 0 0;font-size:15px;color:#2f144f;font-weight:800}
.sem-modal-dept-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:8px}
.sem-modal-dept-card{display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:#faf8fe;border:1.5px solid #ebdff8;border-radius:12px}
.sem-modal-dept-card span{font-size:12.5px;color:#4c1d95;font-weight:700}
.sem-modal-dept-card strong{font-size:15px;color:#1e0c38;font-weight:900}
.sem-modal-dept-empty{padding:12px;text-align:center;color:#8c7e9f;font-size:12.5px;font-style:italic;background:#faf8fe;border:1px dashed #ebdff8;border-radius:12px;grid-column:1 / -1}

.sem-modal-actions{display:flex;align-items:center;justify-content:flex-end;gap:10px;padding:16px 24px 20px;border-top:1px solid #f0e9fb}

/* ── Confirm Modal Specials ── */
.sem-modal-confirm{max-width:540px}
.sem-confirm-head{align-items:center;gap:14px}
.sem-confirm-icon-wrap{width:44px;height:44px;border-radius:14px;background:#ecfdf5;border:1px solid #a7f3d0;color:#059669;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.sem-confirm-icon-wrap svg{width:22px;height:22px}
.sem-confirm-body{padding:20px 24px}
.sem-confirm-prompt{margin:0 0 16px;color:#1e0c38;font-size:15px;line-height:1.45}
.sem-confirm-transition-card{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:16px 18px;border-radius:16px;background:#faf8fe;border:1.5px solid #ebdff8;margin-bottom:16px}
.sem-transition-item{flex:1;display:flex;flex-direction:column;gap:4px}
.sem-transition-item.is-target strong{color:#15803d;font-size:15px;font-weight:900}
.sem-transition-item.is-sibling strong{color:#64748b;font-size:15px;font-weight:800}
.sem-transition-divider svg{width:20px;height:20px;color:#9333ea}

.sem-confirm-note{display:flex;align-items:flex-start;gap:10px;padding:12px 14px;background:#f5f3ff;border:1px solid #ddd6fe;border-radius:12px;color:#5b21b6;font-size:12.5px;line-height:1.4}
.sem-confirm-note svg{width:18px;height:18px;flex-shrink:0;margin-top:1px}

/* ── Forms inside Modals ── */
.sem-form-body{padding:20px 24px;display:flex;flex-direction:column;gap:16px}
.sem-form-group{display:flex;flex-direction:column;gap:6px}
.sem-form-group label{font-size:12px;font-weight:800;color:#502479;text-transform:uppercase;letter-spacing:.05em}
.sem-form-group label .req{color:#e11d48}
.sem-input{width:100%;min-height:46px;border:1.5px solid #e3d5f5;border-radius:12px;padding:0 14px;font:inherit;font-size:14px;outline:none;background:#faf8fe;color:#1e0c38}
.sem-input:focus{border-color:#7c3aed;background:#fff;box-shadow:0 0 0 4px rgba(124,58,237,.12)}
.sem-form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.sem-help{color:#78698d;font-size:12px}
.sem-checkbox-wrap{display:flex;flex-direction:column;gap:4px;padding:12px 14px;background:#faf8fe;border:1px solid #ebdff8;border-radius:12px}
.sem-checkbox-label{display:inline-flex;align-items:center;gap:10px;font-size:13.5px;font-weight:700;color:#2a0f4e;cursor:pointer}
.sem-checkbox-label input[type="checkbox"]{width:18px;height:18px;accent-color:#7c3aed;cursor:pointer}

.sem-edit-term-summary{display:grid;grid-template-columns:1fr 1fr;gap:12px;padding:12px 14px;background:#fbf8ff;border:1.5px solid #ebdff8;border-radius:14px}
.sem-edit-term-pill{display:flex;flex-direction:column;gap:3px}
.sem-edit-term-label{font-size:10.5px;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:#7c3aed}
.sem-edit-term-pill strong{font-size:15px;color:#2f144f;font-weight:900}

/* ── Responsive ── */
@media (max-width: 900px){
    .sem-terms-grid{grid-template-columns:1fr}
    .sem-filter-grid{grid-template-columns:repeat(2,minmax(140px,1fr))}
    .sem-filter-actions{grid-column:1 / -1}
}
@media (max-width: 640px){
    .sem-page-head{flex-direction:column}
    .sem-form-row{grid-template-columns:1fr}
    .sem-filter-grid,.sem-modal-info-grid,.sem-modal-metrics{grid-template-columns:1fr}
    .sem-confirm-transition-card{flex-direction:column;align-items:stretch}
    .sem-transition-divider{align-self:center;transform:rotate(90deg)}
    .sem-table th{display:none}
    .sem-table,.sem-table tbody,.sem-table tr,.sem-table td{display:block;width:100%}
    .sem-table tr{border-top:1px solid #f0e9fb}
    .sem-table td{display:flex;justify-content:space-between;gap:16px;border-top:0;padding:10px 14px}
    .sem-table td::before{content:attr(data-label);color:#86789a;font-size:11px;font-weight:800;text-transform:uppercase}
    .sem-modal-actions{flex-direction:column-reverse}
    .sem-modal-actions .sem-btn{width:100%}
}
</style>
@endsection

@push('scripts')
<script>
(function() {
    const semesterRecords = @json($semesterModalRecords);

    function setText(id, value) {
        const el = document.getElementById(id);
        if (el) el.textContent = value || '-';
    }

    function setLink(id, href) {
        const el = document.getElementById(id);
        if (el) el.href = href || '#';
    }

    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('sem-modal-open');
    }

    function closeModal(modalId) {
        const modal = typeof modalId === 'string' ? document.getElementById(modalId) : modalId;
        if (!modal) return;
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        if (!document.querySelector('.sem-modal-backdrop.is-open')) {
            document.body.classList.remove('sem-modal-open');
        }
    }

    // Modal Close buttons
    document.querySelectorAll('[data-modal-close]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            closeModal(this.getAttribute('data-modal-close'));
        });
    });

    // Close on backdrop click
    document.querySelectorAll('.sem-modal-backdrop').forEach(function(backdrop) {
        backdrop.addEventListener('click', function(e) {
            if (e.target === backdrop) closeModal(backdrop);
        });
    });

    // Close on Escape key
    window.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.sem-modal-backdrop.is-open').forEach(closeModal);
        }
    });

    // Details Modal Trigger
    document.querySelectorAll('.sem-modal-trigger').forEach(function(trigger) {
        trigger.addEventListener('click', function() {
            const id = this.dataset.semesterId;
            const record = semesterRecords[String(id)];
            if (!record) return;

            setText('semesterModalTitle', record.title);
            setText('semesterModalSubtitle', record.schoolYear + ' — ' + record.semesterLabel);
            setText('semesterModalSchoolYear', record.schoolYear);
            setText('semesterModalSemester', record.semesterLabel);
            setText('semesterModalStartDate', record.startDate);
            setText('semesterModalEndDate', record.endDate);
            setText('semesterModalStatusText', record.status);
            setText('semesterModalCreated', record.createdDate);
            setText('semesterModalUsers', record.usersCount);
            setText('semesterModalResearches', record.researchesCount);
            setLink('semesterModalUsersLink', record.usersUrl);
            setLink('semesterModalResearchesLink', record.researchesUrl);

            const status = document.getElementById('semesterModalStatus');
            if (status) {
                status.textContent = record.status.toUpperCase();
                status.className = 'sem-pill-badge ' + (record.statusClass || (record.isActive ? 'is-active' : 'is-inactive'));
            }

            const lockedBanner = document.getElementById('semesterModalLockedBanner');
            if (lockedBanner) {
                lockedBanner.style.display = record.isFinished ? 'flex' : 'none';
            }

            // Populate Department Breakdown
            const deptList = document.getElementById('semesterModalDeptList');
            if (deptList) {
                deptList.innerHTML = '';
                const deptData = record.departmentBreakdown || {};
                const deptKeys = Object.keys(deptData);

                if (deptKeys.length === 0) {
                    deptList.innerHTML = '<div class="sem-modal-dept-empty">No department user enrollments recorded for this semester.</div>';
                } else {
                    deptKeys.forEach(function(deptName) {
                        const count = deptData[deptName];
                        const card = document.createElement('div');
                        card.className = 'sem-modal-dept-card';
                        card.innerHTML = '<span>' + deptName + '</span><strong>' + Number(count).toLocaleString() + ' user' + (count === 1 ? '' : 's') + '</strong>';
                        deptList.appendChild(card);
                    });
                }
            }

            openModal('semesterDetailsModal');
        });
    });

    // Activation Confirmation Modal
    document.querySelectorAll('.js-open-activate-modal').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const semesterName = this.dataset.semesterName;
            const schoolYear = this.dataset.schoolYear;
            const otherSemester = this.dataset.otherSemester;
            const activateUrl = this.dataset.activateUrl;

            setText('confirmSchoolYearText', schoolYear);
            setText('confirmTargetSemesterText', semesterName + ' (' + schoolYear + ')');
            setText('confirmOtherSemesterText', otherSemester + ' (' + schoolYear + ')');

            const form = document.getElementById('activateSemesterForm');
            if (form) form.action = activateUrl;

            openModal('activateConfirmationModal');
        });
    });

    // Add Semester Modal Button
    const openAddBtn = document.getElementById('openAddSemesterBtn');
    if (openAddBtn) {
        openAddBtn.addEventListener('click', function() {
            const syInput = document.getElementById('add_school_year');
            if (syInput && !syInput.value) {
                const now = new Date();
                const year = now.getFullYear();
                syInput.value = year + '-' + (year + 1);
            }
            openModal('addSemesterModal');
        });
    }

    // Initialize missing semester button
    document.querySelectorAll('.js-create-missing').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const schoolYear = this.dataset.schoolYear;
            const semester = this.dataset.semester;

            const syInput = document.getElementById('add_school_year');
            const semSelect = document.getElementById('add_semester');

            if (syInput) syInput.value = schoolYear;
            if (semSelect) semSelect.value = semester;

            openModal('addSemesterModal');
        });
    });

    // Edit Semester Modal
    document.querySelectorAll('.js-open-edit-modal').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const id = this.dataset.semesterId;
            const record = semesterRecords[String(id)];
            if (!record) return;

            const form = document.getElementById('editSemesterForm');
            const syDisplay = document.getElementById('edit_school_year_display');
            const semDisplay = document.getElementById('edit_semester_display');
            const startInput = document.getElementById('edit_start_date');
            const endInput = document.getElementById('edit_end_date');

            if (form) form.action = record.updateUrl;
            if (syDisplay) syDisplay.textContent = record.schoolYear;
            if (semDisplay) semDisplay.textContent = record.semesterLabel;
            if (startInput) startInput.value = record.rawStartDate;
            if (endInput) endInput.value = record.rawEndDate;

            openModal('editSemesterModal');
        });
    });
})();
</script>
@endpush

