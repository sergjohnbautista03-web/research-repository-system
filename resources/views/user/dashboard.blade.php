@extends('layouts.app')

@section('title', 'My Dashboard')

@section('content')
@php
    $submitFields = ['title', 'author_name', 'year_published', 'submission_category', 'department', 'course', 'type', 'keywords', 'abstract', 'file'];
    $hasSubmitErrors = $errors->hasAny($submitFields);
    $submitDepartment = old('department', $user->department);
    $isFacultyAccount = $user->role === 'researcher' && is_null($user->graduation_year);
    $isStudentResearcher = $user->role === 'researcher' && ! is_null($user->graduation_year);
    $canSubmitResearch = $user->canSubmitResearch();
    $dashboardRoleLabel = $isFacultyAccount ? 'Faculty' : ($isStudentResearcher ? 'Student Researcher' : ucfirst($user->role));
    $submissionCategories = \App\Models\Research::submissionCategories();
    $journalTypes = \App\Models\Research::typesForCategory(\App\Models\Research::SUBMISSION_CATEGORY_JOURNAL);
@endphp
<div class="ud-shell">
    <div class="ud-layout">
        <aside class="ud-sidebar">
            <div class="ud-sidebar-card">
                <span class="ud-sidebar-kicker">User Panel</span>
                <h2>{{ $user->name }}</h2>
                <p>{{ $user->department ?? 'PHILCST User' }}</p>
                <div class="ud-sidebar-pills">
                    <span class="ud-pill">{{ $dashboardRoleLabel }}</span>
                    <span class="ud-pill is-approved">Active</span>
                </div>
            </div>

            <nav class="ud-side-nav">
                <a href="#dashboard-top" class="ud-side-link is-active" data-dashboard-link>Dashboard Overview</a>
                <button type="button" class="ud-side-link" data-open-pinned>Saved Papers</button>
                <a href="#profile-settings" class="ud-side-link" data-dashboard-link>Profile Settings</a>
                @if($canSubmitResearch)
                    <a href="#submit-research" class="ud-side-link" data-dashboard-link data-open-submit>Submit Research</a>
                    <button type="button" class="ud-side-link" data-open-submissions>My Submissions</button>
                @endif
            </nav>
        </aside>

        <div class="ud-main" id="dashboard-top">
            <section class="ud-hero">
                <div>
                    <span class="ud-kicker">User Dashboard</span>
                    <h1>{{ $user->name }}</h1>
                    <p>
                        @if($canSubmitResearch)
                            Your account is active. You can browse, view full papers, submit research, and manage your submissions here.
                        @else
                            Browse researches and save papers from your dashboard.
                        @endif
                    </p>
                </div>
                <div class="ud-status-stack">
                    <span class="ud-pill">{{ $dashboardRoleLabel }}</span>
                    <span class="ud-pill is-approved">Active Account</span>
                    @if($user->department)
                        <span class="ud-pill">{{ $user->department }}</span>
                    @endif
                </div>
            </section>

            <section class="ud-stats">
                <article class="ud-stat-card">
                    <span class="ud-stat-label">Saved Papers</span>
                    <strong>{{ number_format($stats['pinned']) }}</strong>
                    <small>Saved to your account</small>
                </article>
                <article class="ud-stat-card">
                    <span class="ud-stat-label">My Submissions</span>
                    <strong>{{ number_format($stats['submissions']) }}</strong>
                    <small>Researches linked to you</small>
                </article>
            </section>

            <section class="ud-card" id="profile-settings">
                <div class="ud-card-head">
                    <div>
                        <h2>Profile Settings</h2>
                        <p>Manage your photo, account details, and password without leaving the dashboard.</p>
                    </div>
                </div>

                <div class="ud-profile-grid">
                    <div class="ud-profile-panel">
                        <span class="ud-mini-kicker">Profile Photo</span>
                        <div class="ud-photo-block">
                            @if($user->profile_photo)
                                <img src="{{ asset('storage/' . $user->profile_photo) }}" alt="Profile Photo" class="ud-profile-photo">
                            @else
                                <div class="ud-profile-initial">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                            @endif
                            <div class="ud-photo-meta">
                                <strong>{{ $user->name }}</strong>
                                <span>{{ $user->isDepartmentDean() ? 'Dean' : $dashboardRoleLabel }}</span>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('profile.photo') }}" enctype="multipart/form-data" class="ud-inline-form">
                            @csrf
                            @method('PATCH')
                            <div class="ud-field">
                                <label for="dashboard_profile_photo">Upload New Photo</label>
                                <input type="file" id="dashboard_profile_photo" name="profile_photo" accept="image/jpeg,image/png,image/jpg,image/gif">
                                <small>JPG, PNG, or GIF up to 2MB.</small>
                            </div>
                            <div class="ud-inline-actions">
                                <button type="submit" class="ud-btn-primary">Upload Photo</button>
                                @if($user->profile_photo)
                                    <a href="{{ route('profile.photo.remove') }}" onclick="return confirm('Remove profile photo?')" class="ud-btn-secondary">Remove</a>
                                @endif
                            </div>
                        </form>
                    </div>

                    <div class="ud-profile-panel">
                        <span class="ud-mini-kicker">Account Details</span>
                        <div class="ud-inline-form">
                            <div class="ud-form-row">
                                <div class="ud-field">
                                    <label>Full Name</label>
                                    <input type="text" value="{{ $user->name }}" readonly>
                                </div>
                                <div class="ud-field">
                                    <label>Email Address</label>
                                    <input type="email" value="{{ $user->email }}" readonly>
                                </div>
                            </div>
                            <div class="ud-form-row">
                                <div class="ud-field">
                                    <label>Department</label>
                                    <input type="text" value="{{ $user->department ?: 'No department assigned' }}" readonly>
                                </div>
                                <div class="ud-field">
                                    <label>{{ $user->isDepartmentDean() ? 'Dean ID' : ($user->isAdmin() ? 'Admin ID' : 'Student / Employee ID') }}</label>
                                    <input type="text" value="{{ $user->student_id ?: 'No ID assigned' }}" readonly>
                                </div>
                            </div>
                            <div class="ud-lock-note">
                                This account information is assigned by your dean or administrator and cannot be edited here.
                            </div>
                        </div>
                    </div>

                    <div class="ud-profile-panel ud-profile-panel--full">
                        <span class="ud-mini-kicker">Security</span>
                        <div class="ud-inline-form">
                            <div class="ud-security-lock">
                                <strong>Password updates are locked</strong>
                                <p>Your login credentials are managed by your dean or administrator. Contact them if you need a password reset.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            @if($canSubmitResearch)
            <div class="ud-modal {{ $hasSubmitErrors ? 'is-visible' : '' }}" id="submitResearchModal">
                <div class="ud-modal-backdrop" data-close-submit></div>
                <div class="ud-modal-dialog ud-modal-dialog--wide" role="dialog" aria-modal="true" aria-labelledby="submitResearchTitle">
                    <div class="ud-modal-head">
                        <div>
                            <span class="ud-mini-kicker">Research Submission</span>
                            <h2 id="submitResearchTitle">Submit Research</h2>
                            <p>Share your academic work without leaving the dashboard.</p>
                        </div>
                        <button type="button" class="ud-modal-close" data-close-submit aria-label="Close submit research modal">×</button>
                    </div>

                    @if($hasSubmitErrors)
                        <div class="ud-modal-alert">
                            @foreach($errors->only($submitFields) as $fieldErrors)
                                @foreach($fieldErrors as $error)
                                    <p>{{ $error }}</p>
                                @endforeach
                            @endforeach
                        </div>
                    @endif

                    <form method="POST" action="{{ route('research.store') }}" enctype="multipart/form-data" class="ud-submit-form">
                        @csrf

                        <div class="ud-submit-section">
                            <span class="ud-submit-step">1</span>
                            <h3>Basic Information</h3>
                        </div>
                        <div class="ud-form-row">
                            <div class="ud-field ud-field-span-2">
                                <label for="submit_title">Research Title</label>
                                <input type="text" id="submit_title" name="title" value="{{ old('title') }}" placeholder="Enter the complete title of your research" required>
                            </div>
                        </div>
                        <div class="ud-form-row">
                            <div class="ud-field">
                                <label for="submit_author_name">Author Name</label>
                                <input type="text" id="submit_author_name" name="author_name" value="{{ old('author_name', $user->name) }}" required>
                            </div>
                            <div class="ud-field">
                                <label for="submit_year_published">Year Published</label>
                                <input type="number" id="submit_year_published" name="year_published" value="{{ old('year_published', 2026) }}" min="2022" max="2026" required>
                            </div>
                        </div>

                        <div class="ud-submit-section">
                            <span class="ud-submit-step">2</span>
                            <h3>Department and Program</h3>
                        </div>
                        <div class="ud-form-row">
                            <div class="ud-field">
                                <label for="submit_submission_category">Submission Category</label>
                                <select id="submit_submission_category" name="submission_category" required>
                                    @foreach($submissionCategories as $value => $label)
                                        <option value="{{ $value }}" {{ old('submission_category', \App\Models\Research::SUBMISSION_CATEGORY_JOURNAL) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="ud-form-row">
                            <div class="ud-field">
                                <label>Department</label>
                                <input type="text" value="{{ $user->department ?: 'No department assigned' }}" readonly>
                                <input type="hidden" id="submit_department" name="department" value="{{ $submitDepartment }}">
                            </div>
                            <div class="ud-field">
                                <label for="submit_course">Program</label>
                                <select id="submit_course" name="course" required {{ $submitDepartment ? '' : 'disabled' }}>
                                    <option value="">{{ $submitDepartment ? '-- Select Program --' : '-- No Department Assigned --' }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="ud-form-row">
                            <div class="ud-field">
                                <label for="submit_type" id="submit_type_label">Research Type</label>
                                <select id="submit_type" name="type" required {{ $submitDepartment ? '' : 'disabled' }}>
                                    <option value="">{{ $submitDepartment ? '-- Select Type --' : '-- No Department Assigned --' }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="ud-submit-section">
                            <span class="ud-submit-step">3</span>
                            <h3>Content and Keywords</h3>
                        </div>
                        <div class="ud-field">
                            <label for="submit_keywords">Keywords</label>
                            <input type="text" id="submit_keywords" name="keywords" value="{{ old('keywords') }}" placeholder="Separate keywords with commas">
                        </div>
                        <div class="ud-field">
                            <label for="submit_abstract">Abstract or Description</label>
                            <textarea id="submit_abstract" name="abstract" rows="7" placeholder="Provide a comprehensive summary of your research" required>{{ old('abstract') }}</textarea>
                        </div>

                        <div class="ud-submit-section">
                            <span class="ud-submit-step">4</span>
                            <h3>File Upload</h3>
                        </div>
                        <div class="ud-field">
                            <label for="submit_file">Research PDF</label>
                            <input type="file" id="submit_file" name="file" accept=".pdf" required>
                            <small>PDF only, up to 30MB.</small>
                        </div>

                        <div class="ud-submit-note">
                            Your submission will be reviewed by an administrator before it becomes publicly visible.
                        </div>

                        <div class="ud-inline-actions">
                            <button type="button" class="ud-btn-secondary" data-close-submit>Cancel</button>
                            <button type="submit" class="ud-btn-primary">Submit for Review</button>
                        </div>
                    </form>
                </div>
            </div>
            @endif

            <div class="ud-modal" id="pinnedPapersModal" aria-hidden="true">
                <div class="ud-modal-backdrop" data-close-pinned></div>
                <div class="ud-modal-dialog ud-modal-dialog--wide" role="dialog" aria-modal="true" aria-labelledby="pinnedPapersTitle">
                    <div class="ud-modal-head">
                        <div>
                            <span class="ud-mini-kicker">Saved Papers</span>
                            <h2 id="pinnedPapersTitle">Saved Papers</h2>
                            <p>{{ $pinnedResearches->count() }} saved paper{{ $pinnedResearches->count() === 1 ? '' : 's' }} in your account.</p>
                        </div>
                        <button type="button" class="ud-modal-close" data-close-pinned aria-label="Close pinned papers modal">×</button>
                    </div>

                    <div class="ud-pinned-modal-body">
                        @forelse($pinnedResearches as $research)
                            <article class="ud-pinned-card">
                                <span class="ud-pinned-type">{{ $research->getSubmissionCategoryLabel() }}: {{ $research->getTypeLabel() }}</span>
                                <h3>
                                    <a href="{{ route('research.show', $research) }}">{{ $research->title }}</a>
                                </h3>
                                <p>{{ Str::limit($research->abstract, 150) }}</p>
                                <div class="ud-pinned-meta">
                                    <span>{{ $research->author_name }}</span>
                                    <span>{{ $research->year_published }}</span>
                                </div>
                                <div class="ud-pinned-footer">
                                    <span>{{ number_format($research->view_count) }} views</span>
                                    <a href="{{ route('research.show', $research) }}">View Details</a>
                                </div>
                            </article>
                        @empty
                            <div class="ud-pinned-empty">
                                <strong>No saved papers yet</strong>
                                <p>Save research papers from the details page to keep them here for quick access.</p>
                                <a href="{{ route('home') }}" class="ud-btn-secondary">Browse Research</a>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            @if($canSubmitResearch)
            <div class="ud-modal" id="mySubmissionsModal" aria-hidden="true">
                <div class="ud-modal-backdrop" data-close-submissions></div>
                <div class="ud-modal-dialog ud-modal-dialog--wide" role="dialog" aria-modal="true" aria-labelledby="mySubmissionsTitle">
                    <div class="ud-modal-head">
                        <div>
                            <span class="ud-mini-kicker">Research Portfolio</span>
                            <h2 id="mySubmissionsTitle">My Submissions</h2>
                            <p>{{ $mySubmissions->count() }} research submission{{ $mySubmissions->count() === 1 ? '' : 's' }} linked to your account.</p>
                        </div>
                        <button type="button" class="ud-modal-close" data-close-submissions aria-label="Close my submissions modal">×</button>
                    </div>

                    <div class="ud-submissions-summary">
                        <div><strong>{{ $mySubmissions->count() }}</strong><span>Total</span></div>
                        <div><strong>{{ $mySubmissions->where('status', 'pending')->count() }}</strong><span>Pending</span></div>
                        <div><strong>{{ $mySubmissions->where('status', 'approved')->count() }}</strong><span>Approved</span></div>
                        <div><strong>{{ $mySubmissions->where('status', 'rejected')->count() }}</strong><span>Rejected</span></div>
                    </div>

                    <div class="ud-submissions-modal-body">
                        @forelse($mySubmissions as $research)
                            <article class="ud-submission-card status-{{ $research->status }}">
                                <div class="ud-submission-top">
                                    <span class="ud-pinned-type">{{ $research->getSubmissionCategoryLabel() }}: {{ $research->getTypeLabel() }}</span>
                                    <span class="ud-submission-status status-{{ $research->status }}">
                                        @if($research->status === 'approved')
                                            Approved
                                        @elseif($research->status === 'pending')
                                            Under Review
                                        @else
                                            Rejected
                                        @endif
                                    </span>
                                </div>
                                <h3>
                                    <a href="{{ route('research.show', $research) }}">{{ $research->title }}</a>
                                </h3>
                                <div class="ud-pinned-meta">
                                    <span>{{ $research->year_published }}</span>
                                    <span>{{ $research->created_at->format('M d, Y') }}</span>
                                    <span>{{ Str::limit($research->department, 34) }}</span>
                                </div>
                                @if($research->status === 'rejected' && $research->rejection_reason)
                                    <div class="ud-submission-note">
                                        <strong>Rejection reason:</strong>
                                        <span>{{ $research->rejection_reason }}</span>
                                    </div>
                                @endif
                                <div class="ud-pinned-footer">
                                    <span>{{ number_format($research->view_count) }} views</span>
                                    <a href="{{ route('research.show', $research) }}">View Details</a>
                                </div>
                            </article>
                        @empty
                            <div class="ud-pinned-empty">
                                <strong>No submissions yet</strong>
                                <p>Use the Submit Research button to send your first research for review.</p>
                                <button type="button" class="ud-btn-secondary" data-open-submit data-close-submissions>Submit Research</button>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
            @endif

        </div>
    </div>
</div>

<style>
.ud-shell{max-width:1200px;margin:0 auto;padding:34px 24px 44px}
html{scroll-behavior:smooth}
.ud-layout{display:grid;grid-template-columns:280px minmax(0,1fr);gap:20px;align-items:start}
.ud-sidebar{position:sticky;top:88px}
.ud-sidebar-card{padding:22px;border-radius:24px;background:linear-gradient(145deg,#2b0d4e 0%,#522087 100%);color:#fff;box-shadow:0 20px 40px rgba(59,15,122,.16);margin-bottom:16px}
.ud-sidebar-kicker{display:inline-flex;padding:6px 10px;border-radius:999px;background:rgba(255,255,255,.12);font-size:11px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;margin-bottom:12px}
.ud-sidebar-card h2{margin:0 0 6px;font-size:26px;line-height:1.05}
.ud-sidebar-card p{margin:0 0 14px;color:rgba(255,255,255,.8);line-height:1.55}
.ud-sidebar-pills{display:flex;flex-wrap:wrap;gap:8px}
.ud-side-nav{display:grid;gap:10px}
.ud-side-nav > .ud-side-link{
    display:flex;
    align-items:center;
    width:100%;
    text-align:left;
    padding:14px 16px;
    border-radius:18px;
    background:#fff;
    border:1px solid #efe7fb;
    box-shadow:0 10px 24px rgba(59,15,122,.05);
    text-decoration:none;
    font-family:var(--font-body);
    font-size:14px;
    line-height:1.4;
    font-weight:700;
    color:#36125f !important;
    letter-spacing:-.01em;
    cursor:pointer;
}
.ud-side-nav > .ud-side-link:hover,
.ud-side-nav > .ud-side-link.is-active{
    background:#f7f1ff;
    border-color:#e7d8fb;
    color:#36125f !important;
}
.ud-main{min-width:0}
.ud-hero{display:flex;justify-content:space-between;gap:24px;align-items:flex-start;padding:28px 30px;border-radius:28px;background:linear-gradient(135deg,#2a0d4f 0%,#5c2093 60%,#7c3aed 100%);color:#fff;box-shadow:0 24px 48px rgba(59,15,122,.18);margin-bottom:24px}
.ud-kicker{display:inline-flex;padding:6px 10px;border-radius:999px;background:rgba(255,255,255,.12);font-size:11px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;margin-bottom:12px}
.ud-hero h1{margin:0 0 10px;font-size:40px;line-height:1.02;letter-spacing:-.04em}
.ud-hero p{margin:0;max-width:720px;color:rgba(255,255,255,.82);line-height:1.65}
.ud-status-stack{display:flex;flex-wrap:wrap;gap:8px;justify-content:flex-end}
.ud-pill{display:inline-flex;padding:8px 12px;border-radius:999px;background:rgba(255,255,255,.12);font-size:12px;font-weight:700}
.ud-pill.is-approved{background:#dcfce7;color:#166534}
.ud-pill.is-pending{background:#fef3c7;color:#92400e}
.ud-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin-bottom:24px}
.ud-stat-card{padding:20px;border-radius:22px;background:#fff;border:1px solid #efe7fb;box-shadow:0 10px 26px rgba(59,15,122,.06)}
.ud-stat-label{display:block;font-size:11px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#9586b0;margin-bottom:10px}
.ud-stat-card strong{display:block;font-size:34px;line-height:1;color:#240a42}
.ud-stat-card small{display:block;margin-top:10px;color:#85779d;line-height:1.45}
.ud-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;margin-bottom:18px}
.ud-card{background:#fff;border:1px solid #efe7fb;border-radius:24px;box-shadow:0 12px 30px rgba(59,15,122,.05);overflow:hidden}
.ud-card[id]{scroll-margin-top:108px}
.ud-card-head{padding:22px 24px 16px;border-bottom:1px solid #f2ecfb}
.ud-card-head h2{margin:0 0 6px;font-size:22px;color:#23093f}
.ud-card-head p{margin:0;color:#8c7ba8;font-size:13px}
.ud-apply-form{padding:20px 24px 24px;display:flex;flex-direction:column;gap:14px}
.ud-form-row{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
.ud-field{display:flex;flex-direction:column;gap:7px}
.ud-field label{font-size:11px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#8f80aa}
.ud-field input,.ud-field select,.ud-field textarea{width:100%;padding:12px 14px;border-radius:14px;border:1.5px solid #e9dff7;background:#fff;font:inherit;color:#210b3d}
.ud-field input:focus,.ud-field select:focus,.ud-field textarea:focus{outline:none;border-color:#7c3aed;box-shadow:0 0 0 3px rgba(124,58,237,.1)}
.ud-field .password-wrap input{padding-right:46px}
.ud-field input[readonly]{background:#f7f2fe;color:#6b2fa0;font-weight:700}
.ud-field textarea{resize:vertical;min-height:160px}
.ud-field small{color:#9082aa;font-size:12px;line-height:1.4}
.ud-field-span-2{grid-column:1 / -1}
.ud-btn-primary{align-self:flex-start;padding:12px 18px;border:none;border-radius:14px;background:#3b0f7a;color:#fff;font-weight:700;cursor:pointer;box-shadow:0 12px 24px rgba(59,15,122,.2)}
.ud-status-box{margin:20px 24px 24px;padding:18px;border-radius:18px;border:1px solid}
.ud-status-box strong{display:block;font-size:18px;margin-bottom:8px}
.ud-status-box p{margin:0;line-height:1.6}
.ud-status-box.is-pending{background:#fff9eb;border-color:#f7d38c;color:#8a5b00}
.ud-status-box.is-approved{background:#ecfdf3;border-color:#bbf7d0;color:#166534}
.ud-status-box.is-error{background:#fef2f2;border-color:#fecaca;color:#b91c1c}
.ud-access-wrap{padding:20px 24px 24px}
.ud-access-status{border-radius:18px;padding:16px 18px;margin-bottom:16px;border:1px solid}
.ud-access-status strong{display:block;margin-bottom:6px}
.ud-access-status p{margin:0;line-height:1.6}
.ud-access-status.is-active{background:#ecfdf3;border-color:#bbf7d0;color:#166534}
.ud-access-status.is-inactive{background:#fff7ed;border-color:#fed7aa;color:#9a3412}
.ud-profile-grid{padding:20px 24px 24px;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
.ud-profile-panel{padding:18px;border-radius:20px;background:#fcfaff;border:1px solid #efe7fb}
.ud-profile-panel--full{grid-column:1 / -1}
.ud-mini-kicker{display:inline-flex;padding:6px 10px;border-radius:999px;background:#f2eaff;color:#6d28d9;font-size:11px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;margin-bottom:14px}
.ud-photo-block{display:flex;align-items:center;gap:14px;margin-bottom:16px}
.ud-profile-photo,.ud-profile-initial{width:74px;height:74px;border-radius:50%;flex-shrink:0}
.ud-profile-photo{object-fit:cover;border:3px solid #fff;box-shadow:0 10px 22px rgba(59,15,122,.12)}
.ud-profile-initial{display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#7c3aed,#4c1d95);color:#fff;font-size:30px;font-weight:800;box-shadow:0 10px 22px rgba(59,15,122,.16)}
.ud-photo-meta strong{display:block;color:#240a42;margin-bottom:5px}
.ud-photo-meta span{display:inline-flex;padding:6px 10px;border-radius:999px;background:#f2eaff;color:#6d28d9;font-size:12px;font-weight:700}
.ud-inline-form{display:flex;flex-direction:column;gap:14px}
.ud-inline-actions{display:flex;gap:10px;flex-wrap:wrap}
.ud-lock-note,.ud-security-lock{padding:15px 16px;border-radius:16px;border:1px solid #e9dff7;background:#faf7ff;color:#6b5b87;line-height:1.6}
.ud-security-lock strong{display:block;color:#240a42;margin-bottom:6px}
.ud-security-lock p{margin:0}
.ud-btn-secondary{display:inline-flex;align-items:center;justify-content:center;padding:12px 18px;border-radius:14px;border:1px solid #dac9f4;background:#fff;color:#5b21b6;font-weight:700;text-decoration:none}
.ud-modal{position:fixed;inset:0;display:none;align-items:center;justify-content:center;padding:24px;z-index:1200}
.ud-modal.is-visible{display:flex}
.ud-modal-backdrop{position:absolute;inset:0;background:rgba(21,8,43,.55);backdrop-filter:blur(4px)}
.ud-modal-dialog{position:relative;width:min(760px,100%);max-height:calc(100vh - 48px);overflow:auto;border-radius:28px;background:#fff;box-shadow:0 24px 60px rgba(22,6,50,.28)}
.ud-modal-dialog--wide{width:min(920px,100%)}
.ud-modal-head{display:flex;justify-content:space-between;gap:16px;padding:24px 26px 18px;border-bottom:1px solid #f0eaf9}
.ud-modal-head h2{margin:0 0 6px;color:#23093f;font-size:28px}
.ud-modal-head p{margin:0;color:#8c7ba8}
.ud-modal-close{width:44px;height:44px;border:none;border-radius:14px;background:#f6f1ff;color:#5b21b6;font-size:28px;line-height:1;cursor:pointer}
.ud-modal-alert{margin:18px 26px 0;padding:14px 16px;border:1px solid #fecaca;border-radius:16px;background:#fef2f2;color:#b91c1c}
.ud-modal-alert p{margin:0 0 4px}
.ud-modal-alert p:last-child{margin-bottom:0}
.ud-pinned-modal-body{padding:22px 26px 26px;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
.ud-pinned-card{display:flex;flex-direction:column;gap:10px;padding:18px;border-radius:20px;background:#fcfbff;border:1px solid #efe7fb;box-shadow:0 8px 22px rgba(59,15,122,.05)}
.ud-pinned-type{align-self:flex-start;padding:6px 10px;border-radius:999px;background:#f2eaff;color:#6d28d9;font-size:11px;font-weight:800}
.ud-pinned-card h3{margin:0;font-size:17px;line-height:1.35}
.ud-pinned-card h3 a{color:#23093f;text-decoration:none}
.ud-pinned-card h3 a:hover{color:#6d28d9}
.ud-pinned-card p{margin:0;color:#78698f;font-size:13px;line-height:1.6}
.ud-pinned-meta{display:flex;flex-wrap:wrap;gap:8px;color:#8b7aaa;font-size:12.5px}
.ud-pinned-footer{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:auto;padding-top:12px;border-top:1px solid #efe7fb;color:#9b8db6;font-size:12.5px}
.ud-pinned-footer a{display:inline-flex;padding:8px 12px;border-radius:999px;background:#3b0f7a;color:#fff;text-decoration:none;font-size:12px;font-weight:800}
.ud-pinned-empty{grid-column:1 / -1;display:flex;flex-direction:column;align-items:center;text-align:center;gap:10px;padding:42px 20px;border-radius:20px;background:#faf7ff;border:1px dashed #dac9f4;color:#6b5b87}
.ud-pinned-empty strong{font-size:18px;color:#240a42}
.ud-pinned-empty p{margin:0;max-width:380px;line-height:1.6}
.ud-submissions-summary{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;padding:20px 26px 0}
.ud-submissions-summary div{padding:14px;border-radius:18px;background:#faf7ff;border:1px solid #efe7fb}
.ud-submissions-summary strong{display:block;color:#240a42;font-size:26px;line-height:1}
.ud-submissions-summary span{display:block;margin-top:7px;color:#8b7aaa;font-size:11px;font-weight:800;letter-spacing:.08em;text-transform:uppercase}
.ud-submissions-modal-body{padding:18px 26px 26px;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
.ud-submission-card{display:flex;flex-direction:column;gap:10px;padding:18px;border-radius:20px;background:#fff;border:1px solid #efe7fb;border-top:4px solid #a78bfa;box-shadow:0 8px 22px rgba(59,15,122,.05)}
.ud-submission-card.status-approved{border-top-color:#22c55e}
.ud-submission-card.status-pending{border-top-color:#f59e0b}
.ud-submission-card.status-rejected{border-top-color:#ef4444}
.ud-submission-top{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap}
.ud-submission-status{display:inline-flex;padding:6px 10px;border-radius:999px;font-size:11px;font-weight:800}
.ud-submission-status.status-approved{background:#dcfce7;color:#166534}
.ud-submission-status.status-pending{background:#fef3c7;color:#92400e}
.ud-submission-status.status-rejected{background:#fee2e2;color:#991b1b}
.ud-submission-card h3{margin:0;font-size:17px;line-height:1.35}
.ud-submission-card h3 a{color:#23093f;text-decoration:none}
.ud-submission-card h3 a:hover{color:#6d28d9}
.ud-submission-note{display:grid;gap:4px;padding:12px 14px;border-radius:14px;background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;font-size:12.5px;line-height:1.5}
.ud-submit-form{padding:22px 26px 26px;display:flex;flex-direction:column;gap:16px}
.ud-submit-section{display:flex;align-items:center;gap:10px;padding-top:6px}
.ud-submit-section h3{margin:0;color:#240a42;font-size:16px}
.ud-submit-step{display:inline-flex;align-items:center;justify-content:center;width:24px;height:24px;border-radius:999px;background:#f2eaff;color:#6d28d9;font-size:12px;font-weight:800}
.ud-submit-note{padding:14px 16px;border-radius:16px;border:1px solid #fcd34d;background:#fffbeb;color:#92400e;line-height:1.6}
.ud-list{padding:18px 24px 24px;display:grid;gap:10px}
.ud-list-item{display:block;padding:14px 16px;border-radius:16px;background:#fcfbff;border:1px solid #efe7fb;text-decoration:none}
.ud-list-item strong{display:block;color:#240a42;margin-bottom:5px}
.ud-list-item span{font-size:13px;color:#8b7aaa}
.ud-empty{padding:16px;border-radius:16px;background:#faf7ff;color:#9b8db6;text-align:center}
@media (max-width: 1100px){.ud-layout{grid-template-columns:1fr}.ud-sidebar{position:static}}
@media (max-width: 980px){.ud-stats{grid-template-columns:repeat(2,minmax(0,1fr))}.ud-grid{grid-template-columns:1fr}.ud-hero{flex-direction:column}.ud-profile-grid{grid-template-columns:1fr}}
@media (max-width: 640px){.ud-shell{padding:24px 16px 34px}.ud-hero{padding:22px}.ud-hero h1{font-size:32px}.ud-stats,.ud-submissions-summary{grid-template-columns:1fr}.ud-form-row,.ud-pinned-modal-body,.ud-submissions-modal-body{grid-template-columns:1fr}.ud-photo-block{align-items:flex-start;flex-direction:column}.ud-modal{padding:12px}.ud-modal-head,.ud-submit-form,.ud-pinned-modal-body,.ud-submissions-modal-body,.ud-submissions-summary{padding-left:18px;padding-right:18px}}
</style>

@push('scripts')
<script>
const submitDeptData = {
    'College of Accountancy and Business Education': {
        courses: ['Accountancy', 'Business Administration-Marketing Mngt.', 'Hospitality Management', 'Tourism Management'],
        types: ['Thesis', 'Feasibility Study', 'Descriptive Research', 'Correlational Research', 'Quantitative Research']
    },
    'College of Computer Studies': {
        courses: ['Computer Science', 'Information Technology'],
        types: ['Capstone 1', 'Capstone 2', 'Thesis', 'Applied Research']
    },
    'College of Criminal Justice Education': {
        courses: ['Criminology'],
        types: ['Thesis', 'Descriptive Research', 'Qualitative Research', 'Mixed Methods Research']
    },
    'College of Education': {
        courses: ['Elementary Education', 'Secondary Education-General Science'],
        types: ['Thesis', 'Action Research', 'Descriptive Research', 'Experimental Research']
    },
    'College of Engineering and Architecture': {
        courses: ['Civil Engineering', 'Computer Engineering', 'Electrical Engineering', 'Electronics Engineering', 'Mechanical Engineering'],
        types: ['Capstone 1', 'Capstone 2', 'Thesis', 'Applied Research', 'Experimental Research']
    },
    'College of Maritime Studies': {
        courses: ['Marine Engineering', 'Transportation'],
        types: ['Thesis', 'Applied Research', 'Descriptive Research', 'Quantitative Research']
    }
};
const submitJournalTypes = @json($journalTypes);

document.querySelectorAll('[data-dashboard-link]').forEach((link) => {
    link.addEventListener('click', () => {
        document.querySelectorAll('[data-dashboard-link]').forEach((item) => item.classList.remove('is-active'));
        document.querySelectorAll('[data-open-pinned]').forEach((item) => item.classList.remove('is-active'));
        document.querySelectorAll('[data-open-submissions]').forEach((item) => item.classList.remove('is-active'));
        link.classList.add('is-active');
    });
});

if (window.location.hash) {
    const activeLink = document.querySelector(`[data-dashboard-link][href="${window.location.hash}"]`);
    if (activeLink) {
        document.querySelectorAll('[data-dashboard-link]').forEach((item) => item.classList.remove('is-active'));
        document.querySelectorAll('[data-open-pinned]').forEach((item) => item.classList.remove('is-active'));
        document.querySelectorAll('[data-open-submissions]').forEach((item) => item.classList.remove('is-active'));
        activeLink.classList.add('is-active');
    }
}

const submitResearchModal = document.getElementById('submitResearchModal');
const pinnedPapersModal = document.getElementById('pinnedPapersModal');
const mySubmissionsModal = document.getElementById('mySubmissionsModal');

function syncDashboardModalOverflow() {
    const hasOpenModal = submitResearchModal?.classList.contains('is-visible')
        || pinnedPapersModal?.classList.contains('is-visible')
        || mySubmissionsModal?.classList.contains('is-visible');

    document.body.style.overflow = hasOpenModal ? 'hidden' : '';
}

function toggleSubmitResearchModal(shouldOpen) {
    if (!submitResearchModal) {
        return;
    }

    submitResearchModal.classList.toggle('is-visible', shouldOpen);
    submitResearchModal.setAttribute('aria-hidden', shouldOpen ? 'false' : 'true');
    syncDashboardModalOverflow();
}

function togglePinnedPapersModal(shouldOpen) {
    if (!pinnedPapersModal) {
        return;
    }

    pinnedPapersModal.classList.toggle('is-visible', shouldOpen);
    pinnedPapersModal.setAttribute('aria-hidden', shouldOpen ? 'false' : 'true');
    syncDashboardModalOverflow();
}

function toggleMySubmissionsModal(shouldOpen) {
    if (!mySubmissionsModal) {
        return;
    }

    mySubmissionsModal.classList.toggle('is-visible', shouldOpen);
    mySubmissionsModal.setAttribute('aria-hidden', shouldOpen ? 'false' : 'true');
    syncDashboardModalOverflow();
}

document.querySelectorAll('[data-open-submit]').forEach((button) => {
    button.addEventListener('click', (event) => {
        event.preventDefault();
        document.querySelectorAll('[data-dashboard-link]').forEach((item) => item.classList.remove('is-active'));
        document.querySelectorAll('[data-open-pinned]').forEach((item) => item.classList.remove('is-active'));
        document.querySelectorAll('[data-open-submissions]').forEach((item) => item.classList.remove('is-active'));
        button.classList.add('is-active');
        toggleSubmitResearchModal(true);
    });
});

document.querySelectorAll('[data-close-submit]').forEach((button) => {
    button.addEventListener('click', () => toggleSubmitResearchModal(false));
});

document.querySelectorAll('[data-open-pinned]').forEach((button) => {
    button.addEventListener('click', (event) => {
        event.preventDefault();
        document.querySelectorAll('[data-dashboard-link]').forEach((item) => item.classList.remove('is-active'));
        document.querySelectorAll('[data-open-submissions]').forEach((item) => item.classList.remove('is-active'));
        document.querySelectorAll('[data-open-pinned]').forEach((item) => item.classList.add('is-active'));
        togglePinnedPapersModal(true);
    });
});

document.querySelectorAll('[data-close-pinned]').forEach((button) => {
    button.addEventListener('click', () => togglePinnedPapersModal(false));
});

document.querySelectorAll('[data-open-submissions]').forEach((button) => {
    button.addEventListener('click', (event) => {
        event.preventDefault();
        document.querySelectorAll('[data-dashboard-link]').forEach((item) => item.classList.remove('is-active'));
        document.querySelectorAll('[data-open-pinned]').forEach((item) => item.classList.remove('is-active'));
        document.querySelectorAll('[data-open-submissions]').forEach((item) => item.classList.add('is-active'));
        toggleMySubmissionsModal(true);
    });
});

document.querySelectorAll('[data-close-submissions]').forEach((button) => {
    button.addEventListener('click', () => toggleMySubmissionsModal(false));
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && submitResearchModal?.classList.contains('is-visible')) {
        toggleSubmitResearchModal(false);
    }
    if (event.key === 'Escape' && pinnedPapersModal?.classList.contains('is-visible')) {
        togglePinnedPapersModal(false);
    }
    if (event.key === 'Escape' && mySubmissionsModal?.classList.contains('is-visible')) {
        toggleMySubmissionsModal(false);
    }
});

function populateSubmitOptions() {
    const department = document.getElementById('submit_department')?.value;
    const category = document.getElementById('submit_submission_category')?.value || 'journal';
    const courseSelect = document.getElementById('submit_course');
    const typeSelect = document.getElementById('submit_type');
    const typeLabel = document.getElementById('submit_type_label');

    if (!courseSelect || !typeSelect || !typeLabel) {
        return;
    }

    const currentCourse = @json(old('course'));
    const currentType = @json(old('type'));

    courseSelect.innerHTML = '';
    typeSelect.innerHTML = '';
    typeLabel.textContent = category === 'journal' ? 'Journal Type' : 'Research Type';

    if (!department || !submitDeptData[department]) {
        courseSelect.innerHTML = '<option value="">-- No Department Assigned --</option>';
        typeSelect.innerHTML = '<option value="">-- No Department Assigned --</option>';
        courseSelect.disabled = true;
        typeSelect.disabled = true;
        return;
    }

    courseSelect.innerHTML = '<option value="">-- Select Program --</option>';
    typeSelect.innerHTML = '<option value="">-- Select Type --</option>';

    submitDeptData[department].courses.forEach((course) => {
        const option = document.createElement('option');
        option.value = course;
        option.textContent = course;
        if (currentCourse === course) {
            option.selected = true;
        }
        courseSelect.appendChild(option);
    });

    const types = category === 'journal' ? submitJournalTypes : submitDeptData[department].types;
    types.forEach((type) => {
        const option = document.createElement('option');
        option.value = type;
        option.textContent = type;
        if (currentType === type) {
            option.selected = true;
        }
        typeSelect.appendChild(option);
    });

    courseSelect.disabled = false;
    typeSelect.disabled = false;
}

populateSubmitOptions();
document.getElementById('submit_submission_category')?.addEventListener('change', populateSubmitOptions);

syncDashboardModalOverflow();
</script>
@endpush
@endsection
