@extends('layouts.admin')
@section('title', 'User: ' . $user->name)
@section('page-title', 'User Profile')

@section('content')
@php
    $isFacultyAccount = $user->role === 'researcher' && is_null($user->graduation_year);
    $isStudentApplicant = $user->role === 'researcher' && ! is_null($user->graduation_year);
    $isStudentResearcher = $isStudentApplicant && $user->is_approved;
    $isCoordinatorAccount = $user->role === 'admin' && $user->is_research_coordinator;
    $detailRoleLabel = $user->is_department_dean
        ? 'Dean'
        : ($isCoordinatorAccount ? 'Research Coordinator' : ($isFacultyAccount ? 'Faculty' : ($isStudentResearcher ? 'Student Researcher' : ($isStudentApplicant ? 'Student' : ucfirst($user->role)))));
    $detailRoleClass = $user->is_department_dean
        ? 'admin'
        : ($isCoordinatorAccount ? 'coordinator' : ($isFacultyAccount ? 'faculty' : (($isStudentResearcher || $isStudentApplicant) ? 'student' : $user->role)));
@endphp
<div style="max-width:960px;">

    {{-- PROFILE HERO CARD --}}
    <div class="up-hero">
        {{-- Left accent bar --}}
        <div class="up-hero-bar"></div>

        <div class="up-hero-body">
            {{-- Avatar + Name --}}
            <div class="up-identity">
                <div class="up-avatar" style="
                    background:{{ ['#f0eaf9','#dbeafe','#dcfce7','#fef9c3','#ffe4e6'][crc32($user->name) % 5] }};
                    color:{{ ['#6b2fa0','#1d4ed8','#15803d','#92400e','#be123c'][crc32($user->name) % 5] }};
                ">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div class="up-identity-text">
                    <h2 class="up-name">{{ $user->name }}</h2>
                    <div class="up-badges">
                        <span class="up-role-badge up-role-{{ $detailRoleClass }}">{{ $detailRoleLabel }}</span>
                        @if($user->is_active)
                            <span class="up-status up-status-active">
                                <span class="up-dot up-dot-pulse"></span> Active
                            </span>
                        @else
                            <span class="up-status up-status-inactive">
                                <span class="up-dot"></span> Inactive
                            </span>
                        @endif
                        @if($user->role === 'researcher')
                            @if($user->isGraduated())
                                <span class="up-status-tag up-tag-graduated">Graduated</span>
                            @endif
                        @endif
                    </div>
                </div>
            </div>

            {{-- Stats Row --}}
            <div class="up-stats-row">
            <div class="up-stat">
                <div class="up-stat-val">{{ $researches->total() }}</div>
                <div class="up-stat-lbl">{{ $user->role === 'admin' ? 'Records' : 'Submissions' }}</div>
            </div>
                @if($user->role === 'researcher' && $user->graduation_year)
                <div class="up-stat">
                    <div class="up-stat-val">{{ $user->graduation_year }}</div>
                    <div class="up-stat-lbl">Grad. Year</div>
                </div>
                <div class="up-stat">
                    <div class="up-stat-val">
                        @php $yl = $user->yearsUntilGraduation(); @endphp
                        {{ $yl !== null ? ($yl > 0 ? $yl . 'yr' : ($yl === 0 ? 'Now' : 'Done')) : '—' }}
                    </div>
                    <div class="up-stat-lbl">Years Left</div>
                </div>
                @endif
                <div class="up-stat">
                    <div class="up-stat-val">{{ $user->created_at->format('Y') }}</div>
                    <div class="up-stat-lbl">Joined</div>
                </div>
            </div>
        </div>
    </div>

    {{-- INFO GRID --}}
    <div class="up-info-card">
        <div class="up-info-grid">
            <div class="up-info-item">
                <div class="up-info-icon">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                </div>
                <div>
                    <div class="up-info-label">Email</div>
                    <div class="up-info-value">{{ $user->email }}</div>
                </div>
            </div>
            <div class="up-info-item">
                <div class="up-info-icon">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                </div>
                <div>
                    <div class="up-info-label">Department</div>
                    <div class="up-info-value">{{ $user->department ?? '—' }}</div>
                </div>
            </div>
            <div class="up-info-item">
                <div class="up-info-icon">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                </div>
                <div>
                    <div class="up-info-label">Joined</div>
                    <div class="up-info-value">{{ $user->created_at->format('F j, Y') }}</div>
                </div>
            </div>
            <div class="up-info-item">
                <div class="up-info-icon">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
                </div>
                <div>
                    <div class="up-info-label">Account Created By</div>
                    <div class="up-info-value">{{ $user->createdBy?->name ?? 'Self registration / legacy' }}</div>
                </div>
            </div>
            @if($user->role === 'researcher' && $user->is_approved && $user->researcher_approved_at)
            <div class="up-info-item">
                <div class="up-info-icon">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/><circle cx="12" cy="12" r="10"/></svg>
                </div>
                <div>
                    <div class="up-info-label">Researcher Approved By</div>
                    <div class="up-info-value">{{ $user->researcherApprovedBy?->name ?? 'Unknown admin' }}</div>
                </div>
            </div>
            <div class="up-info-item">
                <div class="up-info-icon">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <div>
                    <div class="up-info-label">Researcher Approved At</div>
                    <div class="up-info-value">{{ $user->researcher_approved_at->format('F j, Y h:i A') }}</div>
                </div>
            </div>
            @endif
            <div class="up-info-item">
                <div class="up-info-icon">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
                </div>
                <div>
                    <div class="up-info-label">{{ $user->is_department_dean ? 'Dean ID' : ($user->role === 'researcher' ? 'Student / Employee ID' : 'Role') }}</div>
                    <div class="up-info-value">{{ $user->student_id ?? ucfirst($user->role) }}</div>
                </div>
            </div>
            @if($user->role === 'researcher' && $user->year_level)
            <div class="up-info-item">
                <div class="up-info-icon">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/></svg>
                </div>
                <div>
                    <div class="up-info-label">Year Level</div>
                    <div class="up-info-value">{{ $user->year_level_label }}</div>
                </div>
            </div>
            @endif
            @if($user->role === 'researcher' && $user->graduation_year)
            <div class="up-info-item">
                <div class="up-info-icon">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <div>
                    <div class="up-info-label">Graduation Year</div>
                    <div class="up-info-value up-grad-year {{ $user->isGraduated() ? 'up-gy-past' : ($user->isGraduatingSoon() ? 'up-gy-soon' : 'up-gy-future') }}">
                        {{ $user->graduation_year }}
                        @if($user->isGraduated())
                            <span class="up-gy-note">graduated</span>
                        @elseif($user->isGraduatingSoon())
                            <span class="up-gy-note">this year</span>
                        @else
                            <span class="up-gy-note">{{ $user->yearsUntilGraduation() }} yr(s) left</span>
                        @endif
                    </div>
                </div>
            </div>
            @endif
            @if($user->last_seen_at)
            <div class="up-info-item">
                <div class="up-info-icon">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </div>
                <div>
                    <div class="up-info-label">Last Seen</div>
                    <div class="up-info-value">
                        @if($user->isOnline())
                            <span style="color:#16a34a;font-weight:700;">● Online now</span>
                        @else
                            {{ $user->last_seen_at->diffForHumans() }}
                        @endif
                    </div>
                </div>
            </div>
            @elseif($user->role === 'admin')
            <div class="up-info-item">
                <div class="up-info-icon">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M12 4h9"/><path d="M4 9h16"/><path d="M4 15h16"/><path d="M8 4v16"/></svg>
                </div>
                <div>
                    <div class="up-info-label">Activity</div>
                    <div class="up-info-value up-empty-activity">No activity yet</div>
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- RESEARCH SUBMISSIONS --}}
    @if(!$researches->isEmpty())
    <div class="up-table-card">
        <div class="up-table-header">
            <div class="up-table-header-left">
                <div class="up-table-icon">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                </div>
                <h3 class="up-table-title">Research Submissions</h3>
            </div>
            <span class="up-table-count">{{ $researches->total() }} paper{{ $researches->total() != 1 ? 's' : '' }}</span>
        </div>
        <div class="up-table-wrap">
            <table class="up-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Year</th>
                        <th>Status</th>
                        <th>Views</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($researches as $i => $r)
                    <tr>
                        <td class="up-td-num">{{ $researches->firstItem() + $i }}</td>
                        <td>
                            <a href="{{ route('admin.research.show', $r) }}" class="up-research-link">
                                {{ Str::limit($r->title, 55) }}
                            </a>
                        </td>
                        <td><span class="type-badge type-{{ $r->type }} badge-sm">{{ $r->getSubmissionCategoryLabel() }}: {{ $r->getTypeLabel() }}</span></td>
                        <td class="up-td-year">{{ $r->year_published }}</td>
                        <td><span class="status-badge status-{{ $r->status }}">{{ ucfirst($r->status) }}</span></td>
                        <td class="up-td-views">{{ number_format($r->view_count) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="up-table-pagination">{{ $researches->links() }}</div>
    </div>
    @else
    <div class="up-empty-submissions">
        <div class="up-empty-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
        <p>{{ $user->role === 'admin' ? 'No records yet.' : 'No research submissions yet.' }}</p>
    </div>
    @endif

    {{-- ACTION BUTTONS --}}
    <div class="up-actions">
        <form method="POST" action="{{ route('admin.user.toggle', $user) }}" style="display:inline">
            @csrf
            <button type="submit" class="up-btn {{ $user->is_active ? 'up-btn-deactivate' : 'up-btn-activate' }}">
                @if($user->is_active)
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                    Deactivate User
                @else
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                    Activate User
                @endif
            </button>
        </form>
        <a href="{{ route('admin.users') }}" class="up-btn up-btn-ghost">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
            Back to Users
        </a>
    </div>

</div>

<style>
/* ── HERO CARD ────────────────────────────────── */
.up-hero{background:#fff;border-radius:20px;border:1px solid #f0eaf9;box-shadow:0 4px 24px rgba(59,15,122,.07);overflow:hidden;margin-bottom:1.25rem;display:flex;}
.up-hero-bar{width:5px;background:linear-gradient(180deg,#7c3aed,#3b0f7a);flex-shrink:0;}
.up-hero-body{flex:1;padding:28px 32px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:24px;}
.up-identity{display:flex;align-items:center;gap:18px;}
.up-avatar{width:64px;height:64px;border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:26px;font-weight:800;flex-shrink:0;border:2px solid rgba(0,0,0,.06);}
.up-name{font-size:1.5rem;font-weight:800;color:#1a0638;letter-spacing:-.5px;margin:0 0 8px;}
.up-badges{display:flex;align-items:center;gap:8px;flex-wrap:wrap;}
.up-role-badge{display:inline-flex;align-items:center;padding:4px 12px;border-radius:6px;font-size:.75rem;font-weight:700;}
.up-role-researcher{background:#ede9fe;color:#5b21b6;border:1px solid #ddd6fe;}
.up-role-user{background:#dbeafe;color:#1d4ed8;border:1px solid #bfdbfe;}
.up-role-admin{background:#fef9c3;color:#92400e;border:1px solid #fde68a;}
.up-role-coordinator{background:#ccfbf1;color:#0f766e;border:1px solid #99f6e4;}
.up-role-faculty{background:#ede9fe;color:#5b21b6;border:1px solid #ddd6fe;}
.up-role-student{background:#dbeafe;color:#1d4ed8;border:1px solid #bfdbfe;}
.up-status{display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:6px;font-size:.75rem;font-weight:600;}
.up-status-active{background:#dcfce7;color:#15803d;border:1px solid #bbf7d0;}
.up-status-inactive{background:#fee2e2;color:#dc2626;border:1px solid #fecaca;}
.up-dot{width:7px;height:7px;border-radius:50%;flex-shrink:0;}
.up-status-active .up-dot{background:#22c55e;}
.up-status-inactive .up-dot{background:#ef4444;}
@keyframes pulse-dot{0%,100%{opacity:1;transform:scale(1);}50%{opacity:.5;transform:scale(1.4);}}
.up-dot-pulse{animation:pulse-dot 1.8s ease-in-out infinite;}
.up-status-tag{display:inline-flex;align-items:center;padding:4px 10px;border-radius:6px;font-size:.75rem;font-weight:700;}
.up-tag-graduated{background:#ede9fe;color:#6b2fa0;border:1px solid #ddd6fe;}
.up-stats-row{display:flex;gap:32px;flex-wrap:wrap;}
.up-stat{text-align:center;}
.up-stat-val{font-size:1.75rem;font-weight:800;color:#1a0638;letter-spacing:-1px;line-height:1;}
.up-stat-lbl{font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#a090bc;margin-top:4px;}

/* ── INFO CARD ────────────────────────────────── */
.up-info-card{background:#fff;border-radius:16px;border:1px solid #f0eaf9;box-shadow:0 2px 12px rgba(59,15,122,.05);margin-bottom:1.25rem;overflow:hidden;}
.up-info-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:0;}
.up-info-item{display:flex;align-items:flex-start;gap:12px;padding:18px 22px;border-bottom:1px solid #faf7ff;border-right:1px solid #faf7ff;}
.up-info-icon{width:32px;height:32px;border-radius:8px;background:#f4f0fc;border:1px solid #e8dff5;display:flex;align-items:center;justify-content:center;color:#7c3aed;flex-shrink:0;margin-top:2px;}
.up-info-label{font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#a090bc;margin-bottom:4px;}
.up-info-value{font-size:.9375rem;font-weight:600;color:#1a0638;line-height:1.3;}
.up-empty-activity{color:#a090bc;}
.up-grad-year{display:flex;align-items:center;gap:8px;}
.up-gy-note{font-size:.7rem;font-weight:600;padding:2px 8px;border-radius:4px;}
.up-gy-past .up-gy-note{background:#ede9fe;color:#6b2fa0;}
.up-gy-soon .up-gy-note{background:#fef9c3;color:#92400e;}
.up-gy-future .up-gy-note{background:#dcfce7;color:#15803d;}

/* ── TABLE CARD ───────────────────────────────── */
.up-table-card{background:#fff;border-radius:16px;border:1px solid #f0eaf9;box-shadow:0 2px 12px rgba(59,15,122,.05);margin-bottom:1.25rem;overflow:hidden;}
.up-table-header{display:flex;align-items:center;justify-content:space-between;padding:16px 22px;background:linear-gradient(135deg,#fdfbff,#f8f4fe);border-bottom:1px solid #f0eaf9;}
.up-table-header-left{display:flex;align-items:center;gap:10px;}
.up-table-icon{width:30px;height:30px;border-radius:8px;background:#f0eaf9;border:1px solid #e2d5f4;display:flex;align-items:center;justify-content:center;color:#7c3aed;}
.up-table-title{font-size:.9375rem;font-weight:700;color:#1a0638;margin:0;}
.up-table-count{padding:4px 12px;background:#f0eaf9;border:1px solid #e2d5f4;border-radius:50px;font-size:.75rem;font-weight:700;color:#6b2fa0;}
.up-table-wrap{overflow-x:auto;}
.up-table{width:100%;border-collapse:collapse;font-size:.8125rem;}
.up-table thead tr{background:linear-gradient(135deg,#faf8ff,#f5f0fd);border-bottom:2px solid #ede8fa;}
.up-table th{padding:10px 16px;font-size:.6875rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#9070c0;text-align:left;white-space:nowrap;}
.up-table tbody tr{border-bottom:1px solid #faf7ff;transition:background .12s;}
.up-table tbody tr:last-child{border-bottom:none;}
.up-table tbody tr:hover{background:#fdf9ff;}
.up-table td{padding:12px 16px;vertical-align:middle;}
.up-td-num{color:#c0aee0;font-weight:600;font-size:.75rem;width:32px;}
.up-research-link{font-weight:600;color:#1a0638;text-decoration:none;transition:color .12s;}
.up-research-link:hover{color:#7c3aed;}
.up-td-year{font-weight:700;color:#3b0f7a;font-size:.875rem;}
.up-td-views{color:#a090bc;font-size:.8125rem;}
.up-table .status-badge{display:inline-flex;padding:5px 10px;border-radius:999px;font-size:.72rem;font-weight:800;}
.up-table .status-pending{background:#fef3c7;color:#92400e;}
.up-table .status-approved{background:#dcfce7;color:#166534;}
.up-table .status-archived{background:#e5e7eb;color:#374151;}
.up-table .status-rejected{background:#fee2e2;color:#991b1b;}
.up-table-pagination{padding:14px 22px;border-top:1px solid #f5f0fd;background:#fdfcff;}

/* ── EMPTY STATE ──────────────────────────────── */
.up-empty-submissions{background:#fff;border-radius:16px;border:1px solid #f0eaf9;padding:3rem;text-align:center;color:#c0aee0;margin-bottom:1.25rem;}
.up-empty-icon{width:52px;height:52px;border-radius:14px;background:#f4f0fc;border:1px solid #e8dff5;display:flex;align-items:center;justify-content:center;color:#c0aee0;margin:0 auto 12px;}
.up-empty-submissions p{font-size:.9375rem;margin:0;}

/* ── ACTIONS ──────────────────────────────────── */
.up-actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap;}
.up-btn{display:inline-flex;align-items:center;gap:7px;padding:11px 22px;border-radius:10px;font-size:.875rem;font-weight:700;border:none;cursor:pointer;text-decoration:none;transition:all .15s;font-family:inherit;}
.up-btn-deactivate{background:#fee2e2;color:#b91c1c;border:1.5px solid #fecaca;}
.up-btn-deactivate:hover{background:#fecaca;}
.up-btn-activate{background:#dcfce7;color:#15803d;border:1.5px solid #bbf7d0;}
.up-btn-activate:hover{background:#bbf7d0;}
.up-btn-ghost{background:#fff;color:#6b2fa0;border:1.5px solid #e2d5f4;}
.up-btn-ghost:hover{background:#f4f0fc;}

@media(max-width:640px){
    .up-hero-body{flex-direction:column;align-items:flex-start;}
    .up-stats-row{gap:20px;}
    .up-info-grid{grid-template-columns:1fr;}
}
</style>
@endsection
