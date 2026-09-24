@extends('layouts.admin')
@section('title', isset($handoff) ? 'Add Research from Handoff' : 'Add Research')
@section('page-title', isset($handoff) ? 'Add Research from Handoff' : 'Add Research')

@section('content')
@php
    $submissionCategories = \App\Models\Research::adminSubmissionCategories();
    $defaultCategory = old('submission_category', \App\Models\Research::SUBMISSION_CATEGORY_FACULTY_JOURNAL);
    $departmentOptions = \App\Models\Research::departmentOptions();
    $programOptions = \App\Models\Research::programsByDepartment();
    $journalTypes = \App\Models\Research::journalTypeOptions();
    $yearOptions = range(2026, 2022);
    $semesterOptions = \App\Models\Semester::semesterOptions();
    $schoolYears = \App\Models\Semester::query()->distinct()->orderByDesc('school_year')->pluck('school_year');
    $oldAuthors = old('authors', ['']);
    $handoff = $handoff ?? null;
    $isCoordinatorSubmission = auth()->user()?->isResearchCoordinator() && $handoff;
    $defaultDepartment = old('department', $handoff?->department);
    $selectedSchoolYear = old('school_year');
    $selectedSemester = old('semester');

    if (! is_array($oldAuthors) || $oldAuthors === []) {
        $oldAuthors = [''];
    }
@endphp

<div class="ar-shell">
    <div class="ar-card">
        <div class="ar-card-header">
            <div class="ar-header-left">
                <div class="ar-header-icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
                </div>
                <div>
                    <h3 class="ar-header-title">{{ $isCoordinatorSubmission ? 'Add Research from Dean Handoff' : 'Add New Research' }}</h3>
                    <p class="ar-header-sub">
                        {{ $isCoordinatorSubmission ? 'Complete the metadata and forward it to the Research Office/Admin for review.' : 'Publish a faculty or student research journal entry.' }}
                    </p>
                </div>
            </div>
            <a href="{{ $isCoordinatorSubmission ? route('admin.coordinator.dean-submissions') : route('admin.researches') }}" class="ar-back-btn">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                Back
            </a>
        </div>

        @if($errors->any())
            <div class="ar-alert-error">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <div>
                    @foreach($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.store-research') }}" enctype="multipart/form-data" class="ar-form" id="adminResearchForm">
            @csrf
            <input type="hidden" name="workflow_action" id="workflowAction" value="submit">
            @if($isCoordinatorSubmission)
                <input type="hidden" name="handoff_id" value="{{ $handoff->id }}">
            @endif

            <div class="ar-stepper" aria-label="Add research steps">
                <span class="ar-step is-active" data-step-indicator="1"><strong>1</strong> Basic</span>
                <span class="ar-step" data-step-indicator="2"><strong>2</strong> Department</span>
                <span class="ar-step" data-step-indicator="3"><strong>3</strong> Content</span>
                <span class="ar-step" data-step-indicator="4"><strong>4</strong> File</span>
                <span class="ar-step" data-step-indicator="5"><strong>5</strong> Review</span>
            </div>

            <section class="ar-step-panel" data-step-panel="1">
                <div class="ar-section-label">
                    <span class="ar-section-num">1</span> Basic Information
                </div>

                <div class="ar-form-row">
                    <div class="ar-form-group ar-span-2">
                        <label for="title">Title <span class="ar-required">*</span></label>
                        <input type="text" id="title" name="title" value="{{ old('title', $handoff?->title) }}" placeholder="Enter the full research title" required maxlength="500">
                    </div>
                </div>

                <div class="ar-form-row">
                    <div class="ar-form-group">
                        <label for="submission_category">Submission Category <span class="ar-required">*</span></label>
                        <select name="submission_category" id="submission_category" required>
                            @foreach($submissionCategories as $value => $label)
                                <option value="{{ $value }}" {{ $defaultCategory === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="ar-form-group">
                        <label for="year_published">Year Published <span class="ar-required">*</span></label>
                        <select name="year_published" id="year_published" required>
                            <option value="">Select year</option>
                            @foreach($yearOptions as $year)
                                <option value="{{ $year }}" {{ (string) old('year_published', 2026) === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="ar-form-row">
                    <div class="ar-form-group">
                        <label for="school_year">School Year</label>
                        <select name="school_year" id="school_year">
                            <option value="">Not set</option>
                            @foreach($schoolYears as $schoolYear)
                                <option value="{{ $schoolYear }}" {{ $selectedSchoolYear === $schoolYear ? 'selected' : '' }}>{{ $schoolYear }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="ar-form-group">
                        <label for="semester">Semester</label>
                        <select name="semester" id="semester">
                            <option value="">Not set</option>
                            @foreach($semesterOptions as $semester)
                                <option value="{{ $semester }}" {{ $selectedSemester === $semester ? 'selected' : '' }}>
                                    {{ \App\Models\Semester::semesterLabels()[$semester] ?? $semester }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="ar-form-group">
                    <label><span id="authorLabel">Co-author(s)</span> <span class="ar-required">*</span></label>
                    <div class="ar-author-list" id="authorRepeater">
                        @foreach($oldAuthors as $author)
                            <div class="ar-author-row" data-author-row>
                                <input type="text" class="ar-author-input" name="authors[]" value="{{ $author }}" placeholder="Enter co-author name" required maxlength="150">
                                <button type="button" class="ar-icon-btn ar-remove-author" data-remove-author aria-label="Remove author">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v5"/><path d="M14 11v5"/></svg>
                                </button>
                            </div>
                        @endforeach
                    </div>
                    <button type="button" class="ar-add-author" id="addAuthorBtn">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        <span id="addAuthorText">Add Co-author</span>
                    </button>
                </div>
            </section>

            <section class="ar-step-panel" data-step-panel="2" hidden>
                <div class="ar-section-label">
                    <span class="ar-section-num">2</span> Department & Program
                </div>

                <div class="ar-form-row">
                    <div class="ar-form-group">
                        <label for="department">Department <span class="ar-required">*</span></label>
                        @if($isCoordinatorSubmission)
                            <input type="hidden" name="department" id="department" value="{{ $defaultDepartment }}">
                            <div class="ar-fixed-value">{{ $defaultDepartment }}</div>
                        @else
                            <select name="department" id="department" required>
                                <option value="">Select department</option>
                                @foreach($departmentOptions as $department)
                                    <option value="{{ $department }}" {{ $defaultDepartment === $department ? 'selected' : '' }}>{{ $department }}</option>
                                @endforeach
                            </select>
                        @endif
                    </div>

                    <div class="ar-form-group" id="programGroup">
                        <label for="course">Program <span class="ar-required">*</span></label>
                        <select name="course" id="course" data-old="{{ old('course') }}">
                            <option value="">Select department first</option>
                        </select>
                    </div>
                </div>

                <div class="ar-form-row">
                    <div class="ar-form-group">
                        <label for="type">Journal Type <span class="ar-required">*</span></label>
                        <select name="type" id="type" required>
                            <option value="">Select journal type</option>
                            @foreach($journalTypes as $journalType)
                                <option value="{{ $journalType }}" {{ old('type') === $journalType ? 'selected' : '' }}>{{ $journalType }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </section>

            <section class="ar-step-panel" data-step-panel="3" hidden>
                <div class="ar-section-label">
                    <span class="ar-section-num">3</span> Content & Keywords
                </div>

                <div class="ar-form-group">
                    <label for="keywords">Keywords</label>
                    <div class="ar-input-hint-wrap">
                        <input type="text" id="keywords" name="keywords" value="{{ old('keywords') }}" placeholder="machine learning, AI, Philippines" maxlength="500">
                        <span class="ar-input-hint">Comma-separated</span>
                    </div>
                </div>

                <div class="ar-form-group">
                    <label for="abstract">Abstract <span class="ar-required">*</span></label>
                    <textarea id="abstract" name="abstract" rows="7" placeholder="Write the research abstract here" required>{{ old('abstract') }}</textarea>
                </div>
            </section>

            <section class="ar-step-panel" data-step-panel="4" hidden>
                <div class="ar-section-label">
                    <span class="ar-section-num">4</span> File Upload
                </div>

                <div class="ar-form-group">
                    @if($isCoordinatorSubmission)
                        <label>Received Full Paper File</label>
                        <div class="ar-handoff-file">
                            <div class="ar-file-icon" aria-hidden="true">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                            </div>
                            <div>
                                <strong>{{ $handoff->file_name }}</strong>
                                <span>Submitted by {{ $handoff->dean?->name ?? 'Department Dean' }}</span>
                            </div>
                            <a href="{{ route('admin.research-handoffs.file', $handoff) }}" target="_blank" rel="noopener">View PDF</a>
                        </div>
                    @else
                        <label for="fileInput">Full Paper File <span class="ar-required">*</span></label>
                        <div class="ar-file-zone" id="fileUploadArea">
                            <input type="file" name="file" id="fileInput" accept="application/pdf,.pdf" required>
                            <div class="ar-file-zone-inner">
                                <div class="ar-file-icon" aria-hidden="true">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                </div>
                                <p class="ar-file-label" id="fileName">Click to browse or drag and drop PDF here</p>
                                <p class="ar-file-sub">PDF only - max 30MB</p>
                            </div>
                        </div>
                        <p class="ar-file-error" id="fileError" aria-live="polite"></p>
                    @endif
                </div>
            </section>

            <section class="ar-step-panel" data-step-panel="5" hidden>
                <div class="ar-section-label">
                    <span class="ar-section-num">5</span> Review & Submit
                </div>

                <article class="ar-preview-card">
                    <div class="ar-preview-top">
                        <span class="ar-preview-type" id="previewType">Research: Journal Type</span>
                        <span class="ar-preview-views" id="previewViews">Views 0</span>
                    </div>
                    <h4 id="previewTitle">Research title</h4>
                    <p class="ar-preview-author" id="previewAuthor">Author Name</p>
                    <p class="ar-preview-dept" id="previewDepartment">Department</p>
                    <p class="ar-preview-program" id="previewProgram" hidden>Program</p>
                    <p class="ar-preview-abstract" id="previewAbstract">Abstract preview</p>
                    <div class="ar-preview-footer">
                        <span id="previewYear">2026</span>
                        <span id="previewFileName">PDF file</span>
                    </div>
                </article>

                <div class="ar-notice">
                    <div class="ar-notice-icon" aria-hidden="true">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                    @if($isCoordinatorSubmission)
                        <p>This record will be sent to the <strong>Research Office/Admin for review</strong> before publishing.</p>
                    @else
                        <p>Research added by admin will be <strong>automatically approved</strong> and visible on the site immediately.</p>
                    @endif
                </div>
            </section>

            <div class="ar-form-actions">
                <a href="{{ $isCoordinatorSubmission ? route('admin.coordinator.dean-submissions') : route('admin.researches') }}" class="ar-btn ar-btn-ghost" id="cancelBtn">Cancel</a>
                <button type="button" class="ar-btn ar-btn-ghost ar-step-tooltip" id="prevStepBtn" data-tooltip="Go to the previous step" aria-label="Back to the previous step" hidden>
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                    Back
                </button>
                <button type="button" class="ar-btn ar-btn-primary ar-step-tooltip" id="nextStepBtn" data-tooltip="Continue to Department" aria-label="Next: Department">
                    Next
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </button>
                @if($isCoordinatorSubmission)
                    <button type="submit" class="ar-btn ar-btn-ghost" id="saveDraftBtn" hidden data-workflow-action="draft">
                        Save Draft
                    </button>
                @endif
                <button type="submit" class="ar-btn ar-btn-primary" id="submitResearchBtn" hidden>
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    {{ $isCoordinatorSubmission ? 'Submit to Admin' : 'Publish Research' }}
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.ar-shell {
    max-width: 960px;
    margin: 0 auto;
}

.ar-card {
    background: #fff;
    border-radius: 20px;
    border: 1px solid rgba(107,47,160,.1);
    box-shadow: 0 4px 32px rgba(59,15,122,.06), 0 1px 4px rgba(0,0,0,.03);
    overflow: hidden;
    margin-bottom: 40px;
}

.ar-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    padding: 24px 32px;
    background: linear-gradient(160deg, #fdfbff 0%, #f8f4fe 100%);
    border-bottom: 1px solid #f0eaf9;
}

.ar-header-left {
    display: flex;
    align-items: center;
    gap: 14px;
    min-width: 0;
}

.ar-header-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: #f0eaf9;
    border: 1px solid #e2d5f4;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6b2fa0;
    flex-shrink: 0;
}

.ar-header-title {
    font-size: 17px;
    font-weight: 800;
    color: #1a0638;
    margin: 0 0 2px;
}

.ar-header-sub {
    font-size: 12.5px;
    color: #7d6a9d;
    margin: 0;
}

.ar-back-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    background: #fff;
    border: 1.5px solid #e2d5f4;
    border-radius: 50px;
    font-size: 13px;
    font-weight: 700;
    color: #6b2fa0;
    text-decoration: none;
    transition: all .15s;
    white-space: nowrap;
}

.ar-back-btn:hover {
    background: #f4f0fc;
    border-color: #c4a8e8;
}

.ar-alert-error {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin: 20px 32px 0;
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 12px;
    padding: 14px 16px;
    color: #b91c1c;
    font-size: 13px;
}

.ar-alert-error svg {
    flex-shrink: 0;
    margin-top: 1px;
}

.ar-alert-error p {
    margin: 0 0 3px;
}

.ar-alert-error p:last-child {
    margin: 0;
}

.ar-form {
    padding: 28px 32px;
}

.ar-stepper {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 8px;
    margin-bottom: 26px;
}

.ar-step {
    min-height: 42px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 8px 10px;
    border-radius: 12px;
    background: #faf8ff;
    border: 1px solid #eadff8;
    color: #8a78a8;
    font-size: 12px;
    font-weight: 800;
}

.ar-step strong {
    width: 20px;
    height: 20px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #efe7fb;
    color: #6b2fa0;
    font-size: 11px;
}

.ar-step.is-active,
.ar-step.is-complete {
    background: #3b0f7a;
    border-color: #3b0f7a;
    color: #fff;
}

.ar-step.is-active strong,
.ar-step.is-complete strong {
    background: rgba(255,255,255,.18);
    color: #fff;
}

.ar-section-label {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #6b2fa0;
    margin: 0 0 16px;
    padding-bottom: 10px;
    border-bottom: 1px solid #f0eaf9;
}

.ar-section-num {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: #f0eaf9;
    border: 1.5px solid #e0d4f5;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 800;
    color: #6b2fa0;
    flex-shrink: 0;
}

.ar-step-panel {
    min-height: 360px;
}

.ar-form-row {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
}

.ar-span-2 {
    grid-column: 1 / -1;
}

.ar-form-group {
    display: flex;
    flex-direction: column;
    margin-bottom: 16px;
}

.ar-form-group label {
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .7px;
    color: #5b3d8a;
    margin-bottom: 7px;
}

.ar-required {
    color: #e53e3e;
}

.ar-form-group input[type="text"],
.ar-form-group select,
.ar-form-group textarea {
    width: 100%;
    padding: 10px 14px;
    border: 1.5px solid #e8dff5;
    border-radius: 10px;
    background: #faf8ff;
    font-size: 14px;
    color: #1a0638;
    transition: border-color .15s, box-shadow .15s;
    box-sizing: border-box;
    font-family: inherit;
}

.ar-form-group textarea {
    resize: vertical;
    line-height: 1.6;
}

.ar-form-group input:focus,
.ar-form-group select:focus,
.ar-form-group textarea:focus {
    outline: none;
    border-color: #7c3aed;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(124,58,237,.1);
}

.ar-form-group select:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.ar-fixed-value {
    display: flex;
    align-items: center;
    min-height: 43px;
    padding: 10px 14px;
    border: 1.5px solid #e8dff5;
    border-radius: 10px;
    background: #f4f0fc;
    color: #1a0638;
    font-size: 14px;
    font-weight: 700;
    box-sizing: border-box;
}

.ar-input-hint-wrap {
    position: relative;
}

.ar-input-hint {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 11px;
    color: #9f8aba;
    pointer-events: none;
}

.ar-author-list {
    display: grid;
    gap: 10px;
}

.ar-author-row {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 42px;
    gap: 8px;
    align-items: center;
}

.ar-icon-btn {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    border: 1px solid #e8dff5;
    background: #fff;
    color: #b91c1c;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}

.ar-icon-btn:disabled {
    opacity: .35;
    cursor: not-allowed;
}

.ar-add-author {
    align-self: flex-start;
    margin-top: 10px;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    border: 1.5px solid #d8c8ee;
    background: #fff;
    color: #5b21b6;
    border-radius: 999px;
    padding: 9px 14px;
    font-weight: 800;
    cursor: pointer;
}

.ar-file-zone {
    position: relative;
    border: 2px dashed #d4c5ed;
    border-radius: 14px;
    background: #faf8ff;
    padding: 32px 20px;
    text-align: center;
    cursor: pointer;
    transition: border-color .15s, background .15s;
}

.ar-file-zone:hover,
.ar-file-zone.dragging {
    border-color: #7c3aed;
    background: #f4f0fc;
}

.ar-file-zone input[type="file"] {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
}

.ar-file-zone-inner {
    pointer-events: none;
}

.ar-handoff-file {
    display: flex;
    align-items: center;
    gap: 14px;
    border: 1.5px solid #e2d5f4;
    border-radius: 14px;
    background: #faf8ff;
    padding: 16px;
}

.ar-handoff-file .ar-file-icon {
    flex-shrink: 0;
    margin: 0;
}

.ar-handoff-file div:nth-child(2) {
    display: grid;
    gap: 4px;
    min-width: 0;
}

.ar-handoff-file strong {
    color: #25104d;
    font-size: 14px;
    overflow-wrap: anywhere;
}

.ar-handoff-file span {
    color: #8a78a8;
    font-size: 12.5px;
}

.ar-handoff-file a {
    margin-left: auto;
    color: #5b21b6;
    font-size: 12.5px;
    font-weight: 800;
    text-decoration: none;
    white-space: nowrap;
}

.ar-file-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    background: #f0eaf9;
    border: 1.5px solid #e2d5f4;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #7c3aed;
    margin: 0 auto 12px;
}

.ar-file-label {
    font-size: 14px;
    font-weight: 700;
    color: #3b0f7a;
    margin: 0 0 4px;
    overflow-wrap: anywhere;
}

.ar-file-sub {
    font-size: 12px;
    color: #8a78a8;
    margin: 0;
}

.ar-file-error {
    min-height: 18px;
    margin: 8px 0 0;
    color: #b91c1c;
    font-size: 12.5px;
    font-weight: 700;
}

.ar-preview-card {
    border: 1px solid #e5daf3;
    border-top: 3px solid #6b2fa0;
    border-radius: 12px;
    background: #fff;
    padding: 20px;
    box-shadow: 0 8px 22px rgba(59,15,122,.07);
    display: grid;
    gap: 10px;
    margin-bottom: 18px;
}

.ar-preview-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.ar-preview-type {
    display: inline-flex;
    width: fit-content;
    padding: 5px 10px;
    border-radius: 999px;
    background: #ede9fe;
    color: #5b21b6;
    font-size: 11px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: .6px;
}

.ar-preview-views {
    color: #746486;
    font-size: 12px;
    font-weight: 800;
}

.ar-preview-card h4 {
    margin: 0;
    color: #2e1065;
    font-family: var(--font-head);
    font-size: 18px;
    line-height: 1.35;
}

.ar-preview-author,
.ar-preview-dept,
.ar-preview-program,
.ar-preview-abstract {
    margin: 0;
}

.ar-preview-author {
    color: #5b3d8a;
    font-weight: 800;
}

.ar-preview-dept,
.ar-preview-program {
    color: #78698f;
    font-size: 13px;
}

.ar-preview-abstract {
    color: #4b405f;
    font-size: 13.5px;
    line-height: 1.6;
}

.ar-preview-footer {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
    padding-top: 10px;
    border-top: 1px solid #efe7fb;
    color: #746486;
    font-size: 12.5px;
    font-weight: 800;
}

.ar-notice {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    background: #f0fdf4;
    border: 1px solid #86efac;
    border-radius: 12px;
    padding: 14px 18px;
    font-size: 13.5px;
    color: #15803d;
}

.ar-notice-icon {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #dcfce7;
    border: 1.5px solid #86efac;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #16a34a;
    flex-shrink: 0;
}

.ar-notice p {
    margin: 0;
    line-height: 1.6;
}

.ar-form-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    padding-top: 20px;
    border-top: 1px solid #f0eaf9;
}

.ar-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    min-height: 42px;
    padding: 10px 20px;
    border-radius: 50px;
    font-size: 13.5px;
    font-weight: 800;
    cursor: pointer;
    text-decoration: none;
    border: none;
    transition: all .16s ease;
}

.ar-form-actions [hidden] { display: none !important; }
.ar-step-tooltip { position: relative; }
.ar-step-tooltip::after {
    content: attr(data-tooltip);
    position: absolute;
    bottom: calc(100% + 10px);
    left: 50%;
    transform: translateX(-50%);
    width: max-content;
    max-width: 200px;
    padding: 8px 12px;
    border-radius: 8px;
    background: #2e1065;
    color: white;
    font-size: 12px;
    font-weight: 500;
    line-height: 1.4;
    text-align: center;
    box-shadow: 0 4px 12px rgba(46,16,101,.18);
    pointer-events: none;
    opacity: 0;
    z-index: 10;
}
.ar-step-tooltip:not(.tooltip-dismissed):hover::after,
.ar-step-tooltip:not(.tooltip-dismissed):focus-visible::after { opacity: 1; }
.ar-btn-primary {
    background: #3b0f7a;
    color: #fff;
    box-shadow: 0 4px 16px rgba(59,15,122,.28);
}

.ar-btn-primary:hover {
    background: #2d0a5e;
    transform: translateY(-1px);
}

.ar-btn-ghost {
    background: #fff;
    color: #6f5a92;
    border: 1.5px solid #e8dff5;
}

.ar-btn-ghost:hover {
    background: #f4f0fc;
    color: #3b0f7a;
}

@media (max-width: 760px) {
    .ar-card-header,
    .ar-form {
        padding-left: 20px;
        padding-right: 20px;
    }

    .ar-card-header,
    .ar-form-actions {
        align-items: stretch;
        flex-direction: column;
    }

    .ar-stepper {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .ar-form-row {
        grid-template-columns: 1fr;
    }

    .ar-span-2 {
        grid-column: auto;
    }
}
</style>

@push('scripts')
<script>
const researchPrograms = @json($programOptions);
const facultyCategory = @json(\App\Models\Research::SUBMISSION_CATEGORY_FACULTY_JOURNAL);
const studentCategory = @json(\App\Models\Research::SUBMISSION_CATEGORY_STUDENT_JOURNAL);
const isCoordinatorSubmission = @json((bool) $isCoordinatorSubmission);
const handoffFileName = @json($handoff?->file_name);
const maxPdfBytes = 30 * 1024 * 1024;

const form = document.getElementById('adminResearchForm');
const panels = Array.from(document.querySelectorAll('[data-step-panel]'));
const indicators = Array.from(document.querySelectorAll('[data-step-indicator]'));
const prevStepBtn = document.getElementById('prevStepBtn');
const nextStepBtn = document.getElementById('nextStepBtn');
const submitResearchBtn = document.getElementById('submitResearchBtn');
const saveDraftBtn = document.getElementById('saveDraftBtn');
const cancelBtn = document.getElementById('cancelBtn');
const workflowAction = document.getElementById('workflowAction');
const categorySelect = document.getElementById('submission_category');
const departmentSelect = document.getElementById('department');
const programGroup = document.getElementById('programGroup');
const programSelect = document.getElementById('course');
const authorRepeater = document.getElementById('authorRepeater');
const addAuthorBtn = document.getElementById('addAuthorBtn');
const addAuthorText = document.getElementById('addAuthorText');
const authorLabel = document.getElementById('authorLabel');
const fileInput = document.getElementById('fileInput');
const fileUploadArea = document.getElementById('fileUploadArea');
const fileName = document.getElementById('fileName');
const fileError = document.getElementById('fileError');
let currentStep = 1;

function isStudentJournal() {
    return categorySelect.value === studentCategory;
}

function setStep(step, shouldScroll = true) {
    currentStep = Math.max(1, Math.min(5, step));
    const stepNames = ['Basic Information', 'Department', 'Content', 'File', 'Review'];
    prevStepBtn.dataset.tooltip = `Back to ${stepNames[currentStep - 2] || 'Basic Information'}`;
    nextStepBtn.dataset.tooltip = `Continue to ${stepNames[currentStep] || 'Review'}`;
    prevStepBtn.setAttribute('aria-label', prevStepBtn.dataset.tooltip);
    nextStepBtn.setAttribute('aria-label', nextStepBtn.dataset.tooltip);

    panels.forEach((panel) => {
        panel.hidden = Number(panel.dataset.stepPanel) !== currentStep;
    });

    indicators.forEach((indicator) => {
        const indicatorStep = Number(indicator.dataset.stepIndicator);
        indicator.classList.toggle('is-active', indicatorStep === currentStep);
        indicator.classList.toggle('is-complete', indicatorStep < currentStep);
    });

    prevStepBtn.hidden = currentStep === 1;
    cancelBtn.hidden = currentStep !== 1;
    nextStepBtn.hidden = currentStep === 5;
    submitResearchBtn.hidden = currentStep !== 5;
    if (saveDraftBtn) {
        saveDraftBtn.hidden = currentStep !== 5;
    }

    if (currentStep === 5) {
        updatePreview();
    }

    if (shouldScroll) {
        form.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

function setSelectOptions(select, options, placeholder, selectedValue) {
    select.innerHTML = '';

    const placeholderOption = document.createElement('option');
    placeholderOption.value = '';
    placeholderOption.textContent = placeholder;
    select.appendChild(placeholderOption);

    options.forEach((optionValue) => {
        const option = document.createElement('option');
        option.value = optionValue;
        option.textContent = optionValue;
        option.selected = selectedValue === optionValue;
        select.appendChild(option);
    });
}

function populatePrograms() {
    if (!isStudentJournal()) {
        programSelect.required = false;
        programSelect.disabled = true;
        programSelect.value = '';
        programGroup.hidden = true;
        return;
    }

    const department = departmentSelect.value;
    const previousValue = programSelect.dataset.old || programSelect.value;
    const programs = researchPrograms[department] || [];

    programGroup.hidden = false;
    programSelect.required = true;
    programSelect.disabled = programs.length === 0;
    setSelectOptions(programSelect, programs, programs.length ? 'Select program' : 'Select department first', previousValue);
    programSelect.dataset.old = '';
}

function syncCategoryState() {
    const student = isStudentJournal();

    authorLabel.textContent = student ? 'Researchers' : 'Co-author(s)';
    addAuthorText.textContent = student ? 'Add Researcher' : 'Add Co-author';

    document.querySelectorAll('.ar-author-input').forEach((input) => {
        input.placeholder = student ? 'Enter researcher name' : 'Enter co-author name';
    });

    populatePrograms();
    updatePreview();
}

function updateAuthorButtons() {
    const rows = Array.from(document.querySelectorAll('[data-author-row]'));

    rows.forEach((row) => {
        const removeButton = row.querySelector('[data-remove-author]');
        removeButton.disabled = rows.length <= 1;
    });
}

function createAuthorRow() {
    const row = document.createElement('div');
    row.className = 'ar-author-row';
    row.dataset.authorRow = 'true';

    row.innerHTML = `
        <input type="text" class="ar-author-input" name="authors[]" placeholder="${isStudentJournal() ? 'Enter researcher name' : 'Enter co-author name'}" required maxlength="150">
        <button type="button" class="ar-icon-btn ar-remove-author" data-remove-author aria-label="Remove author">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v5"/><path d="M14 11v5"/></svg>
        </button>
    `;

    authorRepeater.appendChild(row);
    row.querySelector('input').focus();
    updateAuthorButtons();
}

function getAuthorNames() {
    return Array.from(document.querySelectorAll('.ar-author-input'))
        .map((input) => input.value.trim())
        .filter(Boolean);
}

function validateFileInput() {
    if (isCoordinatorSubmission || !fileInput) {
        return true;
    }

    const file = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
    fileError.textContent = '';
    fileInput.setCustomValidity('');

    if (!file) {
        fileError.textContent = 'Upload the full paper PDF.';
        fileInput.setCustomValidity('Upload the full paper PDF.');
        return false;
    }

    if (!file.name.toLowerCase().endsWith('.pdf')) {
        fileError.textContent = 'The full paper must be a PDF file.';
        fileInput.setCustomValidity('The full paper must be a PDF file.');
        return false;
    }

    if (file.size > maxPdfBytes) {
        fileError.textContent = 'The full paper PDF must not exceed 30MB.';
        fileInput.setCustomValidity('The full paper PDF must not exceed 30MB.');
        return false;
    }

    return true;
}

function validateStep(step) {
    const panel = panels.find((item) => Number(item.dataset.stepPanel) === step);
    const fields = Array.from(panel.querySelectorAll('input, select, textarea'))
        .filter((field) => !field.disabled && field.type !== 'hidden' && field !== fileInput);

    for (const field of fields) {
        if (!field.checkValidity()) {
            field.reportValidity();
            return false;
        }
    }

    if (step === 4 && !validateFileInput()) {
        return false;
    }

    return true;
}

function updateFileName() {
    if (!fileInput || !fileName) {
        return;
    }

    const file = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;

    if (!file) {
        fileName.textContent = 'Click to browse or drag and drop PDF here';
        return;
    }

    fileName.textContent = file.name;
    validateFileInput();
    updatePreview();
}

function updatePreview() {
    const title = document.getElementById('title').value.trim();
    const type = document.getElementById('type').value.trim();
    const authors = getAuthorNames();
    const author = authors.length > 1 ? `${authors[0]} et al.` : (authors[0] || 'Author Name');
    const department = departmentSelect.value.trim();
    const program = programSelect.value.trim();
    const year = document.getElementById('year_published').value.trim();
    const abstract = document.getElementById('abstract').value.trim();
    const file = isCoordinatorSubmission
        ? (handoffFileName || 'Received PDF file')
        : (fileInput && fileInput.files && fileInput.files[0] ? fileInput.files[0].name : '');

    document.getElementById('previewType').textContent = `Research: ${type || 'Journal Type'}`;
    document.getElementById('previewViews').textContent = 'Views 0';
    document.getElementById('previewTitle').textContent = title || 'Research title';
    document.getElementById('previewAuthor').textContent = author;
    document.getElementById('previewDepartment').textContent = department || 'Department';
    document.getElementById('previewYear').textContent = year || 'Year';
    document.getElementById('previewFileName').textContent = file || 'PDF file';
    document.getElementById('previewAbstract').textContent = abstract || 'Abstract preview';

    const previewProgram = document.getElementById('previewProgram');
    previewProgram.hidden = !isStudentJournal() || !program;
    previewProgram.textContent = program;
}

nextStepBtn.addEventListener('click', () => {
    if (!validateStep(currentStep)) {
        return;
    }

    setStep(currentStep + 1);
});

prevStepBtn.addEventListener('click', () => setStep(currentStep - 1));
[prevStepBtn, nextStepBtn].forEach(button => {
    button.addEventListener('mouseenter', () => button.classList.remove('tooltip-dismissed'));
    button.addEventListener('focus', () => button.classList.remove('tooltip-dismissed'));
});
document.addEventListener('keydown', event => {
    if (event.key === 'Escape') {
        [prevStepBtn, nextStepBtn].forEach(button => button.classList.add('tooltip-dismissed'));
    }
});

categorySelect.addEventListener('change', syncCategoryState);
departmentSelect.addEventListener('change', () => {
    populatePrograms();
    updatePreview();
});
programSelect.addEventListener('change', updatePreview);
document.getElementById('type').addEventListener('change', updatePreview);
document.getElementById('title').addEventListener('input', updatePreview);
document.getElementById('year_published').addEventListener('change', updatePreview);
document.getElementById('abstract').addEventListener('input', updatePreview);
if (fileInput) {
    fileInput.addEventListener('change', updateFileName);
}

submitResearchBtn.addEventListener('click', () => {
    workflowAction.value = 'submit';
});

saveDraftBtn?.addEventListener('click', () => {
    workflowAction.value = 'draft';
});

authorRepeater.addEventListener('input', (event) => {
    if (event.target.classList.contains('ar-author-input')) {
        updatePreview();
    }
});

authorRepeater.addEventListener('click', (event) => {
    const removeButton = event.target.closest('[data-remove-author]');

    if (!removeButton || document.querySelectorAll('[data-author-row]').length <= 1) {
        return;
    }

    removeButton.closest('[data-author-row]').remove();
    updateAuthorButtons();
    updatePreview();
});

addAuthorBtn.addEventListener('click', createAuthorRow);

if (fileUploadArea && fileInput) {
    fileUploadArea.addEventListener('dragover', (event) => {
        event.preventDefault();
        fileUploadArea.classList.add('dragging');
    });

    fileUploadArea.addEventListener('dragleave', () => {
        fileUploadArea.classList.remove('dragging');
    });

    fileUploadArea.addEventListener('drop', (event) => {
        event.preventDefault();
        fileUploadArea.classList.remove('dragging');

        const file = event.dataTransfer.files && event.dataTransfer.files[0] ? event.dataTransfer.files[0] : null;

        if (!file) {
            return;
        }

        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        fileInput.files = dataTransfer.files;
        updateFileName();
    });
}

form.addEventListener('submit', (event) => {
    for (let step = 1; step <= 4; step += 1) {
        setStep(step, false);

        if (!validateStep(step)) {
            event.preventDefault();
            setStep(step);
            return;
        }
    }

    updatePreview();
});

syncCategoryState();
updateAuthorButtons();
setStep(currentStep, false);
</script>
@endpush
@endsection
