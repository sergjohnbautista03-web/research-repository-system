@extends('layouts.admin')
@section('title', 'Manage Researches')
@section('page-title', 'Manage Researches')

@section('content')
@php
    $adminUser = auth()->user();
    $assignedDepartment = $adminUser->isDepartmentDean() ? $adminUser->department : null;
    $activeFilters = collect([
        request('search') ? 'Search: "' . request('search') . '"' : null,
        request('status') ? 'Status: ' . ucfirst(request('status')) : null,
        ($assignedDepartment ?: request('department')) ? 'Department: ' . Str::limit($assignedDepartment ?: request('department'), 36) : null,
        request('year') ? 'Year: ' . request('year') : null,
    ])->filter();
    $shownStart = $researches->firstItem() ?? 0;
    $shownEnd = $researches->lastItem() ?? 0;
@endphp

<form method="GET" class="mr-filter-card">
    @if(! $adminUser->isDepartmentDean())
        <div class="mr-filter-top">
            <a href="{{ route('admin.add-research') }}" class="mr-add-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add Research
            </a>
        </div>
    @endif

    <div class="mr-filter-grid">
        <div class="mr-filter-field mr-filter-search">
            <label for="mr-search">Search</label>
            <div class="mr-control-wrap">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input id="mr-search" type="text" name="search" value="{{ request('search') }}" placeholder="Title or author">
            </div>
        </div>

        <div class="mr-filter-field">
            <label for="mr-status">Status</label>
            <select id="mr-status" name="status">
                <option value="">All Status</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="archived" {{ request('status') == 'archived' ? 'selected' : '' }}>Archived</option>
                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
            </select>
        </div>

        <div class="mr-filter-field mr-filter-department">
            <label for="mr-department">Department</label>
            @if($adminUser->isDepartmentDean())
                <div id="mr-department" class="mr-fixed-department">{{ $assignedDepartment ?: 'Assigned Department' }}</div>
            @else
                <select id="mr-department" name="department">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept }}" {{ request('department') == $dept ? 'selected' : '' }}>{{ $dept }}</option>
                    @endforeach
                </select>
            @endif
        </div>

        <div class="mr-filter-field">
            <label for="mr-year">Year</label>
            <select id="mr-year" name="year">
                <option value="">All Years</option>
                @foreach($years as $year)
                    <option value="{{ $year }}" {{ (string) request('year') === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
                @endforeach
            </select>
        </div>

        <div class="mr-filter-actions">
            <button type="submit" class="mr-filter-btn mr-filter-btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                Filter
            </button>
            <a href="{{ route('admin.researches') }}" class="mr-filter-btn mr-filter-btn-ghost">Clear</a>
        </div>
    </div>

    @if($activeFilters->isNotEmpty())
        <div class="mr-active-filters">
            @foreach($activeFilters as $filter)
                <span>{{ $filter }}</span>
            @endforeach
        </div>
    @endif
</form>

<div class="mr-table-card">
    <div class="mr-table-head">
        <div>
            <span>Research Records</span>
            <h3>Submissions</h3>
        </div>
        <strong>{{ number_format($shownStart) }}-{{ number_format($shownEnd) }} of {{ number_format($researches->total()) }}</strong>
    </div>

    <div class="mr-table-wrap">
        <table class="mr-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Department</th>
                    <th>Type</th>
                    <th>Year</th>
                    <th>Status</th>
                    <th>Views</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($researches as $r)
                <tr>
                    <td data-label="Title" class="mr-title-cell">
                        <a href="{{ route('admin.research.show', $r) }}" class="mr-title-link">{{ Str::limit($r->title, 72) }}</a>
                        <div class="mr-title-meta">
                            <span>{{ optional($r->created_at)->format('M d, Y') ?? 'No date' }}</span>
                            @if($r->file_name)
                                <span>{{ Str::limit($r->file_name, 32) }}</span>
                            @endif
                        </div>
                    </td>
                    <td data-label="Author"><span class="mr-author">{{ Str::limit($r->author_name, 46) }}</span></td>
                    <td data-label="Department"><span class="mr-department">{{ Str::limit($r->department, 34) }}</span></td>
                    <td data-label="Type"><span class="type-badge type-{{ $r->type }} badge-sm">{{ $r->getSubmissionCategoryLabel() }}: {{ $r->getTypeLabel() }}</span></td>
                    <td data-label="Year"><span class="mr-year">{{ $r->year_published }}</span></td>
                    <td data-label="Status"><span class="status-badge status-{{ $r->status }}">{{ ucfirst($r->status) }}</span></td>
                    <td data-label="Views"><span class="mr-views">{{ number_format($r->view_count) }}</span></td>
                    <td data-label="Actions" class="action-cell">
                        <div class="research-action-row">
                            <a href="{{ route('admin.research.show', $r) }}" class="btn-action-view">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                View
                            </a>

                            @if($r->status == 'pending' && ! $adminUser->isDepartmentDean())
                                <form method="POST" action="{{ route('admin.research.approve', $r) }}">
                                    @csrf
                                    <button type="submit" class="btn-action-approve">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                                        Approve
                                    </button>
                                </form>
                            @endif

                            @if($r->status == 'pending' || (! $adminUser->isDepartmentDean() && in_array($r->status, ['approved', 'archived'], true)))
                                <div class="action-menu-wrap">
                                    <button
                                        type="button"
                                        class="btn-action-more"
                                        title="More actions"
                                        aria-label="More actions"
                                        onclick="toggleResearchActionMenu(event, {{ $r->id }})">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/></svg>
                                        More
                                    </button>

                                    <div id="researchActionMenu-{{ $r->id }}" class="research-action-menu">
                                        @if($r->status == 'pending' && ! $adminUser->isDepartmentDean())
                                            <button type="button" class="research-action-menu-item research-action-menu-reject" onclick="openRejectModal({{ $r->id }}); closeResearchActionMenus();">
                                                Reject
                                            </button>
                                        @elseif($r->status == 'pending')
                                            <span class="research-action-note">Admin approval only</span>
                                        @endif

                                        @if(! $adminUser->isDepartmentDean())
                                            @if($r->status === 'approved')
                                                <form method="POST" action="{{ route('admin.research.archive', $r) }}"
                                                    onsubmit="return confirm('Archive and unpublish this research?')">
                                                    @csrf
                                                    <button type="submit" class="research-action-menu-item research-action-menu-archive">Archive / Unpublish</button>
                                                </form>
                                            @elseif($r->status === 'archived')
                                                <form method="POST" action="{{ route('admin.research.publish', $r) }}"
                                                    onsubmit="return confirm('Publish this archived research again?')">
                                                    @csrf
                                                    <button type="submit" class="research-action-menu-item research-action-menu-publish">Publish Again</button>
                                                </form>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="mr-empty-cell">
                        <div class="mr-empty-state">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
                            <strong>No researches found</strong>
                            <p>Try changing your filters or search keyword.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination-wrap">{{ $researches->links() }}</div>
</div>

<style>
.mr-table-head span,
.mr-filter-field label {
    display: inline-flex;
    color: #6b2fa0;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
}
.mr-filter-top {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    margin-bottom: 14px;
}
.mr-add-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 44px;
    padding: 0 18px;
    border-radius: 14px;
    background: linear-gradient(135deg, #4b168f, #6d28d9);
    color: #fff;
    font-size: 13.5px;
    font-weight: 800;
    text-decoration: none;
    box-shadow: 0 14px 24px rgba(59,15,122,.2);
}
.mr-add-btn svg,
.mr-filter-btn svg,
.btn-action-view svg,
.btn-action-approve svg,
.btn-action-more svg {
    width: 15px;
    height: 15px;
    flex-shrink: 0;
}
.mr-filter-card {
    margin-bottom: 22px;
    padding: 18px;
    border-radius: 22px;
    background: #fff;
    border: 1px solid rgba(107,47,160,.1);
    box-shadow: 0 12px 28px rgba(57,26,101,.06);
}
.mr-filter-grid {
    display: grid;
    grid-template-columns: minmax(240px, 1.25fr) minmax(150px, .7fr) minmax(230px, 1fr) minmax(140px, .62fr) auto;
    gap: 12px;
    align-items: end;
}
.mr-filter-field {
    min-width: 0;
}
.mr-filter-field label {
    margin: 0 0 7px 2px;
    color: #7b4bb0;
    font-size: 10.5px;
}
.mr-control-wrap {
    position: relative;
}
.mr-control-wrap svg {
    position: absolute;
    left: 15px;
    top: 50%;
    width: 16px;
    height: 16px;
    transform: translateY(-50%);
    color: #9a84bc;
    pointer-events: none;
}
.mr-filter-field input,
.mr-filter-field select,
.mr-fixed-department {
    width: 100%;
    min-height: 48px;
    border: 1.5px solid #e4d7f6;
    border-radius: 13px;
    background: linear-gradient(180deg, #fff, #fcfaff);
    color: #1d0b3b;
    font: inherit;
    font-size: 14px;
    outline: none;
    transition: border-color .15s, box-shadow .15s, background .15s;
}
.mr-fixed-department {
    display: flex;
    align-items: center;
    padding: 0 14px;
    color: #6b2fa0;
    font-weight: 800;
}
.mr-filter-field input {
    padding: 0 15px 0 42px;
}
.mr-filter-field select {
    padding: 0 38px 0 14px;
}
.mr-filter-field input:focus,
.mr-filter-field select:focus {
    border-color: #7c3aed;
    background: #fff;
    box-shadow: 0 0 0 4px rgba(124,58,237,.11);
}
.mr-filter-actions {
    display: flex;
    align-items: end;
    gap: 10px;
    min-height: 48px;
}
.mr-filter-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 48px;
    padding: 0 18px;
    border-radius: 13px;
    border: 1.5px solid transparent;
    font-size: 13.5px;
    font-weight: 800;
    text-decoration: none;
    cursor: pointer;
    font-family: inherit;
}
.mr-filter-btn-primary {
    background: #6b2fa0;
    color: #fff;
    box-shadow: 0 10px 18px rgba(59,15,122,.17);
}
.mr-filter-btn-ghost {
    background: #faf7ff;
    color: #1d0b3b;
    border-color: #e3d8f2;
}
.mr-active-filters {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 14px;
    padding-top: 14px;
    border-top: 1px solid #f0eaf9;
}
.mr-active-filters span {
    display: inline-flex;
    align-items: center;
    min-height: 30px;
    padding: 0 11px;
    border-radius: 999px;
    background: #f5f0fd;
    border: 1px solid #e3d8f2;
    color: #5b248c;
    font-size: 12px;
    font-weight: 800;
}
.mr-table-card {
    overflow: hidden;
    border-radius: 22px;
    background: #fff;
    border: 1px solid rgba(107,47,160,.1);
    box-shadow: 0 14px 34px rgba(57,26,101,.07);
}
.mr-table-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 18px 22px;
    background: linear-gradient(180deg, #fff, #fbf8ff);
    border-bottom: 1px solid #eee6fa;
}
.mr-table-head h3 {
    margin: 3px 0 0;
    color: #210945;
    font-family: var(--font-head);
    font-size: 20px;
}
.mr-table-head strong {
    display: inline-flex;
    align-items: center;
    min-height: 34px;
    padding: 0 13px;
    border-radius: 999px;
    background: #f5f0fd;
    color: #6b2fa0;
    font-size: 12.5px;
}
.mr-table-wrap {
    overflow-x: auto;
}
.mr-table {
    width: 100%;
    min-width: 1120px;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 13.5px;
}
.mr-table th {
    padding: 13px 20px;
    background: #fbf9ff;
    color: #52297a;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .08em;
    text-align: left;
    text-transform: uppercase;
    white-space: nowrap;
    border-bottom: 1px solid #eee6fa;
}
.mr-table td {
    padding: 17px 20px;
    border-bottom: 1px solid #f2edf9;
    color: #24113f;
    vertical-align: middle;
}
.mr-table tbody tr {
    transition: background .15s;
}
.mr-table tbody tr:hover td {
    background: #fdfbff;
}
.mr-table tbody tr:last-child td {
    border-bottom: none;
}
.mr-title-cell {
    min-width: 260px;
}
.mr-title-link {
    display: inline;
    color: #5b21a0;
    font-weight: 800;
    line-height: 1.45;
    text-decoration: none;
}
.mr-title-link:hover {
    color: #3b0f7a;
    text-decoration: underline;
}
.mr-title-meta {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 7px;
    margin-top: 8px;
}
.mr-title-meta span {
    display: inline-flex;
    align-items: center;
    min-height: 23px;
    padding: 0 8px;
    border-radius: 999px;
    background: #f6f2fb;
    color: #8978a6;
    font-size: 11px;
    font-weight: 700;
}
.mr-author,
.mr-department {
    color: #1d0b3b;
    font-weight: 600;
    line-height: 1.45;
}
.mr-department {
    display: inline-flex;
    max-width: 220px;
}
.mr-year,
.mr-views {
    color: #210945;
    font-weight: 800;
}
.mr-table .type-badge,
.mr-table .status-badge {
    padding: 6px 12px;
    border-radius: 999px;
    font-size: 11.5px;
    font-weight: 800;
}
.mr-table .status-pending {
    background: #fef3c7;
    color: #92400e;
}
.mr-table .status-approved {
    background: #dcfce7;
    color: #166534;
}
.mr-table .status-archived {
    background: #e5e7eb;
    color: #374151;
}
.mr-table .status-rejected {
    background: #fee2e2;
    color: #991b1b;
}
.action-cell {
    min-width: 250px;
}
.research-action-row {
    display: flex;
    align-items: center;
    gap: 8px;
}
.research-action-row form {
    margin: 0;
}
.action-menu-wrap {
    position: relative;
}
.research-action-note {
    display: inline-flex;
    align-items: center;
    padding: 8px 10px;
    border-radius: 12px;
    background: #f4f0fc;
    border: 1px solid #e2d5f4;
    color: #8b7aaa;
    font-size: 12px;
    font-weight: 800;
}
.btn-action-view,
.btn-action-approve,
.btn-action-more {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    min-height: 36px;
    min-width: 76px;
    padding: 0 12px;
    border-radius: 12px;
    text-decoration: none;
    border: 1px solid transparent;
    cursor: pointer;
    transition: transform .15s ease, background .15s ease, border-color .15s ease;
    font-family: inherit;
    font-size: 12px;
    font-weight: 800;
    line-height: 1;
}
.btn-action-view {
    color: #6b2fa0;
    background: #faf7ff;
    border-color: #e2d5f4;
}
.btn-action-view:hover {
    background: #f3ebff;
    transform: translateY(-1px);
}
.btn-action-approve {
    color: #047857;
    background: #ecfdf5;
    border-color: #bbf7d0;
}
.btn-action-approve:hover {
    background: #d1fae5;
    transform: translateY(-1px);
}
.btn-action-more {
    color: #6d28d9;
    background: #fff;
    border-color: #e9d5ff;
}
.btn-action-more:hover {
    background: #faf5ff;
    transform: translateY(-1px);
}
.research-action-menu {
    position: absolute;
    top: calc(100% + 10px);
    right: 0;
    display: none;
    min-width: 142px;
    padding: 8px;
    border-radius: 16px;
    background: #fff;
    border: 1px solid #f0eaf9;
    box-shadow: 0 18px 38px rgba(59,15,122,.16);
    z-index: 20;
}
.research-action-menu.is-open {
    display: grid;
    gap: 6px;
}
.research-action-menu form {
    width: 100%;
}
.research-action-menu-item {
    width: 100%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 9px 12px;
    border-radius: 12px;
    border: 1px solid transparent;
    background: #fff;
    font-size: 12.5px;
    font-weight: 800;
    cursor: pointer;
    transition: all .15s ease;
    font-family: inherit;
}
.research-action-menu-reject {
    color: #dc2626;
    background: #fff5f5;
    border-color: #fecaca;
}
.research-action-menu-reject:hover {
    background: #fee2e2;
}
.research-action-menu-archive {
    color: #374151;
    background: #f9fafb;
    border-color: #d1d5db;
}
.research-action-menu-archive:hover {
    background: #f3f4f6;
}
.research-action-menu-publish {
    color: #047857;
    background: #ecfdf5;
    border-color: #bbf7d0;
}
.research-action-menu-publish:hover {
    background: #d1fae5;
}
.research-action-menu-delete {
    color: #b91c1c;
    background: #fff;
    border-color: #fecaca;
}
.research-action-menu-delete:hover {
    background: #fef2f2;
}
.mr-empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 46px 20px;
    text-align: center;
    color: #8d7aaa;
}
.mr-empty-state svg {
    width: 44px;
    height: 44px;
    color: #a78bfa;
}
.mr-empty-state strong {
    color: #2e1065;
    font-size: 15px;
}
.mr-empty-state p {
    margin: 0;
    font-size: 13px;
}
.modal-overlay {
    position: fixed;
    inset: 0;
    z-index: 1000;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: rgba(26,6,56,.48);
    backdrop-filter: blur(4px);
}
.modal-box {
    width: min(520px, 100%);
    padding: 24px;
    border-radius: 20px;
    background: #fff;
    border: 1px solid #f0eaf9;
    box-shadow: 0 24px 60px rgba(26,6,56,.22);
}
.modal-box h3 {
    margin: 0 0 18px;
    color: #210945;
    font-family: var(--font-head);
    font-size: 22px;
}

@media (max-width: 1200px) {
    .mr-filter-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .mr-filter-actions {
        grid-column: span 2;
    }
}

@media (max-width: 760px) {
    .mr-filter-card,
    .mr-table-card {
        border-radius: 18px;
    }
    .mr-filter-grid {
        grid-template-columns: 1fr;
    }
    .mr-filter-top {
        justify-content: stretch;
    }
    .mr-add-btn {
        width: 100%;
    }
    .mr-filter-actions {
        grid-column: auto;
    }
    .mr-filter-btn {
        flex: 1;
    }
    .mr-table {
        min-width: 0;
    }
    .mr-table thead {
        display: none;
    }
    .mr-table,
    .mr-table tbody,
    .mr-table tr,
    .mr-table td {
        display: block;
        width: 100%;
    }
    .mr-table tr {
        padding: 16px;
        border-bottom: 1px solid #f0eaf9;
    }
    .mr-table td {
        display: grid;
        grid-template-columns: 118px minmax(0, 1fr);
        gap: 12px;
        padding: 9px 0;
        border-bottom: none;
    }
    .mr-table td::before {
        content: attr(data-label);
        color: #8d7aaa;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .06em;
        text-transform: uppercase;
    }
    .mr-table .mr-empty-cell {
        display: block;
        padding: 0;
    }
    .mr-table .mr-empty-cell::before {
        display: none;
    }
    .mr-title-cell {
        min-width: 0;
    }
    .action-cell {
        min-width: 0;
    }
    .research-action-row {
        flex-wrap: wrap;
    }
    .research-action-menu {
        right: auto;
        left: 0;
    }
}
</style>

<!-- Reject Modal -->
<div id="rejectModal" class="modal-overlay" style="display:none">
    <div class="modal-box">
        <h3>Reject Submission</h3>
        <form id="rejectForm" method="POST">
            @csrf
            <div class="form-group">
                <label>Reason for Rejection</label>
                <textarea name="reason" rows="4" placeholder="Explain why..." required style="width:100%;padding:10px;border:2px solid #e8dff5;border-radius:6px;margin-top:8px;"></textarea>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:15px">
                <button type="button" class="btn btn-ghost" onclick="closeRejectModal()">Cancel</button>
                <button type="submit" class="btn btn-red">Reject</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function openRejectModal(id) {
    closeResearchActionMenus();
    document.getElementById('rejectForm').action = '/admin/researches/' + id + '/reject';
    document.getElementById('rejectModal').style.display = 'flex';
}
function closeRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
}

function closeResearchActionMenus() {
    document.querySelectorAll('.research-action-menu.is-open').forEach(function(menu) {
        menu.classList.remove('is-open');
    });
}

function toggleResearchActionMenu(event, id) {
    event.stopPropagation();

    const menu = document.getElementById('researchActionMenu-' + id);
    const isOpen = menu.classList.contains('is-open');

    closeResearchActionMenus();

    if (!isOpen) {
        menu.classList.add('is-open');
    }
}

document.addEventListener('click', function(event) {
    if (!event.target.closest('.action-menu-wrap')) {
        closeResearchActionMenus();
    }
});
</script>
@endpush
