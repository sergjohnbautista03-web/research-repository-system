@extends('layouts.admin')
@section('title', 'Graduated Users')
@section('page-title', 'Graduated Users')

@section('content')
@php
    $graduatedCount = $graduated->total();
    $withResearchCount = $graduated->getCollection()->filter(fn ($user) => (int) $user->researches_count > 0)->count();
    $latestGraduated = $graduated->getCollection()
        ->filter(fn ($user) => !is_null($user->graduated_at))
        ->sortByDesc('graduated_at')
        ->first();
@endphp

<section class="gr-shell">
    <div class="gr-hero">
        <div class="gr-hero-main">
            <span class="gr-kicker">User Archive</span>
            <h2>Graduated users remain credited in the repository while account access is safely closed.</h2>
            <p>These accounts stay visible for authorship and historical records, but they are deactivated after graduation.</p>
        </div>

        <div class="gr-hero-side">
            <span class="gr-hero-pill">{{ $graduatedCount }} archived account{{ $graduatedCount === 1 ? '' : 's' }}</span>
            @if($latestGraduated?->graduated_at)
                <span class="gr-hero-pill gr-hero-pill-muted">Latest archived: {{ $latestGraduated->graduated_at->format('M d, Y') }}</span>
            @endif
        </div>
    </div>

    <div class="gr-stats">
        <article class="gr-stat-card gr-stat-card-primary">
            <strong>{{ $graduatedCount }}</strong>
            <span>Total graduated users</span>
        </article>
        <article class="gr-stat-card">
            <strong>{{ $withResearchCount }}</strong>
            <span>Accounts with published research records</span>
        </article>
        <article class="gr-stat-card">
            <strong>{{ $latestGraduated?->graduation_year ?? '-' }}</strong>
            <span>Most recent recorded graduation year</span>
        </article>
    </div>

    <div class="gr-note-card">
        <div class="gr-note-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <path d="M12 7v5"/>
                <circle cx="12" cy="16" r="1"/>
            </svg>
        </div>
        <div class="gr-note-copy">
            <strong>What happens after graduation</strong>
            <p>When a user reaches their graduation period, the account is automatically deactivated. Published studies stay credited under the user, and admins can restore access if the graduation details are corrected.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="gr-alert gr-alert-success">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    <div class="gr-card">
        <div class="gr-card-header">
            <div>
                <span class="gr-card-kicker">Archived User List</span>
                <h3 class="gr-card-title">Graduated User Accounts</h3>
                <p class="gr-card-sub">These are archived accounts that stay visible for records and author credit, but cannot log in or submit new research.</p>
            </div>
            <span class="gr-count-badge">{{ $graduatedCount }} account{{ $graduatedCount === 1 ? '' : 's' }}</span>
        </div>

        @if($graduated->isEmpty())
            <div class="gr-empty">
                <div class="gr-empty-icon">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                        <path d="M22 10v6M2 10l10-5 10 5-10 5-10-5z"/>
                        <path d="M6 12v5c0 2 2 3 6 3s6-1 6-3v-5"/>
                    </svg>
                </div>
                <strong>No graduated users yet.</strong>
                <p>Archived user accounts will appear here once approved users reach their graduation status.</p>
            </div>
        @else
            <div class="gr-table-wrap">
                <table class="gr-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>User</th>
                            <th>Department</th>
                            <th>ID</th>
                            <th>Year Level</th>
                            <th>Researcher End Date</th>
                            <th>Researches</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($graduated as $i => $user)
                            @php
                                $displayGraduationYear = $user->computeGraduationYear() ?? $user->graduation_year ?? '-';
                                $displayResearcherEndDate = $user->researcher_end_date
                                    ? $user->researcher_end_date->format('M d, Y')
                                    : ($user->graduated_at ? $user->graduated_at->format('M d, Y') : 'Date unavailable');
                                $viewPayload = [
                                    'name' => $user->name,
                                    'email' => $user->email,
                                    'role_label' => is_null($user->graduation_year) ? 'Faculty Researcher' : 'Student Researcher',
                                    'is_active' => (bool) $user->is_active,
                                    'department' => $user->department ?: 'No department listed',
                                    'student_id' => $user->student_id ?: '-',
                                    'year_level' => $user->year_level_label ?: 'Not set',
                                    'course' => $user->course_duration ? $user->course_duration . '-Year Course' : 'Course not set',
                                    'graduation_year' => $displayGraduationYear,
                                    'researcher_end_date' => $displayResearcherEndDate,
                                    'graduated_at' => $user->graduated_at ? $user->graduated_at->format('F d, Y') : 'Date unavailable',
                                    'joined' => $user->created_at ? $user->created_at->format('F d, Y') : 'Date unavailable',
                                    'last_seen' => $user->isOnline()
                                        ? 'Online now'
                                        : ($user->last_seen_at ? $user->last_seen_at->diffForHumans() : 'No activity yet'),
                                    'researcher_status' => $user->researcher_status_label ?: 'Graduated',
                                    'years_left' => $user->yearsUntilGraduation(),
                                    'researches_count' => (int) $user->researches_count,
                                    'researches' => $user->researches->map(function ($research) {
                                        return [
                                            'title' => $research->title,
                                            'type' => $research->getSubmissionCategoryLabel() . ': ' . $research->getTypeLabel(),
                                            'year' => $research->year_published ?: '-',
                                            'status' => ucfirst($research->status ?: 'draft'),
                                            'views' => number_format((int) $research->view_count),
                                        ];
                                    })->values(),
                                ];
                            @endphp
                            <tr>
                                <td data-label="#">{{ $graduated->firstItem() + $i }}</td>
                                <td data-label="User">
                                    <div class="gr-user">
                                        <div class="gr-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                                        <div class="gr-user-copy">
                                            <div class="gr-user-name">{{ $user->name }}</div>
                                            <div class="gr-user-email">{{ $user->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Department" class="gr-cell-soft">{{ $user->department ?: 'No department listed' }}</td>
                                <td data-label="ID">
                                    <span class="gr-id-pill">{{ $user->student_id ?? '-' }}</span>
                                </td>
                                <td data-label="Year Level">
                                    <div class="gr-profile-stack">
                                        <span class="gr-badge gr-badge-neutral">{{ $user->year_level_label }}</span>
                                    </div>
                                </td>
                                <td data-label="Researcher End Date">
                                    <div class="gr-cell-soft">{{ $displayResearcherEndDate }}</div>
                                </td>
                                <td data-label="Researches">
                                    <div class="gr-research-stack">
                                        <span class="gr-cell-soft">{{ $user->researches_count }}</span>
                                    </div>
                                </td>
                                <td data-label="Action">
                                    <div class="gr-actions-cell">
                                        <button type="button" class="gr-btn gr-btn-view" data-graduated-review='@json($viewPayload)'>
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                                <circle cx="12" cy="12" r="3"/>
                                            </svg>
                                            View Review
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($graduated->hasPages())
                <div class="gr-pagination">{{ $graduated->links() }}</div>
            @endif
        @endif
    </div>
</section>

<div class="gr-modal" id="graduatedResearcherModal" aria-hidden="true">
    <div class="gr-modal-backdrop" data-gr-close></div>
    <div class="gr-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="grModalTitle">
        <div class="gr-modal-header">
            <div>
                <span class="gr-modal-kicker">Archived User Details</span>
                <h3 class="gr-modal-title" id="grModalTitle">Graduated User</h3>
                <p class="gr-modal-sub">Review the archived profile, access status, and recorded research without leaving this page.</p>
            </div>
            <button type="button" class="gr-modal-close" aria-label="Close" data-gr-close>&times;</button>
        </div>

        <div class="gr-modal-body">
            <div class="gr-modal-hero">
                <div class="gr-modal-avatar" id="grModalAvatar">R</div>
                <div class="gr-modal-identity">
                    <h4 class="gr-modal-name" id="grModalName">User Name</h4>
                    <div class="gr-modal-pills">
                        <span class="gr-modal-pill gr-modal-pill-primary" id="grModalRole">Researcher</span>
                        <span class="gr-modal-pill" id="grModalAccountState">View-only account</span>
                    </div>
                </div>
                <div class="gr-modal-stat">
                    <strong id="grModalResearchCount">0</strong>
                    <span>Recorded researches</span>
                </div>
            </div>

            <div class="gr-modal-grid">
                <div class="gr-modal-card">
                    <span class="gr-modal-label">Email</span>
                    <div class="gr-modal-value" id="grModalEmail">-</div>
                </div>
                <div class="gr-modal-card">
                    <span class="gr-modal-label">Department</span>
                    <div class="gr-modal-value" id="grModalDepartment">-</div>
                </div>
                <div class="gr-modal-card">
                    <span class="gr-modal-label">ID</span>
                    <div class="gr-modal-value" id="grModalStudentId">-</div>
                </div>
                <div class="gr-modal-card">
                    <span class="gr-modal-label">Year Level</span>
                    <div class="gr-modal-value" id="grModalYearLevel">-</div>
                </div>
                <div class="gr-modal-card">
                    <span class="gr-modal-label">Course</span>
                    <div class="gr-modal-value" id="grModalCourse">-</div>
                </div>
                <div class="gr-modal-card">
                    <span class="gr-modal-label">Researcher End Date</span>
                    <div class="gr-modal-value" id="grModalGraduationYear">-</div>
                </div>
                <div class="gr-modal-card">
                    <span class="gr-modal-label">Researcher Status</span>
                    <div class="gr-modal-value" id="grModalStatus">Graduated</div>
                </div>
                <div class="gr-modal-card">
                    <span class="gr-modal-label">Last Seen</span>
                    <div class="gr-modal-value" id="grModalLastSeen">-</div>
                </div>
                <div class="gr-modal-card">
                    <span class="gr-modal-label">Joined</span>
                    <div class="gr-modal-value" id="grModalJoined">-</div>
                </div>
                <div class="gr-modal-card">
                    <span class="gr-modal-label">Years Until Graduation</span>
                    <div class="gr-modal-value" id="grModalYearsLeft">Completed</div>
                </div>
                <div class="gr-modal-card gr-modal-card-wide">
                    <span class="gr-modal-label">Archived Since</span>
                    <div class="gr-modal-value" id="grModalGraduatedAt">-</div>
                </div>
                <div class="gr-modal-card gr-modal-card-wide">
                    <span class="gr-modal-label">Account Access Status</span>
                    <div class="gr-modal-access">
                        <span class="gr-access-item gr-access-no">Cannot log in</span>
                        <span class="gr-access-item gr-access-yes">Records stay archived</span>
                        <span class="gr-access-item gr-access-yes">Keeps author credit</span>
                        <span class="gr-access-item gr-access-no">Cannot submit new research</span>
                    </div>
                </div>
            </div>

            <div class="gr-modal-section">
                <div class="gr-modal-section-head">
                    <h5>Recorded Research Submissions</h5>
                    <span id="grModalResearchCountBadge">0 record(s)</span>
                </div>
                <div class="gr-modal-research-wrap">
                    <table class="gr-modal-research-table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Type</th>
                                <th>Year</th>
                                <th>Status</th>
                                <th>Views</th>
                            </tr>
                        </thead>
                        <tbody id="grModalResearchTableBody">
                            <tr>
                                <td colspan="5" class="gr-modal-empty">No research submissions found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.gr-shell{display:grid;gap:20px;}
.gr-hero{display:grid;grid-template-columns:minmax(0,1.7fr) minmax(240px,.8fr);gap:20px;padding:30px 32px;border-radius:30px;background:radial-gradient(circle at top right, rgba(109,40,217,.15), transparent 28%),radial-gradient(circle at left bottom, rgba(59,130,246,.08), transparent 24%),linear-gradient(135deg,#ffffff 0%,#f9f6ff 56%,#f1ebff 100%);border:1px solid rgba(122,90,189,.14);box-shadow:0 18px 44px rgba(59,15,122,.08);}
.gr-kicker{display:inline-flex;align-items:center;gap:8px;margin-bottom:12px;font-size:11px;font-weight:800;letter-spacing:.18em;text-transform:uppercase;color:#9179bd;}
.gr-kicker::before{content:"";width:34px;height:1px;background:linear-gradient(90deg,#6d28d9,transparent);}
.gr-hero h2{max-width:760px;margin:0 0 10px;font-size:33px;line-height:1.08;letter-spacing:-.05em;color:#1f123e;}
.gr-hero p{max-width:700px;margin:0;color:#7f72a5;font-size:14px;line-height:1.8;}
.gr-hero-side{display:flex;flex-direction:column;align-items:flex-end;justify-content:flex-start;gap:10px;}
.gr-hero-pill{display:inline-flex;align-items:center;justify-content:center;padding:10px 16px;border-radius:999px;background:#ece7ff;border:1px solid #ddd6fe;color:#6b2fa0;font-size:12px;font-weight:800;text-align:center;}
.gr-hero-pill-muted{background:#fff;border-color:#eadffd;color:#7d69a8;}
.gr-stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;}
.gr-stat-card{display:flex;flex-direction:column;justify-content:center;gap:8px;min-height:120px;padding:22px 24px;border-radius:24px;background:#fff;border:1px solid rgba(124,58,237,.1);box-shadow:0 12px 28px rgba(57,26,101,.05);}
.gr-stat-card strong{font-size:32px;line-height:1;letter-spacing:-.05em;color:#1f123e;font-variant-numeric:tabular-nums;}
.gr-stat-card span{max-width:21ch;font-size:13px;line-height:1.6;color:#8579a8;}
.gr-stat-card-primary{background:linear-gradient(135deg,#24114f 0%,#3b1d74 100%);}
.gr-stat-card-primary strong,.gr-stat-card-primary span{color:#fff;}
.gr-note-card{display:grid;grid-template-columns:auto minmax(0,1fr);gap:14px;padding:20px 22px;border-radius:24px;background:linear-gradient(135deg,#f7f3ff 0%,#f1ebff 100%);border:1px solid #d9c8fb;box-shadow:0 10px 26px rgba(91,33,182,.05);}
.gr-note-icon{width:46px;height:46px;border-radius:16px;display:flex;align-items:center;justify-content:center;background:#fff;border:1px solid #eadffd;color:#6d28d9;}
.gr-note-copy strong{display:block;margin-bottom:6px;font-size:15px;color:#44206f;}
.gr-note-copy p{margin:0;font-size:14px;line-height:1.75;color:#5f418f;}
.gr-alert{display:flex;align-items:center;gap:8px;padding:12px 16px;border-radius:16px;font-size:.875rem;font-weight:700;}
.gr-alert-success{background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;}
.gr-card{overflow:hidden;border-radius:26px;background:#fff;border:1px solid rgba(107,47,160,.1);box-shadow:0 14px 34px rgba(57,26,101,.06);}
.gr-card-header{display:flex;align-items:flex-end;justify-content:space-between;gap:16px;padding:24px 28px;background:linear-gradient(160deg,#fdfbff,#f8f4fe);border-bottom:1px solid #f0eaf9;}
.gr-card-kicker{display:inline-block;margin-bottom:8px;font-size:11px;font-weight:800;letter-spacing:.16em;text-transform:uppercase;color:#9a84c3;}
.gr-card-title{margin:0 0 4px;font-size:22px;line-height:1.15;color:#1a0638;}
.gr-card-sub{margin:0;font-size:13px;color:#9a8aba;line-height:1.65;}
.gr-count-badge{display:inline-flex;align-items:center;justify-content:center;min-height:40px;padding:0 16px;border-radius:999px;background:#f0eaf9;border:1px solid #e2d5f4;font-size:12px;font-weight:800;color:#6b2fa0;white-space:nowrap;}
.gr-table-wrap{overflow-x:auto;}
.gr-table{width:100%;min-width:1160px;border-collapse:separate;border-spacing:0;}
.gr-table thead th{padding:16px 18px;text-align:left;font-size:.74rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:#8f79bb;background:linear-gradient(135deg,#faf8ff,#f5f0fd);border-bottom:1px solid #eee7fb;}
.gr-table tbody td{padding:18px 16px;vertical-align:middle;border-bottom:1px solid #f4effc;background:#fff;}
.gr-table tbody tr:hover td{background:#fcfaff;}
.gr-table tbody tr:last-child td{border-bottom:none;}
.gr-table tbody td:first-child{width:56px;text-align:center;font-weight:700;color:#7b6a9f;}
.gr-user{display:flex;align-items:flex-start;gap:14px;min-width:280px;padding:4px 0;}
.gr-avatar{width:48px;height:48px;border-radius:16px;display:flex;align-items:center;justify-content:center;background:linear-gradient(180deg,#f3eeff,#ece4ff);border:1px solid #ddd2f8;color:#6b2fa0;font-size:15px;font-weight:800;flex-shrink:0;box-shadow:0 10px 20px rgba(109,40,217,.08);}
.gr-user-copy{display:grid;gap:6px;}
.gr-user-name{margin:0;font-size:.95rem;font-weight:800;color:#1a0638;line-height:1.35;}
.gr-user-email{font-size:.88rem;font-weight:500;color:#5b3d8a;word-break:break-word;line-height:1.5;letter-spacing:0;}
.gr-readonly-tag{display:inline-flex;align-items:center;justify-content:center;width:max-content;padding:5px 11px;border-radius:999px;background:#f5f3ff;border:1px solid #e9ddff;color:#7c3aed;font-size:.72rem;font-weight:800;}
.gr-cell-soft{color:#6b7280;font-size:.88rem;line-height:1.55;font-weight:500;}
.gr-table tbody td[data-label="Department"]{min-width:170px;font-size:.88rem;color:#6b7280;font-weight:500;line-height:1.55;}
.gr-meta-label{font-size:.66rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:#ab98ca;}
.gr-id-pill,.gr-badge{display:inline-flex;align-items:center;justify-content:center;font-size:.88rem;font-weight:700;line-height:1.4;}
.gr-id-pill{color:#6b7280;line-height:1.45;text-align:center;}
.gr-badge-neutral{color:#6b7280;}
.gr-profile-stack,.gr-research-stack{display:flex;flex-direction:column;gap:8px;}
.gr-profile-note{font-size:.82rem;color:#8e80af;line-height:1.55;}
.gr-grad-year{margin:0;font-size:.9375rem;line-height:1.35;font-weight:800;letter-spacing:-.5px;color:#6b7280;}
.gr-access-item{display:inline-flex;align-items:center;justify-content:flex-start;width:max-content;max-width:100%;padding:5px 10px;border-radius:999px;font-size:.74rem;font-weight:800;line-height:1.35;white-space:nowrap;background:#fff;border:1px solid transparent;}
.gr-access-yes{color:#15803d;background:#f0fdf4;border-color:#bbf7d0;}
.gr-access-no{color:#dc2626;background:#fef2f2;border-color:#fecaca;}
.gr-actions-cell{display:flex;align-items:center;justify-content:center;min-width:120px;}
.gr-btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;height:44px;padding:0 16px;border-radius:999px;font-size:12.5px;font-weight:600;text-decoration:none;transition:all .14s ease;border:none;cursor:pointer;font-family:inherit;white-space:nowrap;}
.gr-btn-view{background:#f5f3ff;border:1px solid #ddd6fe;color:#5b21b6;}
.gr-btn-view:hover{background:#ede9fe;}
.gr-empty{padding:72px 24px;text-align:center;}
.gr-empty-icon{width:68px;height:68px;margin:0 auto 14px;border-radius:22px;display:flex;align-items:center;justify-content:center;background:#f4f0fc;border:1px solid #e8dff5;color:#c0aee0;}
.gr-empty strong{display:block;margin-bottom:6px;font-size:18px;color:#241248;}
.gr-empty p{margin:0;color:#9889b7;font-size:14px;}
.gr-pagination{padding:20px 24px 24px;border-top:1px solid #f4effc;}
.gr-modal{position:fixed;inset:0;z-index:1300;display:none;}
.gr-modal.is-open{display:block;}
.gr-modal-backdrop{position:absolute;inset:0;background:rgba(24,10,48,.52);backdrop-filter:blur(4px);}
.gr-modal-dialog{position:relative;width:min(980px,calc(100% - 32px));max-height:90vh;margin:28px auto;overflow:auto;border-radius:28px;background:#fff;border:1px solid #eadff8;box-shadow:0 30px 70px rgba(35,13,67,.24);}
.gr-modal-header{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;padding:24px 26px 18px;border-bottom:1px solid #f0eaf9;background:linear-gradient(180deg,#fdfbff 0%,#f8f4fe 100%);}
.gr-modal-kicker{display:block;margin-bottom:6px;font-size:11px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#9f8abf;}
.gr-modal-title{margin:0 0 6px;font-size:24px;line-height:1.1;color:#210b3d;letter-spacing:-.03em;}
.gr-modal-sub{margin:0;font-size:13px;color:#8d7dac;}
.gr-modal-close{width:40px;height:40px;border:none;border-radius:14px;background:#f4effd;color:#6b2fa0;font-size:28px;line-height:1;cursor:pointer;}
.gr-modal-body{padding:24px 26px 26px;}
.gr-modal-hero{display:grid;grid-template-columns:auto minmax(0,1fr) auto;gap:16px;align-items:center;margin-bottom:18px;}
.gr-modal-avatar{width:62px;height:62px;border-radius:20px;display:flex;align-items:center;justify-content:center;background:#f0eaf9;border:1px solid #e3d7f7;color:#6b2fa0;font-size:24px;font-weight:800;}
.gr-modal-name{margin:0 0 8px;font-size:22px;color:#1a0638;}
.gr-modal-pills{display:flex;flex-wrap:wrap;gap:8px;}
.gr-modal-pill{display:inline-flex;align-items:center;padding:5px 11px;border-radius:999px;background:#f5f3ff;border:1px solid #e9ddff;color:#6d28d9;font-size:12px;font-weight:700;}
.gr-modal-pill-primary{background:#ede9fe;border-color:#ddd6fe;color:#5b21b6;}
.gr-modal-stat{min-width:132px;padding:14px 16px;border-radius:18px;border:1px solid #efe8fb;background:#fcfbff;text-align:center;}
.gr-modal-stat strong{display:block;font-size:24px;line-height:1;color:#2e1253;}
.gr-modal-stat span{display:block;margin-top:6px;font-size:11px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:#9f8abf;}
.gr-modal-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-bottom:18px;}
.gr-modal-card{padding:15px 16px;border:1px solid #f0eaf9;border-radius:18px;background:#fff;}
.gr-modal-card-wide{grid-column:span 2;}
.gr-modal-label{display:block;margin-bottom:7px;font-size:11px;font-weight:800;letter-spacing:.07em;text-transform:uppercase;color:#a090bc;}
.gr-modal-value{font-size:14px;font-weight:600;line-height:1.55;color:#1a0638;word-break:break-word;}
.gr-modal-access{display:flex;flex-wrap:wrap;gap:8px 16px;}
.gr-modal-section{border:1px solid #f0eaf9;border-radius:20px;overflow:hidden;background:#fff;}
.gr-modal-section-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:16px 18px;background:linear-gradient(160deg,#fdfbff,#f8f4fe);border-bottom:1px solid #f0eaf9;}
.gr-modal-section-head h5{margin:0;font-size:15px;color:#1a0638;}
.gr-modal-section-head span{display:inline-flex;align-items:center;justify-content:center;min-height:32px;padding:0 12px;border-radius:999px;background:#f0eaf9;border:1px solid #e2d5f4;font-size:12px;font-weight:800;color:#6b2fa0;}
.gr-modal-research-wrap{overflow-x:auto;}
.gr-modal-research-table{width:100%;border-collapse:collapse;}
.gr-modal-research-table th{padding:12px 16px;text-align:left;font-size:.72rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#8f79bb;background:#fbf9ff;border-bottom:1px solid #f0eaf9;}
.gr-modal-research-table td{padding:14px 16px;border-bottom:1px solid #f4effc;font-size:.85rem;color:#2b174d;vertical-align:top;}
.gr-modal-research-table tbody tr:last-child td{border-bottom:none;}
.gr-modal-research-title{font-weight:700;line-height:1.5;color:#1a0638;}
.gr-modal-research-type{color:#6b2fa0;font-weight:700;}
.gr-modal-research-status{display:inline-flex;align-items:center;justify-content:center;padding:5px 10px;border-radius:999px;background:#f3e8ff;border:1px solid #e9d5ff;color:#6b21a8;font-size:.74rem;font-weight:800;}
.gr-modal-empty{text-align:center;color:#9787b7;}
@media (max-width:1100px){.gr-hero{grid-template-columns:1fr;}.gr-hero-side{align-items:flex-start;flex-direction:row;flex-wrap:wrap;}.gr-stats{grid-template-columns:1fr;}}
@media (max-width:768px){.gr-hero,.gr-note-card,.gr-card-header{padding:22px 20px;}.gr-hero h2{font-size:27px;}.gr-card-header{align-items:flex-start;flex-direction:column;}.gr-table{min-width:0;}.gr-table thead{display:none;}.gr-table,.gr-table tbody,.gr-table tr,.gr-table td{display:block;width:100%;}.gr-table tbody tr{padding:16px 18px;border-bottom:1px solid #f4effc;}.gr-table tbody tr:last-child{border-bottom:none;}.gr-table tbody td{padding:10px 0;border-bottom:none;background:transparent;}.gr-table td::before{content:attr(data-label);display:block;margin-bottom:6px;font-size:.7rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:#957fbd;}.gr-user{min-width:0;}.gr-modal-header,.gr-modal-body{padding-left:18px;padding-right:18px;}.gr-modal-title{font-size:20px;}.gr-modal-hero,.gr-modal-grid{grid-template-columns:1fr;}.gr-modal-card-wide{grid-column:auto;}.gr-modal-dialog{width:min(100%,calc(100% - 16px));margin:12px auto;}}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('graduatedResearcherModal');
    if (!modal) return;

    const nameEl = document.getElementById('grModalName');
    const avatarEl = document.getElementById('grModalAvatar');
    const roleEl = document.getElementById('grModalRole');
    const accountStateEl = document.getElementById('grModalAccountState');
    const researchCountEl = document.getElementById('grModalResearchCount');
    const researchCountBadgeEl = document.getElementById('grModalResearchCountBadge');
    const emailEl = document.getElementById('grModalEmail');
    const departmentEl = document.getElementById('grModalDepartment');
    const studentIdEl = document.getElementById('grModalStudentId');
    const yearLevelEl = document.getElementById('grModalYearLevel');
    const courseEl = document.getElementById('grModalCourse');
    const graduationYearEl = document.getElementById('grModalGraduationYear');
    const statusEl = document.getElementById('grModalStatus');
    const lastSeenEl = document.getElementById('grModalLastSeen');
    const joinedEl = document.getElementById('grModalJoined');
    const yearsLeftEl = document.getElementById('grModalYearsLeft');
    const graduatedAtEl = document.getElementById('grModalGraduatedAt');
    const researchTableBodyEl = document.getElementById('grModalResearchTableBody');

    function escapeHtml(value) {
        return String(value ?? '-')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function renderResearchRows(researches) {
        if (!Array.isArray(researches) || researches.length === 0) {
            researchTableBodyEl.innerHTML = '<tr><td colspan="5" class="gr-modal-empty">No recorded research submissions found.</td></tr>';
            return;
        }

        researchTableBodyEl.innerHTML = researches.map(function (research) {
            return `
                <tr>
                    <td><div class="gr-modal-research-title">${escapeHtml(research.title)}</div></td>
                    <td><span class="gr-modal-research-type">${escapeHtml(research.type)}</span></td>
                    <td>${escapeHtml(research.year)}</td>
                    <td><span class="gr-modal-research-status">${escapeHtml(research.status)}</span></td>
                    <td>${escapeHtml(research.views)}</td>
                </tr>
            `;
        }).join('');
    }

    function openGraduatedModal(payload) {
        nameEl.textContent = payload.name || 'Researcher';
        avatarEl.textContent = (payload.name || 'R').trim().charAt(0).toUpperCase();
        roleEl.textContent = payload.role_label || 'Researcher';
        accountStateEl.textContent = payload.is_active ? 'Active view-only account' : 'Inactive archived account';
        researchCountEl.textContent = payload.researches_count ?? 0;
        researchCountBadgeEl.textContent = `${payload.researches_count ?? 0} recorded item(s)`;
        emailEl.textContent = payload.email || '-';
        departmentEl.textContent = payload.department || '-';
        studentIdEl.textContent = payload.student_id || '-';
        yearLevelEl.textContent = payload.year_level || '-';
        courseEl.textContent = payload.course || '-';
        graduationYearEl.textContent = payload.graduation_year || '-';
        statusEl.textContent = payload.researcher_status || 'Graduated';
        lastSeenEl.textContent = payload.last_seen || '-';
        joinedEl.textContent = payload.joined || '-';
        yearsLeftEl.textContent = payload.years_left === null || payload.years_left === undefined
            ? 'Already graduated'
            : (payload.years_left > 0 ? `${payload.years_left} year(s)` : 'Already graduated');
        graduatedAtEl.textContent = payload.graduated_at || '-';
        renderResearchRows(payload.researches || []);

        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeGraduatedModal() {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    document.querySelectorAll('[data-graduated-review]').forEach(function (button) {
        button.addEventListener('click', function () {
            const payload = JSON.parse(button.getAttribute('data-graduated-review') || '{}');
            openGraduatedModal(payload);
        });
    });

    modal.querySelectorAll('[data-gr-close]').forEach(function (element) {
        element.addEventListener('click', closeGraduatedModal);
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal.classList.contains('is-open')) {
            closeGraduatedModal();
        }
    });
});
</script>
@endsection
