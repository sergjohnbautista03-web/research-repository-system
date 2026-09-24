@extends('layouts.admin')
@section('title', 'Research Detail')
@section('page-title', 'Research Detail')

@section('content')
@php
    $adminUser = auth()->user();
    $authorMetaLabel = match ($research->submission_category) {
        \App\Models\Research::SUBMISSION_CATEGORY_STUDENT_JOURNAL => 'Researchers',
        \App\Models\Research::SUBMISSION_CATEGORY_FACULTY_JOURNAL => 'Co-author(s)',
        default => 'Author',
    };
@endphp

<div class="rd-shell">
    <div class="rd-card">

        {{-- HEADER --}}
        <div class="rd-header">
            <div class="rd-header-top">
                <div class="rd-badges">
                    <span class="rd-type-badge">{{ $research->getSubmissionCategoryLabel() }}: {{ $research->getTypeLabel() }}</span>
                    <span class="rd-status-badge rd-status-{{ $research->status }}">{{ $research->coordinatorStageLabel() }}</span>
                </div>
                <a href="{{ route('admin.researches') }}" class="rd-back-btn">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                    Back
                </a>
            </div>
            <h2 class="rd-title">{{ $research->title }}</h2>
            @if($research->keywords)
                <div class="rd-keywords">
                    @foreach(explode(',', $research->keywords) as $kw)
                        <span class="rd-keyword">{{ trim($kw) }}</span>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- META GRID --}}
        <div class="rd-meta-grid">
            <div class="rd-meta-item">
                <div class="rd-meta-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>
                <div><span class="rd-meta-label">{{ $authorMetaLabel }}</span><span class="rd-meta-value">{{ $research->authorListLabel() }}</span></div>
            </div>
            <div class="rd-meta-item">
                <div class="rd-meta-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>
                <div><span class="rd-meta-label">Submitted By</span><span class="rd-meta-value">{{ $research->user->name }}</span><span class="rd-meta-sub">{{ $research->user->email }}</span></div>
            </div>
            <div class="rd-meta-item">
                <div class="rd-meta-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></div>
                <div><span class="rd-meta-label">Department</span><span class="rd-meta-value">{{ $research->department }}</span></div>
            </div>
            <div class="rd-meta-item">
                <div class="rd-meta-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg></div>
                <div><span class="rd-meta-label">Program</span><span class="rd-meta-value">{{ $research->course ?? $research->program ?? '—' }}</span></div>
            </div>
            <div class="rd-meta-item">
                <div class="rd-meta-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
                <div><span class="rd-meta-label">Year Published</span><span class="rd-meta-value">{{ $research->year_published }}</span></div>
            </div>
            <div class="rd-meta-item">
                <div class="rd-meta-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
                <div><span class="rd-meta-label">Submitted</span><span class="rd-meta-value">{{ $research->created_at->format('F j, Y') }}</span><span class="rd-meta-sub">{{ $research->created_at->format('g:i A') }}</span></div>
            </div>
            <div class="rd-meta-item">
                <div class="rd-meta-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></div>
                <div><span class="rd-meta-label">Views</span><span class="rd-meta-value">{{ number_format($research->view_count) }}</span></div>
            </div>
            @if($research->issn)
                <div class="rd-meta-item">
                    <div class="rd-meta-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5z"/></svg></div>
                    <div><span class="rd-meta-label">ISSN</span><span class="rd-meta-value">{{ $research->issn }}</span></div>
                </div>
            @endif
        </div>

        {{-- ABSTRACT --}}
        <div class="rd-section">
            <div class="rd-section-title">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                Abstract
            </div>
            <p class="rd-abstract">{{ $research->abstract }}</p>
        </div>

        {{-- ATTACHED FILE --}}
        @if($research->file_path)
            <div class="rd-section rd-file-section">
                <div class="rd-section-title">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    Attached File
                </div>

                {{-- File row --}}
                <div class="rd-file-info">
                    <div class="rd-file-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    </div>
                    <span class="rd-file-name">{{ $research->file_name }}</span>
                    <div class="rd-file-actions">
                        {{-- View button — toggles inline PDF viewer, NO new tab --}}
                        <button type="button" onclick="togglePdfViewer()" id="viewerBtn" class="rd-btn rd-btn-outline">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            View
                        </button>
                    </div>
                </div>

                {{-- Inline PDF Viewer — hidden by default, loads on click --}}
                <div id="pdfViewer" style="display:none; margin-top:14px; border:1.5px solid #e8dff5; border-radius:12px; overflow:hidden;">
                    <div style="display:flex; align-items:center; justify-content:space-between; padding:10px 16px; background:#faf8ff; border-bottom:1px solid #e8dff5;">
                        <span style="font-size:13px; font-weight:600; color:#5b3d8a;">{{ $research->file_name }}</span>
                        <button type="button" onclick="togglePdfViewer()" style="background:none; border:none; cursor:pointer; font-size:13px; color:#a090bc; font-weight:600; padding:0;">✕ Close</button>
                    </div>
                    <iframe
                        id="pdfFrame"
                        src=""
                        data-src="{{ route('admin.research.view-file', $research) }}"
                        style="width:100%; height:80vh; border:none; display:block;"
                        allowfullscreen>
                    </iframe>
                </div>
            </div>
        @else
            <div class="rd-section">
                <div class="rd-no-file">No file attached to this research.</div>
            </div>
        @endif

        {{-- REJECTION REASON --}}
        @if($research->rejection_reason)
            <div class="rd-section rd-rejection">
                <div class="rd-section-title">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    Correction Remarks
                </div>
                <p class="rd-rejection-text">{{ $research->rejection_reason }}</p>
            </div>
        @endif

        {{-- ACTIONS --}}
        @if($adminUser->isGlobalAdmin() && $research->status == 'pending')
            <div class="rd-actions">
                <form method="POST" action="{{ route('admin.research.approve', $research) }}" style="display:inline">
                    @csrf
                    <button type="submit" class="rd-btn rd-btn-approve">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        Publish
                    </button>
                </form>
                <button class="rd-btn rd-btn-reject" onclick="openRejectModal({{ $research->id }})">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    Return for Correction
                </button>
            </div>
        @elseif($adminUser->isGlobalAdmin() && $research->status == 'approved')
            <div class="rd-actions">
                <form method="POST" action="{{ route('admin.research.archive', $research) }}" style="display:inline" onsubmit="return confirm('Archive and unpublish this research?')">
                    @csrf
                    <button type="submit" class="rd-btn rd-btn-archive">
                        Archive / Unpublish
                    </button>
                </form>
            </div>
        @elseif($adminUser->isGlobalAdmin() && $research->status == 'archived')
            <div class="rd-actions">
                <form method="POST" action="{{ route('admin.research.publish', $research) }}" style="display:inline" onsubmit="return confirm('Publish this archived research again?')">
                    @csrf
                    <button type="submit" class="rd-btn rd-btn-approve">
                        Publish Again
                    </button>
                </form>
            </div>
        @endif

    </div>
</div>

{{-- REJECT MODAL --}}
<div id="rejectModal" class="rd-modal-overlay" style="display:none">
    <div class="rd-modal">
        <div class="rd-modal-header">
            <div class="rd-modal-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            </div>
            <div>
                <h3 class="rd-modal-title">Return for Correction</h3>
                <p class="rd-modal-sub">Provide remarks so the Coordinator can correct and resubmit the research.</p>
            </div>
        </div>
        <form id="rejectForm" method="POST">
            @csrf
            <div class="rd-modal-body">
                <label class="rd-modal-label">Reason <span style="color:#e53e3e">*</span></label>
                <textarea name="reason" rows="4" required placeholder="Explain what needs correction, e.g. incorrect author name or abstract."></textarea>
            </div>
            <div class="rd-modal-actions">
                <button type="button" class="rd-btn rd-btn-ghost" onclick="closeRejectModal()">Cancel</button>
                <button type="submit" class="rd-btn rd-btn-reject">Return for Correction</button>
            </div>
        </form>
    </div>
</div>

<style>
.rd-shell{width:min(100%,1280px);margin:0 auto;}
.rd-card{background:#fff;border-radius:20px;border:1px solid rgba(107,47,160,.1);box-shadow:0 4px 32px rgba(59,15,122,.06),0 1px 4px rgba(0,0,0,.03);overflow:hidden;margin-bottom:40px;}
.rd-header{padding:28px 36px 24px;background:linear-gradient(160deg,#fdfbff 0%,#f8f4fe 100%);border-bottom:1px solid #f0eaf9;}
.rd-header-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;}
.rd-badges{display:flex;align-items:center;gap:8px;}
.rd-type-badge{display:inline-flex;align-items:center;padding:5px 14px;background:#f0eaf9;border:1.5px solid #e2d5f4;border-radius:50px;font-size:12px;font-weight:700;color:#6b2fa0;text-transform:uppercase;letter-spacing:.5px;}
.rd-status-badge{display:inline-flex;align-items:center;padding:5px 14px;border-radius:50px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;}
.rd-status-pending{background:#fef9c3;border:1.5px solid #fde047;color:#854d0e;}
.rd-status-approved{background:#dcfce7;border:1.5px solid #86efac;color:#166534;}
.rd-status-archived{background:#e5e7eb;border:1.5px solid #d1d5db;color:#374151;}
.rd-status-rejected{background:#fee2e2;border:1.5px solid #fca5a5;color:#991b1b;}
.rd-back-btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:#fff;border:1.5px solid #e2d5f4;border-radius:50px;font-size:13px;font-weight:600;color:#6b2fa0;text-decoration:none;transition:all .15s;}
.rd-back-btn:hover{background:#f4f0fc;border-color:#c4a8e8;}
.rd-title{font-size:24px;font-weight:800;color:#1a0638;margin:0 0 14px;line-height:1.32;letter-spacing:-.3px;max-width:1100px;}
.rd-keywords{display:flex;flex-wrap:wrap;gap:6px;}
.rd-keyword{padding:4px 12px;background:#fff;border:1.5px solid #e2d5f4;border-radius:50px;font-size:12px;color:#7c4daa;font-weight:500;}
.rd-meta-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:0;border-bottom:1px solid #f0eaf9;}
.rd-meta-item{display:flex;align-items:flex-start;gap:10px;min-width:0;padding:18px 24px;border-right:1px solid #f0eaf9;border-bottom:1px solid #f0eaf9;}
.rd-meta-item:nth-child(4n){border-right:none;}
.rd-meta-icon{width:30px;height:30px;border-radius:8px;background:#f0eaf9;border:1px solid #e2d5f4;display:flex;align-items:center;justify-content:center;color:#6b2fa0;flex-shrink:0;margin-top:2px;}
.rd-meta-item>div{display:flex;flex-direction:column;gap:2px;min-width:0;}
.rd-meta-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#a090bc;}
.rd-meta-value{font-size:14px;font-weight:600;color:#1a0638;word-break:break-word;}
.rd-meta-sub{font-size:12px;color:#a090bc;word-break:break-word;}
.rd-section{padding:24px 36px;border-bottom:1px solid #f0eaf9;}
.rd-section:last-of-type{border-bottom:none;}
.rd-section-title{display:flex;align-items:center;gap:8px;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:1px;color:#6b2fa0;margin-bottom:14px;}
.rd-abstract{font-size:15px;line-height:1.85;color:#3d2060;margin:0;}
.rd-file-info{display:flex;align-items:center;gap:14px;background:#faf8ff;border:1.5px solid #e8dff5;border-radius:12px;padding:14px 18px;}
.rd-file-icon{width:42px;height:42px;border-radius:10px;background:#f0eaf9;border:1px solid #e2d5f4;display:flex;align-items:center;justify-content:center;color:#6b2fa0;flex-shrink:0;}
.rd-file-name{flex:1;font-size:14px;font-weight:600;color:#1a0638;word-break:break-all;}
.rd-file-actions{display:flex;gap:8px;flex-shrink:0;}
.rd-no-file{font-size:14px;color:#a090bc;font-style:italic;}
.rd-rejection{background:#fef2f2;border-top:1px solid #fecaca;border-bottom:1px solid #fecaca;
.rd-rejection .rd-section-title{color:#b91c1c;}
.rd-rejection-text{font-size:14px;line-height:1.75;color:#7f1d1d;margin:0;}
.rd-actions{display:flex;align-items:center;gap:10px;padding:20px 36px;background:linear-gradient(160deg,#fdfbff 0%,#f8f4fe 100%);border-top:1px solid #f0eaf9;}
.rd-btn{display:inline-flex;align-items:center;gap:7px;padding:10px 20px;border-radius:50px;font-size:13.5px;font-weight:700;cursor:pointer;text-decoration:none;border:none;transition:all .16s ease;font-family:inherit;}
.rd-btn-primary{background:#3b0f7a;color:#fff;box-shadow:0 4px 14px rgba(59,15,122,.28);}
.rd-btn-primary:hover{background:#2d0a5e;transform:translateY(-1px);}
.rd-btn-outline{background:#fff;color:#6b2fa0;border:1.5px solid #e2d5f4;}
.rd-btn-outline:hover{background:#f4f0fc;border-color:#c4a8e8;}
.rd-btn-ghost{background:#fff;color:#8b7aaa;border:1.5px solid #e8dff5;}
.rd-btn-ghost:hover{background:#f4f0fc;color:#3b0f7a;}
.rd-btn-approve{background:#16a34a;color:#fff;box-shadow:0 4px 14px rgba(22,163,74,.25);}
.rd-btn-approve:hover{background:#15803d;transform:translateY(-1px);}
.rd-btn-archive{background:#4b5563;color:#fff;box-shadow:0 4px 14px rgba(75,85,99,.25);}
.rd-btn-archive:hover{background:#374151;transform:translateY(-1px);}
.rd-btn-reject{background:#dc2626;color:#fff;box-shadow:0 4px 14px rgba(220,38,38,.25);}
.rd-btn-reject:hover{background:#b91c1c;transform:translateY(-1px);}
.rd-modal-overlay{position:fixed;inset:0;background:rgba(26,6,56,.45);backdrop-filter:blur(3px);display:flex;align-items:center;justify-content:center;z-index:9999;padding:20px;}
.rd-modal{background:#fff;border-radius:20px;border:1px solid rgba(107,47,160,.12);box-shadow:0 24px 64px rgba(59,15,122,.22);width:100%;max-width:480px;overflow:hidden;}
.rd-modal-header{display:flex;align-items:flex-start;gap:14px;padding:24px 28px 20px;background:linear-gradient(160deg,#fdfbff 0%,#f8f4fe 100%);border-bottom:1px solid #f0eaf9;}
.rd-modal-icon{width:40px;height:40px;border-radius:10px;background:#fee2e2;border:1px solid #fca5a5;display:flex;align-items:center;justify-content:center;color:#dc2626;flex-shrink:0;}
.rd-modal-title{font-size:16px;font-weight:800;color:#1a0638;margin:0 0 3px;}
.rd-modal-sub{font-size:12.5px;color:#a090bc;margin:0;}
.rd-modal-body{padding:20px 28px;}
.rd-modal-label{display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:#5b3d8a;margin-bottom:8px;}
.rd-modal-body textarea{width:100%;padding:10px 14px;border:1.5px solid #e8dff5;border-radius:10px;background:#faf8ff;font-size:14px;color:#1a0638;font-family:inherit;line-height:1.6;resize:vertical;transition:border-color .15s,box-shadow .15s;box-sizing:border-box;}
.rd-modal-body textarea:focus{outline:none;border-color:#7c3aed;background:#fff;box-shadow:0 0 0 3px rgba(124,58,237,.1);}
.rd-modal-actions{display:flex;align-items:center;justify-content:flex-end;gap:10px;padding:16px 28px 24px;border-top:1px solid #f0eaf9;}
@media(min-width:1180px){.rd-meta-grid{grid-template-columns:repeat(4,minmax(0,1fr));}.rd-abstract{max-width:1120px;}}
@media(max-width:768px){.rd-shell{width:100%;}.rd-meta-grid{grid-template-columns:repeat(2,1fr);}.rd-meta-item:nth-child(4n){border-right:1px solid #f0eaf9;}.rd-meta-item:nth-child(2n){border-right:none;}.rd-header,.rd-section,.rd-actions{padding-left:20px;padding-right:20px;}.rd-file-info{flex-wrap:wrap;}.rd-file-actions{width:100%;}}
@media(max-width:480px){.rd-meta-grid{grid-template-columns:1fr;}.rd-meta-item{border-right:none!important;}.rd-title{font-size:18px;}}
</style>

@push('scripts')
<script>
function togglePdfViewer() {
    const viewer = document.getElementById('pdfViewer');
    const frame  = document.getElementById('pdfFrame');
    const btn    = document.getElementById('viewerBtn');

    if (viewer.style.display === 'none') {
        // Load the PDF only once — lazy load
        if (!frame.src || frame.src === window.location.href) {
            frame.src = frame.getAttribute('data-src');
        }
        viewer.style.display = 'block';
        btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg> Close';
        viewer.scrollIntoView({ behavior: 'smooth', block: 'start' });
    } else {
        viewer.style.display = 'none';
        btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg> View';
    }
}

function openRejectModal(id) {
    document.getElementById('rejectForm').action = '/admin/researches/' + id + '/reject';
    document.getElementById('rejectModal').style.display = 'flex';
}
function closeRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
}
document.getElementById('rejectModal').addEventListener('click', function(e) {
    if (e.target === this) closeRejectModal();
});
</script>
@endpush

@endsection
