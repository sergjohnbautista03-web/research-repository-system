@extends('layouts.app')
@section('title', 'Saved Research - Ube Repository')

@section('content')

<div class="pin-root">

{{-- ══ HERO ══ --}}
<div class="pin-hero">
    <div class="pin-hero-grid"></div>
    <div class="pin-glow pin-glow-1"></div>
    <div class="pin-glow pin-glow-2"></div>
    <div class="pin-glow pin-glow-3"></div>

    <div class="pin-hero-inner">
        <div>
            <div class="pin-eyebrow">
                <span class="pin-eyebrow-dot"></span>
                Your Collection
            </div>
            <h1 class="pin-hero-title">Saved Research</h1>
            <p class="pin-hero-sub">Research papers you've saved for quick access</p>
        </div>
        <a href="{{ route('home') }}" class="pin-browse-btn">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            Browse Research
        </a>
    </div>
</div>

{{-- ══ BODY ══ --}}
<div class="pin-body">

    @if($researches->isEmpty())
    <div class="pin-empty">
        <div class="pin-empty-visual">
            <div class="pin-empty-ring pin-er-3"></div>
            <div class="pin-empty-ring pin-er-2"></div>
            <div class="pin-empty-ring pin-er-1"></div>
            <div class="pin-empty-core">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            </div>
        </div>
        <h3 class="pin-empty-title">No saved research yet</h3>
        <p class="pin-empty-desc">Open a research detail page and click Save to keep it here for quick access later.</p>
       
    </div>

    @else
    <div class="pin-grid">
        @foreach($researches as $item)
            @include('components.research-card', ['research' => $item])
        @endforeach
    </div>
    <div class="pagination-wrap">{{ $researches->links() }}</div>
    @endif

</div>
</div>

<style>
.pin-root { font-family: var(--font-body); background: #eeeaf6; min-height: 100vh; }

/* ── HERO ── */
.pin-hero {
    position: relative;
    background: #3b0f7a;
    overflow: hidden;
    padding: 36px 44px 40px;
}
.pin-hero-grid {
    position: absolute; inset: 0; pointer-events: none;
    background-image:
        linear-gradient(rgba(255,255,255,.04) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.04) 1px, transparent 1px);
    background-size: 38px 38px;
}
.pin-glow { position: absolute; border-radius: 50%; filter: blur(80px); pointer-events: none; }
.pin-glow-1 { width: 500px; height: 500px; background: #7c3aed; opacity: .22; top: -200px; right: -100px; }
.pin-glow-2 { width: 280px; height: 280px; background: #a855f7; opacity: .15; bottom: -80px; left: 18%; }
.pin-glow-3 { width: 180px; height: 180px; background: #6d28d9; opacity: .18; top: 10px; left: -50px; }

.pin-hero-inner {
    position: relative; z-index: 2;
    display: flex; align-items: flex-start;
    justify-content: space-between;
    flex-wrap: wrap; gap: 16px;
}
.pin-eyebrow {
    display: inline-flex; align-items: center; gap: 7px;
    font-size: 11px; font-weight: 600;
    color: rgba(255,255,255,.5);
    text-transform: uppercase; letter-spacing: 1.2px;
    margin-bottom: 10px;
}
.pin-eyebrow-dot { width: 7px; height: 7px; border-radius: 50%; background: #c084fc; }
.pin-hero-title {
    font-family: var(--font-body);
    font-size: 40px; font-weight: 700;
    color: white; margin-bottom: 6px;
    letter-spacing: -.6px; line-height: 1.1;
}
.pin-hero-sub { font-size: 14px; color: rgba(255,255,255,.55); }

.pin-browse-btn {
    display: inline-flex; align-items: center; gap: 8px;
    background: white; color: var(--purple-main);
    font-weight: 700; font-size: 13.5px;
    padding: 12px 24px; border-radius: 50px;
    text-decoration: none; transition: all .2s;
    box-shadow: 0 4px 20px rgba(0,0,0,.22), inset 0 1px 0 rgba(255,255,255,.9);
    white-space: nowrap; align-self: flex-start; margin-top: 30px;
}
.pin-browse-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 28px rgba(0,0,0,.28); color: var(--purple-deep); }
.pin-browse-btn--solid {
    background: var(--purple-main); color: white;
    box-shadow: 0 4px 20px rgba(107,47,160,.4);
    margin-top: 0;
}
.pin-browse-btn--solid:hover { background: var(--purple-deep); color: white; }

/* ── BODY ── */
.pin-body { padding: 32px 44px 56px; }

/* ── EMPTY ── */
.pin-empty {
    display: flex; flex-direction: column; align-items: center;
    gap: 18px; padding: 90px 20px; text-align: center;
}
.pin-empty-visual {
    position: relative; width: 110px; height: 110px;
    display: flex; align-items: center; justify-content: center;
}
.pin-empty-ring {
    position: absolute; border-radius: 50%;
    border: 1.5px dashed;
    animation: pin-spin 20s linear infinite;
}
.pin-er-1 { width: 70px;  height: 70px;  border-color: #c4b5fd; animation-duration: 10s; }
.pin-er-2 { width: 90px;  height: 90px;  border-color: #ddd6fe; animation-duration: 16s; animation-direction: reverse; }
.pin-er-3 { width: 110px; height: 110px; border-color: #ede9fe; animation-duration: 22s; }
@keyframes pin-spin { to { transform: rotate(360deg); } }
.pin-empty-core {
    width: 52px; height: 52px; border-radius: 50%;
    background: white; display: flex; align-items: center; justify-content: center;
    color: var(--purple-main);
    box-shadow: 0 4px 16px rgba(107,47,160,.15);
    z-index: 1; position: relative;
}
.pin-empty-title { font-family: var(--font-head); font-size: 24px; color: var(--purple-deep); }
.pin-empty-desc  { font-size: 14.5px; color: var(--muted); max-width: 360px; line-height: 1.65; }

/* ── GRID — override existing research-grid ── */
.pin-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 28px;
}

/* ── OVERRIDE research-card styles for this page ── */
.pin-grid .research-card {
    border-radius: 18px !important;
    border: 1px solid rgba(109,40,217,.07) !important;
    border-top: 4px solid var(--purple-main) !important;
    box-shadow: 0 1px 3px rgba(46,16,101,.04), 0 4px 16px rgba(46,16,101,.07) !important;
    transition: transform .22s cubic-bezier(.4,0,.2,1), box-shadow .22s !important;
    background: white !important;
}
.pin-grid .research-card:hover {
    transform: translateY(-4px) !important;
    box-shadow: 0 4px 8px rgba(46,16,101,.06), 0 16px 40px rgba(46,16,101,.13) !important;
}
.pin-grid .pin-btn.pinned {
    background: var(--purple-main) !important;
    color: white !important;
    border-color: var(--purple-main) !important;
}

@media(max-width:768px){
    .pin-hero  { padding: 24px 20px 28px; }
    .pin-body  { padding: 20px 16px 40px; }
    .pin-grid  { grid-template-columns: 1fr; }
    .pin-hero-title { font-size: 28px; }
}
</style>

@endsection
