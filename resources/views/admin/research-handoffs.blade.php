@extends('layouts.admin')
@section('title', 'Research Handoffs')
@section('page-title', 'Research Handoffs')

@section('content')
@php
    $adminUser = auth()->user();
    $isDean = $adminUser->isDepartmentDean();
    $isCoordinator = $adminUser->isResearchCoordinator();
    $statusLabels = [
        \App\Models\ResearchHandoff::STATUS_PENDING => 'Pending',
        \App\Models\ResearchHandoff::STATUS_ADDED => 'Added',
    ];
    $firstPendingHandoff = $isCoordinator
        ? $handoffs->getCollection()->firstWhere('status', \App\Models\ResearchHandoff::STATUS_PENDING)
        : null;
    $selectedPendingHandoff = $firstPendingHandoff;

    if ($isCoordinator && old('handoff_id')) {
        $selectedPendingHandoff = $handoffs->getCollection()->firstWhere('id', (int) old('handoff_id')) ?: $firstPendingHandoff;
    }

    $submissionCategories = \App\Models\Research::adminSubmissionCategories();
    $defaultCategory = old('submission_category', \App\Models\Research::SUBMISSION_CATEGORY_FACULTY_JOURNAL);
    $programOptions = \App\Models\Research::programsByDepartment();
    $journalTypes = \App\Models\Research::journalTypeOptions();
    $yearOptions = range(max(2026, now('Asia/Manila')->year), 2022);
    $oldAuthors = old('authors', ['']);

    if (! is_array($oldAuthors) || $oldAuthors === []) {
        $oldAuthors = [''];
    }
@endphp

<div class="rh-shell">
    <section class="rh-list">
        @forelse($handoffs as $handoff)
            @php
                $canAddThisHandoff = $isCoordinator && $handoff->status === \App\Models\ResearchHandoff::STATUS_PENDING;
            @endphp
            <article
                class="rh-card{{ $canAddThisHandoff ? ' rh-card-selectable' : '' }}{{ $selectedPendingHandoff && $handoff->id === $selectedPendingHandoff->id ? ' is-selected' : '' }}"
                @if($canAddThisHandoff)
                    data-handoff-id="{{ $handoff->id }}"
                    data-add-research-url="{{ route('admin.research-handoffs.add-research', $handoff) }}"
                    data-handoff-title="{{ $handoff->title }}"
                    data-handoff-department="{{ $handoff->department }}"
                    data-handoff-file-name="{{ $handoff->file_name }}"
                    data-handoff-file-url="{{ route('admin.research-handoffs.file', ['handoff' => $handoff, 'download' => 1]) }}"
                    role="button"
                    tabindex="0"
                    aria-label="Select {{ $handoff->title }} for Add Research"
                @endif
            >
                <div class="rh-card-main">
                    <div class="rh-card-head">
                        @if($handoff->status !== \App\Models\ResearchHandoff::STATUS_PENDING)
                            <span class="rh-status rh-status-{{ $handoff->status }}">{{ $statusLabels[$handoff->status] ?? ucfirst($handoff->status) }}</span>
                        @endif
                        <span>{{ $handoff->created_at->format('M d, Y h:i A') }}</span>
                    </div>
                    <h3>{{ $handoff->title }}</h3>
                    <div class="rh-meta">
                        <span>{{ $handoff->department }}</span>
                    </div>
                </div>

                <div class="rh-actions">
                    <a href="{{ route('admin.research-handoffs.file', ['handoff' => $handoff, 'download' => 1]) }}" class="rh-btn rh-btn-pdf">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        Download PDF
                    </a>
                    @if($handoff->research)
                        @if($adminUser->isGlobalAdmin() || $isDean)
                            <a href="{{ route('admin.research.show', $handoff->research) }}" class="rh-btn rh-btn-ghost">View Record</a>
                        @else
                            <span class="rh-done">Forwarded for admin review</span>
                        @endif
                    @endif
                </div>
            </article>
        @empty
            <div class="rh-empty">
                <strong>No research handoffs yet.</strong>
                <p>
                    @if($isDean)
                        Submit the final defended research file once it is ready for your coordinator.
                    @elseif($isCoordinator)
                        Files submitted by your Department Dean will appear here.
                    @else
                        Department handoffs will appear here when deans submit files.
                    @endif
                </p>
            </div>
        @endforelse
    </section>

    <div class="rh-pagination">{{ $handoffs->links() }}</div>
</div>

@if($isCoordinator && $firstPendingHandoff)
    <button type="button" class="rh-btn rh-btn-primary rh-fab" id="addResearchFab" onclick="openAddResearchModal()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add Research
    </button>

    <div id="addResearchModal" class="rh-modal" aria-hidden="true">
        <div class="rh-modal-dialog rh-add-dialog" role="dialog" aria-modal="true" aria-labelledby="addResearchModalTitle">
            <div class="rh-modal-header">
                <div>
                    <h2 id="addResearchModalTitle">Add Research</h2>
                </div>
                <button type="button" class="rh-modal-close" onclick="closeAddResearchModal()" aria-label="Close add research modal">&times;</button>
            </div>

            <div class="rh-modal-body">
                @if($errors->any())
                    <div class="rh-alert rh-alert-error">
                        @foreach($errors->all() as $error)
                            <span>{{ $error }}</span>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.store-research') }}" class="rh-add-form" id="coordinatorResearchForm">
                    @csrf
                    <input type="hidden" name="handoff_id" id="add_handoff_id" value="{{ old('handoff_id', $selectedPendingHandoff?->id) }}">
                    <input type="hidden" name="department" id="add_department" value="{{ old('department', $selectedPendingHandoff?->department) }}">

                    <div class="rh-add-context">
                        <div>
                            <span>Selected Handoff</span>
                            <strong id="addSelectedTitle">{{ $selectedPendingHandoff?->title }}</strong>
                            <small id="addSelectedDepartment">{{ $selectedPendingHandoff?->department }}</small>
                        </div>
                        <a
                            href="{{ $selectedPendingHandoff ? route('admin.research-handoffs.file', ['handoff' => $selectedPendingHandoff, 'download' => 1]) : '#' }}"
                            id="addSelectedFileLink"
                            class="rh-btn rh-btn-pdf"
                        >
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                            Download PDF
                        </a>
                    </div>

                    <div class="rh-add-grid">
                        <div class="rh-submit-field rh-span-2">
                            <label for="add_title">Title <span>*</span></label>
                            <input type="text" id="add_title" name="title" value="{{ old('title', $selectedPendingHandoff?->title) }}" required maxlength="500" placeholder="Enter the full research title">
                        </div>

                        <div class="rh-submit-field">
                            <label for="add_submission_category">Submission Category <span>*</span></label>
                            <select name="submission_category" id="add_submission_category" required>
                                @foreach($submissionCategories as $value => $label)
                                    <option value="{{ $value }}" {{ $defaultCategory === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="rh-submit-field">
                            <label for="add_year_published">Year Published <span>*</span></label>
                            <select name="year_published" id="add_year_published" required>
                                <option value="">Select year</option>
                                @foreach($yearOptions as $year)
                                    <option value="{{ $year }}" {{ (string) old('year_published', max(2026, now('Asia/Manila')->year)) === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="rh-submit-field">
                            <label for="add_type">Journal Type <span>*</span></label>
                            <select name="type" id="add_type" required>
                                <option value="">Select journal type</option>
                                @foreach($journalTypes as $journalType)
                                    <option value="{{ $journalType }}" {{ old('type') === $journalType ? 'selected' : '' }}>{{ $journalType }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="rh-submit-field" id="addProgramGroup" hidden>
                            <label for="add_course">Program <span>*</span></label>
                            <select name="course" id="add_course" data-old="{{ old('course') }}">
                                <option value="">Select program</option>
                            </select>
                        </div>

                        <div class="rh-submit-field rh-span-2">
                            <label><span id="addAuthorLabel" class="rh-label-text">Co-author(s)</span> <span>*</span></label>
                            <div class="rh-author-list" id="addAuthorRepeater">
                                @foreach($oldAuthors as $author)
                                    <div class="rh-author-row" data-add-author-row>
                                        <input type="text" class="rh-author-input" name="authors[]" value="{{ $author }}" placeholder="Enter co-author name" required maxlength="150">
                                        <button type="button" class="rh-icon-btn" data-add-remove-author aria-label="Remove author">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v5"/><path d="M14 11v5"/></svg>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                            <button type="button" class="rh-add-author-btn" id="addAuthorBtn">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                <span id="addAuthorText">Add Co-author</span>
                            </button>
                        </div>

                        <div class="rh-submit-field rh-span-2">
                            <label for="add_keywords">Keywords</label>
                            <input type="text" id="add_keywords" name="keywords" value="{{ old('keywords') }}" maxlength="500" placeholder="machine learning, AI, Philippines">
                        </div>

                        <div class="rh-submit-field rh-span-2">
                            <label for="add_abstract">Abstract <span>*</span></label>
                            <textarea id="add_abstract" name="abstract" rows="6" required placeholder="Write the research abstract here">{{ old('abstract') }}</textarea>
                        </div>
                    </div>

                    <div class="rh-modal-actions">
                        <button type="button" class="rh-btn rh-btn-ghost" onclick="closeAddResearchModal()">Cancel</button>
                        <button type="submit" class="rh-btn rh-btn-primary">Submit for Review</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@if($isDean)
    <button type="button" class="rh-btn rh-btn-primary rh-fab" onclick="openSubmitFileModal()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Submit File
    </button>

    <div id="submitFileModal" class="rh-modal" aria-hidden="true">
        <div class="rh-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="submitFileModalTitle">
            <div class="rh-modal-header">
                <div>
                    <h2 id="submitFileModalTitle">Submit Research File</h2>
                </div>
                <button type="button" class="rh-modal-close" onclick="closeSubmitFileModal()" aria-label="Close submit file modal">&times;</button>
            </div>

            <div class="rh-modal-body">
                @if(! $handoffCoordinator)
                    <div class="rh-alert rh-alert-warning">
                        <strong>No active Research Coordinator found.</strong>
                        <span>Create or activate a Research Coordinator for {{ $handoffDepartment }} before submitting a research file.</span>
                    </div>
                @endif

                @if($errors->any())
                    <div class="rh-alert rh-alert-error">
                        @foreach($errors->all() as $error)
                            <span>{{ $error }}</span>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.research-handoffs.store') }}" enctype="multipart/form-data" class="rh-submit-form">
                    @csrf

                    <div class="rh-submit-grid">
                        <div class="rh-submit-field">
                            <label>Assigned Department</label>
                            <div class="rh-readonly">{{ $handoffDepartment }}</div>
                        </div>
                        <div class="rh-submit-field">
                            <label>Research Coordinator</label>
                            <div class="rh-readonly">{{ $handoffCoordinator?->name ?? 'Not assigned' }}</div>
                        </div>
                    </div>

                    <div class="rh-submit-field">
                        <label for="handoff_title">Research Title <span>*</span></label>
                        <input type="text" id="handoff_title" name="title" value="{{ old('title') }}" required maxlength="500" placeholder="Enter the defended research title">
                    </div>

                    <div class="rh-submit-field">
                        <label for="handoff_file">Final Defended PDF <span>*</span></label>
                        <label class="rh-file-upload">
                            <input type="file" id="handoff_file" name="file" accept="application/pdf,.pdf" required {{ ! $handoffCoordinator ? 'disabled' : '' }}>
                            <span class="rh-file-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                            </span>
                            <span>
                                <strong id="handoffFileName">Choose PDF file</strong>
                                <small>PDF only, maximum 30MB</small>
                            </span>
                        </label>
                    </div>

                    <div class="rh-modal-actions">
                        <button type="button" class="rh-btn rh-btn-ghost" onclick="closeSubmitFileModal()">Cancel</button>
                        <button type="submit" class="rh-btn rh-btn-primary" {{ ! $handoffCoordinator ? 'disabled' : '' }}>Submit to Coordinator</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

<style>
.rh-shell{max-width:1180px;display:grid;gap:18px;}
.rh-btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:42px;padding:0 16px;border-radius:12px;border:1px solid transparent;text-decoration:none;font:800 13px/1 inherit;cursor:pointer;white-space:nowrap;}
.rh-btn svg{width:14px;height:14px;}
.rh-btn-primary{background:#4c1d95;color:#fff;box-shadow:0 12px 24px rgba(76,29,149,.18);}
.rh-btn-ghost{background:#fff;color:#5b348f;border-color:#e8dff5;}
.rh-btn-pdf{min-height:42px;padding:0 16px;border-radius:13px;background:linear-gradient(135deg,#6d28d9 0%,#4c1d95 100%);color:#fff;border-color:#4c1d95;font-size:13px;font-weight:800;letter-spacing:.01em;box-shadow:0 12px 22px rgba(76,29,149,.2);transition:transform .14s ease,box-shadow .14s ease,background .14s ease;}
.rh-btn-pdf:hover{transform:translateY(-1px);box-shadow:0 18px 32px rgba(76,29,149,.3);}
.rh-btn-pdf svg{width:14px;height:14px;}
.rh-fab{position:fixed;right:34px;bottom:34px;z-index:70;min-height:54px;padding:0 22px;border-radius:999px;font-size:15px;box-shadow:0 18px 34px rgba(76,29,149,.28);}
.rh-fab svg{width:17px;height:17px;}
.rh-list{display:grid;gap:12px;}
.rh-card{display:flex;align-items:center;justify-content:space-between;gap:18px;background:#fff;border:1px solid #eee6fa;border-radius:16px;padding:18px 20px;box-shadow:0 6px 20px rgba(57,26,101,.04);}
.rh-card-selectable{cursor:pointer;transition:border-color .14s ease,box-shadow .14s ease,transform .14s ease;}
.rh-card-selectable:hover,.rh-card-selectable.is-selected{border-color:#7c3aed;box-shadow:0 14px 34px rgba(76,29,149,.12);transform:translateY(-1px);}
.rh-card-main{min-width:0;}
.rh-card-head{display:flex;align-items:center;gap:10px;margin-bottom:8px;color:#8b7aa8;font-size:12px;font-weight:700;}
.rh-status{display:inline-flex;padding:4px 10px;border-radius:999px;font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:.06em;}
.rh-status-pending{background:#fef9c3;color:#854d0e;border:1px solid #fde68a;}
.rh-status-added{background:#dcfce7;color:#166534;border:1px solid #bbf7d0;}
.rh-card h3{margin:0 0 8px;color:#201044;font-size:18px;line-height:1.35;}
.rh-meta{display:flex;flex-wrap:wrap;gap:8px 12px;color:#6f5a92;font-size:13px;}
.rh-actions{display:flex;align-items:center;justify-content:flex-end;gap:8px;flex-wrap:wrap;}
.rh-done{display:inline-flex;color:#166534;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:999px;padding:8px 12px;font-size:12px;font-weight:800;}
.rh-empty{background:#fff;border:1px dashed #d8c8ef;border-radius:16px;padding:34px;text-align:center;color:#6f5a92;}
.rh-empty strong{display:block;color:#201044;margin-bottom:6px;font-size:18px;}
.rh-empty p{margin:0;}
.rh-pagination{display:flex;justify-content:flex-end;}
.rh-modal{position:fixed;inset:0;background:rgba(26,6,56,.48);z-index:90;display:none;align-items:center;justify-content:center;padding:18px;}
.rh-modal.is-open{display:flex;}
.rh-modal-dialog{width:min(720px,100%);max-height:92vh;background:#fff;border:1px solid rgba(107,47,160,.14);border-radius:18px;box-shadow:0 26px 70px rgba(26,6,56,.3);overflow:hidden;display:flex;flex-direction:column;}
.rh-add-dialog{width:min(860px,100%);}
.rh-modal-header{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;padding:22px 24px 16px;border-bottom:1px solid #f0eaf9;background:linear-gradient(160deg,#fdfbff 0%,#f8f4fe 100%);}
.rh-modal-header h2{margin:0;color:#1a0638;font-size:23px;line-height:1.2;}
.rh-modal-close{width:36px;height:36px;border-radius:10px;border:1px solid #e8dff5;background:#fff;color:#5b348f;font-size:25px;line-height:1;cursor:pointer;}
.rh-modal-close:hover{background:#f8f4fe;}
.rh-modal-body{padding:20px 24px 24px;overflow-y:auto;}
.rh-alert{display:grid;gap:4px;margin-bottom:16px;padding:13px 15px;border-radius:14px;font-size:13px;line-height:1.45;}
.rh-alert-warning{background:#fff8e8;border:1px solid #fde2a8;color:#8a5300;}
.rh-alert-error{background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;}
.rh-submit-form{display:grid;gap:16px;}
.rh-submit-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;}
.rh-submit-field{display:grid;gap:8px;}
.rh-submit-field label{font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:.08em;color:#5f418f;}
.rh-submit-field label span{color:#dc2626;}
.rh-submit-field label .rh-label-text{color:inherit;}
.rh-submit-field input,.rh-submit-field textarea{width:100%;border:1.5px solid #e7ddf9;border-radius:14px;background:#fff;color:#211143;font:inherit;box-sizing:border-box;}
.rh-submit-field select{width:100%;height:46px;padding:0 14px;border:1.5px solid #e7ddf9;border-radius:14px;background:#fff;color:#211143;font:inherit;box-sizing:border-box;}
.rh-submit-field input{height:46px;padding:0 14px;}
.rh-submit-field textarea{padding:13px 14px;resize:vertical;line-height:1.55;}
.rh-submit-field input:focus,.rh-submit-field select:focus,.rh-submit-field textarea:focus{outline:none;border-color:#7c3aed;box-shadow:0 0 0 4px rgba(124,58,237,.11);}
.rh-readonly{display:flex;align-items:center;min-height:46px;padding:0 14px;border-radius:14px;background:#f8f4fe;border:1.5px solid #e7ddf9;color:#211143;font-weight:800;}
.rh-file-upload{display:flex!important;align-items:center;gap:12px;min-height:74px;padding:15px;border:1.5px dashed #cdb7ec;border-radius:16px;background:#fcfaff;cursor:pointer;text-transform:none!important;letter-spacing:0!important;}
.rh-file-upload input{position:absolute;opacity:0;pointer-events:none;}
.rh-file-icon{display:grid;place-items:center;width:42px;height:42px;border-radius:12px;background:#ede9fe;color:#6d28d9;flex-shrink:0;}
.rh-file-icon svg{width:17px;height:17px;}
.rh-file-upload strong{display:block;color:#211143;font-size:14px;margin-bottom:4px;}
.rh-file-upload small{display:block;color:#7a6a95;font-size:12px;}
.rh-modal-actions{display:flex;justify-content:flex-end;gap:10px;padding-top:16px;border-top:1px solid #f0eaf9;}
.rh-btn:disabled{opacity:.55;cursor:not-allowed;box-shadow:none;}
.rh-add-form{display:grid;gap:18px;}
.rh-add-context{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:15px;border:1px solid #eadff8;border-radius:16px;background:#fbf8ff;}
.rh-add-context div{display:grid;gap:3px;min-width:0;}
.rh-add-context span{font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:.08em;color:#6b2fa0;}
.rh-add-context strong{color:#1a0638;font-size:15px;line-height:1.35;overflow-wrap:anywhere;}
.rh-add-context small{color:#796895;font-size:12.5px;}
.rh-add-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;}
.rh-span-2{grid-column:1 / -1;}
.rh-author-list{display:grid;gap:10px;}
.rh-author-row{display:grid;grid-template-columns:minmax(0,1fr) 44px;gap:8px;align-items:center;}
.rh-icon-btn{width:44px;height:44px;border-radius:12px;border:1px solid #eadff8;background:#fff;color:#b91c1c;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;}
.rh-icon-btn svg{width:15px;height:15px;}
.rh-icon-btn:disabled{opacity:.35;cursor:not-allowed;}
.rh-add-author-btn{justify-self:start;display:inline-flex;align-items:center;gap:7px;border:1.5px solid #d8c8ee;background:#fff;color:#5b21b6;border-radius:999px;padding:9px 14px;font-weight:800;cursor:pointer;}
.rh-add-author-btn svg{width:14px;height:14px;}
@media(max-width:760px){.rh-card{align-items:stretch;flex-direction:column}.rh-actions{justify-content:flex-start}.rh-fab{right:16px;bottom:18px;left:16px;width:calc(100% - 32px);}.rh-modal{align-items:flex-end;padding:12px}.rh-modal-dialog{border-radius:16px 16px 0 0}.rh-submit-grid,.rh-add-grid{grid-template-columns:1fr}.rh-span-2{grid-column:auto}.rh-add-context{align-items:stretch;flex-direction:column}.rh-add-context .rh-btn{width:100%;}.rh-modal-actions{flex-direction:column-reverse}.rh-modal-actions .rh-btn{width:100%;}.rh-modal-header{padding-left:20px;padding-right:20px}.rh-modal-body{padding-left:20px;padding-right:20px}}
</style>

@if($isDean)
    @push('scripts')
    <script>
    function openSubmitFileModal() {
        const modal = document.getElementById('submitFileModal');

        if (!modal) {
            return;
        }

        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.getElementById('handoff_title')?.focus();
    }

    function closeSubmitFileModal() {
        const modal = document.getElementById('submitFileModal');

        if (!modal) {
            return;
        }

        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
    }

    const handoffFileInput = document.getElementById('handoff_file');
    const handoffFileName = document.getElementById('handoffFileName');

    if (handoffFileInput && handoffFileName) {
        handoffFileInput.addEventListener('change', function () {
            const file = handoffFileInput.files && handoffFileInput.files[0] ? handoffFileInput.files[0] : null;
            handoffFileName.textContent = file ? file.name : 'Choose PDF file';
        });
    }

    document.getElementById('submitFileModal')?.addEventListener('click', function (event) {
        if (event.target === event.currentTarget) {
            closeSubmitFileModal();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && document.getElementById('submitFileModal')?.classList.contains('is-open')) {
            closeSubmitFileModal();
        }
    });

    if (@json($errors->any() || session('error'))) {
        openSubmitFileModal();
    }
    </script>
    @endpush
@endif

@if($isCoordinator)
    @push('scripts')
    <script>
    const researchPrograms = @json($programOptions);
    const studentCategory = @json(\App\Models\Research::SUBMISSION_CATEGORY_STUDENT_JOURNAL);
    const addResearchModal = document.getElementById('addResearchModal');
    const addableHandoffCards = Array.from(document.querySelectorAll('[data-add-research-url]'));
    const addHandoffId = document.getElementById('add_handoff_id');
    const addDepartment = document.getElementById('add_department');
    const addTitle = document.getElementById('add_title');
    const addSelectedTitle = document.getElementById('addSelectedTitle');
    const addSelectedDepartment = document.getElementById('addSelectedDepartment');
    const addSelectedFileLink = document.getElementById('addSelectedFileLink');
    const addCategory = document.getElementById('add_submission_category');
    const addProgramGroup = document.getElementById('addProgramGroup');
    const addProgram = document.getElementById('add_course');
    const addAuthorRepeater = document.getElementById('addAuthorRepeater');
    const addAuthorBtn = document.getElementById('addAuthorBtn');
    const addAuthorLabel = document.getElementById('addAuthorLabel');
    const addAuthorText = document.getElementById('addAuthorText');

    function selectedAddCard() {
        return addableHandoffCards.find((card) => card.classList.contains('is-selected')) || addableHandoffCards[0] || null;
    }

    function syncAddResearchProgramOptions() {
        if (!addCategory || !addProgramGroup || !addProgram || !addDepartment) {
            return;
        }

        const isStudent = addCategory.value === studentCategory;
        const department = addDepartment.value;
        const selectedProgram = addProgram.dataset.old || addProgram.value;
        const programs = researchPrograms[department] || [];

        addAuthorLabel.textContent = isStudent ? 'Researchers' : 'Co-author(s)';
        addAuthorText.textContent = isStudent ? 'Add Researcher' : 'Add Co-author';
        document.querySelectorAll('.rh-author-input').forEach((input) => {
            input.placeholder = isStudent ? 'Enter researcher name' : 'Enter co-author name';
        });

        if (!isStudent) {
            addProgramGroup.hidden = true;
            addProgram.disabled = true;
            addProgram.required = false;
            addProgram.value = '';
            return;
        }

        addProgramGroup.hidden = false;
        addProgram.disabled = programs.length === 0;
        addProgram.required = true;
        addProgram.innerHTML = '';

        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = programs.length ? 'Select program' : 'No programs available';
        addProgram.appendChild(placeholder);

        programs.forEach((program) => {
            const option = document.createElement('option');
            option.value = program;
            option.textContent = program;
            option.selected = selectedProgram === program;
            addProgram.appendChild(option);
        });

        addProgram.dataset.old = '';
    }

    function updateAddAuthorButtons() {
        const rows = Array.from(document.querySelectorAll('[data-add-author-row]'));

        rows.forEach((row) => {
            const button = row.querySelector('[data-add-remove-author]');
            button.disabled = rows.length <= 1;
        });
    }

    function selectAddResearchCard(card, syncTitle = true) {
        if (!card) {
            return;
        }

        addableHandoffCards.forEach((item) => item.classList.remove('is-selected'));
        card.classList.add('is-selected');

        if (addHandoffId) {
            addHandoffId.value = card.dataset.handoffId || '';
        }

        if (addDepartment) {
            addDepartment.value = card.dataset.handoffDepartment || '';
        }

        if (addSelectedTitle) {
            addSelectedTitle.textContent = card.dataset.handoffTitle || 'Selected handoff';
        }

        if (addSelectedDepartment) {
            addSelectedDepartment.textContent = card.dataset.handoffDepartment || '';
        }

        if (addSelectedFileLink) {
            addSelectedFileLink.href = card.dataset.handoffFileUrl || '#';
        }

        if (syncTitle && addTitle) {
            addTitle.value = card.dataset.handoffTitle || '';
        }

        syncAddResearchProgramOptions();
    }

    function openAddResearchModal() {
        selectAddResearchCard(selectedAddCard(), false);

        if (!addResearchModal) {
            return;
        }

        addResearchModal.classList.add('is-open');
        addResearchModal.setAttribute('aria-hidden', 'false');
        addTitle?.focus();
    }

    function closeAddResearchModal() {
        if (!addResearchModal) {
            return;
        }

        addResearchModal.classList.remove('is-open');
        addResearchModal.setAttribute('aria-hidden', 'true');
    }

    addableHandoffCards.forEach(function (card) {
        const selectCard = function () {
            selectAddResearchCard(card);
        };

        card.addEventListener('click', function (event) {
            if (event.target.closest('a, button, input, select, textarea')) {
                return;
            }

            selectCard();
        });

        card.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                selectCard();
            }
        });
    });

    addCategory?.addEventListener('change', syncAddResearchProgramOptions);

    addAuthorBtn?.addEventListener('click', function () {
        const row = document.createElement('div');
        row.className = 'rh-author-row';
        row.dataset.addAuthorRow = 'true';

        row.innerHTML = `
            <input type="text" class="rh-author-input" name="authors[]" placeholder="${addCategory?.value === studentCategory ? 'Enter researcher name' : 'Enter co-author name'}" required maxlength="150">
            <button type="button" class="rh-icon-btn" data-add-remove-author aria-label="Remove author">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v5"/><path d="M14 11v5"/></svg>
            </button>
        `;

        addAuthorRepeater.appendChild(row);
        row.querySelector('input')?.focus();
        updateAddAuthorButtons();
    });

    addAuthorRepeater?.addEventListener('click', function (event) {
        const removeButton = event.target.closest('[data-add-remove-author]');

        if (!removeButton || document.querySelectorAll('[data-add-author-row]').length <= 1) {
            return;
        }

        removeButton.closest('[data-add-author-row]').remove();
        updateAddAuthorButtons();
    });

    addResearchModal?.addEventListener('click', function (event) {
        if (event.target === event.currentTarget) {
            closeAddResearchModal();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && addResearchModal?.classList.contains('is-open')) {
            closeAddResearchModal();
        }
    });

    selectAddResearchCard(selectedAddCard(), false);
    syncAddResearchProgramOptions();
    updateAddAuthorButtons();

    if (@json($errors->any())) {
        openAddResearchModal();
    }
    </script>
    @endpush
@endif
@endsection
