@extends('layouts.app')

@section('title', 'My Dashboard — Ube Repository')

@section('content')
@php
    $isFacultyAccount = $user->role === 'researcher' && is_null($user->graduation_year);
    $isStudentResearcher = $user->role === 'researcher' && ! is_null($user->graduation_year);
    $dashboardRoleLabel = $user->isDepartmentDean() ? 'Dean' : ($isFacultyAccount ? 'Faculty' : ($isStudentResearcher ? 'Student Researcher' : ucfirst($user->role)));
@endphp

<div class="ud-shell">
    <div class="ud-layout">

        {{-- ══ SIDEBAR ══ --}}
        <aside class="ud-sidebar">
            <div class="ud-sidebar-card">
                <div class="ud-sidebar-avatar-wrap">
                    @if($user->profile_photo)
                        <img src="{{ asset('storage/' . $user->profile_photo) }}" alt="{{ $user->name }}" class="ud-sidebar-avatar-img">
                    @else
                        <div class="ud-sidebar-avatar-initial">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                    @endif
                </div>
                <span class="ud-sidebar-kicker">User Panel</span>
                <h2>{{ $user->name }}</h2>
                <p>{{ $user->department ?? 'PHILCST User' }}</p>
                <div class="ud-sidebar-pills">
                    <span class="ud-pill">{{ $dashboardRoleLabel }}</span>
                    <span class="ud-pill is-approved">Active</span>
                </div>
            </div>

            <nav class="ud-side-nav">
                <a href="#dashboard-top" class="ud-side-link is-active" data-dashboard-link>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    <span>Dashboard Overview</span>
                </a>
                <button type="button" class="ud-side-link" data-open-pinned>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"/></svg>
                    <span>Saved Researches</span>
                    <span class="ud-side-badge">{{ $pinnedResearches->count() }}</span>
                </button>
                <a href="{{ route('home') }}" class="ud-side-link">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <span>Browse Repository</span>
                </a>
            </nav>
        </aside>

        {{-- ══ MAIN CONTENT ══ --}}
        <div class="ud-main" id="dashboard-top">

            {{-- Hero Banner --}}
            <section class="ud-hero">
                <div>
                    <span class="ud-kicker">Research Portal</span>
                    <h1>Welcome, {{ Str::before($user->name, ' ') }}!</h1>
                    <p>Access published academic works, discover latest departmental research, and manage your saved researches from your personal portal.</p>
                </div>
                <div class="ud-status-stack">
                    <span class="ud-pill">{{ $dashboardRoleLabel }}</span>
                    <span class="ud-pill is-approved">Active Account</span>
                    @if($user->department)
                        <span class="ud-pill">{{ $user->department }}</span>
                    @endif
                </div>
            </section>

            {{-- Stat Cards (Balanced 2-Column Grid) --}}
            <section class="ud-stats">
                {{-- Clickable Saved Researches Card --}}
                <article class="ud-stat-card ud-stat-card--clickable" data-open-pinned role="button" tabindex="0" title="Click to view saved researches">
                    <div class="ud-stat-top">
                        <span class="ud-stat-label">Saved Researches</span>
                        <div class="ud-stat-icon-wrap" aria-hidden="true">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"/></svg>
                        </div>
                    </div>
                    <strong>{{ number_format($stats['pinned']) }}</strong>
                    <div class="ud-stat-action-row">
                        <small>Researches saved to your list</small>
                        <span class="ud-stat-arrow">Open list →</span>
                    </div>
                </article>

                {{-- Department / Repository Card --}}
                @if($user->department)
                    <a href="{{ route('research.department', $user->department) }}" class="ud-stat-card ud-stat-card--clickable" title="Browse department researches">
                        <div class="ud-stat-top">
                            <span class="ud-stat-label">Department Repository</span>
                            <div class="ud-stat-icon-wrap" aria-hidden="true">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                            </div>
                        </div>
                        <strong>{{ number_format($stats['department_researches']) }}</strong>
                        <div class="ud-stat-action-row">
                            <small>{{ Str::limit($user->department, 32) }}</small>
                            <span class="ud-stat-arrow">Browse Department →</span>
                        </div>
                    </a>
                @else
                    <a href="{{ route('home') }}" class="ud-stat-card ud-stat-card--clickable" title="Explore repository">
                        <div class="ud-stat-top">
                            <span class="ud-stat-label">Explore Repository</span>
                            <div class="ud-stat-icon-wrap" aria-hidden="true">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            </div>
                        </div>
                        <strong style="font-size:28px; padding-top:4px;">Browse All</strong>
                        <div class="ud-stat-action-row">
                            <small>Search academic publications</small>
                            <span class="ud-stat-arrow">Explore All →</span>
                        </div>
                    </a>
                @endif
            </section>

            {{-- Saved Researches Section --}}
            <section class="ud-card">
                <div class="ud-card-head ud-card-head--split">
                    <div>
                        <span class="ud-mini-kicker">Personal Collection</span>
                        <h2>Saved Researches</h2>
                        <p>Quick access to research papers you have saved for reading and citation.</p>
                    </div>
                    @if($pinnedResearches->count() > 0)
                        <button type="button" class="ud-btn-secondary" data-open-pinned>
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"/></svg>
                            View All ({{ $pinnedResearches->count() }})
                        </button>
                    @endif
                </div>

                <div class="ud-saved-grid">
                    @forelse($pinnedResearches->take(6) as $research)
                        <article class="ud-pinned-card">
                            <div class="ud-pinned-badge-row">
                                <span class="ud-pinned-type">{{ $research->getSubmissionCategoryLabel() }}: {{ $research->getTypeLabel() }}</span>
                                <span class="ud-pinned-year">{{ $research->year_published }}</span>
                            </div>
                            <h3>
                                <a href="{{ route('research.show', $research) }}">{{ $research->title }}</a>
                            </h3>
                            <p>{{ Str::limit($research->abstract, 140) }}</p>
                            <div class="ud-pinned-meta">
                                <span><strong>Author:</strong> {{ $research->author_name }}</span>
                                @if($research->department)
                                    <span><strong>Dept:</strong> {{ Str::limit($research->department, 26) }}</span>
                                @endif
                            </div>
                            <div class="ud-pinned-footer">
                                <span>{{ number_format($research->view_count) }} views</span>
                                <a href="{{ route('research.show', $research) }}" class="ud-btn-view">Read Paper →</a>
                            </div>
                        </article>
                    @empty
                        <div class="ud-pinned-empty">
                            <div class="ud-pinned-empty-icon">
                                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"/></svg>
                            </div>
                            <strong>No saved researches yet</strong>
                            <p>You haven't saved any research papers yet. Browse the repository and click "Pin / Save" on any research to keep it here for quick reference.</p>
                            <a href="{{ route('home') }}" class="ud-btn-primary" style="margin-top:8px;">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                                Browse Research Repository
                            </a>
                        </div>
                    @endforelse
                </div>
            </section>

        </div>
    </div>
</div>

{{-- ══ SAVED RESEARCHES MODAL ══ --}}
<div class="ud-modal" id="pinnedPapersModal" aria-hidden="true">
    <div class="ud-modal-backdrop" data-close-pinned></div>
    <div class="ud-modal-dialog ud-modal-dialog--wide" role="dialog" aria-modal="true" aria-labelledby="pinnedPapersTitle">
        <div class="ud-modal-head">
            <div>
                <span class="ud-mini-kicker">Saved Researches</span>
                <h2 id="pinnedPapersTitle">Saved Researches</h2>
                <p id="pinnedCountSubtitle">{{ $pinnedResearches->count() }} saved research paper{{ $pinnedResearches->count() === 1 ? '' : 's' }} in your account.</p>
            </div>
            <button type="button" class="ud-modal-close" data-close-pinned aria-label="Close saved researches modal">×</button>
        </div>

        @if($pinnedResearches->count() > 10)
            {{-- Filter Toolbar (Active when saved records exceed 10) --}}
            <div class="ud-filter-toolbar" id="savedFilterToolbar">
                <div class="ud-search-box">
                    <svg class="ud-search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" id="savedSearchInput" class="ud-filter-input" placeholder="Search by title, author, keyword..." autocomplete="off">
                    <button type="button" id="savedSearchClear" class="ud-search-clear" aria-label="Clear search" style="display: none;">×</button>
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

                    <button type="button" id="savedResetFilters" class="ud-btn-filter-reset" title="Reset all filters">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                        <span>Reset</span>
                    </button>
                </div>
            </div>

            {{-- Filter Match Status --}}
            <div class="ud-filter-status" id="savedFilterStatus">
                <span id="savedMatchCount">Showing 1–10 of {{ $pinnedResearches->count() }} researches</span>
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
                    <h3>
                        <a href="{{ route('research.show', $research) }}">{{ $research->title }}</a>
                    </h3>
                    <p>{{ Str::limit($research->abstract, 150) }}</p>
                    <div class="ud-pinned-meta">
                        <span><strong>Author:</strong> {{ $research->author_name }}</span>
                        @if($research->department)
                            <span><strong>Dept:</strong> {{ Str::limit($research->department, 32) }}</span>
                        @endif
                    </div>
                    <div class="ud-pinned-footer">
                        <span>{{ number_format($research->view_count) }} views</span>
                        <a href="{{ route('research.show', $research) }}" class="ud-btn-view">View Details →</a>
                    </div>
                </article>
            @empty
                <div class="ud-pinned-empty">
                    <div class="ud-pinned-empty-icon">
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"/></svg>
                    </div>
                    <strong>No saved researches yet</strong>
                    <p>Save research papers from the details page to keep them here for quick access.</p>
                    <a href="{{ route('home') }}" class="ud-btn-primary">Browse Research</a>
                </div>
            @endforelse

            {{-- Empty search result state --}}
            <div class="ud-pinned-empty ud-search-no-results" id="savedNoResultsState" style="display: none;">
                <div class="ud-pinned-empty-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </div>
                <strong>No matching researches found</strong>
                <p>No saved research matches your search criteria or filters.</p>
                <button type="button" class="ud-btn-secondary" id="savedEmptyResetBtn" style="margin-top: 6px;">
                    Clear Filters
                </button>
            </div>
        </div>

        {{-- Pagination Container --}}
        <div class="ud-pagination-wrap" id="savedPaginationWrap" style="display: none;">
            <div class="ud-pagination-info" id="savedPaginationInfo">Showing 1–10 of {{ $pinnedResearches->count() }}</div>
            <div class="ud-pagination-nav" id="savedPaginationNav"></div>
        </div>
    </div>
</div>

<style>
.ud-shell { max-width: 1220px; margin: 0 auto; padding: 34px 24px 54px; }
html { scroll-behavior: smooth; }
.ud-layout { display: grid; grid-template-columns: 290px minmax(0, 1fr); gap: 24px; align-items: start; }
.ud-sidebar { position: sticky; top: 88px; }

/* SIDEBAR CARD */
.ud-sidebar-card {
    padding: 26px 22px;
    border-radius: 24px;
    background: linear-gradient(145deg, #2b0d4e 0%, #522087 100%);
    color: #fff;
    box-shadow: 0 20px 40px rgba(59,15,122,.16);
    margin-bottom: 16px;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
}
.ud-sidebar-avatar-wrap {
    margin-bottom: 14px;
}
.ud-sidebar-avatar-img,
.ud-sidebar-avatar-initial {
    width: 78px;
    height: 78px;
    border-radius: 50%;
}
.ud-sidebar-avatar-img {
    object-fit: cover;
    border: 3px solid rgba(255,255,255,.9);
    box-shadow: 0 10px 22px rgba(22,6,50,.3);
}
.ud-sidebar-avatar-initial {
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #7c3aed, #a855f7);
    color: #fff;
    font-size: 32px;
    font-weight: 800;
    border: 3px solid rgba(255,255,255,.9);
    box-shadow: 0 10px 22px rgba(22,6,50,.3);
}
.ud-sidebar-kicker {
    display: inline-flex;
    padding: 5px 12px;
    border-radius: 999px;
    background: rgba(255,255,255,.14);
    font-size: 10.5px;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
    margin-bottom: 10px;
    color: rgba(255,255,255,.9);
}
.ud-sidebar-card h2 { margin: 0 0 6px; font-size: 22px; line-height: 1.15; font-weight: 800; color: #fff; }
.ud-sidebar-card p { margin: 0 0 14px; color: rgba(255,255,255,.8); font-size: 13px; line-height: 1.5; }
.ud-sidebar-pills { display: flex; flex-wrap: wrap; gap: 8px; justify-content: center; }

/* SIDEBAR NAV */
.ud-side-nav { display: grid; gap: 10px; }
.ud-side-nav > .ud-side-link {
    display: flex;
    align-items: center;
    gap: 12px;
    width: 100%;
    text-align: left;
    padding: 13px 18px;
    border-radius: 16px;
    background: #fff;
    border: 1px solid #efe7fb;
    box-shadow: 0 6px 18px rgba(59,15,122,.04);
    text-decoration: none;
    font-family: var(--font-body);
    font-size: 14px;
    line-height: 1.4;
    font-weight: 700;
    color: #36125f !important;
    cursor: pointer;
    transition: all .18s ease;
}
.ud-side-nav > .ud-side-link svg {
    flex-shrink: 0;
    color: #7c3aed;
    transition: transform .18s ease;
}
.ud-side-nav > .ud-side-link:hover {
    background: #f7f1ff;
    border-color: #d8c4fa;
    color: #5b21b6 !important;
    transform: translateX(3px);
}
.ud-side-nav > .ud-side-link.is-active {
    background: #f3e8ff;
    border-color: #c4b5fd;
    color: #4c1d95 !important;
    box-shadow: 0 8px 20px rgba(109,40,217,.12);
}
.ud-side-badge {
    margin-left: auto;
    padding: 2px 9px;
    border-radius: 999px;
    background: #7c3aed;
    color: #fff;
    font-size: 11px;
    font-weight: 800;
}

/* MAIN BODY */
.ud-main { min-width: 0; }
.ud-hero {
    display: flex;
    justify-content: space-between;
    gap: 24px;
    align-items: flex-start;
    padding: 32px 36px;
    border-radius: 26px;
    background: linear-gradient(135deg, #2a0d4f 0%, #5c2093 60%, #7c3aed 100%);
    color: #fff;
    box-shadow: 0 24px 48px rgba(59,15,122,.18);
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
}
.ud-hero::after {
    content: '';
    position: absolute;
    right: -40px;
    bottom: -40px;
    width: 220px;
    height: 220px;
    background: radial-gradient(circle, rgba(255,255,255,.14), transparent 70%);
    pointer-events: none;
}
.ud-kicker {
    display: inline-flex;
    padding: 5px 12px;
    border-radius: 999px;
    background: rgba(255,255,255,.14);
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
    margin-bottom: 12px;
    color: #f3e8ff;
}
.ud-hero h1 { margin: 0 0 10px; font-size: 36px; line-height: 1.05; letter-spacing: -.03em; font-weight: 800; color: #fff; }
.ud-hero p { margin: 0; max-width: 600px; color: rgba(255,255,255,.84); line-height: 1.6; font-size: 14.5px; }
.ud-status-stack { display: flex; flex-wrap: wrap; gap: 8px; justify-content: flex-end; position: relative; z-index: 1; }
.ud-pill { display: inline-flex; padding: 7px 14px; border-radius: 999px; background: rgba(255,255,255,.14); font-size: 12px; font-weight: 700; color: #fff; }
.ud-pill.is-approved { background: #dcfce7; color: #166534; }

/* STAT CARDS */
.ud-stats { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 20px; margin-bottom: 24px; }
.ud-stat-card {
    padding: 24px 26px;
    border-radius: 22px;
    background: #fff;
    border: 1.5px solid #efe7fb;
    box-shadow: 0 10px 26px rgba(59,15,122,.05);
    display: flex;
    flex-direction: column;
    text-decoration: none;
    color: inherit;
    transition: all .2s ease;
    position: relative;
    overflow: hidden;
}
.ud-stat-card--clickable {
    cursor: pointer;
    user-select: none;
}
.ud-stat-card--clickable:hover {
    transform: translateY(-3px);
    border-color: #c4b5fd;
    box-shadow: 0 16px 36px rgba(109,40,217,.14);
}
.ud-stat-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
}
.ud-stat-label {
    display: block;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: #8c7aa8;
}
.ud-stat-icon-wrap {
    width: 42px;
    height: 42px;
    border-radius: 14px;
    background: #f5effe;
    color: #7c3aed;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all .2s ease;
}
.ud-stat-card--clickable:hover .ud-stat-icon-wrap {
    background: #7c3aed;
    color: #fff;
    transform: scale(1.08);
}
.ud-stat-card strong {
    display: block;
    font-size: 38px;
    line-height: 1;
    color: #240a42;
    font-weight: 800;
    letter-spacing: -.02em;
    margin-bottom: 12px;
}
.ud-stat-action-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-top: auto;
    padding-top: 10px;
    border-top: 1px solid #f6f1fd;
}
.ud-stat-action-row small { color: #85779d; font-size: 13px; font-weight: 500; }
.ud-stat-arrow { font-size: 12.5px; font-weight: 800; color: #7c3aed; }

/* CARD & GRID */
.ud-card {
    background: #fff;
    border: 1px solid #efe7fb;
    border-radius: 24px;
    box-shadow: 0 12px 30px rgba(59,15,122,.05);
    overflow: hidden;
}
.ud-card-head { padding: 24px 28px 18px; border-bottom: 1px solid #f2ecfb; }
.ud-card-head--split { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
.ud-card-head h2 { margin: 0 0 6px; font-size: 22px; color: #23093f; font-weight: 800; }
.ud-card-head p { margin: 0; color: #8c7ba8; font-size: 13.5px; }
.ud-mini-kicker {
    display: inline-flex;
    padding: 4px 10px;
    border-radius: 999px;
    background: #f2eaff;
    color: #6d28d9;
    font-size: 10.5px;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
    margin-bottom: 10px;
}

/* SAVED RESEARCHES GRID */
.ud-saved-grid {
    padding: 24px 28px;
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
}
.ud-pinned-card {
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding: 20px;
    border-radius: 20px;
    background: #fdfbff;
    border: 1px solid #efe7fb;
    box-shadow: 0 8px 22px rgba(59,15,122,.04);
    transition: all .2s ease;
}
.ud-pinned-card:hover {
    border-color: #d4bbf9;
    box-shadow: 0 12px 28px rgba(109,40,217,.08);
    transform: translateY(-2px);
}
.ud-pinned-badge-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
}
.ud-pinned-type {
    align-self: flex-start;
    padding: 5px 11px;
    border-radius: 999px;
    background: #f2eaff;
    color: #6d28d9;
    font-size: 11px;
    font-weight: 800;
}
.ud-pinned-year {
    font-size: 12px;
    font-weight: 700;
    color: #8c7aa8;
}
.ud-pinned-card h3 { margin: 0; font-size: 17px; line-height: 1.35; font-weight: 700; }
.ud-pinned-card h3 a { color: #23093f; text-decoration: none; transition: color .15s; }
.ud-pinned-card h3 a:hover { color: #6d28d9; }
.ud-pinned-card p { margin: 0; color: #6e5e85; font-size: 13px; line-height: 1.6; }
.ud-pinned-meta { display: flex; flex-wrap: wrap; gap: 12px; color: #8b7aaa; font-size: 12.5px; }
.ud-pinned-meta strong { color: #431f6d; }
.ud-pinned-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-top: auto;
    padding-top: 14px;
    border-top: 1px solid #efe7fb;
    color: #9b8db6;
    font-size: 12.5px;
}
.ud-btn-view {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border-radius: 999px;
    background: #3b0f7a;
    color: #fff;
    text-decoration: none;
    font-size: 12.5px;
    font-weight: 800;
    transition: all .18s ease;
}
.ud-btn-view:hover {
    background: #250754;
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(59,15,122,.3);
}

/* EMPTY STATE */
.ud-pinned-empty {
    grid-column: 1 / -1;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 10px;
    padding: 48px 24px;
    border-radius: 20px;
    background: #faf7ff;
    border: 1.5px dashed #d8c7f3;
    color: #6b5b87;
}
.ud-pinned-empty-icon {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: #ede4fc;
    color: #7c3aed;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 4px;
}
.ud-pinned-empty strong { font-size: 19px; color: #240a42; }
.ud-pinned-empty p { margin: 0; max-width: 440px; line-height: 1.6; font-size: 13.5px; }

/* BUTTONS */
.ud-btn-primary {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 22px;
    border: none;
    border-radius: 14px;
    background: #3b0f7a;
    color: #fff;
    font-weight: 700;
    font-size: 13.5px;
    cursor: pointer;
    text-decoration: none;
    box-shadow: 0 10px 22px rgba(59,15,122,.24);
    transition: all .18s;
}
.ud-btn-primary:hover {
    background: #250754;
    transform: translateY(-1px);
}
.ud-btn-secondary {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    justify-content: center;
    padding: 10px 18px;
    border-radius: 14px;
    border: 1px solid #dac9f4;
    background: #fff;
    color: #5b21b6;
    font-weight: 700;
    font-size: 13px;
    text-decoration: none;
    cursor: pointer;
    transition: all .18s;
}
.ud-btn-secondary:hover {
    background: #f7f1ff;
    border-color: #c4b5fd;
}

/* MODAL */
.ud-modal { position: fixed; inset: 0; display: none; align-items: center; justify-content: center; padding: 24px; z-index: 1200; }
.ud-modal.is-visible { display: flex; }
.ud-modal-backdrop { position: absolute; inset: 0; background: rgba(21,8,43,.55); backdrop-filter: blur(4px); }
.ud-modal-dialog { position: relative; width: min(760px, 100%); max-height: calc(100vh - 48px); overflow: auto; border-radius: 28px; background: #fff; box-shadow: 0 24px 60px rgba(22,6,50,.28); display: flex; flex-direction: column; }
.ud-modal-dialog--wide { width: min(940px, 100%); }
.ud-modal-head { display: flex; justify-content: space-between; gap: 16px; padding: 26px 28px 18px; border-bottom: 1px solid #f0eaf9; }
.ud-modal-head h2 { margin: 0 0 6px; color: #23093f; font-size: 26px; font-weight: 800; }
.ud-modal-head p { margin: 0; color: #8c7ba8; font-size: 13.5px; }
.ud-modal-close { width: 42px; height: 42px; border: none; border-radius: 12px; background: #f6f1ff; color: #5b21b6; font-size: 26px; line-height: 1; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all .15s; }
.ud-modal-close:hover { background: #eddffa; color: #3b0f7a; }
.ud-pinned-modal-body { padding: 24px 28px 28px; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; overflow-y: auto; }

/* FILTER TOOLBAR */
.ud-filter-toolbar {
    padding: 14px 28px;
    background: #fbf9fe;
    border-bottom: 1px solid #f0eaf9;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}
.ud-search-box {
    position: relative;
    flex: 1 1 240px;
    min-width: 200px;
    display: flex;
    align-items: center;
}
.ud-search-icon {
    position: absolute;
    left: 14px;
    color: #8c7aa8;
    pointer-events: none;
}
.ud-filter-input {
    width: 100%;
    padding: 10px 36px 10px 38px;
    border-radius: 12px;
    border: 1.5px solid #e5d8f6;
    background: #fff;
    font-size: 13.5px;
    font-family: inherit;
    color: #2b0d4e;
    transition: all .18s;
}
.ud-filter-input:focus {
    outline: none;
    border-color: #7c3aed;
    box-shadow: 0 0 0 3px rgba(124,58,237,.12);
}
.ud-filter-input::placeholder {
    color: #a395ba;
}
.ud-search-clear {
    position: absolute;
    right: 10px;
    width: 22px;
    height: 22px;
    border: none;
    border-radius: 50%;
    background: #eee5f8;
    color: #6d28d9;
    font-size: 16px;
    line-height: 1;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}
.ud-search-clear:hover {
    background: #e2d1f8;
}
.ud-filter-controls {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.ud-filter-select {
    padding: 9px 30px 9px 12px;
    border-radius: 12px;
    border: 1.5px solid #e5d8f6;
    background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%237c3aed' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E") no-repeat right 10px center;
    font-size: 13px;
    font-family: inherit;
    font-weight: 600;
    color: #3b1464;
    cursor: pointer;
    appearance: none;
    -webkit-appearance: none;
    transition: all .18s;
}
.ud-filter-select:focus {
    outline: none;
    border-color: #7c3aed;
    box-shadow: 0 0 0 3px rgba(124,58,237,.12);
}
.ud-btn-filter-reset {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 8px 14px;
    border-radius: 12px;
    border: 1.5px solid #e5d8f6;
    background: #fff;
    color: #6d28d9;
    font-size: 12.5px;
    font-weight: 700;
    cursor: pointer;
    transition: all .18s;
}
.ud-btn-filter-reset:hover {
    background: #f3e8ff;
    border-color: #c4b5fd;
    color: #4c1d95;
}
.ud-filter-status {
    padding: 8px 28px 0;
    color: #8c7aa8;
    font-size: 12px;
    font-weight: 600;
}

/* PAGINATION */
.ud-pagination-wrap {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    padding: 16px 28px 20px;
    border-top: 1px solid #f0eaf9;
    background: #fff;
    margin-top: auto;
}
.ud-pagination-info {
    color: #8b7aa8;
    font-size: 13px;
    font-weight: 600;
}
.ud-pagination-nav {
    display: flex;
    align-items: center;
    gap: 6px;
}
.ud-pag-btn {
    min-width: 36px;
    height: 36px;
    padding: 0 10px;
    border-radius: 10px;
    border: 1.5px solid #e5d8f6;
    background: #fff;
    color: #3b0f7a;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all .15s ease;
}
.ud-pag-btn:hover:not(:disabled):not(.is-active) {
    background: #f7f1ff;
    border-color: #c4b5fd;
    color: #5b21b6;
    transform: translateY(-1px);
}
.ud-pag-btn.is-active {
    background: #3b0f7a;
    border-color: #3b0f7a;
    color: #fff;
    box-shadow: 0 4px 12px rgba(59,15,122,.24);
}
.ud-pag-btn:disabled,
.ud-pag-btn.is-disabled {
    opacity: .45;
    cursor: not-allowed;
    pointer-events: none;
}
.ud-pag-nav-btn {
    padding: 0 12px;
    font-weight: 700;
}
.ud-pag-ellipsis {
    padding: 0 6px;
    color: #8c7aa8;
    font-weight: 700;
}

/* RESPONSIVE */
@media (max-width: 1100px) {
    .ud-layout { grid-template-columns: 1fr; }
    .ud-sidebar { position: static; }
}
@media (max-width: 900px) {
    .ud-stats { grid-template-columns: 1fr; }
    .ud-hero { flex-direction: column; }
    .ud-saved-grid, .ud-pinned-modal-body { grid-template-columns: 1fr; }
    .ud-filter-toolbar { flex-direction: column; align-items: stretch; }
    .ud-filter-controls { width: 100%; }
    .ud-filter-select { flex: 1 1 140px; }
}
@media (max-width: 640px) {
    .ud-shell { padding: 20px 14px 34px; }
    .ud-hero { padding: 22px 20px; }
    .ud-hero h1 { font-size: 28px; }
    .ud-saved-grid, .ud-pinned-modal-body { padding: 18px; }
    .ud-card-head { padding: 20px 18px 14px; }
    .ud-pagination-wrap { flex-direction: column; align-items: center; }
}
</style>

@push('scripts')
<script>
document.querySelectorAll('[data-dashboard-link]').forEach((link) => {
    link.addEventListener('click', () => {
        document.querySelectorAll('[data-dashboard-link]').forEach((item) => item.classList.remove('is-active'));
        link.classList.add('is-active');
    });
});

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

// ══ SAVED RESEARCHES FILTERING & PAGINATION ══
(function initSavedResearchesPaginationAndFilter() {
    const cards = Array.from(document.querySelectorAll('#savedItemsContainer [data-saved-card]'));
    const totalCount = cards.length;
    const PAGE_SIZE = 10;
    let currentPage = 1;
    let filteredCards = [];

    // If 10 or fewer records, all are shown directly and pagination is hidden
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

        // Previous button
        const prevBtn = document.createElement('button');
        prevBtn.type = 'button';
        prevBtn.className = `ud-pag-btn ud-pag-nav-btn ${activePage <= 1 ? 'is-disabled' : ''}`;
        prevBtn.innerHTML = '‹ Prev';
        prevBtn.disabled = activePage <= 1;
        prevBtn.addEventListener('click', () => {
            if (activePage > 1) {
                applyFilterAndPaginate(activePage - 1);
                modalBody?.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });
        paginationNav.appendChild(prevBtn);

        // Page number list
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
                ellipsis.textContent = '…';
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

        // Next button
        const nextBtn = document.createElement('button');
        nextBtn.type = 'button';
        nextBtn.className = `ud-pag-btn ud-pag-nav-btn ${activePage >= totalPages ? 'is-disabled' : ''}`;
        nextBtn.innerHTML = 'Next ›';
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

        // Filter cards matching criteria
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

        // Hide all cards first
        cards.forEach((card) => { card.style.display = 'none'; });

        if (totalFiltered === 0) {
            if (noResultsState) noResultsState.style.display = 'flex';
            if (paginationWrap) paginationWrap.style.display = 'none';
            if (matchCountLabel) matchCountLabel.textContent = 'No matching researches found';
            return;
        }

        if (noResultsState) noResultsState.style.display = 'none';

        // Show page slice
        const startIndex = (currentPage - 1) * PAGE_SIZE;
        const endIndex = Math.min(startIndex + PAGE_SIZE, totalFiltered);

        for (let i = startIndex; i < endIndex; i++) {
            filteredCards[i].style.display = '';
        }

        const rangeSummary = `Showing ${startIndex + 1}–${endIndex} of ${totalFiltered} saved ${totalFiltered === 1 ? 'research' : 'researches'}`;
        if (matchCountLabel) matchCountLabel.textContent = rangeSummary;
        if (paginationInfo) paginationInfo.textContent = rangeSummary;

        // Show pagination controls only if filtered items exceed PAGE_SIZE (10)
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

    // Expose for external re-sync if needed
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

    // Initial setup
    applyFilterAndPaginate(1);
})();
</script>
@endpush
@endsection
