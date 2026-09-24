@extends('layouts.admin')
@section('title', 'Edit Research Summary')
@section('page-title', 'Edit Research Summary')

@section('content')
@include('admin.partials.coordinator-styles')
<style>
.summary-editor{max-width:1500px}.summary-intro{margin:-10px 0 12px;color:#796291;font-size:14px;line-height:1.6}
.summary-form{min-width:0}.summary-heading{border:1px solid #eee5fa;border-radius:16px;margin-bottom:18px;background:#fdfbff}
.summary-steps{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:14px;margin:20px 0 26px}
.summary-step{display:flex;align-items:center;justify-content:center;gap:10px;min-height:52px;padding:10px;border:1px solid #ece2fa;border-radius:15px;background:#faf7fe;color:#80659f;font:inherit;font-size:13px;cursor:pointer}
.summary-step span{display:grid;place-items:center;width:27px;height:27px;flex-shrink:0;border-radius:50%;background:#eee5fa;font-weight:600}
.summary-step[aria-current=step]{background:#3b1466;color:white;border-color:#3b1466;box-shadow:0 5px 15px #4c1d9514}.summary-step[aria-current=step] span{background:#ffffff33}
.summary-columns{display:grid;grid-template-columns:minmax(0,1.8fr) minmax(300px,1fr);gap:18px;align-items:start}
.summary-fields{background:white;border:1px solid #eee5fa;border-radius:18px;padding:26px;min-width:0;min-height:490px;align-content:start}
.summary-fields .coord-field label{text-transform:none;letter-spacing:0;font-size:13px;font-weight:500;color:#725098}
.summary-fields input,.summary-fields select,.summary-fields textarea{background:#fdfbff;border:1px solid #e5d7f8;border-radius:11px;min-height:44px;font-size:14px}
.summary-fields input:focus,.summary-fields select:focus,.summary-fields textarea:focus{outline:2px solid #c4a5ef;outline-offset:2px}
.summary-fields [data-author-row]{margin-bottom:5px}.summary-fields .coord-grid-3{grid-template-columns:repeat(3,minmax(0,1fr))}
.summary-preview{position:sticky;top:18px;background:linear-gradient(150deg,#fff,#faf7ff);box-shadow:none;min-height:490px}
.summary-preview .coord-head{background:transparent;border:0;padding:23px}.summary-preview .coord-card-pad{margin:0 16px 20px;background:white;border:1px solid #eee5fa;border-radius:15px;padding:24px}
.summary-preview #previewTitle{font-size:23px;line-height:1.4;overflow-wrap:anywhere}.summary-preview p{overflow-wrap:anywhere}.summary-preview #previewMeta{padding:17px 0;border-top:1px solid #f0e9f8;line-height:1.8}
.summary-footer{position:sticky;bottom:0;z-index:5;justify-content:flex-end;background:#ffffffef;border:1px solid #eee5fa;border-radius:16px;margin-top:16px;padding:18px;box-shadow:0 -4px 20px #3b146607}
.summary-footer .coord-btn{border-radius:30px;padding:12px 24px;min-height:46px;font-weight:600}.summary-footer .coord-btn-primary{background:#3b1466;box-shadow:none}
.summary-editor [hidden]{display:none!important}.summary-review h3{margin:0 0 18px;color:#35105f;font-size:22px}.summary-review dl{display:grid;gap:12px}.summary-review dt{font-size:12px;color:#886b9e}.summary-review dd{margin:4px 0 0;color:#3d2159;overflow-wrap:anywhere;white-space:pre-wrap}
@media(max-width:1100px){.summary-columns{grid-template-columns:1fr}.summary-preview{position:static;min-height:0}.summary-fields{min-height:0}}
@media(max-width:650px){.summary-steps{grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}.summary-fields{padding:18px}.summary-fields .coord-grid-3{grid-template-columns:1fr}.summary-footer .coord-actions{width:100%}.summary-footer .coord-btn{width:auto;flex:1;padding:10px 14px}.summary-heading{align-items:flex-start}}
</style>

@php
    $authors = old('authors', $research->authorNames());
    if (! is_array($authors) || $authors === []) {
        $authors = [''];
    }
    $selectedCategory = old('submission_category', $research->submission_category);
    $selectedSchoolYear = old('school_year', $research->semester?->school_year);
    $selectedSemester = old('semester', $research->semester?->semester);
    $schoolYearList = collect($schoolYears)->merge($selectedSchoolYear ? [$selectedSchoolYear] : [])->filter()->unique()->values();
@endphp

<div class="coord-shell summary-editor">
<p class="summary-intro">Update the research details and make sure all information is correct before submitting to the admin.</p>
    <form method="POST" action="{{ route('admin.coordinator.summaries.update', $research) }}" enctype="multipart/form-data" class="summary-form" id="summaryForm">
        @csrf
        @method('PATCH')
        <input type="hidden" name="workflow_action" id="workflowAction" value="draft">

        <div class="coord-head summary-heading">
            <div>
                <h2>{{ $research->status === \App\Models\Research::STATUS_REJECTED ? 'Revise Returned Summary' : 'Basic Information' }}</h2>
                <p>{{ $research->handoff?->dean?->name ? 'Forwarded by ' . $research->handoff->dean->name : 'Repository summary' }}</p>
            </div>
        </div>

        @if($errors->any())
            <div class="coord-card-pad" style="background:#fef2f2;color:#991b1b;border-bottom:1px solid #fecaca;">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        @if($research->rejection_reason)
            <div class="coord-card-pad" style="background:#fff7ed;color:#92400e;border-bottom:1px solid #fed7aa;">
                <strong>Admin return reason:</strong> {{ $research->rejection_reason }}
            </div>
        @endif

        <nav class="summary-steps" aria-label="Research editing steps"></nav><div class="summary-columns">
            <div class="coord-grid summary-fields">
                <div class="coord-field">
                    <label for="title">Title</label>
                    <input id="title" type="text" name="title" value="{{ old('title', $research->title) }}" required maxlength="500">
                </div>

                <div class="coord-grid coord-grid-3">
                    <div class="coord-field">
                        <label for="submission_category">Submission Category</label>
                        <select id="submission_category" name="submission_category" required>
                            @foreach($submissionCategories as $value => $label)
                                <option value="{{ $value }}" {{ $selectedCategory === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="coord-field">
                        <label for="type">Research Type</label>
                        <select id="type" name="type" required>
                            <option value="">Select type</option>
                            @foreach($journalTypes as $type)
                                <option value="{{ $type }}" {{ old('type', $research->type) === $type ? 'selected' : '' }}>{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="coord-field">
                        <label for="year_published">Year Published</label>
                        <select id="year_published" name="year_published" required>
                            @foreach($yearOptions as $year)
                                <option value="{{ $year }}" {{ (string) old('year_published', $research->year_published) === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="coord-grid coord-grid-3">
                    <div class="coord-field">
                        <label>Department</label>
                        <input type="text" value="{{ auth()->user()->department }}" readonly>
                    </div>
                    <div class="coord-field">
                        <label for="school_year">School Year</label>
                        <select id="school_year" name="school_year">
                            <option value="">Not set</option>
                            @foreach($schoolYearList as $schoolYear)
                                <option value="{{ $schoolYear }}" {{ $selectedSchoolYear === $schoolYear ? 'selected' : '' }}>{{ $schoolYear }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="coord-field">
                        <label for="semester">Semester</label>
                        <select id="semester" name="semester">
                            <option value="">Not set</option>
                            @foreach($semesterOptions as $semester)
                                <option value="{{ $semester }}" {{ $selectedSemester === $semester ? 'selected' : '' }}>{{ \App\Models\Semester::semesterLabels()[$semester] ?? $semester }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="coord-field" id="programGroup">
                    <label for="course">Program</label>
                    <select id="course" name="course" data-current="{{ old('course', $research->course ?? $research->program) }}">
                        <option value="">Select program</option>
                    </select>
                </div>

                <div class="coord-field">
                    <label>Authors</label>
                    <div id="authorRepeater" class="coord-grid">
                        @foreach($authors as $author)
                            <div style="display:grid;grid-template-columns:minmax(0,1fr) 42px;gap:8px;" data-author-row>
                                <input type="text" name="authors[]" value="{{ $author }}" required maxlength="150">
                                <button type="button" class="coord-btn coord-btn-danger" data-remove-author aria-label="Remove author">X</button>
                            </div>
                        @endforeach
                    </div>
                    <button type="button" class="coord-btn coord-btn-soft" id="addAuthorBtn" style="justify-self:start;">Add Author</button>
                </div>

                <div class="coord-field">
                    <label for="keywords">Keywords</label>
                    <input id="keywords" type="text" name="keywords" value="{{ old('keywords', $research->keywords) }}" maxlength="500" placeholder="keyword one, keyword two">
                </div>

                <div class="coord-field">
                    <label for="abstract">Abstract</label>
                    <textarea id="abstract" name="abstract" required>{{ old('abstract', $research->abstract) }}</textarea>
                </div>

                <div class="coord-field">
                    <label for="file">Research PDF / Document</label>
                    <input id="file" type="file" name="file" accept="application/pdf,.pdf">
                    <span class="coord-muted">Current file: {{ $research->file_name ?: 'No file attached' }}</span>
                </div>
            </div>

            <aside class="coord-card summary-preview">
                <div class="coord-head"><div><h3>Preview</h3><p>Live preview of the research summary.</p></div></div>
                <div class="coord-card-pad">
                    <span class="coord-pill" id="previewType">{{ $research->getTypeLabel() }}</span>
                    <h3 id="previewTitle" style="margin:14px 0 8px;color:#201044;">{{ $research->title }}</h3>
                    <p id="previewAuthors" style="margin:0 0 8px;color:#5b248c;font-weight:800;">{{ $research->authorListLabel() }}</p>
                    <p id="previewMeta" class="coord-muted">{{ $research->department }} / {{ $research->semester?->label ?? 'Not set' }}</p>
                    <p id="previewYear" class="coord-muted"></p>
                    <p id="previewCategory" class="coord-muted"></p>
                    <p id="previewAbstract" style="color:#3d2060;line-height:1.7;">{{ Str::limit($research->abstract, 500) }}</p>
                </div>
            </aside>
        </div>

        <div class="coord-head summary-footer">
            <div class="coord-actions">
                <button type="button" class="coord-btn coord-btn-soft" id="summaryBack" aria-label="Previous step">←</button>
<button type="submit" class="coord-btn coord-btn-soft" data-action="draft">Save Draft</button>
                <button type="button" class="coord-btn coord-btn-primary" id="summaryNext">Next →</button>
<button type="submit" class="coord-btn coord-btn-primary" id="summarySubmit" data-action="submit">Submit to Admin</button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
const programsByDepartment = @json($programOptions);
const studentCategory = @json(\App\Models\Research::SUBMISSION_CATEGORY_STUDENT_JOURNAL);
const departmentName = @json(auth()->user()->department);
const form = document.getElementById('summaryForm');
const workflowAction = document.getElementById('workflowAction');
const category = document.getElementById('submission_category');
const programGroup = document.getElementById('programGroup');
const programSelect = document.getElementById('course');
const authorRepeater = document.getElementById('authorRepeater');

function syncPrograms() {
    const isStudent = category.value === studentCategory;
    const programs = programsByDepartment[departmentName] || [];
    const selected = programSelect.dataset.current || programSelect.value;

    programGroup.hidden = !isStudent;
    programSelect.required = isStudent;
    programSelect.disabled = !isStudent;
    programSelect.innerHTML = '<option value="">Select program</option>';

    programs.forEach(program => {
        const option = document.createElement('option');
        option.value = program;
        option.textContent = program;
        option.selected = selected === program;
        programSelect.appendChild(option);
    });

    programSelect.dataset.current = '';
}

function authorsText() {
    return Array.from(authorRepeater.querySelectorAll('input'))
        .map(input => input.value.trim())
        .filter(Boolean)
        .join('; ') || 'No authors yet';
}

function updatePreview() {
    const schoolYear = document.getElementById('school_year').value || 'Not set';
    const semester = document.getElementById('semester').selectedOptions[0]?.textContent.trim() || 'Not set';

    document.getElementById('previewType').textContent = document.getElementById('type').value || 'Research Type';
    document.getElementById('previewTitle').textContent = document.getElementById('title').value || 'Untitled research';
    document.getElementById('previewAuthors').textContent = authorsText();
    document.getElementById('previewMeta').textContent = departmentName + ' / ' + schoolYear + ' ' + semester;
    document.getElementById('previewYear').textContent = 'Year Published: ' + document.getElementById('year_published').value;
    document.getElementById('previewCategory').textContent = category.selectedOptions[0]?.textContent.trim() || '';
    document.getElementById('previewAbstract').textContent = document.getElementById('abstract').value || 'No abstract yet.';
}

category.addEventListener('change', () => {
    syncPrograms();
    updatePreview();
});

form.addEventListener('input', updatePreview);
form.addEventListener('change', updatePreview);

document.getElementById('addAuthorBtn').addEventListener('click', () => {
    const row = document.createElement('div');
    row.style.cssText = 'display:grid;grid-template-columns:minmax(0,1fr) 42px;gap:8px;';
    row.dataset.authorRow = 'true';
    row.innerHTML = '<input type="text" name="authors[]" required maxlength="150"><button type="button" class="coord-btn coord-btn-danger" data-remove-author aria-label="Remove author">X</button>';
    authorRepeater.appendChild(row);
    row.querySelector('input').focus();
    updatePreview();
});

authorRepeater.addEventListener('click', event => {
    const button = event.target.closest('[data-remove-author]');

    if (!button || authorRepeater.querySelectorAll('[data-author-row]').length <= 1) {
        return;
    }

    button.closest('[data-author-row]').remove();
    updatePreview();
});

form.addEventListener('click', event => {
    const button = event.target.closest('[data-action]');

    if (button) {
        workflowAction.value = button.dataset.action;
    }
});

syncPrograms();
updatePreview();

const fields = document.querySelector('.summary-fields');
const steps = ['Basic Information', 'Department', 'Content', 'File', 'Review'];
const originalGroups = Array.from(fields.children);
const groups = [
    originalGroups.filter(group => group.querySelector('#title, #submission_category, #authorRepeater, #keywords')),
    originalGroups.filter(group => group.querySelector('#school_year, #course')),
    originalGroups.filter(group => group.querySelector('#abstract')),
    originalGroups.filter(group => group.querySelector('#file')),
    [],
];
const review = document.createElement('section');
review.className = 'summary-review';
fields.appendChild(review);
groups[4].push(review);
const nav = document.querySelector('.summary-steps');
const back = document.getElementById('summaryBack');
const next = document.getElementById('summaryNext');
const submit = document.getElementById('summarySubmit');
let currentStep = 0;
function renderReview() {
    review.replaceChildren();
    const heading = document.createElement('h3');
    heading.textContent = 'Review before submitting';
    review.appendChild(heading);
    const list = document.createElement('dl');
    const values = [
        ['Title', document.getElementById('title').value],
        ['Authors', authorsText()], ['Department', departmentName],
        ['Research Type', document.getElementById('type').value],
        ['Academic Year', document.getElementById('school_year').value || 'Not set'],
        ['Abstract', document.getElementById('abstract').value],
        ['File', document.getElementById('file').files[0]?.name || @json($research->file_name)],
    ];
    values.forEach(([label, value]) => {
        const item = document.createElement('div');
        const term = document.createElement('dt');
        const detail = document.createElement('dd');
        term.textContent = label; detail.textContent = value || 'Not set';
        item.append(term, detail); list.appendChild(item);
    });
    review.appendChild(list);
}
function showStep(index) {
    currentStep = Math.max(0, Math.min(4, index));
    groups.forEach((group, step) => group.forEach(element => {
        element.hidden = step !== currentStep || (element === programGroup && category.value !== studentCategory);
    }));
    Array.from(nav.children).forEach((button, step) => {
        if (step === currentStep) button.setAttribute('aria-current', 'step');
        else button.removeAttribute('aria-current');
    });
    back.hidden = currentStep === 0;
    next.hidden = currentStep === 4;
    submit.hidden = currentStep !== 4;
    back.title = `Back to ${steps[currentStep - 1] || steps[0]}`;
    next.title = `Continue to ${steps[currentStep + 1] || steps[4]}`;
    if (currentStep === 4) renderReview();
}
function validateStep(index) {
    for (const group of groups[index]) {
        for (const field of group.querySelectorAll('input, select, textarea')) {
            if (!field.disabled && !field.checkValidity()) {
                showStep(index); field.reportValidity(); return false;
            }
        }
    }
    return true;
}
function navigate(index) {
    if (index > currentStep) {
        for (let step = currentStep; step < index; step++) if (!validateStep(step)) return;
    }
    showStep(index);
}
steps.forEach((label, index) => {
    const button = document.createElement('button');
    button.type = 'button'; button.className = 'summary-step';
    const number = document.createElement('span'); number.textContent = String(index + 1);
    button.append(number, document.createTextNode(label));
    button.addEventListener('click', () => navigate(index)); nav.appendChild(button);
});
back.addEventListener('click', () => navigate(currentStep - 1));
next.addEventListener('click', () => navigate(currentStep + 1));
category.addEventListener('change', () => showStep(currentStep));
// Validate every step before saving, exposing any invalid field before focusing it.
form.noValidate = true;
form.addEventListener('submit', event => {
    workflowAction.value = event.submitter?.dataset.action || 'draft';
    for (let step = 0; step < 4; step++) {
        if (!validateStep(step)) { event.preventDefault(); return; }
    }
});
showStep(0);
</script>
@endpush
@endsection
