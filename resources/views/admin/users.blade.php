@extends('layouts.admin')
@section('title', 'Manage Users')
@section('page-title', 'Manage Users')

@section('content')

{{-- SUMMARY CARDS --}}
@php
    $allResearchers = $users->getCollection()->filter(fn($u) => $u->role === 'researcher');
    $approvedStudents = $users->getCollection()->filter(fn($u) => $u->role === 'user' && !empty($u->student_id));
    $deans = $users->getCollection()->filter(fn($u) => $u->role === 'admin' && $u->is_department_dean);
    $currentYear = (int) date('Y');
@endphp

<div class="mu-page-intro">
    <div>
        <span class="mu-page-kicker">User Directory</span>
        <h2 class="mu-page-title">Oversee users, deans, and researcher accounts from one control panel</h2>
        <p class="mu-page-sub">Track account roles, review engagement, and manage institution-wide access with clearer visibility.</p>
    </div>
</div>

<div class="summary-grid">
    <div class="summary-card sc-total">
        <div class="sc-icon sc-i-total">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <div class="sc-info"><div class="sc-val">{{ $users->total() }}</div><div class="sc-lbl">Total Users</div></div>
    </div>

    @if(auth()->user() && auth()->user()->canManageDepartmentKeys())
    <div class="summary-card sc-dean">
        <div class="sc-icon sc-i-dean">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3l9 4.5-9 4.5L3 7.5 12 3Z"/><path d="M7 10.5V15c0 1.8 2.2 3 5 3s5-1.2 5-3v-4.5"/><path d="M21 9v6"/></svg>
        </div>
        <div class="sc-info"><div class="sc-val">{{ $deans->count() }}</div><div class="sc-lbl">Deans Listed</div></div>
    </div>
    @endif

</div>

@if(session('success'))
    <div class="mu-alert mu-alert-success">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        {{ session('success') }}
    </div>
@endif
@if(session('error'))
    <div class="mu-alert mu-alert-error">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        {{ session('error') }}
    </div>
@endif
@if(session('import_errors') && count(session('import_errors')) > 0)
    <div class="mu-import-report">
        <strong>Import issues</strong>
        <div class="mu-import-errors">
            @foreach(session('import_errors') as $issue)
                <div>Row {{ $issue['row'] }}: {{ $issue['error'] }}</div>
            @endforeach
        </div>
    </div>
@endif
@if(session('import_skipped') && count(session('import_skipped')) > 0)
    <div class="mu-import-skip-report">
        <strong>Skipped existing or duplicate rows</strong>
        <div class="mu-import-errors">
            @foreach(session('import_skipped') as $skipped)
                <div>
                    Row {{ $skipped['row'] }}: {{ $skipped['name'] }}
                    <code>{{ $skipped['login_id'] }}</code>
                    <small>{{ $skipped['email'] }}</small>
                    <span>{{ $skipped['reason'] }}</span>
                </div>
            @endforeach
        </div>
    </div>
@endif
@if(request()->boolean('imported') && session('imported_user_ids'))
    <div class="mu-import-filter">
        Showing only the users from your latest import.
        <a href="{{ route('admin.users') }}">Show all users</a>
    </div>
@endif
@if(session('import_preview') && count(session('import_preview')) > 0)
    <div class="mu-import-preview">
        <strong>Latest imported login IDs{{ session('import_semester') ? ' - ' . session('import_semester') : '' }}</strong>
        <div class="mu-import-preview-list">
            @foreach(session('import_preview') as $imported)
                <div>
                    @if(! empty($imported['action']))
                        <span class="mu-import-action">{{ $imported['action'] }}</span>
                    @endif
                    <span>{{ $imported['name'] }}</span>
                    <code>{{ $imported['login_id'] }}</code>
                    <small>{{ $imported['email'] }}</small>
                </div>
            @endforeach
        </div>
    </div>
@endif

{{-- FILTER BAR --}}
<form method="GET" class="mu-filter-card">
    <div class="mu-filter-main">
        <div class="mu-search-wrap">
            <span class="mu-search-icon">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </span>
            <input id="mu-user-search" type="text" name="search" value="{{ request('search') }}" placeholder="Search name initial or name..." autocomplete="off">
        </div>

        <div class="mu-select-wrap">
            <select name="role" onchange="this.form.submit()">
                @if($isDepartmentScoped)
                    <option value="" {{ request('role') ? '' : 'selected' }} hidden>All Roles</option>
                    <option value="student" {{ request('role') == 'student' ? 'selected' : '' }}>Student</option>
                    <option value="faculty" {{ request('role') == 'faculty' ? 'selected' : '' }}>Faculty</option>
                @else
                    <option value="">All Roles</option>
                    <option value="dean"       {{ request('role') == 'dean'       ? 'selected' : '' }}>Dean</option>
                    <option value="faculty"    {{ request('role') == 'faculty'    ? 'selected' : '' }}>Faculty</option>
                    <option value="student"    {{ request('role') == 'student'    ? 'selected' : '' }}>Student</option>
                @endif
            </select>
        </div>

        @unless($isDepartmentScoped)
            <div class="mu-select-wrap">
                <select name="department">
                    <option value="">All Departments</option>
                    @foreach($departments as $department)
                        <option value="{{ $department }}" {{ request('department') == $department ? 'selected' : '' }}>{{ $department }}</option>
                    @endforeach
                </select>
            </div>
        @endunless

        <div class="mu-select-wrap">
            <select name="school_year">
                <option value="">All School Years</option>
                @foreach($schoolYears as $schoolYear)
                    <option value="{{ $schoolYear }}" {{ $selectedSchoolYear === $schoolYear ? 'selected' : '' }}>{{ $schoolYear }}</option>
                @endforeach
            </select>
        </div>

        <div class="mu-select-wrap">
            <select name="semester">
                <option value="">All Semesters</option>
                @foreach($semesterOptions as $semesterOption)
                    <option value="{{ $semesterOption }}" {{ $selectedSemester === $semesterOption ? 'selected' : '' }}>{{ $semesterOption }}</option>
                @endforeach
            </select>
        </div>

    </div>

    <div class="mu-filter-actions">
        <button type="submit" class="mu-btn mu-btn-primary">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
            Filter
        </button>
        <a href="{{ route('admin.users') }}" class="mu-btn mu-btn-ghost">Clear</a>
        @if(auth()->user() && auth()->user()->canImportUsers())
            <button type="button" class="mu-btn mu-btn-outline" onclick="openImportUserModal()">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                Import Users
            </button>
        @endif
        @if(auth()->user() && auth()->user()->canManageDepartmentKeys())
            <a href="{{ route('admin.create-admin') }}" class="mu-btn mu-btn-outline">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add Dean
            </a>
        @endif
    </div>
</form>

{{-- TABLE CARD --}}
<div class="mu-card">
    <div class="mu-card-header">
        <div class="mu-card-header-left">
            <div class="mu-card-icon">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div>
                <h3 class="mu-card-title">All Users</h3>
                <p class="mu-card-sub">Manage accounts and permissions</p>
            </div>
        </div>
        <span class="mu-count-badge">{{ $users->total() }} user(s)</span>
    </div>

    <div class="mu-table-wrap">
        <table class="mu-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Department</th>
                    <th>Semester</th>
                    <th>ID</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                @php
                    $isResearcher = $user->role === 'researcher';
                    $isFaculty    = $isResearcher && is_null($user->graduation_year);
                    $isStudent    = $isResearcher && !is_null($user->graduation_year);
                    $isApprovedStudentResearcher = $isStudent && $user->is_approved;
                    $isApprovedStudentAccount = $user->role === 'user' && !empty($user->student_id);
                    $isDean = $user->role === 'admin' && $user->is_department_dean;
                    $grad         = $user->graduation_year;
                    $years        = $grad ? ($grad - $currentYear) : null;

                    if (!$isResearcher) {
                        $rowClass = '';
                    } elseif ($years !== null && $years <= 1) {
                        $rowClass = 'row-warning';
                    } else {
                        $rowClass = '';
                    }
                @endphp
                <tr class="mu-row {{ !$user->is_active ? 'mu-row-inactive' : '' }} {{ $rowClass }}">
                    <td data-label="Name">
                        <button type="button" class="mu-name-cell mu-name-trigger" onclick="openUserModal({{ $user->id }})">
                            <div class="mu-avatar" style="background:{{ ['#f0eaf9','#dbeafe','#dcfce7','#fef9c3','#ffe4e6'][crc32($user->name) % 5] }};color:{{ ['#6b2fa0','#1d4ed8','#15803d','#92400e','#be123c'][crc32($user->name) % 5] }};">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                            <div>
                                <div class="mu-name">{{ $user->name }}</div>
                                @if($isFaculty)
                                    <small class="mu-sub-tag mu-tag-faculty">Faculty</small>
                                @endif
                            </div>
                        </button>
                    </td>
                    <td class="mu-email" data-label="Email">{{ $user->email }}</td>
                    <td data-label="Role">
                        @if($isDean)
                            <span class="mu-role-badge mu-role-dean">Dean</span>
                        @elseif($isApprovedStudentResearcher)
                            <span class="mu-role-badge mu-role-student">Student Researcher</span>
                        @elseif($isStudent)
                            <span class="mu-role-badge mu-role-student">Student</span>
                        @elseif($isApprovedStudentAccount)
                            <span class="mu-role-badge mu-role-student">Student</span>
                        @elseif($isFaculty)
                            <span class="mu-role-badge mu-role-faculty">Faculty</span>
                        @else
                            <span class="mu-role-badge mu-role-{{ $user->role }}">{{ ucfirst($user->role) }}</span>
                        @endif
                    </td>
                    <td class="mu-dept" data-label="Department">{{ $user->department ? Str::limit($user->department, 40) : '—' }}</td>
                    <td data-label="Semester">
                        @if($user->currentSemester)
                            <span class="mu-semester {{ $user->currentSemester->isArchived() ? 'is-archived' : '' }}">
                                {{ $user->currentSemester->semester }}
                                <small>{{ $user->currentSemester->school_year }}</small>
                            </span>
                        @else
                            <span class="mu-muted">Unassigned</span>
                        @endif
                    </td>
                    <td class="mu-id" data-label="ID">{{ ($isDean || $user->role === 'admin') ? '—' : ($user->student_id ?? '—') }}</td>
                    
                    <td data-label="Actions">
                        <div class="mu-actions">
                            <button type="button" class="act act-view" onclick="openUserModal({{ $user->id }})">
                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                                View
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="mu-empty">
                        <div class="mu-empty-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                        </div>
                        <p>No users found.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mu-pagination">{{ $users->links('vendor.pagination.custom') }}</div>
</div>

<div id="userModal" class="mu-modal-overlay" style="display:none;" onclick="closeUserModal(event)">
    <div class="mu-modal-card" onclick="event.stopPropagation()">
        <div class="mu-modal-head">
            <div class="mu-modal-title-wrap">
                <span class="mu-modal-kicker">User Overview</span>
                <h3 id="userModalTitle" class="mu-modal-title">User Profile</h3>
            </div>
            <button type="button" class="mu-modal-close" onclick="closeUserModal()">&times;</button>
        </div>
        <div id="userModalBody" class="mu-modal-body"></div>
        <div class="mu-modal-actions">
            <form id="userModalToggleForm" method="POST">
                @csrf
                <button type="submit" id="userModalToggleButton" class="mu-modal-action-btn"></button>
            </form>
        </div>
    </div>
</div>

@if(auth()->user() && auth()->user()->canImportUsers())
<div id="importUserModal" class="mu-modal-overlay" style="display:none;" onclick="closeImportUserModal(event)">
    <div class="mu-modal-card mu-import-modal-card" onclick="event.stopPropagation()">
        <div class="mu-modal-head">
            <div class="mu-modal-title-wrap">
                <span class="mu-modal-kicker">Bulk Account Setup</span>
                <h3 class="mu-modal-title">Import Users</h3>
            </div>
            <button type="button" class="mu-modal-close" onclick="closeImportUserModal()">&times;</button>
        </div>

        <form method="POST" action="{{ route('admin.import-users') }}" class="mu-create-form" enctype="multipart/form-data">
            @csrf
            @if($errors->importUsers->any())
                <div class="mu-alert mu-alert-error">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    {{ $errors->importUsers->first() }}
                </div>
            @endif

            <div class="mu-import-term">
                <div class="mu-create-field">
                    <label>Semester</label>
                    <div class="mu-semester-choice-row">
                        @foreach($semesterOptions as $semesterOption)
                            <label class="mu-semester-choice">
                                <input type="radio" name="semester" value="{{ $semesterOption }}" {{ old('semester', $semesterOptions[0]) === $semesterOption ? 'checked' : '' }} required>
                                <span>{{ $semesterOption }} Sem</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="mu-create-field">
                    <label for="iu_school_year">School Year</label>
                    <input type="text" id="iu_school_year" name="school_year" value="{{ old('school_year', $selectedSchoolYear ?? '') }}" placeholder="2026-2027" pattern="\d{4}-\d{4}" required>
                </div>
            </div>

            <div class="mu-import-term">
                <div class="mu-create-field">
                    <label for="iu_start_date">Semester Start Date</label>
                    <input type="date" id="iu_start_date" name="start_date" value="{{ old('start_date') }}">
                </div>
                <div class="mu-create-field">
                    <label for="iu_end_date">Semester End Date</label>
                    <input type="date" id="iu_end_date" name="end_date" value="{{ old('end_date') }}">
                </div>
            </div>

            <div id="iu_guide_section" class="mu-import-guide">
                <strong>Accepted columns</strong>
                <span>firstname, middlename, lastname, member_type, student_id, employee_id, year_level, email, password</span>
            </div>
            <div id="iu_file_section" class="mu-create-field">
                <label for="iu_file">Excel or CSV file</label>
                <input type="file" id="iu_file" name="file" accept=".xlsx,.csv,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
                <small class="mu-field-hint">Imported users are automatically active and approved for your department. They can submit research and view full research files immediately.</small>
            </div>
            <div class="mu-create-actions">
                <button type="button" class="mu-btn mu-btn-ghost" onclick="closeImportUserModal()">Cancel</button>
                <button type="submit" id="iu_submit_btn" class="mu-btn mu-btn-primary">Import Users</button>
            </div>
        </form>
    </div>
</div>
@endif

<style>
.mu-page-intro{margin-bottom:18px;padding:28px 30px;border-radius:28px;background:radial-gradient(circle at top right, rgba(124,58,237,.16), transparent 28%),radial-gradient(circle at left bottom, rgba(14,165,233,.1), transparent 24%),linear-gradient(135deg,#ffffff 0%,#f8f4ff 58%,#f1ecff 100%);border:1px solid rgba(122,90,189,.14);box-shadow:0 18px 44px rgba(59,15,122,.08);}
.mu-page-kicker{display:inline-flex;align-items:center;gap:8px;margin-bottom:12px;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.18em;color:#8d78bb;}
.mu-page-kicker::before{content:"";width:30px;height:1px;background:linear-gradient(90deg,#6d28d9,transparent);}
.mu-page-title{max-width:760px;margin:0 0 8px;font-size:31px;line-height:1.08;letter-spacing:-.05em;color:#1f123e;}
.mu-page-sub{max-width:720px;margin:0;color:#7e72a6;font-size:14px;line-height:1.75;}

.summary-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:14px;margin-bottom:18px;}
.summary-card{background:#fff;border-radius:22px;padding:18px 20px;display:grid;grid-template-columns:50px minmax(0,1fr);align-items:center;gap:14px;border:1px solid rgba(124,58,237,.1);box-shadow:0 12px 28px rgba(57,26,101,.05);position:relative;overflow:hidden;transition:transform .16s ease,box-shadow .16s ease;}
.summary-card:hover{transform:translateY(-2px);box-shadow:0 18px 34px rgba(57,26,101,.08);}
.sc-icon{width:46px;height:46px;border-radius:15px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.sc-i-total{background:#f0eaf9;color:#6b2fa0;border-top:3px solid #e2d5f4;}
.sc-i-researcher{background:#ede9fe;color:#7c3aed;}
.sc-i-student{background:#dbeafe;color:#1d4ed8;}
.sc-i-dean{background:#ede9fe;color:#6b21a8;}
.sc-total{border-top:3px solid #e2d5f4;}
.sc-researcher{border-top:3px solid #7c3aed;}
.sc-student{border-top:3px solid #1d4ed8;}
.sc-dean{border-top:3px solid #6b21a8;}
.sc-info{min-width:0;}
.sc-val{font-size:2rem;font-weight:800;line-height:1;color:#1a0638;letter-spacing:-1px;}
.sc-lbl{font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#a090bc;margin-top:5px;}

.mu-alert{display:flex;align-items:center;gap:8px;padding:11px 16px;border-radius:10px;margin-bottom:1rem;font-size:.875rem;font-weight:500;}
.mu-alert-success{background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;}
.mu-alert-error{background:#fef2f2;color:#dc2626;border:1px solid #fecaca;}
.mu-import-report{padding:14px 16px;margin-bottom:18px;border:1px solid #fed7aa;border-radius:14px;background:#fff7ed;color:#9a3412;font-size:13px;line-height:1.55;}
.mu-import-report strong{display:block;margin-bottom:6px;font-size:12px;text-transform:uppercase;letter-spacing:.08em;}
.mu-import-errors{display:grid;gap:3px;max-height:170px;overflow:auto;}
.mu-import-skip-report{padding:14px 16px;margin-bottom:18px;border:1px solid #bfdbfe;border-radius:14px;background:#eff6ff;color:#1d4ed8;font-size:13px;line-height:1.55;}
.mu-import-skip-report strong{display:block;margin-bottom:6px;font-size:12px;text-transform:uppercase;letter-spacing:.08em;}
.mu-import-skip-report code{display:inline-flex;margin:0 4px;padding:2px 7px;border-radius:7px;background:#dbeafe;color:#1e3a8a;font-weight:800;}
.mu-import-skip-report small{margin-right:4px;color:#1d4ed8;font-weight:700;}
.mu-import-filter{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 16px;margin-bottom:18px;border:1px solid #bfdbfe;border-radius:14px;background:#eff6ff;color:#1d4ed8;font-size:13px;font-weight:700;flex-wrap:wrap;}
.mu-import-filter a{color:#1e40af;text-decoration:none;border-bottom:1px solid currentColor;}
.mu-import-preview{padding:14px 16px;margin-bottom:18px;border:1px solid #bbf7d0;border-radius:14px;background:#f0fdf4;color:#166534;font-size:13px;}
.mu-import-preview strong{display:block;margin-bottom:8px;font-size:12px;text-transform:uppercase;letter-spacing:.08em;}
.mu-import-preview-list{display:grid;gap:6px;}
.mu-import-preview-list div{display:flex;align-items:center;gap:10px;flex-wrap:wrap;}
.mu-import-preview-list code{padding:2px 7px;border-radius:7px;background:#dcfce7;color:#14532d;font-weight:800;}
.mu-import-preview-list small{color:#15803d;}
.mu-import-action{padding:2px 8px;border-radius:999px;background:#e0f2fe;color:#0369a1;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.04em;}
.mu-import-guide{display:grid;gap:6px;padding:13px 15px;border:1px solid #e8dff5;border-radius:14px;background:#faf8ff;color:#6b2fa0;font-size:13px;line-height:1.45;}
.mu-import-guide strong{font-size:12px;text-transform:uppercase;letter-spacing:.08em;color:#3b0f7a;}
.mu-reuse-notice{display:grid;gap:8px;padding:14px 16px;border:1.5px solid #c4b5fd;border-radius:16px;background:linear-gradient(135deg,#fbf9ff 0%,#f5f0ff 100%);color:#4c1d95;font-size:13px;line-height:1.5;}
.mu-reuse-badge{display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#6d28d9;}
.mu-reuse-badge svg{color:#7c3aed;flex-shrink:0;}
.mu-reuse-notice p{margin:0;color:#5b21b6;font-size:12.5px;font-weight:500;}
.mu-reuse-upload-opt{display:flex;align-items:center;margin-top:2px;}
.mu-reuse-link-btn{background:none;border:none;padding:0;color:#7c3aed;font-size:12px;font-weight:700;cursor:pointer;text-decoration:underline;font-family:inherit;}
.mu-reuse-link-btn:hover{color:#5b21b6;}
.mu-import-term{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;align-items:end;}
@media(max-width:640px){.mu-import-term{grid-template-columns:1fr;}}
.mu-semester-choice-row{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;}
.mu-semester-choice{display:flex;align-items:center;gap:8px;min-height:46px;padding:10px 12px;border:1px solid #e8dff5;border-radius:14px;background:#fff;color:#3b0f7a;font-size:13px;font-weight:800;cursor:pointer;}
.mu-semester-choice input{accent-color:#6d28d9;}
.mu-semester{display:inline-flex;flex-direction:column;gap:1px;min-width:118px;padding:6px 9px;border-radius:10px;background:#f0fdf4;color:#166534;border:1px solid #bbf7d0;font-size:12px;font-weight:800;line-height:1.2;}
.mu-semester small{color:#15803d;font-size:11px;font-weight:700;}
.mu-semester.is-archived{background:#f1f5f9;color:#475569;border-color:#cbd5e1;}
.mu-semester.is-archived small{color:#64748b;}
.mu-field-hint{display:block;margin-top:7px;font-size:11.5px;color:#9f8abf;line-height:1.5;}

.mu-filter-card{background:#fff;border-radius:24px;border:1px solid rgba(107,47,160,.1);box-shadow:0 14px 34px rgba(57,26,101,.06);padding:20px 22px;margin-bottom:18px;display:flex;align-items:flex-start;justify-content:space-between;gap:16px;}
.mu-filter-main{display:flex;align-items:center;gap:12px;flex:1;min-width:0;flex-wrap:wrap;}
.mu-filter-actions{display:flex;align-items:center;justify-content:flex-end;gap:10px;flex-wrap:wrap;flex-shrink:0;}
.mu-search-wrap{position:relative;flex:1;min-width:200px;}
.mu-search-icon{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#c0aee0;display:flex;align-items:center;pointer-events:none;}
.mu-filter-card input[type="text"]{width:100%;height:50px;padding:0 16px 0 38px;border:1.5px solid #e8dff5;border-radius:999px;background:linear-gradient(180deg,#fefcff 0%,#faf7ff 100%);font-size:14px;color:#1a0638;transition:border-color .15s;box-sizing:border-box;font-family:inherit;}
.mu-filter-card input:focus{outline:none;border-color:#7c3aed;box-shadow:0 0 0 4px rgba(124,58,237,.11);}
.mu-select-wrap{min-width:140px;}
.mu-select-wrap select{width:100%;height:50px;padding:0 14px;border:1.5px solid #e8dff5;border-radius:999px;background:linear-gradient(180deg,#fefcff 0%,#faf7ff 100%);font-size:14px;color:#1a0638;transition:border-color .15s;box-sizing:border-box;font-family:inherit;}
.mu-select-wrap select:focus{outline:none;border-color:#7c3aed;box-shadow:0 0 0 4px rgba(124,58,237,.11);}
.mu-btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;height:50px;padding:0 18px;border-radius:999px;font-size:13px;font-weight:800;cursor:pointer;border:none;text-decoration:none;transition:all .14s;font-family:inherit;white-space:nowrap;}
.mu-btn-primary{background:linear-gradient(135deg,#5b21b6 0%,#43188e 100%);color:#fff;box-shadow:0 14px 24px rgba(91,33,182,.22);}
.mu-btn-primary:hover{transform:translateY(-1px);}
.mu-btn-ghost{background:#fff;color:#8b7aaa;border:1.5px solid #e8dff5;}
.mu-btn-ghost:hover{background:#f4f0fc;color:#3b0f7a;}
.mu-btn-outline{color:#6b2fa0;border:1.5px solid #e2d5f4;background:#faf8ff;}
.mu-btn-outline:hover{background:#f0eaf9;}
.mu-card{background:#fff;border-radius:24px;border:1px solid rgba(107,47,160,.1);box-shadow:0 14px 34px rgba(57,26,101,.06);overflow:hidden;}
.mu-card-header{display:flex;align-items:center;justify-content:space-between;padding:22px 26px;background:linear-gradient(160deg,#fdfbff,#f8f4fe);border-bottom:1px solid #f0eaf9;flex-wrap:wrap;gap:12px;}
.mu-card-header-left{display:flex;align-items:center;gap:14px;}
.mu-card-icon{width:44px;height:44px;border-radius:14px;background:#f0eaf9;border:1px solid #e2d5f4;display:flex;align-items:center;justify-content:center;color:#6b2fa0;flex-shrink:0;}
.mu-card-title{font-size:18px;font-weight:800;color:#1a0638;margin:0 0 3px;letter-spacing:-.3px;}
.mu-card-sub{font-size:12.5px;color:#a090bc;margin:0;}
.mu-count-badge{padding:7px 14px;background:#f0eaf9;border:1px solid #e2d5f4;border-radius:999px;font-size:12px;font-weight:800;color:#6b2fa0;}

.mu-table-wrap{overflow-x:auto;}
.mu-table{width:100%;border-collapse:collapse;font-size:.8125rem;}
.mu-table thead tr{background:linear-gradient(135deg,#faf8ff,#f5f0fd);border-bottom:2px solid #ede8fa;}
.mu-table th{padding:14px 16px;font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:#9070c0;text-align:left;white-space:nowrap;}
.mu-table th:nth-child(1){min-width:260px;}
.mu-table th:nth-child(2){min-width:220px;}
.mu-table th:nth-child(4){min-width:260px;}
.mu-table tbody tr{border-bottom:1px solid #faf7ff;transition:background .12s;}
.mu-table tbody tr:last-child{border-bottom:none;}
.mu-table tbody tr:hover{background:#fcfaff;}
.mu-table td{padding:16px 16px;vertical-align:middle;}
.mu-row-inactive{opacity:.5;}
.row-warning{background:#fffdf0 !important;}

.mu-name-cell{display:flex;align-items:center;gap:12px;text-decoration:none;}
.mu-name-trigger{width:100%;padding:0;border:none;background:none;text-align:left;cursor:pointer;font-family:inherit;}
.mu-avatar{width:40px;height:40px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:800;flex-shrink:0;border:1.5px solid rgba(0,0,0,.06);}
.mu-name{font-size:.95rem;font-weight:800;color:#1a0638;}
.mu-name-cell:hover .mu-name{color:#7c3aed;}
.mu-sub-tag{font-size:.65rem;font-weight:600;display:block;margin-top:1px;}
.mu-tag-faculty{color:#6b2fa0;}

.mu-role-badge{display:inline-flex;align-items:center;padding:5px 11px;border-radius:999px;font-size:.72rem;font-weight:800;}
.mu-role-user{background:#dbeafe;color:#1d4ed8;border:1px solid #bfdbfe;}
.mu-role-dean{background:#f3e8ff;color:#6b21a8;border:1px solid #e9d5ff;}
.mu-role-researcher{background:#ede9fe;color:#6b2fa0;border:1px solid #ddd6fe;}
.mu-role-student{background:#dbeafe;color:#1d4ed8;border:1px solid #bfdbfe;}
.mu-role-faculty{background:#ede9fe;color:#6b2fa0;border:1px solid #ddd6fe;}

.mu-email{font-size:.88rem;color:#5b3d8a;}
.mu-dept{font-size:.88rem;color:#8e80af;max-width:340px;line-height:1.55;}
.mu-id{font-size:.8rem;color:#a090bc;font-family:monospace;}
.mu-muted{color:#c0aee0;font-size:.8125rem;}
.mu-empty-cell{display:block;min-height:16px;}
.mu-perm{font-size:.75rem;color:#6b2fa0;font-weight:600;}

.mu-grad-year{display:flex;flex-direction:column;gap:1px;}
.mu-grad-year{font-size:.9375rem;font-weight:800;letter-spacing:-.5px;}
.mu-grad-year small{font-size:.65rem;font-weight:600;}
.gy-soon{color:#92400e;} .gy-soon small{color:#b45309;}
.gy-future{color:#15803d;} .gy-future small{color:#16a34a;}

.mu-res-status{display:inline-flex;align-items:center;padding:5px 10px;border-radius:999px;font-size:.72rem;font-weight:800;}
.rs-active{background:#dcfce7;color:#15803d;border:1px solid #bbf7d0;}

.mu-online{display:inline-flex;align-items:center;gap:5px;font-size:.75rem;font-weight:600;color:#15803d;}
.mu-away{font-size:.75rem;color:#6b7280;}
.mu-dot{width:7px;height:7px;border-radius:50%;background:#22c55e;flex-shrink:0;}
@keyframes pulse-dot{0%,100%{opacity:1;transform:scale(1);}50%{opacity:.5;transform:scale(1.4);}}
.mu-dot-pulse{animation:pulse-dot 1.8s ease-in-out infinite;}

.mu-papers{display:inline-flex;align-items:center;justify-content:center;min-width:28px;height:24px;padding:0 8px;background:#f4f0fc;border-radius:999px;font-size:.78rem;font-weight:800;color:#6b2fa0;border:1px solid #e2d5f4;}

.mu-actions{display:flex;gap:6px;flex-wrap:wrap;}
.act{display:inline-flex;align-items:center;gap:4px;padding:6px 10px;border-radius:999px;font-size:.7rem;font-weight:800;border:none;cursor:pointer;text-decoration:none;transition:all .13s;white-space:nowrap;font-family:inherit;}
.act-view{background:#f4f0fc;color:#6b2fa0;border:1px solid #e2d5f4;} .act-view:hover{background:#ede8fa;}
.act-deactivate{background:#fef3c7;color:#92400e;border:1px solid #fde68a;} .act-deactivate:hover{background:#fde68a;}
.act-activate{background:#dcfce7;color:#15803d;border:1px solid #bbf7d0;} .act-activate:hover{background:#bbf7d0;}

.mu-modal-overlay{position:fixed;inset:0;background:rgba(19,8,38,.55);backdrop-filter:blur(3px);display:flex;align-items:center;justify-content:center;padding:20px;z-index:1200;}
.mu-modal-card{width:min(760px,100%);max-height:90vh;overflow:auto;background:#fff;border:1px solid #eadff8;border-radius:24px;box-shadow:0 30px 70px rgba(35,13,67,.24);}
.mu-modal-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;padding:22px 24px 16px;border-bottom:1px solid #f0eaf9;background:linear-gradient(180deg,#fdfbff 0%,#f8f4fe 100%);}
.mu-modal-kicker{display:block;font-size:11px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#9f8abf;margin-bottom:6px;}
.mu-modal-title{margin:0;font-size:24px;line-height:1.1;color:#210b3d;letter-spacing:-.03em;}
.mu-modal-close{width:38px;height:38px;border:none;border-radius:12px;background:#f4effd;color:#6b2fa0;font-size:26px;line-height:1;cursor:pointer;}
.mu-modal-body{padding:22px 24px 24px;}
.mu-create-modal-card{width:min(860px,100%)}
.mu-create-form{padding:22px 24px 24px;display:flex;flex-direction:column;gap:16px}
.mu-create-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
.mu-create-field{display:flex;flex-direction:column;gap:7px}
.mu-create-field label{font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:#8f80aa}
.mu-create-field input,.mu-create-field select{width:100%;padding:11px 13px;border-radius:14px;border:1.5px solid #e8dff5;background:#fff;font:inherit;color:#210b3d}
.mu-create-field input:focus,.mu-create-field select:focus{outline:none;border-color:#7c3aed;box-shadow:0 0 0 3px rgba(124,58,237,.1)}
.mu-create-field input[readonly]{background:#f7f2fe;color:#6b2fa0;font-weight:700}
.mu-create-field input.is-invalid,.mu-create-field select.is-invalid{border-color:#ef4444;background:#fff7f7}
.mu-create-field input.is-invalid:focus,.mu-create-field select.is-invalid:focus{border-color:#ef4444;box-shadow:0 0 0 3px rgba(239,68,68,.12)}
.mu-field-error{min-height:16px;color:#dc2626;font-size:11.5px;font-weight:700;line-height:1.35}
.mu-field-error:empty{display:none}
.mu-create-preview-head{display:flex;flex-direction:column;gap:5px;margin-bottom:12px}
.mu-create-preview-head strong{font-size:13px;color:#2c1250}
.mu-create-preview-head span{font-size:12px;color:#8f80aa;line-height:1.45}
.mu-create-block{padding:16px;border-radius:18px;border:1px solid #f0eaf9;background:#fcfbff}
.mu-create-actions{display:flex;justify-content:flex-end;gap:10px}
.mu-modal-actions{display:flex;justify-content:flex-end;padding:0 24px 24px;}
.mu-modal-action-btn{display:inline-flex;align-items:center;justify-content:center;min-width:140px;padding:11px 16px;border:none;border-radius:14px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;}
.mu-modal-action-btn.is-deactivate{background:#fef3c7;color:#92400e;border:1px solid #fde68a;}
.mu-modal-action-btn.is-activate{background:#dcfce7;color:#15803d;border:1px solid #bbf7d0;}
.mu-modal-hero{display:flex;align-items:flex-start;justify-content:space-between;gap:18px;flex-wrap:wrap;margin-bottom:18px;}
.mu-modal-identity{display:flex;align-items:center;gap:14px;}
.mu-modal-avatar{width:62px;height:62px;border-radius:18px;display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:800;border:1px solid rgba(0,0,0,.06);}
.mu-modal-name{margin:0 0 8px;font-size:22px;color:#1a0638;}
.mu-modal-badges{display:flex;gap:8px;flex-wrap:wrap;}
.mu-modal-badge{display:inline-flex;align-items:center;padding:5px 11px;border-radius:999px;font-size:12px;font-weight:700;}
.mu-modal-badge-role{background:#f3e8ff;color:#6b21a8;border:1px solid #e9d5ff;}
.mu-modal-badge-active{background:#dcfce7;color:#15803d;border:1px solid #bbf7d0;}
.mu-modal-badge-inactive{background:#fee2e2;color:#dc2626;border:1px solid #fecaca;}
.mu-modal-badge-note{background:#fef9c3;color:#92400e;border:1px solid #fde68a;}
.mu-modal-stats{display:grid;grid-template-columns:repeat(3,minmax(90px,1fr));gap:10px;min-width:min(100%,280px);}
.mu-modal-stat{padding:14px 12px;border:1px solid #efe8fb;border-radius:16px;background:#fcfbff;text-align:center;}
.mu-modal-stat strong{display:block;font-size:22px;line-height:1;color:#2e1253;}
.mu-modal-stat span{display:block;margin-top:6px;font-size:11px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:#9f8abf;}
.mu-modal-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;}
.mu-modal-info{padding:14px 15px;border:1px solid #f0eaf9;border-radius:16px;background:#fff;}
.mu-modal-info-label{display:block;font-size:11px;font-weight:800;letter-spacing:.07em;text-transform:uppercase;color:#a090bc;margin-bottom:6px;}
.mu-modal-info-value{font-size:14px;font-weight:600;color:#1a0638;line-height:1.45;word-break:break-word;}

.mu-empty{text-align:center;padding:48px 20px !important;color:#c0aee0;}
.mu-empty-icon{width:48px;height:48px;border-radius:12px;background:#f4f0fc;border:1px solid #e8dff5;display:flex;align-items:center;justify-content:center;color:#c0aee0;margin:0 auto 12px;}
.mu-empty p{font-size:14px;margin:0;}

.mu-pagination{padding:12px 18px;border-top:1px solid #f0eaf9;}

@media(max-width:700px){
    .mu-page-intro{padding:22px 20px;}
    .mu-page-title{font-size:25px;}
    .mu-filter-card{flex-direction:column;align-items:stretch;}
    .mu-filter-main,.mu-filter-actions{width:100%;}
    .mu-select-wrap,.mu-search-wrap{width:100%;min-width:0;}
    .mu-btn{width:100%;justify-content:center;}
    .mu-card-header{padding:16px 18px;}
    .mu-table thead{display:none;}
    .mu-table,.mu-table tbody,.mu-table tr,.mu-table td{display:block;width:100%;}
    .mu-table-wrap{overflow:visible;padding:12px;}
    .mu-table tbody{display:grid;gap:12px;}
    .mu-table tbody tr{border:1px solid #efe7fb;border-radius:16px;background:#fff;box-shadow:0 8px 24px rgba(59,15,122,.05);padding:8px 0;}
    .mu-table td{padding:10px 14px;border:none;}
    .mu-table td::before{content:attr(data-label);display:block;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#a090bc;margin-bottom:6px;}
    .mu-table td:first-child::before{display:none;}
    .mu-dept{max-width:none;}
    .mu-actions{flex-direction:column;gap:8px;}
    .mu-actions .act,.mu-actions form{width:100%;}
    .mu-actions .act{justify-content:center;}
    .mu-modal-head,.mu-modal-body{padding-left:18px;padding-right:18px;}
    .mu-modal-title{font-size:20px;}
    .mu-modal-grid,.mu-modal-stats{grid-template-columns:1fr;}
    .mu-import-term,.mu-semester-choice-row{grid-template-columns:1fr;}
    .mu-create-form{padding-left:18px;padding-right:18px;}
    .mu-create-grid{grid-template-columns:1fr;}
    .mu-create-actions{flex-direction:column;}
}
</style>

@php
    $userModalData = $users->getCollection()->mapWithKeys(function ($user) use ($currentYear) {
        $isResearcher = $user->role === 'researcher';
        $isFaculty = $isResearcher && is_null($user->graduation_year);
        $isStudentResearcher = $isResearcher && ! is_null($user->graduation_year);
        $isApprovedStudentResearcher = $isStudentResearcher && $user->is_approved;
        $isApprovedStudentAccount = $user->role === 'user' && ! empty($user->student_id);
        $isDean = $user->role === 'admin' && $user->is_department_dean;

        if ($isDean) {
            $roleLabel = 'Dean';
        } elseif ($isFaculty) {
            $roleLabel = 'Faculty';
        } elseif ($isApprovedStudentResearcher) {
            $roleLabel = 'Student Researcher';
        } elseif ($isStudentResearcher || $isApprovedStudentAccount) {
            $roleLabel = 'Student';
        } else {
            $roleLabel = ucfirst($user->role);
        }

        if (!$user->is_active) {
            $statusNote = 'Inactive';
        } elseif ($isResearcher && !$user->is_approved) {
            $statusNote = 'Pending Approval';
        } else {
            $statusNote = null;
        }

        return [
            $user->id => [
                'name' => $user->name,
                'email' => $user->email,
                'department' => $user->department ?: '—',
                'student_id' => ($isDean || $user->role === 'admin') ? '—' : ($user->student_id ?: '—'),
                'graduation_year' => $user->graduation_year ?: '—',
                'researcher_status' => $isResearcher ? ($user->researcher_status_label ?? 'Active') : '—',
                'last_seen' => $user->isOnline()
                    ? 'Online'
                    : ($user->last_seen_at ? $user->last_seen_at->diffForHumans() : ($isDean || $user->role === 'admin' ? 'No activity yet' : 'Never')),
                'joined' => $user->created_at->format('F j, Y'),
                'created_by' => $user->createdBy?->name ?? 'Self registration / legacy',
                'researcher_approved_by' => ($isResearcher && $user->researcher_approved_at)
                    ? ($user->researcherApprovedBy?->name ?? 'Unknown admin')
                    : '—',
                'researcher_approved_at' => ($isResearcher && $user->researcher_approved_at)
                    ? $user->researcher_approved_at->format('F j, Y h:i A')
                    : '—',
                'show_researcher_approval' => $isResearcher && $user->is_approved && $user->researcher_approved_at,
                'approved_by' => ($isResearcher && $user->is_approved && $user->researcher_approved_at)
                    ? ($user->researcherApprovedBy?->name ?? 'Unknown admin')
                    : '—',
                'approved_at' => ($isResearcher && $user->is_approved && $user->researcher_approved_at)
                    ? $user->researcher_approved_at->format('F j, Y h:i A')
                    : '—',
                'papers' => (int) $user->researches_count,
                'is_active' => (bool) $user->is_active,
                'role_label' => $roleLabel,
                'status_note' => $statusNote,
                'year_level' => $user->year_level_label ?? '—',
                'avatar_letter' => strtoupper(substr($user->name, 0, 1)),
                'avatar_bg' => ['#f0eaf9','#dbeafe','#dcfce7','#fef9c3','#ffe4e6'][crc32($user->name) % 5],
                'avatar_color' => ['#6b2fa0','#1d4ed8','#15803d','#92400e','#be123c'][crc32($user->name) % 5],
                'toggle_url' => route('admin.user.toggle', $user),
            ],
        ];
    });
@endphp

<script>
setTimeout(function() {
    document.querySelectorAll('.mu-alert').forEach(function(alert) {
        alert.style.transition = 'opacity 0.4s ease';
        alert.style.opacity = '0';

        setTimeout(function() {
            alert.remove();
        }, 400);
    });
}, 3000);

const userModalData = @json($userModalData);

function openUserModal(userId) {
    const user = userModalData[userId];
    if (!user) return;

    document.getElementById('userModalTitle').textContent = user.name;

    const statusBadge = user.is_active
        ? '<span class="mu-modal-badge mu-modal-badge-active">Active</span>'
        : '<span class="mu-modal-badge mu-modal-badge-inactive">Inactive</span>';

    const noteBadge = user.status_note
        ? `<span class="mu-modal-badge mu-modal-badge-note">${user.status_note}</span>`
        : '';

    const approvalHtml = user.show_researcher_approval
        ? `
            <div class="mu-modal-info"><span class="mu-modal-info-label">Researcher Approved By</span><div class="mu-modal-info-value">${user.approved_by}</div></div>
            <div class="mu-modal-info"><span class="mu-modal-info-label">Researcher Approved At</span><div class="mu-modal-info-value">${user.approved_at}</div></div>
        `
        : '';

    document.getElementById('userModalBody').innerHTML = `
        <div class="mu-modal-hero">
            <div class="mu-modal-identity">
                <div class="mu-modal-avatar" style="background:${user.avatar_bg};color:${user.avatar_color};">${user.avatar_letter}</div>
                <div>
                    <h4 class="mu-modal-name">${user.name}</h4>
                    <div class="mu-modal-badges">
                        <span class="mu-modal-badge mu-modal-badge-role">${user.role_label}</span>
                        ${statusBadge}
                        ${noteBadge}
                    </div>
                </div>
            </div>
            <div class="mu-modal-stats">
                <div class="mu-modal-stat"><strong>${user.papers}</strong><span>Papers</span></div>
                <div class="mu-modal-stat"><strong>${user.graduation_year}</strong><span>Grad. Year</span></div>
                <div class="mu-modal-stat"><strong>${user.year_level}</strong><span>Year Level</span></div>
            </div>
        </div>
        <div class="mu-modal-grid">
            <div class="mu-modal-info"><span class="mu-modal-info-label">Email</span><div class="mu-modal-info-value">${user.email}</div></div>
            <div class="mu-modal-info"><span class="mu-modal-info-label">Department</span><div class="mu-modal-info-value">${user.department}</div></div>
            <div class="mu-modal-info"><span class="mu-modal-info-label">ID</span><div class="mu-modal-info-value">${user.student_id}</div></div>
            <div class="mu-modal-info"><span class="mu-modal-info-label">Researcher Status</span><div class="mu-modal-info-value">${user.researcher_status}</div></div>
            ${approvalHtml}
            <div class="mu-modal-info"><span class="mu-modal-info-label">Account Created By</span><div class="mu-modal-info-value">${user.created_by}</div></div>
            <div class="mu-modal-info"><span class="mu-modal-info-label">Last Seen</span><div class="mu-modal-info-value">${user.last_seen}</div></div>
            <div class="mu-modal-info"><span class="mu-modal-info-label">Joined</span><div class="mu-modal-info-value">${user.joined}</div></div>
        </div>
    `;

    const toggleForm = document.getElementById('userModalToggleForm');
    const toggleButton = document.getElementById('userModalToggleButton');
    toggleForm.action = user.toggle_url;
    toggleForm.onsubmit = function() {
        return confirm(`${user.is_active ? 'Deactivate' : 'Activate'} ${user.name}?`);
    };
    toggleButton.textContent = user.is_active ? 'Deactivate User' : 'Activate User';
    toggleButton.className = `mu-modal-action-btn ${user.is_active ? 'is-deactivate' : 'is-activate'}`;

    document.getElementById('userModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeUserModal(event) {
    if (event && event.target && event.target !== document.getElementById('userModal')) {
        return;
    }

    document.getElementById('userModal').style.display = 'none';
    document.body.style.overflow = '';
}

function openImportUserModal() {
    const modal = document.getElementById('importUserModal');
    if (!modal) return;

    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeImportUserModal(event) {
    const modal = document.getElementById('importUserModal');
    if (!modal) return;

    if (event && event.target && event.target !== modal) {
        return;
    }

    modal.style.display = 'none';
    document.body.style.overflow = '';
}

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeUserModal();
        closeImportUserModal();
    }
});

@if($errors->importUsers->any())
openImportUserModal();
@endif
</script>
@endsection
