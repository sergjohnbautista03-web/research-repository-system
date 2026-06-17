@extends('layouts.app')
@section('title', 'My Submissions — Ube Repository')

@section('content')

<div class="mysub-root">

{{-- HERO --}}
<div class="mysub-hero">
    <div class="mysub-hero-grid"></div>
    <div class="mysub-glow mysub-glow-1"></div>
    <div class="mysub-glow mysub-glow-2"></div>
    <div class="mysub-glow mysub-glow-3"></div>

    <div class="mysub-hero-inner">
        <div class="mysub-hero-text">
            <div class="mysub-eyebrow"><span class="mysub-eyebrow-dot"></span> Research Portfolio</div>
            <h1>My Submissions</h1>
            <p>Track and manage your research submissions</p>
        </div>
        <a href="{{ route('research.submit') }}" class="btn-submit-new">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            New Submission
        </a>
    </div>

    <div class="mysub-stats">
        <div class="mysub-stat">
            <span class="mysub-stat-num">{{ $researches->total() }}</span>
            <span class="mysub-stat-label">Total</span>
        </div>
        <div class="mysub-stat mysub-stat--pending">
            <span class="mysub-stat-num pending-num">{{ $researches->getCollection()->where('status','pending')->count() }}</span>
            <span class="mysub-stat-label">Pending</span>
        </div>
        <div class="mysub-stat mysub-stat--approved">
            <span class="mysub-stat-num approved-num">{{ $researches->getCollection()->where('status','approved')->count() }}</span>
            <span class="mysub-stat-label">Approved</span>
        </div>
        <div class="mysub-stat mysub-stat--rejected">
            <span class="mysub-stat-num rejected-num">{{ $researches->getCollection()->where('status','rejected')->count() }}</span>
            <span class="mysub-stat-label">Rejected</span>
        </div>
        <div class="mysub-stat mysub-stat--archived">
            <span class="mysub-stat-num archived-num">{{ $researches->getCollection()->where('status','archived')->count() }}</span>
            <span class="mysub-stat-label">Archived</span>
        </div>
    </div>
</div>

{{-- BODY --}}
<div class="mysub-body">

    @if($researches->isEmpty())
    <div class="mysub-empty">
        <div class="empty-circle">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14,2 14,8 20,8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
        </div>
        <h3>No submissions yet</h3>
        <p>Start contributing to the Ube Repository by submitting your research paper, thesis, or study.</p>
        <a href="{{ route('research.submit') }}" class="btn-submit-new" style="background:var(--purple-main);color:white;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Submit Your First Research
        </a>
    </div>

    @else
    <div class="mysub-cards">
        @foreach($researches as $r)
        <div class="mysub-card status-border-{{ $r->status }}">

            <div class="mysub-card-top">
                <div class="mysub-card-badges">
                    <span class="type-chip type-{{ $r->type }}">{{ $r->getSubmissionCategoryLabel() }}: {{ $r->getTypeLabel() }}</span>
                    <span class="status-chip status-chip-{{ $r->status }}">
                        <span class="status-dot status-dot-{{ $r->status }}{{ $r->status == 'pending' ? ' status-dot-pulse' : '' }}"></span>
                        @if($r->status == 'approved') Approved
                        @elseif($r->status == 'pending') Under Review
                        @elseif($r->status == 'archived') Archived
                        @else Rejected @endif
                    </span>
                </div>
                <span class="mysub-card-year">{{ $r->year_published }}</span>
            </div>

            <h3 class="mysub-card-title">
                @if($r->status == 'approved')
                    <a href="{{ route('research.show', $r) }}">{{ $r->title }}</a>
                @else
                    {{ $r->title }}
                @endif
            </h3>

            <div class="mysub-card-meta">
                <span>
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M3 10h18M3 7l9-4 9 4M4 10v11M20 10v11M8 10v11M12 10v11M16 10v11"/></svg>
                    {{ Str::limit($r->department, 40) }}
                </span>
                <span class="mysub-meta-dot"></span>
                <span>
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    {{ $r->created_at->format('M d, Y') }}
                </span>
            </div>

            @if($r->status == 'rejected' && $r->rejection_reason)
            <div class="mysub-rejection-note">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <div>
                    <strong>Rejection Reason:</strong>
                    <p>{{ $r->rejection_reason }}</p>
                </div>
            </div>
            @endif

            <div class="mysub-card-footer">
                @if($r->status == 'approved')
                    <a href="{{ route('research.show', $r) }}" class="mysub-action-btn view-btn">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        View Paper
                    </a>
                @elseif($r->status == 'pending')
                    <span class="mysub-pending-badge"><span class="pending-dot"></span> Awaiting admin review</span>
                @elseif($r->status == 'archived')
                    <span class="mysub-archived-badge">Unpublished by admin</span>
                @else
                    <span class="mysub-rejected-badge">Submission rejected</span>
                @endif
                <span class="mysub-card-views">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    {{ number_format($r->view_count) }} views
                </span>
            </div>
        </div>
        @endforeach
    </div>
    <div class="pagination-wrap">{{ $researches->links() }}</div>
    @endif

</div>
</div>

<style>
.mysub-root { font-family: var(--font-body); background: #eeeaf6; min-height: 100vh; }

/* ── HERO ── */
.mysub-hero {
    position: relative;
    background: #3b0f7a;
    overflow: hidden;
    padding: 36px 44px 0;
}
.mysub-hero-grid {
    position: absolute; inset: 0; pointer-events: none;
    background-image:
        linear-gradient(rgba(255,255,255,.04) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.04) 1px, transparent 1px);
    background-size: 38px 38px;
}
.mysub-glow { position: absolute; border-radius: 50%; filter: blur(80px); pointer-events: none; }
.mysub-glow-1 { width: 500px; height: 500px; background: #7c3aed; opacity: .22; top: -200px; right: -100px; }
.mysub-glow-2 { width: 280px; height: 280px; background: #a855f7; opacity: .15; bottom: -80px; left: 18%; }
.mysub-glow-3 { width: 180px; height: 180px; background: #6d28d9; opacity: .18; top: 10px; left: -50px; }

.mysub-hero-inner {
    position: relative; z-index: 2;
    display: flex; align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 32px; flex-wrap: wrap; gap: 16px;
}
.mysub-eyebrow {
    display: inline-flex; align-items: center; gap: 7px;
    font-size: 11px; font-weight: 600;
    color: rgba(255,255,255,.5);
    text-transform: uppercase; letter-spacing: 1.2px;
    margin-bottom: 10px;
}
.mysub-eyebrow-dot { width: 7px; height: 7px; border-radius: 50%; background: #c084fc; }
.mysub-hero-text h1 {
    font-family: var(--font-body);
    font-size: 40px; font-weight: 700;
    color: white; margin-bottom: 6px;
    letter-spacing: -.6px; line-height: 1.1;
}
.mysub-hero-text p { color: rgba(255,255,255,.55); font-size: 14px; }

.btn-submit-new {
    display: inline-flex; align-items: center; gap: 8px;
    background: white; color: var(--purple-main);
    font-weight: 700; font-size: 13.5px;
    padding: 12px 24px; border-radius: 50px;
    text-decoration: none; transition: all .2s;
    box-shadow: 0 4px 20px rgba(0,0,0,.22), inset 0 1px 0 rgba(255,255,255,.9);
    white-space: nowrap; align-self: flex-start; margin-top: 30px;
}
.btn-submit-new:hover { transform: translateY(-2px); box-shadow: 0 8px 28px rgba(0,0,0,.28); opacity: 1; }
.btn-submit-new svg { flex-shrink: 0; }

/* ── STATS ── */
.mysub-stats {
    position: relative; z-index: 2;
    display: flex; gap: 1px;
    background: rgba(255,255,255,.07);
    border-radius: 12px 12px 0 0;
    border: 1px solid rgba(255,255,255,.1);
    border-bottom: none; overflow: hidden;
}
.mysub-stat {
    flex: 1; display: flex; flex-direction: column; align-items: center;
    padding: 20px 12px 22px; position: relative;
    transition: background .2s; cursor: default;
}
.mysub-stat + .mysub-stat::before {
    content: ''; position: absolute; left: 0; top: 20%; height: 60%;
    width: 1px; background: rgba(255,255,255,.12);
}
.mysub-stat:hover { background: rgba(255,255,255,.07); }
.mysub-stat-num {
    font-family: var(--font-head);
    font-size: 30px; font-weight: 700;
    color: white; line-height: 1; margin-bottom: 6px;
}
.mysub-stat-label {
    font-size: 11px; font-weight: 600;
    text-transform: uppercase; letter-spacing: .9px;
    color: rgba(255,255,255,.4);
}
.pending-num  { color: #fcd34d; }
.approved-num { color: #6ee7b7; }
.rejected-num { color: #fca5a5; }
.archived-num { color: #cbd5e1; }

/* ── BODY ── */
.mysub-body { padding: 32px 44px 56px; }

/* ── EMPTY ── */
.mysub-empty {
    text-align: center; padding: 80px 20px;
    display: flex; flex-direction: column; align-items: center; gap: 16px;
}
.empty-circle {
    width: 100px; height: 100px;
    background: var(--purple-pale); border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    color: var(--purple-main);
    border: 3px dashed var(--purple-light);
    box-shadow: 0 4px 16px rgba(107,47,160,.1);
}
.mysub-empty h3 { font-family: var(--font-head); font-size: 24px; color: var(--purple-deep); }
.mysub-empty p  { color: var(--muted); max-width: 400px; line-height: 1.6; }

/* ── CARDS ── */
.mysub-cards {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 20px; margin-bottom: 24px;
}
.mysub-card {
    background: white;
    border-radius: 18px;
    border: 1px solid rgba(109,40,217,.07);
    border-top: 4px solid;
    padding: 22px;
    box-shadow: 0 1px 3px rgba(46,16,101,.04), 0 4px 16px rgba(46,16,101,.07);
    transition: transform .22s cubic-bezier(.4,0,.2,1), box-shadow .22s;
    display: flex; flex-direction: column; gap: 12px;
}
.mysub-card:hover { transform: translateY(-4px); box-shadow: 0 4px 8px rgba(46,16,101,.06), 0 16px 40px rgba(46,16,101,.13); }
.status-border-approved { border-top-color: #10b981; }
.status-border-pending  { border-top-color: #f59e0b; }
.status-border-rejected { border-top-color: #ef4444; }
.status-border-archived { border-top-color: #64748b; }

.mysub-card-top { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
.mysub-card-badges { display: flex; gap: 7px; flex-wrap: wrap; align-items: center; }
.mysub-card-year {
    font-size: 12px; font-weight: 700;
    color: var(--purple-main); background: var(--purple-ghost);
    border: 1px solid var(--purple-pale);
    padding: 3px 10px; border-radius: 50px; white-space: nowrap;
}

.type-chip { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; padding: 3px 10px; border-radius: 20px; }
.status-chip {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 20px;
}
.status-chip-approved { background: #d1fae5; color: #065f46; }
.status-chip-pending  { background: #fef3c7; color: #92400e; }
.status-chip-rejected { background: #fee2e2; color: #991b1b; }
.status-chip-archived { background: #e5e7eb; color: #374151; }

.status-dot { display: inline-block; width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
.status-dot-approved { background: #10b981; }
.status-dot-pending  { background: #f59e0b; }
.status-dot-rejected { background: #ef4444; }
.status-dot-archived { background: #64748b; }
.status-dot-pulse { animation: sub-pulse 1.6s ease-in-out infinite; }
@keyframes sub-pulse { 0%,100%{ opacity:1; transform:scale(1); } 50%{ opacity:.7; transform:scale(.8); } }

.mysub-card-title { font-family: var(--font-head); font-size: 16px; color: var(--purple-deep); line-height: 1.4; }
.mysub-card-title a { color: var(--purple-deep); text-decoration: none; transition: color .15s; }
.mysub-card-title a:hover { color: var(--purple-main); }

.mysub-card-meta { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; font-size: 13px; color: var(--muted); }
.mysub-card-meta span { display: inline-flex; align-items: center; gap: 5px; }
.mysub-card-meta svg { opacity: .65; flex-shrink: 0; }
.mysub-meta-dot { width: 3px !important; height: 3px !important; border-radius: 50%; background: var(--purple-light); flex-shrink: 0; }

.mysub-rejection-note {
    display: flex; gap: 10px;
    background: #fff8f8; border: 1px solid #fecdd3;
    border-left: 3px solid #ef4444; border-radius: 8px;
    padding: 12px 14px; font-size: 13px;
}
.mysub-rejection-note strong { color: #991b1b; display: block; margin-bottom: 3px; font-size: 11px; text-transform: uppercase; letter-spacing: .4px; }
.mysub-rejection-note p { color: #7f1d1d; margin: 0; line-height: 1.5; }

.mysub-card-footer {
    display: flex; align-items: center; gap: 10px;
    padding-top: 12px; border-top: 1px solid var(--border);
    margin-top: auto; flex-wrap: wrap;
}
.mysub-action-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 14px; border-radius: 9px;
    font-size: 13px; font-weight: 600;
    text-decoration: none; transition: all .2s;
}
.view-btn { background: var(--purple-pale); color: var(--purple-main); border: 1px solid var(--purple-light); }
.view-btn:hover { background: var(--purple-main); color: white; }

.mysub-pending-badge {
    display: inline-flex; align-items: center; gap: 7px;
    font-size: 13px; color: #92400e; background: #fef3c7;
    padding: 6px 12px; border-radius: 9px; font-weight: 500;
    border: 1px solid #fde68a;
}
.pending-dot { width: 8px; height: 8px; background: #f59e0b; border-radius: 50%; animation: pulse 1.5s infinite; flex-shrink: 0; }
@keyframes pulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.5;transform:scale(.8)} }
.mysub-rejected-badge {
    font-size: 13px; color: #991b1b; background: #fee2e2;
    padding: 6px 12px; border-radius: 9px; font-weight: 500;
    border: 1px solid #fecaca;
}
.mysub-archived-badge {
    font-size: 13px; color: #374151; background: #f3f4f6;
    padding: 6px 12px; border-radius: 9px; font-weight: 500;
    border: 1px solid #d1d5db;
}
.mysub-card-views {
    margin-left: auto; font-size: 12px; color: var(--muted);
    display: inline-flex; align-items: center; gap: 4px;
}
.mysub-card-views svg { opacity: .6; }

/* ── RESPONSIVE ── */
@media(max-width:768px){
    .mysub-hero  { padding: 24px 20px 0; }
    .mysub-body  { padding: 20px 16px 40px; }
    .mysub-cards { grid-template-columns: 1fr; }
    .mysub-stat  { padding: 14px 8px 16px; }
    .mysub-hero-text h1 { font-size: 28px; }
}
@media(max-width:480px){
    .mysub-stat-label { font-size: 9px; }
    .mysub-stat-num   { font-size: 24px; }
}
</style>

@endsection
