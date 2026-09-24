@extends('layouts.user-dashboard')

@section('title', 'My Dashboard')
@section('page-title', 'My Dashboard')

@section('content')
@php
    $isFacultyAccount = $user->role === 'researcher' && is_null($user->graduation_year);
    $isStudentResearcher = $user->role === 'researcher' && ! is_null($user->graduation_year);
    $dashboardRoleLabel = $isFacultyAccount ? 'Faculty' : ($isStudentResearcher ? 'Student Researcher' : 'Student');
    $firstName = Str::before($user->name, ' ');
    $departmentUrl = $user->department ? route('research.department', $user->department) : route('home');
    $dashboardHour = now('Asia/Manila')->hour;
    $dashboardGreeting = $dashboardHour < 12 ? 'Good morning' : ($dashboardHour < 18 ? 'Good afternoon' : 'Good evening');
@endphp

<div class="student-dashboard">
<section class="student-dashboard-section is-active" data-dashboard-section="overview">

    <div class="wd-banner">
        @if($user->profile_photo)
            <img src="{{ asset('storage/' . $user->profile_photo) }}" alt="{{ $user->name }}" class="wd-banner-avatar-img">
        @else
            <span class="wd-banner-avatar">{{ strtoupper(substr($firstName, 0, 1)) }}</span>
        @endif
        <div class="wd-banner-copy">
            <span>Welcome Back</span>
            <h2 class="wd-banner-heading">{{ $dashboardGreeting }}, {{ $firstName }}!</h2>
        </div>
    </div>

    <div class="ra-summary-grid">
        <button type="button" class="ra-summary-card is-paper" data-dashboard-jump="saved">
            <div class="ra-summary-copy">
                <span>Saved Researches</span>
                <strong>{{ number_format($stats['pinned']) }}</strong>
                <small>Research papers saved to your personal list</small>
                <span class="ra-summary-action">Open saved list
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                </span>
            </div>
            <div class="ra-summary-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"/></svg>
            </div>
        </button>

        <a href="{{ $departmentUrl }}" class="ra-summary-card is-view">
            <div class="ra-summary-copy">
                <span>Department Repository</span>
                <strong>{{ number_format($stats['department_researches']) }}</strong>
                <small>{{ $user->department ? Str::limit($user->department, 46) : 'Browse all approved research papers' }}</small>
                <span class="ra-summary-action">Browse papers
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                </span>
            </div>
            <div class="ra-summary-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
            </div>
        </a>
    </div>


        <section class="ra-panel">
            <div class="ra-panel-header">
                <div>
                    <h3>Latest Department Research</h3>
                    <span>{{ $user->department ?: 'Repository updates' }}</span>
                </div>
            </div>
            <div class="ra-table-wrap">
                <table class="ra-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Year</th>
                            <th>Views</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentDepartmentResearches as $research)
                            <tr>
                                <td>
                                    <a href="{{ route('research.show', $research) }}" class="ra-table-link">{{ $research->title }}</a>
                                    <span>{{ $research->getSubmissionCategoryLabel() }}: {{ $research->getTypeLabel() }}</span>
                                </td>
                                <td>{{ $research->author_name }}</td>
                                <td>{{ $research->year_published ?: 'N/A' }}</td>
                                <td>{{ number_format((int) $research->view_count) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="ra-empty-cell">No recent department research available.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
</section>

<section class="student-dashboard-section" data-dashboard-section="saved" hidden>
    <section class="ra-panel">
        <div class="ra-panel-header">
            <div>
                <h3>Saved Researches</h3>
                <span id="savedCountLabel">{{ $pinnedResearches->count() }} saved paper{{ $pinnedResearches->count() === 1 ? '' : 's' }}</span>
            </div>
        </div>

        {{-- Search bar --}}
        <div class="sr-search-toolbar" id="srSearchToolbar">
            <div class="sr-search-box">
                <svg class="sr-search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                <input type="text" id="srSearchInput" class="sr-search-input" placeholder="Search by title, author, or keyword..." autocomplete="off">
                <button type="button" id="srSearchClear" class="sr-search-clear" aria-label="Clear search" style="display:none;">&times;</button>
            </div>
            <div class="sr-filter-status" id="srFilterStatus" style="display:none;">
                <span id="srMatchCount"></span>
            </div>
        </div>

        <div class="student-card-grid" id="srCardsContainer">
            @forelse($pinnedResearches as $research)
                <article class="ud-pinned-card"
                    data-sr-card
                    data-title="{{ strtolower($research->title) }}"
                    data-author="{{ strtolower($research->author_name) }}"
                    data-keywords="{{ strtolower($research->keywords ?? '') }}"
                    data-dept="{{ strtolower($research->department ?? '') }}"
                    data-year="{{ $research->year_published }}"
                    data-type="{{ strtolower($research->getTypeLabel() ?? '') }}">
                    <div class="ud-pinned-badge-row">
                        <span class="ud-pinned-type">{{ $research->getSubmissionCategoryLabel() }}: {{ $research->getTypeLabel() }}</span>
                        <span class="ud-pinned-year">{{ $research->year_published ?: 'N/A' }}</span>
                    </div>
                    <h3><a href="{{ route('research.show', $research) }}">{{ $research->title }}</a></h3>
                    <p>{{ Str::limit($research->abstract, 155) }}</p>
                    <div class="ud-pinned-meta">
                        <span><strong>Author:</strong> {{ $research->author_name }}</span>
                        @if($research->department)
                            <span><strong>Dept:</strong> {{ Str::limit($research->department, 34) }}</span>
                        @endif
                    </div>
                    <div class="ud-pinned-footer">
                        <span>{{ number_format($research->view_count) }} views</span>
                        <a href="{{ route('research.show', $research) }}" class="ud-btn-view">View Details</a>
                    </div>
                </article>
            @empty
                <div class="student-empty" id="srEmptyState">
                    <strong>No saved research yet.</strong>
                    <p>Open a research detail page and click Save to keep it here.</p>
                    <a href="{{ $departmentUrl }}" class="ra-link-btn">Browse Repository</a>
                </div>
            @endforelse

            {{-- No search results state --}}
            <div class="student-empty sr-no-results" id="srNoResults" style="display:none;">
                <strong>No matching researches found</strong>
                <p>No saved research matches your search. Try a different keyword.</p>
                <button type="button" class="ra-link-btn" id="srClearFiltersBtn">Clear Search</button>
            </div>
        </div>

        {{-- Pagination --}}
        <div class="sr-pagination-wrap" id="srPaginationWrap" style="display:none;">
            <div class="sr-pagination-info" id="srPaginationInfo"></div>
            <div class="sr-pagination-nav" id="srPaginationNav"></div>
        </div>
    </section>
</section>
</div>

<div class="ud-modal" id="pinnedPapersModal" aria-hidden="true">
    <div class="ud-modal-backdrop" data-close-pinned></div>
    <div class="ud-modal-dialog ud-modal-dialog--wide" role="dialog" aria-modal="true" aria-labelledby="pinnedPapersTitle">
        <div class="ud-modal-head">
            <div>
                <span class="ra-eyebrow">Saved Researches</span>
                <h2 id="pinnedPapersTitle">Saved Researches</h2>
                <p id="pinnedCountSubtitle">{{ $pinnedResearches->count() }} saved research paper{{ $pinnedResearches->count() === 1 ? '' : 's' }} in your account.</p>
            </div>
            <button type="button" class="ud-modal-close" data-close-pinned aria-label="Close saved researches modal">&times;</button>
        </div>

        @if($pinnedResearches->count() > 10)
            <div class="ud-filter-toolbar" id="savedFilterToolbar">
                <div class="ud-search-box">
                    <svg class="ud-search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                    <input type="text" id="savedSearchInput" class="ud-filter-input" placeholder="Search by title, author, keyword..." autocomplete="off">
                    <button type="button" id="savedSearchClear" class="ud-search-clear" aria-label="Clear search" style="display:none;">&times;</button>
                </div>
                <div class="ud-filter-controls">
                    <select id="savedDeptFilter" class="ud-filter-select" aria-label="Filter by department">
                        <option value="">All Departments</option>
                        @foreach($pinnedResearches->pluck('department')->filter()->unique()->sort() as $dept)
                            <option value="{{ strtolower($dept) }}">{{ $dept }}</option>
                        @endforeach
                    </select>
                    <select id="savedYearFilter" class="ud-filter-select" aria-label="Filter by year">
                        <option value="">All Years</option>
                        @foreach($pinnedResearches->pluck('year_published')->filter()->unique()->sortDesc() as $yr)
                            <option value="{{ $yr }}">{{ $yr }}</option>
                        @endforeach
                    </select>
                    <button type="button" id="savedResetFilters" class="ud-btn-filter-reset">Reset</button>
                </div>
            </div>
            <div class="ud-filter-status" id="savedFilterStatus">
                <span id="savedMatchCount">Showing 1-10 of {{ $pinnedResearches->count() }} researches</span>
            </div>
        @endif

        <div class="ud-pinned-modal-body" id="savedItemsContainer">
            @forelse($pinnedResearches as $research)
                <article class="ud-pinned-card"
                    data-saved-card
                    data-title="{{ strtolower($research->title) }}"
                    data-author="{{ strtolower($research->author_name) }}"
                    data-dept="{{ strtolower($research->department ?? '') }}"
                    data-year="{{ $research->year_published }}"
                    data-keywords="{{ strtolower($research->keywords ?? '') }}"
                    data-type="{{ strtolower($research->getTypeLabel() ?? '') }}">
                    <div class="ud-pinned-badge-row">
                        <span class="ud-pinned-type">{{ $research->getSubmissionCategoryLabel() }}: {{ $research->getTypeLabel() }}</span>
                        <span class="ud-pinned-year">{{ $research->year_published }}</span>
                    </div>
                    <h3><a href="{{ route('research.show', $research) }}">{{ $research->title }}</a></h3>
                    <p>{{ Str::limit($research->abstract, 150) }}</p>
                    <div class="ud-pinned-meta">
                        <span><strong>Author:</strong> {{ $research->author_name }}</span>
                        @if($research->department)
                            <span><strong>Dept:</strong> {{ Str::limit($research->department, 32) }}</span>
                        @endif
                    </div>
                    <div class="ud-pinned-footer">
                        <span>{{ number_format($research->view_count) }} views</span>
                        <a href="{{ route('research.show', $research) }}" class="ud-btn-view">View Details</a>
                    </div>
                </article>
            @empty
                <div class="ud-pinned-empty">
                    <strong>No saved researches yet</strong>
                    <p>Save research papers from the details page to keep them here for quick access.</p>
                    <a href="{{ $departmentUrl }}" class="ra-link-btn">Browse Research</a>
                </div>
            @endforelse

            <div class="ud-pinned-empty ud-search-no-results" id="savedNoResultsState" style="display:none;">
                <strong>No matching researches found</strong>
                <p>No saved research matches your search criteria or filters.</p>
                <button type="button" class="ra-link-btn" id="savedEmptyResetBtn">Clear Filters</button>
            </div>
        </div>

        <div class="ud-pagination-wrap" id="savedPaginationWrap" style="display:none;">
            <div class="ud-pagination-info" id="savedPaginationInfo">Showing 1-10 of {{ $pinnedResearches->count() }}</div>
            <div class="ud-pagination-nav" id="savedPaginationNav"></div>
        </div>
    </div>
</div>

<style>
.student-dashboard{display:grid;gap:18px}
.student-dashboard-section{display:grid;gap:18px}
.student-dashboard-section[hidden]{display:none!important}
.wd-banner{display:flex;align-items:center;gap:16px;padding:18px 20px;border-radius:16px;background:linear-gradient(135deg,#fff 0%,#fbf8ff 100%);border:1px solid rgba(109,40,217,.1);box-shadow:0 8px 28px rgba(46,16,101,.06)}
.wd-banner-avatar,.wd-banner-avatar-img{width:54px;height:54px;border-radius:16px;flex:0 0 auto}
.wd-banner-avatar{display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#6d28d9,#43216f);color:#fff;font-size:20px;font-weight:900;box-shadow:0 10px 20px rgba(67,33,111,.16)}
.wd-banner-avatar-img{object-fit:cover;border:2px solid #fff;box-shadow:0 10px 20px rgba(67,33,111,.16)}
.wd-banner-copy{min-width:0;flex:1}
.wd-banner-copy>span{display:block;margin-bottom:3px;color:#7a5ca8;font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:.08em;line-height:1.25}
.wd-banner-heading{margin:0;color:#1f1235;font-size:clamp(22px,2.4vw,30px);font-weight:900;letter-spacing:0;line-height:1.15}
.student-section-head{padding:20px 22px;background:#fff;border:1px solid rgba(109,40,217,.1);border-radius:16px;box-shadow:0 8px 28px rgba(46,16,101,.06)}
.student-section-head h2{margin:0;color:#1f1235;font-size:24px;font-weight:850;line-height:1.2}
.student-section-head p{margin:7px 0 0;color:#6d5d85;font-size:13px;line-height:1.5}
.student-welcome{align-items:center}
.ra-toolbar{display:flex;align-items:flex-end;justify-content:space-between;gap:16px;margin-bottom:2px;padding:20px 22px;background:#fff;border:1px solid rgba(109,40,217,.1);border-radius:16px;box-shadow:0 8px 28px rgba(46,16,101,.06)}
.ra-toolbar-copy{min-width:0}
.ra-eyebrow{display:block;margin-bottom:6px;color:#7a5ca8;font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:.09em}
.ra-toolbar h2{margin:0;color:#1f1235;font-size:24px;font-weight:850;letter-spacing:0;line-height:1.2}
.ra-toolbar p{margin:7px 0 0;color:#6d5d85;font-size:13px;line-height:1.5}
.student-account-card{display:grid;grid-template-columns:42px minmax(0,1fr);gap:11px;align-items:center;min-width:min(100%,280px);padding:10px 12px;border:1px solid #e6daf5;border-radius:14px;background:#fbf9ff}
.student-avatar,.student-avatar-img{width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.student-avatar{background:#43216f;color:#fff;font-size:16px;font-weight:900}
.student-avatar-img{object-fit:cover}
.student-account-card strong{display:block;color:#1f1235;font-size:13.5px;line-height:1.25;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.student-account-card span{display:block;margin-top:3px;color:#7d6c98;font-size:11.5px;font-weight:700;line-height:1.35}
.ra-link-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:34px;padding:0 13px;border-radius:10px;border:1px solid #d8c8ef;background:#fff;color:#4f1d7a;font:inherit;font-size:13px;font-weight:850;text-decoration:none;white-space:nowrap;cursor:pointer;transition:background .16s ease,border-color .16s ease,transform .16s ease}
.ra-link-btn:hover{background:#f5f1fb;border-color:#bda6df;color:#3b0f63}
.ra-summary-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
.ra-summary-card{appearance:none;width:100%;position:relative;min-height:142px;display:flex;align-items:flex-start;justify-content:space-between;gap:14px;padding:20px;border-radius:16px;background:#fff;border:1px solid rgba(109,40,217,.1);box-shadow:0 10px 26px rgba(46,16,101,.07);overflow:hidden;text-align:left;font:inherit;color:inherit;cursor:pointer;text-decoration:none;transition:transform .18s ease,box-shadow .18s ease,border-color .18s ease}
.ra-summary-card::before{content:"";position:absolute;inset:0 0 auto;height:4px;background:var(--accent,#6d28d9)}
.ra-summary-card:hover,.ra-summary-card:focus-visible{transform:translateY(-4px);border-color:color-mix(in srgb,var(--accent,#6d28d9) 34%,#fff);box-shadow:0 18px 40px rgba(46,16,101,.12);outline:none}
.ra-summary-card.is-paper{--accent:#6d28d9}
.ra-summary-card.is-view{--accent:#0f766e}
.ra-summary-copy{position:relative;z-index:1;min-width:0}
.ra-summary-copy>span:first-child{display:block;color:#746585;font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:.08em;line-height:1.35}
.ra-summary-copy strong{display:block;margin-top:12px;color:#1f1235;font-size:34px;font-weight:900;line-height:1;letter-spacing:0}
.ra-summary-copy small{display:block;margin-top:9px;color:#786890;font-size:12px;line-height:1.35}
.ra-summary-action{display:inline-flex;align-items:center;gap:6px;margin-top:14px;color:var(--accent,#6d28d9);font-size:12px;font-weight:900;line-height:1.2}
.ra-summary-action svg{width:14px;height:14px;transition:transform .18s ease}
.ra-summary-card:hover .ra-summary-action svg,.ra-summary-card:focus-visible .ra-summary-action svg{transform:translateX(3px)}
.ra-summary-icon{width:46px;height:46px;display:flex;align-items:center;justify-content:center;flex:0 0 auto;border-radius:12px;color:var(--accent,#6d28d9);background:color-mix(in srgb,var(--accent,#6d28d9) 12%,#fff);border:1px solid color-mix(in srgb,var(--accent,#6d28d9) 18%,#fff)}
.ra-summary-icon svg{width:22px;height:22px}
.ra-dashboard-grid{display:grid;grid-template-columns:minmax(0,1fr) 320px;gap:18px}
.ra-panel{background:#fff;border:1px solid rgba(109,40,217,.1);border-radius:16px;box-shadow:0 8px 28px rgba(46,16,101,.06);overflow:hidden}
.ra-panel-header{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:17px 20px;background:#fbf9ff;border-bottom:1px solid rgba(109,40,217,.08)}
.ra-panel-header h3{margin:0;color:#1f1235;font-size:16px;font-weight:850;letter-spacing:0;line-height:1.25}
.ra-panel-header span{display:block;margin-top:4px;color:#7d6c98;font-size:12px;font-weight:700}
.ra-wide-panel{grid-column:1/-1}
.student-paper-list{display:grid}
.student-paper-row{display:grid;grid-template-columns:minmax(0,1fr) 38px;gap:14px;align-items:center;padding:16px 20px;border-bottom:1px solid #f1ebfa}
.student-paper-row:last-child{border-bottom:none}
.student-paper-type{display:inline-flex;margin-bottom:7px;padding:4px 9px;border-radius:999px;background:#f2eaff;color:#6d28d9;font-size:10.5px;font-weight:900}
.student-paper-main a{display:block;color:#24113f;font-size:14px;font-weight:850;line-height:1.35;text-decoration:none}
.student-paper-main a:hover{color:#6d28d9;text-decoration:underline}
.student-paper-main p{margin:6px 0 0;color:#6d5d85;font-size:12.5px;line-height:1.55}
.student-paper-meta{display:flex;flex-wrap:wrap;gap:10px;margin-top:8px;color:#7d6c98;font-size:11.5px;font-weight:700}
.student-row-action{width:38px;height:38px;display:flex;align-items:center;justify-content:center;border-radius:10px;background:#f4effb;color:#4f1d7a;text-decoration:none}
.student-row-action svg{width:17px;height:17px}
.student-row-action:hover{background:#43216f;color:#fff}
.student-empty{display:grid;justify-items:center;gap:8px;padding:42px 20px;text-align:center;color:#7d6c98}
.student-empty strong{color:#1f1235;font-size:16px}
.student-empty p{margin:0;font-size:13px}
.student-action-list{display:grid;padding:8px}
.student-action{display:grid;grid-template-columns:38px minmax(0,1fr);gap:11px;align-items:center;width:100%;padding:11px;border:0;border-radius:12px;background:transparent;text-align:left;text-decoration:none;color:#1f1235;font:inherit;cursor:pointer}
.student-action:hover{background:#f8f4ff}
.student-action-icon{width:38px;height:38px;display:flex;align-items:center;justify-content:center;border-radius:11px;background:#f0eaf9;color:#6d28d9}
.student-action-icon svg{width:18px;height:18px}
.student-action strong{display:block;font-size:13px;line-height:1.3}
.student-action small{display:block;margin-top:3px;color:#7d6c98;font-size:11.5px;line-height:1.35}
.ra-table-wrap{overflow-x:auto}
.ra-table{width:100%;border-collapse:collapse;font-size:13px}
.ra-table th{text-align:left;padding:11px 16px;background:#fbf9ff;color:#5b3d8a;font-size:10.5px;font-weight:900;text-transform:uppercase;letter-spacing:.06em;border-bottom:1px solid #eadff8;white-space:nowrap}
.ra-table td{padding:13px 16px;border-bottom:1px solid #f1ebfa;color:#2d2440;vertical-align:top}
.ra-table tbody tr:last-child td{border-bottom:none}
.ra-table tbody tr:hover td{background:#fdfbff}
.ra-table-link{display:block;color:#24113f;font-weight:850;text-decoration:none;line-height:1.35;max-width:720px}
.ra-table-link:hover{color:#6d28d9;text-decoration:underline}
.ra-table td span{display:block;margin-top:4px;color:#7d6c98;font-size:12px;line-height:1.35}
.ra-empty-cell{text-align:center;color:#8b7aaa!important;padding:26px!important}
.student-card-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;padding:20px}
.ud-modal{position:fixed;inset:0;display:none;align-items:center;justify-content:center;padding:20px;z-index:1400;background:rgba(19,8,38,.58);backdrop-filter:blur(4px)}
.ud-modal.is-visible{display:flex}
.ud-modal-backdrop{position:absolute;inset:0}
.ud-modal-dialog{position:relative;z-index:1;width:min(760px,100%);max-height:calc(100vh - 40px);overflow:auto;border-radius:16px;background:#fff;box-shadow:0 30px 90px rgba(26,6,56,.32);display:flex;flex-direction:column;border:1px solid #e7daf7}
.ud-modal-dialog--wide{width:min(980px,96vw)}
.ud-modal-head{display:flex;justify-content:space-between;gap:16px;padding:22px 24px;background:#fbf9ff;border-bottom:1px solid #eadff8}
.ud-modal-head h2{margin:0 0 6px;color:#1f1235;font-size:22px;font-weight:900}
.ud-modal-head p{margin:0;color:#6d5d85;font-size:13px}
.ud-modal-close{width:38px;height:38px;border:1px solid #e2d5f4;border-radius:10px;background:#fff;color:#4f1d7a;font-size:24px;line-height:1;cursor:pointer;display:flex;align-items:center;justify-content:center}
.ud-modal-close:hover{background:#f5f1fb}
.ud-pinned-modal-body{padding:20px 24px 24px;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;overflow-y:auto}
.ud-pinned-card{display:flex;flex-direction:column;gap:10px;padding:16px;border-radius:14px;background:#fcfbff;border:1px solid #efe7fb}
.ud-pinned-badge-row{display:flex;align-items:center;justify-content:space-between;gap:10px}
.ud-pinned-type{padding:4px 9px;border-radius:999px;background:#f2eaff;color:#6d28d9;font-size:10.5px;font-weight:900}
.ud-pinned-year{font-size:12px;font-weight:800;color:#8c7aa8}
.ud-pinned-card h3{margin:0;font-size:15px;line-height:1.35;font-weight:850}
.ud-pinned-card h3 a{color:#23093f;text-decoration:none}
.ud-pinned-card h3 a:hover{color:#6d28d9;text-decoration:underline}
.ud-pinned-card p{margin:0;color:#6e5e85;font-size:12.5px;line-height:1.55}
.ud-pinned-meta{display:flex;flex-wrap:wrap;gap:10px;color:#8b7aaa;font-size:12px}
.ud-pinned-meta strong{color:#431f6d}
.ud-pinned-footer{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:auto;padding-top:12px;border-top:1px solid #efe7fb;color:#9b8db6;font-size:12px}
.ud-btn-view{display:inline-flex;align-items:center;justify-content:center;min-height:32px;padding:0 12px;border-radius:10px;background:#43216f;color:#fff;text-decoration:none;font-size:12px;font-weight:850}
.ud-pinned-empty{grid-column:1/-1;display:flex;flex-direction:column;align-items:center;text-align:center;gap:9px;padding:42px 20px;border-radius:14px;background:#faf7ff;border:1px dashed #d8c7f3;color:#6b5b87}
.ud-pinned-empty strong{font-size:16px;color:#240a42}
.ud-pinned-empty p{margin:0;max-width:420px;line-height:1.55;font-size:13px}
.ud-filter-toolbar{padding:14px 24px;background:#fff;border-bottom:1px solid #f0eaf9;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px}
.ud-search-box{position:relative;flex:1 1 260px;min-width:220px;display:flex;align-items:center}
.ud-search-icon{position:absolute;left:13px;color:#8c7aa8;pointer-events:none}
.ud-filter-input{width:100%;padding:10px 36px 10px 38px;border-radius:10px;border:1.5px solid #e5d8f6;background:#fff;font-size:13px;font-family:inherit;color:#2b0d4e}
.ud-filter-input:focus,.ud-filter-select:focus{outline:none;border-color:#7c3aed;box-shadow:0 0 0 3px rgba(124,58,237,.12)}
.ud-search-clear{position:absolute;right:10px;width:22px;height:22px;border:0;border-radius:50%;background:#eee5f8;color:#6d28d9;font-size:16px;line-height:1;cursor:pointer;display:flex;align-items:center;justify-content:center}
.ud-filter-controls{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.ud-filter-select{padding:9px 12px;border-radius:10px;border:1.5px solid #e5d8f6;background:#fff;font-size:13px;font-family:inherit;font-weight:700;color:#3b1464}
.ud-btn-filter-reset{min-height:37px;padding:0 13px;border-radius:10px;border:1.5px solid #e5d8f6;background:#fff;color:#6d28d9;font-size:12.5px;font-weight:800;cursor:pointer}
.ud-filter-status{padding:8px 24px 0;color:#8c7aa8;font-size:12px;font-weight:700}
.ud-pagination-wrap{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;padding:16px 24px 20px;border-top:1px solid #f0eaf9;background:#fff;margin-top:auto}
.ud-pagination-info{color:#8b7aa8;font-size:13px;font-weight:700}
.ud-pagination-nav{display:flex;align-items:center;gap:6px}
.ud-pag-btn{min-width:36px;height:36px;padding:0 10px;border-radius:10px;border:1.5px solid #e5d8f6;background:#fff;color:#3b0f7a;font-size:13px;font-weight:800;cursor:pointer;display:inline-flex;align-items:center;justify-content:center}
.ud-pag-btn.is-active{background:#43216f;border-color:#43216f;color:#fff}
.ud-pag-btn:disabled,.ud-pag-btn.is-disabled{opacity:.45;cursor:not-allowed;pointer-events:none}
.ud-pag-ellipsis{padding:0 6px;color:#8c7aa8;font-weight:800}
@media(max-width:1200px){.ra-dashboard-grid{grid-template-columns:1fr}.ra-wide-panel{grid-column:auto}.student-actions-panel{order:3}.ra-wide-panel{order:2}}
@media(max-width:960px){.ra-summary-grid{grid-template-columns:1fr}.student-welcome{align-items:stretch;flex-direction:column}.student-account-card{width:100%}}
@media(max-width:760px){.wd-banner{align-items:flex-start;flex-wrap:wrap;padding:18px}.student-card-grid{grid-template-columns:1fr}.ra-toolbar,.student-section-head{padding:18px}.ra-summary-card{min-height:118px}.ra-panel-header{align-items:flex-start;flex-direction:column}.ud-pinned-modal-body{grid-template-columns:1fr;padding:18px}.ud-modal{padding:10px}.ud-modal-head{padding:18px}.ud-filter-toolbar{padding:14px 18px;align-items:stretch;flex-direction:column}.ud-filter-controls{width:100%}.ud-filter-select{flex:1 1 150px}.student-card-grid{padding:18px}}
@media(max-width:520px){.student-paper-row{grid-template-columns:1fr}.student-row-action{justify-self:start}.ra-summary-copy strong{font-size:30px}.student-account-card{grid-template-columns:38px minmax(0,1fr)}}
@media(prefers-reduced-motion:reduce){.ra-summary-card,.ra-summary-action svg,.student-row-action,.ra-link-btn{transition:none}}

/* Saved Research - inline search bar */
.sr-search-toolbar{padding:14px 20px;background:#fff;border-bottom:1px solid #f0eaf9}
.sr-search-box{position:relative;display:flex;align-items:center}
.sr-search-icon{position:absolute;left:14px;color:#8c7aa8;pointer-events:none}
.sr-search-input{width:100%;padding:11px 40px 11px 40px;border-radius:12px;border:1.5px solid #e5d8f6;background:#fbf9ff;font-size:13.5px;font-family:inherit;color:#2b0d4e;transition:border-color .18s ease,box-shadow .18s ease}
.sr-search-input:focus{outline:none;border-color:#7c3aed;box-shadow:0 0 0 3px rgba(124,58,237,.12);background:#fff}
.sr-search-input::placeholder{color:#a898c2;font-weight:600}
.sr-search-clear{position:absolute;right:10px;width:24px;height:24px;border:0;border-radius:50%;background:#eee5f8;color:#6d28d9;font-size:17px;line-height:1;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .14s ease}
.sr-search-clear:hover{background:#d8c7f3}
.sr-filter-status{padding:8px 0 0;color:#8c7aa8;font-size:12px;font-weight:700}
.sr-no-results{grid-column:1/-1}
/* Saved Research - pagination */
.sr-pagination-wrap{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;padding:16px 20px 20px;border-top:1px solid #f0eaf9;background:#fff}
.sr-pagination-info{color:#8b7aa8;font-size:13px;font-weight:700}
.sr-pagination-nav{display:flex;align-items:center;gap:6px}
.sr-pag-btn{min-width:36px;height:36px;padding:0 10px;border-radius:10px;border:1.5px solid #e5d8f6;background:#fff;color:#3b0f7a;font-size:13px;font-weight:800;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;transition:background .14s ease,border-color .14s ease}
.sr-pag-btn:hover{background:#f5f1fb;border-color:#bda6df}
.sr-pag-btn.is-active{background:#43216f;border-color:#43216f;color:#fff}
.sr-pag-btn:disabled,.sr-pag-btn.is-disabled{opacity:.45;cursor:not-allowed;pointer-events:none}
.sr-pag-ellipsis{padding:0 6px;color:#8c7aa8;font-weight:800}
</style>
@endsection

@push('scripts')
<script>
const dashboardSectionTitles = {
    overview: 'My Dashboard',
    saved: 'Saved Research',
};

function showStudentDashboardSection(sectionKey, updateHash = true) {
    const target = document.querySelector(`[data-dashboard-section="${sectionKey}"]`);
    if (!target) return;

    document.querySelectorAll('[data-dashboard-section]').forEach((section) => {
        const isActive = section === target;
        section.hidden = !isActive;
        section.classList.toggle('is-active', isActive);
    });

    document.querySelectorAll('[data-dashboard-nav]').forEach((item) => {
        item.classList.toggle('active', item.dataset.dashboardNav === sectionKey);
    });

    document.body.classList.remove('admin-sidebar-open');
    document.querySelector('.admin-main')?.scrollTo({ top: 0, behavior: 'smooth' });

    if (updateHash) {
        history.replaceState(null, '', `${window.location.pathname}#${sectionKey}`);
    }
}

document.querySelectorAll('[data-dashboard-nav], [data-dashboard-jump]').forEach((trigger) => {
    trigger.addEventListener('click', (event) => {
        event.preventDefault();
        const sectionKey = trigger.dataset.dashboardNav || trigger.dataset.dashboardJump;
        showStudentDashboardSection(sectionKey);
    });
});

const requestedSection = window.location.hash.replace('#', '') || 'overview';
showStudentDashboardSection(dashboardSectionTitles[requestedSection] ? requestedSection : 'overview', false);

const pinnedPapersModal = document.getElementById('pinnedPapersModal');

function syncDashboardModalOverflow() {
    const hasOpenModal = pinnedPapersModal?.classList.contains('is-visible');
    document.body.style.overflow = hasOpenModal ? 'hidden' : '';
}

function togglePinnedPapersModal(shouldOpen) {
    if (!pinnedPapersModal) return;
    pinnedPapersModal.classList.toggle('is-visible', shouldOpen);
    pinnedPapersModal.setAttribute('aria-hidden', shouldOpen ? 'false' : 'true');
    syncDashboardModalOverflow();

    if (shouldOpen && typeof window.applySavedResearchesFilterAndPaginate === 'function') {
        window.applySavedResearchesFilterAndPaginate(1);
    }
}

document.querySelectorAll('[data-open-pinned]').forEach((button) => {
    button.addEventListener('click', (event) => {
        event.preventDefault();
        togglePinnedPapersModal(true);
    });
});

document.querySelectorAll('[data-close-pinned]').forEach((button) => {
    button.addEventListener('click', () => togglePinnedPapersModal(false));
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && pinnedPapersModal?.classList.contains('is-visible')) {
        togglePinnedPapersModal(false);
    }
});

(function initSavedResearchesPaginationAndFilter() {
    const cards = Array.from(document.querySelectorAll('#savedItemsContainer [data-saved-card]'));
    const totalCount = cards.length;
    const PAGE_SIZE = 10;
    let currentPage = 1;
    let filteredCards = [];

    if (totalCount <= 10) {
        cards.forEach((card) => { card.style.display = ''; });
        const paginationWrap = document.getElementById('savedPaginationWrap');
        if (paginationWrap) paginationWrap.style.display = 'none';
        return;
    }

    const searchInput = document.getElementById('savedSearchInput');
    const searchClear = document.getElementById('savedSearchClear');
    const deptFilter = document.getElementById('savedDeptFilter');
    const yearFilter = document.getElementById('savedYearFilter');
    const resetBtn = document.getElementById('savedResetFilters');
    const emptyResetBtn = document.getElementById('savedEmptyResetBtn');
    const noResultsState = document.getElementById('savedNoResultsState');
    const matchCountLabel = document.getElementById('savedMatchCount');
    const paginationWrap = document.getElementById('savedPaginationWrap');
    const paginationNav = document.getElementById('savedPaginationNav');
    const paginationInfo = document.getElementById('savedPaginationInfo');
    const modalBody = document.getElementById('savedItemsContainer');

    function renderPaginationButtons(totalPages, activePage) {
        if (!paginationNav) return;
        paginationNav.innerHTML = '';

        const prevBtn = document.createElement('button');
        prevBtn.type = 'button';
        prevBtn.className = `ud-pag-btn ud-pag-nav-btn ${activePage <= 1 ? 'is-disabled' : ''}`;
        prevBtn.textContent = 'Prev';
        prevBtn.disabled = activePage <= 1;
        prevBtn.addEventListener('click', () => {
            if (activePage > 1) {
                applyFilterAndPaginate(activePage - 1);
                modalBody?.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });
        paginationNav.appendChild(prevBtn);

        const pages = [];
        if (totalPages <= 7) {
            for (let i = 1; i <= totalPages; i++) pages.push(i);
        } else {
            pages.push(1);
            if (activePage > 3) pages.push('...');
            const start = Math.max(2, activePage - 1);
            const end = Math.min(totalPages - 1, activePage + 1);
            for (let i = start; i <= end; i++) {
                if (!pages.includes(i)) pages.push(i);
            }
            if (activePage < totalPages - 2) pages.push('...');
            if (!pages.includes(totalPages)) pages.push(totalPages);
        }

        pages.forEach((p) => {
            if (p === '...') {
                const ellipsis = document.createElement('span');
                ellipsis.className = 'ud-pag-ellipsis';
                ellipsis.textContent = '...';
                paginationNav.appendChild(ellipsis);
            } else {
                const pageBtn = document.createElement('button');
                pageBtn.type = 'button';
                pageBtn.className = `ud-pag-btn ${p === activePage ? 'is-active' : ''}`;
                pageBtn.textContent = p;
                pageBtn.addEventListener('click', () => {
                    applyFilterAndPaginate(p);
                    modalBody?.scrollTo({ top: 0, behavior: 'smooth' });
                });
                paginationNav.appendChild(pageBtn);
            }
        });

        const nextBtn = document.createElement('button');
        nextBtn.type = 'button';
        nextBtn.className = `ud-pag-btn ud-pag-nav-btn ${activePage >= totalPages ? 'is-disabled' : ''}`;
        nextBtn.textContent = 'Next';
        nextBtn.disabled = activePage >= totalPages;
        nextBtn.addEventListener('click', () => {
            if (activePage < totalPages) {
                applyFilterAndPaginate(activePage + 1);
                modalBody?.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });
        paginationNav.appendChild(nextBtn);
    }

    function applyFilterAndPaginate(page = 1) {
        currentPage = page;
        const query = (searchInput?.value || '').trim().toLowerCase();
        const selectedDept = (deptFilter?.value || '').trim().toLowerCase();
        const selectedYear = (yearFilter?.value || '').trim();

        if (searchClear) {
            searchClear.style.display = query.length > 0 ? 'flex' : 'none';
        }

        filteredCards = cards.filter((card) => {
            const title = card.getAttribute('data-title') || '';
            const author = card.getAttribute('data-author') || '';
            const dept = card.getAttribute('data-dept') || '';
            const year = card.getAttribute('data-year') || '';
            const keywords = card.getAttribute('data-keywords') || '';
            const type = card.getAttribute('data-type') || '';

            const matchesQuery = !query ||
                title.includes(query) ||
                author.includes(query) ||
                dept.includes(query) ||
                keywords.includes(query) ||
                type.includes(query);

            const matchesDept = !selectedDept || dept.includes(selectedDept);
            const matchesYear = !selectedYear || year === selectedYear;

            return matchesQuery && matchesDept && matchesYear;
        });

        const totalFiltered = filteredCards.length;
        const totalPages = Math.ceil(totalFiltered / PAGE_SIZE);

        if (currentPage > totalPages && totalPages > 0) {
            currentPage = totalPages;
        }

        cards.forEach((card) => { card.style.display = 'none'; });

        if (totalFiltered === 0) {
            if (noResultsState) noResultsState.style.display = 'flex';
            if (paginationWrap) paginationWrap.style.display = 'none';
            if (matchCountLabel) matchCountLabel.textContent = 'No matching researches found';
            return;
        }

        if (noResultsState) noResultsState.style.display = 'none';

        const startIndex = (currentPage - 1) * PAGE_SIZE;
        const endIndex = Math.min(startIndex + PAGE_SIZE, totalFiltered);

        for (let i = startIndex; i < endIndex; i++) {
            filteredCards[i].style.display = '';
        }

        const rangeSummary = `Showing ${startIndex + 1}-${endIndex} of ${totalFiltered} saved ${totalFiltered === 1 ? 'research' : 'researches'}`;
        if (matchCountLabel) matchCountLabel.textContent = rangeSummary;
        if (paginationInfo) paginationInfo.textContent = rangeSummary;

        if (totalFiltered > PAGE_SIZE) {
            if (paginationWrap) paginationWrap.style.display = 'flex';
            renderPaginationButtons(totalPages, currentPage);
        } else {
            if (paginationWrap) paginationWrap.style.display = 'none';
        }
    }

    function resetFilters() {
        if (searchInput) searchInput.value = '';
        if (deptFilter) deptFilter.value = '';
        if (yearFilter) yearFilter.value = '';
        applyFilterAndPaginate(1);
    }

    window.applySavedResearchesFilterAndPaginate = applyFilterAndPaginate;

    if (searchInput) searchInput.addEventListener('input', () => applyFilterAndPaginate(1));
    if (searchClear) {
        searchClear.addEventListener('click', () => {
            if (searchInput) searchInput.value = '';
            applyFilterAndPaginate(1);
            searchInput?.focus();
        });
    }
    if (deptFilter) deptFilter.addEventListener('change', () => applyFilterAndPaginate(1));
    if (yearFilter) yearFilter.addEventListener('change', () => applyFilterAndPaginate(1));
    if (resetBtn) resetBtn.addEventListener('click', resetFilters);
    if (emptyResetBtn) emptyResetBtn.addEventListener('click', resetFilters);

    applyFilterAndPaginate(1);
})();

/* ── Saved Research Section: inline search + pagination ── */
(function initSavedResearchInlineSearch() {
    const cards = Array.from(document.querySelectorAll('#srCardsContainer [data-sr-card]'));
    const totalCount = cards.length;
    if (totalCount === 0) return; // nothing to search/paginate

    const PAGE_SIZE = 10;
    let currentPage = 1;
    let filteredCards = [];

    const searchInput = document.getElementById('srSearchInput');
    const searchClear = document.getElementById('srSearchClear');
    const filterStatus = document.getElementById('srFilterStatus');
    const matchCountLabel = document.getElementById('srMatchCount');
    const noResults = document.getElementById('srNoResults');
    const clearFiltersBtn = document.getElementById('srClearFiltersBtn');
    const paginationWrap = document.getElementById('srPaginationWrap');
    const paginationNav = document.getElementById('srPaginationNav');
    const paginationInfo = document.getElementById('srPaginationInfo');
    const container = document.getElementById('srCardsContainer');
    const countLabel = document.getElementById('savedCountLabel');

    function renderPagButtons(totalPages, activePage) {
        if (!paginationNav) return;
        paginationNav.innerHTML = '';

        const prevBtn = document.createElement('button');
        prevBtn.type = 'button';
        prevBtn.className = `sr-pag-btn ${activePage <= 1 ? 'is-disabled' : ''}`;
        prevBtn.textContent = 'Prev';
        prevBtn.disabled = activePage <= 1;
        prevBtn.addEventListener('click', () => { if (activePage > 1) applySearch(activePage - 1); });
        paginationNav.appendChild(prevBtn);

        const pages = [];
        if (totalPages <= 7) {
            for (let i = 1; i <= totalPages; i++) pages.push(i);
        } else {
            pages.push(1);
            if (activePage > 3) pages.push('...');
            const start = Math.max(2, activePage - 1);
            const end = Math.min(totalPages - 1, activePage + 1);
            for (let i = start; i <= end; i++) { if (!pages.includes(i)) pages.push(i); }
            if (activePage < totalPages - 2) pages.push('...');
            if (!pages.includes(totalPages)) pages.push(totalPages);
        }

        pages.forEach((p) => {
            if (p === '...') {
                const el = document.createElement('span');
                el.className = 'sr-pag-ellipsis';
                el.textContent = '...';
                paginationNav.appendChild(el);
            } else {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = `sr-pag-btn ${p === activePage ? 'is-active' : ''}`;
                btn.textContent = p;
                btn.addEventListener('click', () => applySearch(p));
                paginationNav.appendChild(btn);
            }
        });

        const nextBtn = document.createElement('button');
        nextBtn.type = 'button';
        nextBtn.className = `sr-pag-btn ${activePage >= totalPages ? 'is-disabled' : ''}`;
        nextBtn.textContent = 'Next';
        nextBtn.disabled = activePage >= totalPages;
        nextBtn.addEventListener('click', () => { if (activePage < totalPages) applySearch(activePage + 1); });
        paginationNav.appendChild(nextBtn);
    }

    function applySearch(page = 1) {
        currentPage = page;
        const query = (searchInput?.value || '').trim().toLowerCase();

        if (searchClear) searchClear.style.display = query.length > 0 ? 'flex' : 'none';

        filteredCards = cards.filter((card) => {
            if (!query) return true;
            const title = card.getAttribute('data-title') || '';
            const author = card.getAttribute('data-author') || '';
            const keywords = card.getAttribute('data-keywords') || '';
            const dept = card.getAttribute('data-dept') || '';
            const type = card.getAttribute('data-type') || '';
            return title.includes(query) || author.includes(query) || keywords.includes(query) || dept.includes(query) || type.includes(query);
        });

        const totalFiltered = filteredCards.length;
        const totalPages = Math.ceil(totalFiltered / PAGE_SIZE);
        if (currentPage > totalPages && totalPages > 0) currentPage = totalPages;

        // Hide all cards first
        cards.forEach((c) => { c.style.display = 'none'; });

        if (totalFiltered === 0) {
            if (noResults) noResults.style.display = 'flex';
            if (paginationWrap) paginationWrap.style.display = 'none';
            if (filterStatus) { filterStatus.style.display = 'block'; matchCountLabel.textContent = 'No matching researches found'; }
            return;
        }

        if (noResults) noResults.style.display = 'none';

        const startIdx = (currentPage - 1) * PAGE_SIZE;
        const endIdx = Math.min(startIdx + PAGE_SIZE, totalFiltered);

        for (let i = startIdx; i < endIdx; i++) {
            filteredCards[i].style.display = '';
        }

        const rangeSummary = `Showing ${startIdx + 1}\u2013${endIdx} of ${totalFiltered} saved research${totalFiltered === 1 ? '' : 'es'}`;
        if (filterStatus && query) {
            filterStatus.style.display = 'block';
            matchCountLabel.textContent = rangeSummary;
        } else if (filterStatus && !query) {
            filterStatus.style.display = totalFiltered > PAGE_SIZE ? 'block' : 'none';
            if (matchCountLabel) matchCountLabel.textContent = rangeSummary;
        }

        if (countLabel) {
            const label = query ? `${totalFiltered} result${totalFiltered === 1 ? '' : 's'} found` : `${totalCount} saved paper${totalCount === 1 ? '' : 's'}`;
            countLabel.textContent = label;
        }

        if (totalFiltered > PAGE_SIZE) {
            if (paginationWrap) paginationWrap.style.display = 'flex';
            renderPagButtons(totalPages, currentPage);
            if (paginationInfo) paginationInfo.textContent = rangeSummary;
        } else {
            if (paginationWrap) paginationWrap.style.display = 'none';
        }

        // Scroll container to top on page change
        if (page !== 1) {
            container?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    function clearSearch() {
        if (searchInput) searchInput.value = '';
        applySearch(1);
        searchInput?.focus();
    }

    // Event listeners
    if (searchInput) searchInput.addEventListener('input', () => applySearch(1));
    if (searchClear) searchClear.addEventListener('click', clearSearch);
    if (clearFiltersBtn) clearFiltersBtn.addEventListener('click', clearSearch);

    // Initial render
    applySearch(1);
})();
</script>
@endpush
