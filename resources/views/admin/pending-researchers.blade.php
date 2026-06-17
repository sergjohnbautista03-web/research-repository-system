@extends('layouts.admin')
@section('title', 'Pending Researchers')
@section('page-title', 'Pending Researcher Approvals')

@section('content')
@php
    $pendingCount = $pendingResearchers->total() ?? $pendingResearchers->count();
    $studentCount = $pendingResearchers->getCollection()->filter(fn ($researcher) => !is_null($researcher->graduation_year))->count();
    $facultyCount = $pendingResearchers->getCollection()->filter(fn ($researcher) => is_null($researcher->graduation_year))->count();
    $activeSearch = request('search');
@endphp

<section class="pr-shell">
    <div class="pr-hero">
        <div class="pr-hero-copy">
            <span class="pr-kicker">Approval Queue</span>
            <h2>Review pending researcher applications with clearer context and faster decisions</h2>
            <p>
                {{ $approvalDepartment
                    ? 'You are reviewing researcher requests submitted under ' . $approvalDepartment . '.'
                    : 'Review submitted researcher accounts, inspect uploaded documents, and approve only qualified applicants.' }}
            </p>
        </div>

        <div class="pr-hero-badges">
            <span class="pr-hero-pill">{{ $pendingCount }} pending</span>
            @if($activeSearch)
                <span class="pr-hero-pill pr-hero-pill-muted">Search: {{ $activeSearch }}</span>
            @endif
        </div>
    </div>

    @if($errors->any())
        <div class="pr-alert pr-alert-error">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    @if(session('success'))
        <div class="pr-alert pr-alert-success">
            <p>{{ session('success') }}</p>
        </div>
    @endif

    <div class="pr-stats">
        <article class="pr-stat-card pr-stat-card-primary">
            <div class="pr-stat-copy">
                <strong>{{ $pendingCount }}</strong>
                <span>Total requests in queue</span>
            </div>
        </article>
        <article class="pr-stat-card">
            <div class="pr-stat-copy">
                <strong>{{ $studentCount }}</strong>
                <span>Student applications</span>
            </div>
        </article>
        <article class="pr-stat-card">
            <div class="pr-stat-copy">
                <strong>{{ $facultyCount }}</strong>
                <span>Faculty applications</span>
            </div>
        </article>
    </div>

    <form method="GET" class="pr-search-card">
        <div class="pr-search-copy">
            <span class="pr-search-kicker">Search Queue</span>
            <p>Find a request by applicant name, email, or ID.</p>
        </div>

        <div class="pr-search-bar">
            <div class="pr-search-wrap">
                <span class="pr-search-icon">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                </span>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, email, or ID...">
            </div>
            <button type="submit" class="pr-btn pr-btn-primary">Search</button>
            <a href="{{ route('admin.pending-researchers') }}" class="pr-btn pr-btn-ghost">Clear</a>
        </div>
    </form>

    <div class="pr-card">
        <div class="pr-card-header">
            <div class="pr-card-header-left">
                <div class="pr-card-icon">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <polyline points="23 11 17 11"/>
                        <line x1="20" y1="8" x2="20" y2="14"/>
                    </svg>
                </div>
                <div>
                    <h3 class="pr-card-title">Pending Approvals</h3>
                    <p class="pr-card-sub">
                        {{ $approvalDepartment ? 'Review researcher requests for ' . $approvalDepartment : 'Review and approve researcher account requests' }}
                    </p>
                </div>
            </div>
            <span class="pr-count-badge">{{ $pendingCount }} pending</span>
        </div>

        <div class="pr-table-wrap">
            <table class="pr-table">
                <thead>
                    <tr>
                        <th>Applicant</th>
                        <th>ID</th>
                        <th>Department</th>
                        <th>Type</th>
                        <th>Files</th>
                        <th>Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pendingResearchers as $researcher)
                    @php
                        $verificationDocuments = $researcher->verification_documents ?? [];
                        $researchDocuments = $researcher->research_documents ?? [];
                        $researcherType = $researcher->graduation_year ? 'Student' : 'Faculty';
                        $registeredAt = ($researcher->researcher_applied_at ?? $researcher->updated_at ?? $researcher->created_at)->copy()->timezone('Asia/Manila');
                        $documents = collect($verificationDocuments)
                            ->map(fn ($document) => [
                                'group' => 'Credential',
                                'label' => $document['original_name'] ?? 'Credential Document',
                                'path' => !empty($document['path']) ? asset('storage/' . $document['path']) : null,
                            ])
                            ->merge(
                                collect($researchDocuments)->map(fn ($document) => [
                                    'group' => 'Research Proof',
                                    'label' => $document['original_name'] ?? 'Research Proof Document',
                                    'path' => !empty($document['path']) ? asset('storage/' . $document['path']) : null,
                                ])
                            )
                            ->values();
                        $reviewPayload = [
                            'name' => $researcher->name,
                            'email' => $researcher->email,
                            'student_id' => $researcher->student_id ?: 'N/A',
                            'department' => $researcher->department ?: 'N/A',
                            'type' => $researcherType,
                            'year_level' => $researcher->year_level ? $researcher->year_level_label : 'N/A',
                            'graduation_year' => $researcher->graduation_year ?: 'N/A',
                            'suggested_end_date' => $researcher->graduation_year ? ($researcher->graduation_year . '-12-31') : '',
                            'registered_at' => $registeredAt->format('F j, Y'),
                            'registered_time' => $registeredAt->format('g:i A'),
                            'verification_count' => count($verificationDocuments),
                            'research_count' => count($researchDocuments),
                            'documents' => $documents,
                            'approve_url' => route('admin.approve-researcher', $researcher),
                            'reject_url' => route('admin.reject-researcher', $researcher),
                        ];
                    @endphp
                    <tr>
                        <td data-label="Applicant">
                            <div class="pr-profile">
                                <div class="pr-avatar">{{ strtoupper(substr($researcher->name, 0, 1)) }}</div>
                                <div>
                                    <div class="pr-name">{{ $researcher->name }}</div>
                                    <div class="pr-email">{{ $researcher->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td data-label="Student / Employee ID">
                            <span class="pr-id-badge">{{ $researcher->student_id ?? 'N/A' }}</span>
                        </td>
                        <td data-label="Department">
                            <div class="pr-dept">{{ $researcher->department ?? '-' }}</div>
                        </td>
                        <td data-label="Type">
                            <span class="pr-type-badge {{ $researcher->graduation_year ? 'pr-type-student' : 'pr-type-faculty' }}">
                                {{ $researcherType }}
                            </span>
                        </td>
                        <td data-label="Files">
                            <div class="pr-file-summary">
                                <span class="pr-file-pill">{{ count($verificationDocuments) }} credential</span>
                                <span class="pr-file-pill pr-file-pill-alt">{{ count($researchDocuments) }} research</span>
                            </div>
                        </td>
                        <td data-label="Registered">
                            <div class="pr-date">{{ $registeredAt->format('M d, Y') }}</div>
                            <div class="pr-date-sub">{{ $registeredAt->format('h:i A') }}</div>
                        </td>
                        <td data-label="Actions">
                            <div class="pr-actions-cell">
                                <button type="button" class="pr-btn pr-btn-view" data-review='@json($reviewPayload)'>
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                    View Review
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="pr-empty">
                            <div class="pr-empty-icon">
                                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                    <circle cx="9" cy="7" r="4"/>
                                    <polyline points="23 11 17 11"/>
                                </svg>
                            </div>
                            <p>No pending researcher approvals.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($pendingResearchers->hasPages())
            <div class="pr-pagination">
                {{ $pendingResearchers->links() }}
            </div>
        @endif
    </div>
</section>

<div class="pr-modal" id="reviewModal" aria-hidden="true">
    <div class="pr-modal-backdrop" data-close-modal></div>
    <div class="pr-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="reviewModalTitle">
        <div class="pr-modal-header">
            <div>
                <h3 class="pr-modal-title" id="reviewModalTitle">Researcher Review</h3>
                <p class="pr-modal-sub">Check the details and files first, then approve or reject the request here.</p>
            </div>
            <button type="button" class="pr-modal-close" aria-label="Close" data-close-modal>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>

        <div class="pr-modal-body">
            <div class="pr-review-hero">
                <div class="pr-review-avatar" id="modalAvatar">R</div>
                <div>
                    <div class="pr-review-name" id="modalName">Researcher Name</div>
                    <div class="pr-review-meta">
                        <span class="pr-type-badge" id="modalType">Researcher</span>
                        <span class="pr-review-meta-item" id="modalDepartment">Department</span>
                    </div>
                </div>
            </div>

            <div class="pr-review-grid">
                <div class="pr-review-card">
                    <div class="pr-review-card-title">Profile Information</div>
                    <div class="pr-review-list">
                        <div class="pr-review-item">
                            <span class="pr-review-label">Email</span>
                            <span class="pr-review-value" id="modalEmail">-</span>
                        </div>
                        <div class="pr-review-item">
                            <span class="pr-review-label">Student / Employee ID</span>
                            <span class="pr-review-value" id="modalId">-</span>
                        </div>
                        <div class="pr-review-item">
                            <span class="pr-review-label">Year Level</span>
                            <span class="pr-review-value" id="modalYearLevel">-</span>
                        </div>
                        <div class="pr-review-item">
                            <span class="pr-review-label">Graduation Year</span>
                            <span class="pr-review-value" id="modalGraduationYear">-</span>
                        </div>
                        <div class="pr-review-item">
                            <span class="pr-review-label">Researcher End Date</span>
                            <span class="pr-review-value">Required before approval</span>
                        </div>
                        <div class="pr-review-item">
                            <span class="pr-review-label">Registered</span>
                            <span class="pr-review-value" id="modalRegistered">-</span>
                        </div>
                    </div>
                </div>

                <div class="pr-review-card">
                    <div class="pr-review-card-title">Uploaded Documents</div>
                    <div class="pr-review-counts">
                        <span class="pr-file-pill" id="modalCredentialCount">0 credential</span>
                        <span class="pr-file-pill pr-file-pill-alt" id="modalResearchCount">0 research</span>
                    </div>
                    <div class="pr-review-doc-list" id="modalDocuments">
                        <div class="pr-doc-empty">No files uploaded.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="pr-modal-actions">
            <form method="POST" id="modalApproveForm" class="pr-approve-form">
                @csrf
                @method('PATCH')
                <div class="pr-approve-field">
                    <label for="modalResearcherEndDate">Researcher End Date</label>
                    <input type="date" name="researcher_end_date" id="modalResearcherEndDate" required>
                </div>
                <button type="submit" class="pr-btn pr-btn-approve">
                    Approve Researcher
                </button>
            </form>
            <form method="POST" id="modalRejectForm" class="pr-reject-form" onsubmit="return confirm('Reject this researcher application? The account will remain active as a Student.')">
                @csrf
                @method('DELETE')
                <div class="pr-reject-field">
                    <label for="modalRejectionReason">What should the applicant fix?</label>
                    <textarea name="rejection_reason" id="modalRejectionReason" rows="3" minlength="10" maxlength="2000" required placeholder="Example: Please upload a clearer school ID and attach your approved research proposal."></textarea>
                </div>
                <button type="submit" class="pr-btn pr-btn-reject">
                    Reject Researcher
                </button>
            </form>
        </div>
    </div>
</div>

<div class="pr-file-viewer" id="fileViewerModal" aria-hidden="true">
    <div class="pr-file-viewer-backdrop" data-close-file-viewer></div>
    <div class="pr-file-viewer-dialog" role="dialog" aria-modal="true" aria-labelledby="fileViewerTitle">
        <div class="pr-file-viewer-header">
            <div>
                <h3 class="pr-modal-title" id="fileViewerTitle">Document Preview</h3>
                <p class="pr-modal-sub" id="fileViewerLabel">Preview the uploaded file without leaving this page.</p>
            </div>
            <button type="button" class="pr-modal-close" aria-label="Close" data-close-file-viewer>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <div class="pr-file-viewer-body" id="fileViewerBody"></div>
    </div>
</div>

<style>
.pr-shell{
    display:grid;
    gap:18px;
}

.pr-hero{
    display:flex;
    align-items:flex-end;
    justify-content:space-between;
    gap:18px;
    padding:28px 30px;
    border-radius:28px;
    background:
        radial-gradient(circle at top right, rgba(124,58,237,.16), transparent 28%),
        radial-gradient(circle at left bottom, rgba(14,165,233,.1), transparent 24%),
        linear-gradient(135deg, #ffffff 0%, #f8f4ff 58%, #f1ecff 100%);
    border:1px solid rgba(122,90,189,.14);
    box-shadow:0 18px 44px rgba(59,15,122,.08);
}

.pr-kicker{
    display:inline-flex;
    align-items:center;
    gap:8px;
    margin-bottom:12px;
    font-size:11px;
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:.18em;
    color:#8d78bb;
}

.pr-kicker::before{
    content:"";
    width:30px;
    height:1px;
    background:linear-gradient(90deg, #6d28d9, transparent);
}

.pr-hero h2{
    max-width:780px;
    margin:0 0 8px;
    font-size:31px;
    line-height:1.08;
    letter-spacing:-.05em;
    color:#1f123e;
}

.pr-hero p{
    max-width:720px;
    margin:0;
    color:#7e72a6;
    font-size:14px;
    line-height:1.75;
}

.pr-hero-badges{
    display:flex;
    flex-wrap:wrap;
    gap:10px;
}

.pr-hero-pill{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    padding:10px 16px;
    border-radius:999px;
    background:#fff3c4;
    border:1px solid #f7dd8b;
    color:#8a5a00;
    font-size:12px;
    font-weight:800;
}

.pr-hero-pill-muted{
    background:#fff;
    border-color:#eadffd;
    color:#7d69a8;
}

.pr-alert{
    padding:14px 16px;
    border-radius:16px;
    font-size:13px;
    font-weight:700;
    line-height:1.5;
}

.pr-alert p{
    margin:0 0 4px;
}

.pr-alert p:last-child{
    margin-bottom:0;
}

.pr-alert-error{
    background:#fef2f2;
    border:1px solid #fecaca;
    color:#991b1b;
}

.pr-alert-success{
    background:#ecfdf3;
    border:1px solid #bbf7d0;
    color:#166534;
}

.pr-stats{
    display:grid;
    grid-template-columns:repeat(3, minmax(0, 1fr));
    gap:14px;
}

.pr-stat-card{
    display:flex;
    align-items:center;
    justify-content:center;
    min-height:114px;
    padding:18px;
    border-radius:22px;
    background:#fff;
    border:1px solid rgba(124,58,237,.1);
    box-shadow:0 12px 28px rgba(57,26,101,.05);
}

.pr-stat-card-primary{
    background:linear-gradient(135deg, #1f1146 0%, #34166c 100%);
}

.pr-stat-copy{
    display:grid;
    grid-template-columns:34px minmax(0, 1fr);
    align-items:center;
    column-gap:8px;
    width:100%;
}

.pr-stat-card strong{
    display:block;
    margin:0;
    font-size:28px;
    line-height:1;
    letter-spacing:-.05em;
    color:#1f123e;
    font-variant-numeric:tabular-nums;
}

.pr-stat-card span{
    display:block;
    color:#8678a9;
    font-size:12.5px;
    line-height:1.55;
    max-width:18ch;
}

.pr-stat-card-primary strong,
.pr-stat-card-primary span{
    color:#fff;
}

.pr-search-card,
.pr-card{
    background:#fff;
    border-radius:24px;
    border:1px solid rgba(117,83,182,.12);
    box-shadow:0 14px 34px rgba(57,26,101,.06);
}

.pr-search-card{
    display:flex;
    align-items:flex-end;
    justify-content:space-between;
    gap:16px;
    padding:20px 22px;
}

.pr-search-kicker{
    display:block;
    margin-bottom:4px;
    font-size:11px;
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:.14em;
    color:#907db8;
}

.pr-search-copy p{
    margin:0;
    color:#8678a9;
    font-size:13px;
}

.pr-search-bar{
    display:flex;
    align-items:center;
    gap:10px;
    width:min(760px, 100%);
}

.pr-search-wrap{
    position:relative;
    flex:1;
}

.pr-search-icon{
    position:absolute;
    left:13px;
    top:50%;
    transform:translateY(-50%);
    color:#c0aee0;
    display:flex;
    align-items:center;
    pointer-events:none;
}

.pr-search-bar input{
    width:100%;
    height:50px;
    padding:0 14px 0 40px;
    border:1.5px solid #e8dff5;
    border-radius:999px;
    background:#fff;
    font-size:14px;
    color:#1a0638;
    transition:border-color .15s, box-shadow .15s;
    box-sizing:border-box;
    font-family:inherit;
}

.pr-search-bar input:focus{
    outline:none;
    border-color:#7c3aed;
    box-shadow:0 0 0 3px rgba(124,58,237,.1);
}

.pr-card{
    overflow:hidden;
}

.pr-card-header{
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:22px 28px;
    background:linear-gradient(160deg, #fdfbff 0%, #f8f4fe 100%);
    border-bottom:1px solid #f0eaf9;
    flex-wrap:wrap;
    gap:12px;
}

.pr-card-header-left{
    display:flex;
    align-items:center;
    gap:14px;
}

.pr-card-icon{
    width:44px;
    height:44px;
    border-radius:14px;
    background:#f0eaf9;
    border:1px solid #e2d5f4;
    display:flex;
    align-items:center;
    justify-content:center;
    color:#6b2fa0;
    flex-shrink:0;
}

.pr-card-title{
    font-size:17px;
    font-weight:800;
    color:#1a0638;
    margin:0 0 2px;
    letter-spacing:-.3px;
}

.pr-card-sub{
    font-size:12.5px;
    color:#a090bc;
    margin:0;
}

.pr-count-badge{
    padding:7px 14px;
    background:#fef3c7;
    border:1px solid #fde68a;
    border-radius:999px;
    font-size:12px;
    font-weight:800;
    color:#92400e;
}

.pr-table-wrap{
    overflow-x:auto;
}

.pr-table{
    width:100%;
    border-collapse:separate;
    border-spacing:0;
}

.pr-table th{
    padding:16px 20px;
    font-size:11px;
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:.15em;
    color:#9c89c0;
    text-align:left;
    background:#fbf8ff;
    border-bottom:1px solid #f0eaf9;
}

.pr-table td{
    padding:18px 20px;
    vertical-align:middle;
    border-bottom:1px solid #f6efff;
    background:#fff;
}

.pr-table tbody tr:hover td{
    background:#fcfaff;
}

.pr-table tbody tr:last-child td{
    border-bottom:none;
}

.pr-profile{
    display:flex;
    align-items:flex-start;
    gap:12px;
    min-width:250px;
}

.pr-avatar{
    width:40px;
    height:40px;
    border-radius:50%;
    background:#f0eaf9;
    border:1.5px solid #e2d5f4;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:14px;
    font-weight:800;
    color:#6b2fa0;
    flex-shrink:0;
}

.pr-name{
    font-size:14px;
    font-weight:800;
    color:#1a0638;
    margin-bottom:4px;
}

.pr-email{
    font-size:13px;
    color:#5b3d8a;
    word-break:break-word;
}

.pr-dept{
    font-size:13px;
    color:#6b5e8a;
    line-height:1.6;
}

.pr-date{
    font-size:13px;
    font-weight:700;
    color:#4b386f;
    white-space:nowrap;
}

.pr-date-sub{
    margin-top:4px;
    font-size:12px;
    color:#a090bc;
}

.pr-id-badge{
    display:inline-block;
    padding:5px 12px;
    background:#f3e8ff;
    border:1px solid #e9d5ff;
    border-radius:20px;
    font-size:12.5px;
    font-weight:700;
    color:#7c3aed;
}

.pr-type-badge{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-width:112px;
    min-height:48px;
    padding:6px 12px;
    border-radius:999px;
    font-size:11.5px;
    font-weight:800;
    line-height:1.45;
    text-align:center;
}

.pr-type-student{
    background:#dbeafe;
    color:#1d4ed8;
    border:1px solid #bfdbfe;
}

.pr-type-faculty{
    background:#f3e8ff;
    color:#6b21a8;
    border:1px solid #e9d5ff;
}

.pr-file-summary{
    display:flex;
    flex-wrap:wrap;
    gap:6px;
    min-width:180px;
}

.pr-file-pill{
    display:inline-flex;
    align-items:center;
    padding:5px 10px;
    border-radius:999px;
    background:#eef2ff;
    border:1px solid #c7d2fe;
    color:#4338ca;
    font-size:12px;
    font-weight:700;
}

.pr-file-pill-alt{
    background:#f3e8ff;
    border-color:#e9d5ff;
    color:#7c3aed;
}

.pr-doc-empty{
    font-size:12px;
    color:#a090bc;
}

.pr-actions-cell{
    display:flex;
    gap:8px;
}

.pr-btn{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:6px;
    height:44px;
    padding:0 16px;
    border-radius:999px;
    font-size:12.5px;
    font-weight:800;
    cursor:pointer;
    border:none;
    text-decoration:none;
    transition:all .14s ease;
    font-family:inherit;
}

.pr-btn-primary{
    background:#3b0f7a;
    color:#fff;
    box-shadow:0 3px 12px rgba(59,15,122,.25);
}

.pr-btn-primary:hover{
    background:#2d0a5e;
    transform:translateY(-1px);
}

.pr-btn-ghost{
    background:#fff;
    color:#8b7aaa;
    border:1.5px solid #e8dff5;
}

.pr-btn-ghost:hover{
    background:#f4f0fc;
    color:#3b0f7a;
}

.pr-btn-view{
    background:#f5f3ff;
    color:#5b21b6;
    border:1px solid #ddd6fe;
}

.pr-btn-view:hover{
    background:#ede9fe;
}

.pr-btn-approve{
    background:#f0fdf4;
    color:#15803d;
    border:1px solid #bbf7d0;
}

.pr-btn-approve:hover{
    background:#dcfce7;
}

.pr-btn-reject{
    background:#fef2f2;
    color:#b91c1c;
    border:1px solid #fecaca;
}

.pr-btn-reject:hover{
    background:#fee2e2;
}

.pr-empty{
    text-align:center;
    padding:56px 20px !important;
    color:#c0aee0;
}

.pr-empty-icon{
    width:58px;
    height:58px;
    border-radius:18px;
    background:#f4f0fc;
    border:1px solid #e8dff5;
    display:flex;
    align-items:center;
    justify-content:center;
    color:#c0aee0;
    margin:0 auto 12px;
}

.pr-empty p{
    margin:0;
    font-size:14px;
}

.pr-pagination{
    padding:16px 24px;
    border-top:1px solid #f0eaf9;
}

.pr-modal{
    position:fixed;
    inset:0;
    z-index:9999;
    display:none;
    align-items:center;
    justify-content:center;
    padding:24px;
}

.pr-modal.is-open{
    display:flex;
}

.pr-modal-backdrop{
    position:absolute;
    inset:0;
    background:rgba(26, 6, 56, .5);
    backdrop-filter:blur(4px);
}

.pr-modal-dialog{
    position:relative;
    width:min(920px, 100%);
    max-height:calc(100vh - 48px);
    background:#fff;
    border-radius:24px;
    border:1px solid rgba(107,47,160,.12);
    box-shadow:0 24px 80px rgba(59,15,122,.22);
    overflow:hidden;
    display:flex;
    flex-direction:column;
}

.pr-modal-header,
.pr-modal-actions{
    padding:20px 24px;
    border-bottom:1px solid #f0eaf9;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:16px;
}

.pr-modal-actions{
    border-bottom:0;
    border-top:1px solid #f0eaf9;
    justify-content:flex-end;
    flex-wrap:wrap;
}

.pr-approve-form{
    display:flex;
    align-items:flex-end;
    gap:12px;
    flex:1 1 460px;
    flex-wrap:wrap;
}

.pr-approve-field{
    display:grid;
    gap:6px;
    min-width:240px;
    flex:1 1 260px;
}

.pr-approve-field label{
    font-size:11px;
    font-weight:800;
    letter-spacing:.07em;
    text-transform:uppercase;
    color:#8f80aa;
}

.pr-approve-field input{
    height:42px;
    padding:0 12px;
    border:1.5px solid #e8dff5;
    border-radius:12px;
    background:#fff;
    font-size:14px;
    color:#1a0638;
    font-family:inherit;
}

.pr-approve-field input:focus{
    outline:none;
    border-color:#7c3aed;
    box-shadow:0 0 0 3px rgba(124,58,237,.12);
}

.pr-approve-form .pr-btn{
    flex:0 0 auto;
}

.pr-reject-form{
    display:flex;
    align-items:flex-end;
    gap:12px;
    flex:1 1 460px;
    flex-wrap:wrap;
}

.pr-reject-field{
    display:grid;
    gap:6px;
    min-width:280px;
    flex:1 1 320px;
}

.pr-reject-field label{
    font-size:11px;
    font-weight:800;
    letter-spacing:.07em;
    text-transform:uppercase;
    color:#8f80aa;
}

.pr-reject-field textarea{
    min-height:74px;
    resize:vertical;
    padding:10px 12px;
    border:1.5px solid #e8dff5;
    border-radius:12px;
    background:#fff;
    font-size:14px;
    line-height:1.5;
    color:#1a0638;
    font-family:inherit;
}

.pr-reject-field textarea:focus{
    outline:none;
    border-color:#ef4444;
    box-shadow:0 0 0 3px rgba(239,68,68,.12);
}

.pr-modal-title{
    margin:0 0 4px;
    font-size:20px;
    font-weight:800;
    color:#1a0638;
}

.pr-modal-sub{
    margin:0;
    font-size:13px;
    color:#8b7aaa;
}

.pr-modal-close{
    width:38px;
    height:38px;
    border-radius:50%;
    border:1px solid #eadcfb;
    background:#faf8ff;
    color:#6b2fa0;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    cursor:pointer;
    flex-shrink:0;
}

.pr-modal-close:hover{
    background:#f3edff;
}

.pr-modal-body{
    padding:24px;
    overflow-y:auto;
    background:linear-gradient(180deg, #fff 0%, #fcfaff 100%);
}

.pr-review-hero{
    display:flex;
    align-items:center;
    gap:16px;
    padding:18px;
    border-radius:18px;
    background:linear-gradient(135deg, #faf5ff, #eef2ff);
    border:1px solid #ece4fb;
    margin-bottom:18px;
}

.pr-review-avatar{
    width:58px;
    height:58px;
    border-radius:18px;
    background:#fff;
    border:1px solid #e9d5ff;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:24px;
    font-weight:800;
    color:#6b2fa0;
    flex-shrink:0;
}

.pr-review-name{
    font-size:20px;
    font-weight:800;
    color:#1a0638;
    margin-bottom:8px;
}

.pr-review-meta{
    display:flex;
    flex-wrap:wrap;
    gap:8px;
    align-items:center;
}

.pr-review-meta-item{
    display:inline-flex;
    align-items:center;
    padding:4px 10px;
    border-radius:999px;
    background:#fff;
    border:1px solid #e8dff5;
    color:#6b5e8a;
    font-size:12px;
    font-weight:700;
}

.pr-review-grid{
    display:grid;
    grid-template-columns:minmax(0, 1fr) minmax(0, 1.2fr);
    gap:18px;
}

.pr-review-card{
    background:#fff;
    border:1px solid #f0eaf9;
    border-radius:18px;
    padding:18px;
}

.pr-review-card-title{
    font-size:13px;
    font-weight:800;
    letter-spacing:.04em;
    text-transform:uppercase;
    color:#8b7aaa;
    margin-bottom:14px;
}

.pr-review-list{
    display:flex;
    flex-direction:column;
    gap:12px;
}

.pr-review-item{
    display:flex;
    flex-direction:column;
    gap:4px;
    padding-bottom:12px;
    border-bottom:1px solid #f6f1fd;
}

.pr-review-item:last-child{
    padding-bottom:0;
    border-bottom:0;
}

.pr-review-label{
    font-size:11px;
    font-weight:800;
    letter-spacing:.08em;
    text-transform:uppercase;
    color:#a090bc;
}

.pr-review-value{
    font-size:14px;
    font-weight:700;
    color:#1a0638;
    word-break:break-word;
}

.pr-review-counts{
    display:flex;
    flex-wrap:wrap;
    gap:8px;
    margin-bottom:14px;
}

.pr-review-doc-list{
    display:flex;
    flex-direction:column;
    gap:10px;
}

.pr-review-doc{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    padding:12px 14px;
    border-radius:14px;
    background:#faf8ff;
    border:1px solid #eee7fb;
}

.pr-review-doc-meta{
    display:flex;
    flex-direction:column;
    gap:4px;
    min-width:0;
}

.pr-review-doc-group{
    font-size:11px;
    font-weight:800;
    letter-spacing:.08em;
    text-transform:uppercase;
    color:#8b7aaa;
}

.pr-review-doc-name{
    font-size:13px;
    font-weight:700;
    color:#1a0638;
    word-break:break-word;
}

.pr-review-doc-link{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    padding:8px 12px;
    border-radius:999px;
    background:#ede9fe;
    border:1px solid #ddd6fe;
    color:#5b21b6;
    font-size:12px;
    font-weight:700;
    text-decoration:none;
    font-family:inherit;
    cursor:pointer;
    white-space:nowrap;
}

.pr-review-doc-link:hover{
    background:#ddd6fe;
}

.pr-file-viewer{
    position:fixed;
    inset:0;
    z-index:10020;
    display:none;
    align-items:center;
    justify-content:center;
    padding:24px;
}

.pr-file-viewer.is-open{
    display:flex;
}

.pr-file-viewer-backdrop{
    position:absolute;
    inset:0;
    background:rgba(26, 6, 56, .58);
    backdrop-filter:blur(4px);
}

.pr-file-viewer-dialog{
    position:relative;
    width:min(980px, 100%);
    height:min(86vh, 900px);
    background:#fff;
    border-radius:24px;
    border:1px solid rgba(107,47,160,.12);
    box-shadow:0 24px 80px rgba(59,15,122,.24);
    overflow:hidden;
    display:flex;
    flex-direction:column;
}

.pr-file-viewer-header{
    padding:20px 24px;
    border-bottom:1px solid #f0eaf9;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:16px;
}

.pr-file-viewer-body{
    flex:1;
    min-height:0;
    padding:18px;
    background:#fbf9fe;
}

.pr-file-frame,
.pr-file-image{
    width:100%;
    height:100%;
    border:none;
    border-radius:18px;
    background:#fff;
    box-shadow:inset 0 0 0 1px #eee6fa;
}

.pr-file-image{
    object-fit:contain;
    padding:16px;
}

.pr-file-fallback{
    height:100%;
    display:flex;
    align-items:center;
    justify-content:center;
    text-align:center;
    border:1px dashed #d9c6f7;
    border-radius:18px;
    padding:24px;
    background:#fff;
}

.pr-file-fallback a{
    color:#6d28d9;
    font-weight:700;
    text-decoration:none;
}

@media (max-width: 1080px){
    .pr-hero,
    .pr-search-card{
        flex-direction:column;
        align-items:flex-start;
    }

    .pr-stats{
        grid-template-columns:1fr;
    }

    .pr-search-bar{
        width:100%;
    }
}

@media (max-width: 768px){
    .pr-search-bar{
        flex-direction:column;
        align-items:stretch;
    }

    .pr-search-bar .pr-btn{
        width:100%;
        justify-content:center;
    }

    .pr-card-header{
        padding:18px;
    }

    .pr-card-header-left{
        align-items:flex-start;
    }

    .pr-table thead{
        display:none;
    }

    .pr-table,
    .pr-table tbody,
    .pr-table tr,
    .pr-table td{
        display:block;
        width:100%;
    }

    .pr-table-wrap{
        overflow:visible;
        padding:12px;
    }

    .pr-table tbody{
        display:grid;
        gap:12px;
    }

    .pr-table tbody tr{
        background:#fff;
        border:1px solid #efe7fb;
        border-radius:16px;
        box-shadow:0 8px 24px rgba(59,15,122,.05);
        padding:8px 0;
    }

    .pr-table td{
        padding:10px 14px;
        border:none;
    }

    .pr-table td::before{
        content:attr(data-label);
        display:block;
        font-size:10px;
        font-weight:800;
        text-transform:uppercase;
        letter-spacing:.08em;
        color:#a090bc;
        margin-bottom:6px;
    }

    .pr-table td:first-child::before{
        display:none;
    }

    .pr-actions-cell{
        flex-direction:column;
        gap:8px;
    }

    .pr-actions-cell .pr-btn{
        width:100%;
        justify-content:center;
    }

    .pr-modal{
        padding:12px;
    }

    .pr-modal-dialog{
        max-height:calc(100vh - 24px);
        border-radius:20px;
    }

    .pr-modal-header,
    .pr-modal-body,
    .pr-modal-actions{
        padding:16px;
    }

    .pr-modal-actions{
        justify-content:stretch;
    }

    .pr-modal-actions form,
    .pr-modal-actions .pr-btn{
        width:100%;
    }

    .pr-approve-form{
        display:grid;
        gap:12px;
    }

    .pr-modal-actions .pr-btn{
        justify-content:center;
    }

    .pr-review-hero{
        align-items:flex-start;
    }

    .pr-review-grid{
        grid-template-columns:1fr;
    }

    .pr-review-doc{
        flex-direction:column;
        align-items:stretch;
    }

    .pr-review-doc-link{
        width:100%;
    }

    .pr-file-viewer{
        padding:12px;
    }

    .pr-file-viewer-dialog{
        width:100%;
        height:calc(100vh - 24px);
        border-radius:20px;
    }

    .pr-file-viewer-header,
    .pr-file-viewer-body{
        padding:16px;
    }
}
</style>

@push('scripts')
<script>
    (function () {
        const modal = document.getElementById('reviewModal');
        if (!modal) return;

        const modalAvatar = document.getElementById('modalAvatar');
        const modalName = document.getElementById('modalName');
        const modalType = document.getElementById('modalType');
        const modalDepartment = document.getElementById('modalDepartment');
        const modalEmail = document.getElementById('modalEmail');
        const modalId = document.getElementById('modalId');
        const modalYearLevel = document.getElementById('modalYearLevel');
        const modalGraduationYear = document.getElementById('modalGraduationYear');
        const modalRegistered = document.getElementById('modalRegistered');
        const modalCredentialCount = document.getElementById('modalCredentialCount');
        const modalResearchCount = document.getElementById('modalResearchCount');
        const modalDocuments = document.getElementById('modalDocuments');
        const modalApproveForm = document.getElementById('modalApproveForm');
        const modalResearcherEndDate = document.getElementById('modalResearcherEndDate');
        const modalRejectForm = document.getElementById('modalRejectForm');
        const fileViewerModal = document.getElementById('fileViewerModal');
        const fileViewerBody = document.getElementById('fileViewerBody');
        const fileViewerLabel = document.getElementById('fileViewerLabel');

        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        const minEndDate = tomorrow.toISOString().split('T')[0];
        modalResearcherEndDate.min = minEndDate;

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function closeModal() {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }

        function closeFileViewer() {
            fileViewerModal.classList.remove('is-open');
            fileViewerModal.setAttribute('aria-hidden', 'true');
            fileViewerBody.innerHTML = '';
            fileViewerLabel.textContent = 'Preview the uploaded file without leaving this page.';
            document.body.style.overflow = modal.classList.contains('is-open') ? 'hidden' : '';
        }

        function openFileViewer(path, label) {
            const safePath = path || '#';
            const safeLabel = label || 'Uploaded document';
            const extension = safePath.split('.').pop().toLowerCase().split('?')[0];

            fileViewerLabel.textContent = safeLabel;

            if (['png', 'jpg', 'jpeg', 'gif', 'webp'].includes(extension)) {
                fileViewerBody.innerHTML = `<img src="${safePath}" alt="${escapeHtml(safeLabel)}" class="pr-file-image">`;
            } else if (extension === 'pdf') {
                fileViewerBody.innerHTML = `<iframe src="${safePath}" class="pr-file-frame" title="${escapeHtml(safeLabel)}"></iframe>`;
            } else {
                fileViewerBody.innerHTML = `
                    <div class="pr-file-fallback">
                        <div>
                            <p>This file type cannot be previewed inline here.</p>
                            <a href="${safePath}" target="_blank" rel="noopener">Open file</a>
                        </div>
                    </div>
                `;
            }

            fileViewerModal.classList.add('is-open');
            fileViewerModal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }

        function buildDocumentCard(document) {
            const label = escapeHtml(document.label || 'Document');
            const group = escapeHtml(document.group || 'File');
            const path = escapeHtml(document.path || '#');

            return `
                <div class="pr-review-doc">
                    <div class="pr-review-doc-meta">
                        <span class="pr-review-doc-group">${group}</span>
                        <span class="pr-review-doc-name">${label}</span>
                    </div>
                    <button type="button" class="pr-review-doc-link" data-view-file="${path}" data-file-label="${label}">View File</button>
                </div>
            `;
        }

        function openModal(payload) {
            modalAvatar.textContent = (payload.name || 'R').charAt(0).toUpperCase();
            modalName.textContent = payload.name || 'Researcher';
            modalType.textContent = payload.type || 'Researcher';
            modalType.className = 'pr-type-badge ' + ((payload.type || '').toLowerCase().includes('student') ? 'pr-type-student' : 'pr-type-faculty');
            modalDepartment.textContent = payload.department || 'N/A';
            modalEmail.textContent = payload.email || 'N/A';
            modalId.textContent = payload.student_id || 'N/A';
            modalYearLevel.textContent = payload.year_level || 'N/A';
            modalGraduationYear.textContent = payload.graduation_year || 'N/A';
            modalRegistered.textContent = [payload.registered_at, payload.registered_time].filter(Boolean).join(' at ');
            modalCredentialCount.textContent = `${payload.verification_count || 0} credential`;
            modalResearchCount.textContent = `${payload.research_count || 0} research`;
            modalApproveForm.action = payload.approve_url || '#';
            modalRejectForm.action = payload.reject_url || '#';
            document.getElementById('modalRejectionReason').value = '';
            modalResearcherEndDate.value = payload.suggested_end_date || '';
            modalResearcherEndDate.min = minEndDate;

            if (Array.isArray(payload.documents) && payload.documents.length) {
                modalDocuments.innerHTML = payload.documents.map(buildDocumentCard).join('');
            } else {
                modalDocuments.innerHTML = '<div class="pr-doc-empty">No files uploaded.</div>';
            }

            modalDocuments.querySelectorAll('[data-view-file]').forEach(function (button) {
                button.addEventListener('click', function () {
                    openFileViewer(this.getAttribute('data-view-file'), this.getAttribute('data-file-label'));
                });
            });

            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }

        document.querySelectorAll('[data-review]').forEach(function (button) {
            button.addEventListener('click', function () {
                openModal(JSON.parse(this.getAttribute('data-review')));
            });
        });

        modal.querySelectorAll('[data-close-modal]').forEach(function (element) {
            element.addEventListener('click', closeModal);
        });

        fileViewerModal.querySelectorAll('[data-close-file-viewer]').forEach(function (element) {
            element.addEventListener('click', closeFileViewer);
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && fileViewerModal.classList.contains('is-open')) {
                closeFileViewer();
                return;
            }
            if (event.key === 'Escape' && modal.classList.contains('is-open')) {
                closeModal();
            }
        });
    })();
</script>
@endpush

@endsection
