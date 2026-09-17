@extends('layouts.admin')
@section('title', $semester->label)
@section('page-title', 'Semester Details')

@section('content')
@php
    $isArchived = $semester->isArchived();
@endphp

<div class="sd-shell">
    <section class="sd-hero">
        <div>
            <a href="{{ route('admin.semesters') }}" class="sd-back-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
                Back to semesters
            </a>
            <span class="sd-kicker">Academic Semester</span>
            <h2>{{ $semester->semester }}</h2>
            <p>{{ $semester->school_year }}</p>
        </div>
        <div class="sd-hero-actions">
            <span class="sd-status {{ $isArchived ? 'is-archived' : 'is-active' }}">{{ $isArchived ? 'Archived' : 'Active' }}</span>
            @if($canArchiveSemesters && ! $isArchived)
                <form method="POST" action="{{ route('admin.semesters.archive', $semester) }}" onsubmit="return confirm('Archive {{ $semester->label }}?')">
                    @csrf
                    <button type="submit" class="sd-btn sd-btn-danger">Archive</button>
                </form>
            @endif
        </div>
    </section>

    <div class="sd-stat-grid">
        <div class="sd-stat-card">
            <span>Assigned Users</span>
            <strong>{{ number_format($users->total()) }}</strong>
        </div>
        <div class="sd-stat-card">
            <span>Research Records</span>
            <strong>{{ number_format($researches->total()) }}</strong>
        </div>
        <div class="sd-stat-card">
            <span>Created</span>
            <strong>{{ optional($semester->created_at)->format('M d, Y') ?? 'Legacy' }}</strong>
        </div>
        <div class="sd-stat-card">
            <span>{{ $isArchived ? 'Archived By' : 'Created By' }}</span>
            <strong>{{ $isArchived ? ($semester->archivedBy?->name ?? 'Unknown') : ($semester->creator?->name ?? 'System') }}</strong>
        </div>
    </div>

    @if($isArchived)
        <div class="sd-archive-note">
            Archived on {{ $semester->archived_at->format('M d, Y h:i A') }}. Linked users and researches remain visible for reference.
        </div>
    @endif

    <section class="sd-card">
        <div class="sd-card-head">
            <div>
                <span>Users</span>
                <h3>Assigned accounts</h3>
            </div>
            <a href="{{ route('admin.users', ['school_year' => $semester->school_year, 'semester' => $semester->semester]) }}" class="sd-btn sd-btn-ghost">Open filtered users</a>
        </div>

        <div class="sd-table-wrap">
            <table class="sd-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Department</th>
                        <th>Current</th>
                        <th>Papers</th>
                        <th>Assigned</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        @php
                            $assignedAt = $user->pivot?->assigned_at ?: $user->pivot?->created_at;
                            $assignedDate = $assignedAt ? \Illuminate\Support\Carbon::parse($assignedAt)->format('M d, Y') : 'Legacy';
                        @endphp
                        <tr>
                            <td data-label="Name">
                                <a href="{{ route('admin.user.show', $user) }}" class="sd-main-link">{{ $user->name }}</a>
                                <small>{{ $user->student_id ?: ucfirst($user->role) }}</small>
                            </td>
                            <td data-label="Email">{{ $user->email }}</td>
                            <td data-label="Department">{{ $user->department ? Str::limit($user->department, 34) : 'Unassigned' }}</td>
                            <td data-label="Current">
                                <span class="sd-mini-status {{ (int) $user->current_academic_semester_id === (int) $semester->id ? 'is-current' : 'is-history' }}">
                                    {{ (int) $user->current_academic_semester_id === (int) $semester->id ? 'Current' : 'History' }}
                                </span>
                            </td>
                            <td data-label="Papers">{{ number_format($user->researches_count) }}</td>
                            <td data-label="Assigned">{{ $assignedDate }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="sd-empty">No users are assigned to this semester.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="sd-pagination">{{ $users->links('vendor.pagination.custom') }}</div>
        @endif
    </section>

    <section class="sd-card">
        <div class="sd-card-head">
            <div>
                <span>Researches</span>
                <h3>Linked records</h3>
            </div>
            <a href="{{ route('admin.researches', ['school_year' => $semester->school_year, 'semester' => $semester->semester]) }}" class="sd-btn sd-btn-ghost">Open filtered researches</a>
        </div>

        <div class="sd-table-wrap">
            <table class="sd-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Department</th>
                        <th>Year</th>
                        <th>Status</th>
                        <th>Views</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($researches as $research)
                        <tr>
                            <td data-label="Title">
                                <a href="{{ route('admin.research.show', $research) }}" class="sd-main-link">{{ Str::limit($research->title, 70) }}</a>
                                <small>{{ $research->getSubmissionCategoryLabel() }}: {{ $research->getTypeLabel() }}</small>
                            </td>
                            <td data-label="Author">{{ Str::limit($research->author_name, 38) }}</td>
                            <td data-label="Department">{{ Str::limit($research->department, 34) }}</td>
                            <td data-label="Year">{{ $research->year_published }}</td>
                            <td data-label="Status"><span class="status-badge status-{{ $research->status }}">{{ ucfirst($research->status) }}</span></td>
                            <td data-label="Views">{{ number_format($research->view_count) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="sd-empty">No research records are linked to this semester.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($researches->hasPages())
            <div class="sd-pagination">{{ $researches->links('vendor.pagination.custom') }}</div>
        @endif
    </section>
</div>

<style>
.sd-shell{display:grid;gap:22px;max-width:1180px}
.sd-hero,.sd-card,.sd-stat-card,.sd-archive-note{background:#fff;border:1px solid rgba(107,47,160,.1);box-shadow:0 12px 28px rgba(57,26,101,.06)}
.sd-hero{display:flex;align-items:flex-start;justify-content:space-between;gap:20px;padding:24px;border-radius:22px}
.sd-back-link{display:inline-flex;align-items:center;gap:7px;margin-bottom:14px;color:#5f3890;font-size:13px;font-weight:800;text-decoration:none}
.sd-back-link svg,.sd-btn svg{width:15px;height:15px}
.sd-kicker,.sd-card-head span,.sd-stat-card span{display:inline-flex;color:#6b2fa0;font-size:11px;font-weight:800;letter-spacing:.08em;text-transform:uppercase}
.sd-hero h2{margin:4px 0 2px;color:#2f144f;font-size:32px}
.sd-hero p{margin:0;color:#5f3890;font-size:17px;font-weight:800}
.sd-hero-actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.sd-hero-actions form{margin:0}
.sd-status,.sd-mini-status{display:inline-flex;align-items:center;border-radius:999px;font-size:12px;font-weight:900}
.sd-status{min-height:34px;padding:0 12px}
.sd-status.is-active,.sd-mini-status.is-current{background:#dcfce7;color:#166534}
.sd-status.is-archived,.sd-mini-status.is-history{background:#e5e7eb;color:#4b5563}
.sd-mini-status{min-height:28px;padding:0 10px}
.sd-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:40px;padding:0 14px;border:0;border-radius:12px;font-size:13px;font-weight:900;text-decoration:none;cursor:pointer;white-space:nowrap}
.sd-btn-ghost{background:#f6f1ff;color:#5f3890;border:1px solid #e5d8f7}
.sd-btn-danger{background:#fff1f2;color:#be123c;border:1px solid #fecdd3}
.sd-stat-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}
.sd-stat-card{padding:18px;border-radius:18px}
.sd-stat-card strong{display:block;margin-top:8px;color:#2f144f;font-size:22px;line-height:1.15}
.sd-archive-note{padding:14px 16px;border-radius:16px;background:#f8fafc;color:#475569;font-size:14px}
.sd-card{border-radius:22px;overflow:hidden}
.sd-card-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;padding:18px 20px;border-bottom:1px solid #f0e9fb}
.sd-card-head h3{margin:4px 0 0;color:#2f144f}
.sd-table-wrap{overflow:auto}
.sd-table{width:100%;border-collapse:collapse;font-size:14px}
.sd-table th{padding:13px 16px;text-align:left;color:#6f6189;background:#faf7ff;font-size:11px;text-transform:uppercase;letter-spacing:.06em;white-space:nowrap}
.sd-table td{padding:15px 16px;border-top:1px solid #f0e9fb;color:#2f144f;vertical-align:middle}
.sd-table td small{display:block;margin-top:3px;color:#837596;font-size:12px}
.sd-main-link{color:#3b0f7a;font-weight:900;text-decoration:none}
.sd-main-link:hover{text-decoration:underline}
.sd-empty{padding:34px 16px!important;text-align:center;color:#837596}
.sd-pagination{padding:16px 20px;border-top:1px solid #f0e9fb}
@media (max-width: 980px){.sd-stat-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.sd-hero{flex-direction:column}.sd-hero-actions{width:100%}}
@media (max-width: 720px){.sd-stat-grid{grid-template-columns:1fr}.sd-card-head{flex-direction:column}.sd-table th{display:none}.sd-table,.sd-table tbody,.sd-table tr,.sd-table td{display:block;width:100%}.sd-table tr{border-top:1px solid #f0e9fb}.sd-table td{display:flex;justify-content:space-between;gap:16px;border-top:0;padding:11px 16px}.sd-table td::before{content:attr(data-label);color:#86789a;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.06em}.sd-table td[data-label="Name"],.sd-table td[data-label="Title"]{display:block}.sd-table td[data-label="Name"]::before,.sd-table td[data-label="Title"]::before{display:block;margin-bottom:6px}}
</style>
@endsection
