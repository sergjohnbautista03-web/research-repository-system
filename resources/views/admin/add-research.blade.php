@extends('layouts.admin')
@section('title', 'Add Research')
@section('page-title', 'Add Research')

@section('content')

<div style="max-width:860px; margin:0 auto;">

    <div class="ar-card">

        {{-- HEADER --}}
        <div class="ar-card-header">
            <div class="ar-header-left">
                <div class="ar-header-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
                </div>
                <div>
                    <h3 class="ar-header-title">Add New Research</h3>
                    <p class="ar-header-sub">Fill in the details to publish a new entry</p>
                </div>
            </div>
            <a href="{{ route('admin.researches') }}" class="ar-back-btn">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                Back
            </a>
        </div>

        @if($errors->any())
            <div class="ar-alert-error">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <div>@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.store-research') }}" enctype="multipart/form-data" class="ar-form">
            @csrf
            @php
                $submissionCategories = \App\Models\Research::submissionCategories();
                $journalTypes = \App\Models\Research::typesForCategory(\App\Models\Research::SUBMISSION_CATEGORY_JOURNAL);
            @endphp

            {{-- SECTION: Basic Info --}}
            <div class="ar-section-label">
                <span class="ar-section-num">1</span> Basic Information
            </div>

            <div class="ar-form-row">
                <div class="ar-form-group ar-span-2">
                    <label>Title <span class="ar-required">*</span></label>
                    <input type="text" name="title" value="{{ old('title') }}" placeholder="Enter the full research title" required>
                </div>
            </div>

            <div class="ar-form-row">
                <div class="ar-form-group">
                    <label>Author Name <span class="ar-required">*</span></label>
                    <input type="text" name="author_name" value="{{ old('author_name') }}" placeholder="e.g. Dr. Juan dela Cruz" required>
                </div>
                <div class="ar-form-group">
                    <label>Year Published <span class="ar-required">*</span></label>
                    <input type="number" name="year_published" value="{{ old('year_published', 2026) }}" min="2022" max="2026" required>
                </div>
            </div>

            <div class="ar-form-row">
                <div class="ar-form-group">
                    <label>Submission Category <span class="ar-required">*</span></label>
                    <select name="submission_category" id="submission_category" required onchange="updateCoursesAndTypes()">
                        @foreach($submissionCategories as $value => $label)
                            <option value="{{ $value }}" {{ old('submission_category', \App\Models\Research::SUBMISSION_CATEGORY_JOURNAL) === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- SECTION: Department --}}
            <div class="ar-section-label">
                <span class="ar-section-num">2</span> Department & Program
            </div>

            <div class="ar-form-row">
                <div class="ar-form-group">
                    <label>Department <span class="ar-required">*</span></label>
                    <select name="department" id="department" required onchange="updateCoursesAndTypes(this)">
                        <option value="">-- Select Department --</option>
                        <option value="College of Accountancy and Business Education" {{ old('department') == 'College of Accountancy and Business Education' ? 'selected' : '' }}>College of Accountancy and Business Education</option>
                        <option value="College of Computer Studies" {{ old('department') == 'College of Computer Studies' ? 'selected' : '' }}>College of Computer Studies</option>
                        <option value="College of Criminal Justice Education" {{ old('department') == 'College of Criminal Justice Education' ? 'selected' : '' }}>College of Criminal Justice Education</option>
                        <option value="College of Education" {{ old('department') == 'College of Education' ? 'selected' : '' }}>College of Education</option>
                        <option value="College of Engineering and Architecture" {{ old('department') == 'College of Engineering and Architecture' ? 'selected' : '' }}>College of Engineering and Architecture</option>
                        <option value="College of Maritime Studies" {{ old('department') == 'College of Maritime Studies' ? 'selected' : '' }}>College of Maritime Studies</option>
                    </select>
                </div>
                <div class="ar-form-group">
                    <label>Program <span class="ar-required">*</span></label>
                    <select name="course" id="course" required>
                        <option value="">-- Select Department First --</option>
                        @php
                        $courses = [
                            'College of Accountancy and Business Education' => ['Accountancy','Business Administration-Marketing Mngt.','Hospitality Management','Tourism Management'],
                            'College of Computer Studies' => ['Computer Science','Information Technology'],
                            'College of Criminal Justice Education' => ['Criminology'],
                            'College of Education' => ['Elementary Education','Secondary Education-General Science'],
                            'College of Engineering and Architecture' => ['Civil Engineering','Computer Engineering','Electrical Engineering','Electronics Engineering','Mechanical Engineering'],
                            'College of Maritime Studies' => ['Marine Engineering','Transportation'],
                        ];
                        $selectedDept = old('department');
                        @endphp
                        @if($selectedDept && isset($courses[$selectedDept]))
                            @foreach($courses[$selectedDept] as $c)
                                <option value="{{ $c }}" {{ old('course') == $c ? 'selected' : '' }}>{{ $c }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
            </div>

            <div class="ar-form-row">
                <div class="ar-form-group">
                    <label><span id="typeLabel">Research Type</span> <span class="ar-required">*</span></label>
                    <select name="type" id="type" required>
                        <option value="">-- Select Department First --</option>
                        @php
                        $types = [
                            'College of Accountancy and Business Education' => ['Thesis','Feasibility Study','Descriptive Research','Correlational Research','Quantitative Research'],
                            'College of Computer Studies' => ['Capstone 1','Capstone 2','Thesis','Applied Research'],
                            'College of Criminal Justice Education' => ['Thesis','Descriptive Research','Qualitative Research','Mixed Methods Research'],
                            'College of Education' => ['Thesis','Action Research','Descriptive Research','Experimental Research'],
                            'College of Engineering and Architecture' => ['Capstone 1','Capstone 2','Thesis','Applied Research','Experimental Research'],
                            'College of Maritime Studies' => ['Thesis','Applied Research','Descriptive Research','Quantitative Research'],
                        ];
                        $selectedCategory = old('submission_category', \App\Models\Research::SUBMISSION_CATEGORY_JOURNAL);
                        @endphp
                        @if($selectedCategory === \App\Models\Research::SUBMISSION_CATEGORY_JOURNAL)
                            @foreach($journalTypes as $t)
                                <option value="{{ $t }}" {{ old('type') == $t ? 'selected' : '' }}>{{ $t }}</option>
                            @endforeach
                        @elseif($selectedDept && isset($types[$selectedDept]))
                            @foreach($types[$selectedDept] as $t)
                                <option value="{{ $t }}" {{ old('type') == $t ? 'selected' : '' }}>{{ $t }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
            </div>

            {{-- SECTION: Content --}}
            <div class="ar-section-label">
                <span class="ar-section-num">3</span> Content & Keywords
            </div>

            <div class="ar-form-group">
                <label>Keywords</label>
                <div class="ar-input-hint-wrap">
                    <input type="text" name="keywords" value="{{ old('keywords') }}" placeholder="e.g. machine learning, AI, Philippines">
                    <span class="ar-input-hint">Separate with commas</span>
                </div>
            </div>

            <div class="ar-form-group">
                <label>Abstract <span class="ar-required">*</span></label>
                <textarea name="abstract" rows="6" placeholder="Write the research abstract here..." required>{{ old('abstract') }}</textarea>
            </div>

            {{-- SECTION: File --}}
            <div class="ar-section-label">
                <span class="ar-section-num">4</span> File Upload
            </div>

            <div class="ar-form-group">
                <div class="ar-file-zone" id="fileUploadArea" onclick="document.getElementById('fileInput').click()" ondragover="event.preventDefault(); this.classList.add('dragging')" ondragleave="this.classList.remove('dragging')" ondrop="handleDrop(event)">
                    <input type="file" name="file" id="fileInput" accept=".pdf" onchange="updateFileName(this)" style="display:none;">
                    <div class="ar-file-zone-inner">
                        <div class="ar-file-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        </div>
                        <p class="ar-file-label" id="fileName">Click to browse or drag & drop file here</p>
                        <p class="ar-file-sub">PDF Only — Max 30MB</p>
                    </div>
                </div>
            </div>

            {{-- AUTO-APPROVE NOTICE --}}
            <div class="ar-notice">
                <div class="ar-notice-icon">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <p>Research added by admin will be <strong>automatically approved</strong> and visible on the site immediately.</p>
            </div>

            {{-- FORM ACTIONS --}}
            <div class="ar-form-actions">
                <a href="{{ route('admin.researches') }}" class="ar-btn ar-btn-ghost">Cancel</a>
                <button type="submit" class="ar-btn ar-btn-primary">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Add Research
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* ── CARD ──────────────────────────────────────── */
.ar-card {
    background: #fff;
    border-radius: 20px;
    border: 1px solid rgba(107,47,160,.1);
    box-shadow: 0 4px 32px rgba(59,15,122,.06), 0 1px 4px rgba(0,0,0,.03);
    overflow: hidden;
    margin-bottom: 40px;
}

/* ── CARD HEADER ────────────────────────────────── */
.ar-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 24px 32px;
    background: linear-gradient(160deg, #fdfbff 0%, #f8f4fe 100%);
    border-bottom: 1px solid #f0eaf9;
}
.ar-header-left { display: flex; align-items: center; gap: 14px; }
.ar-header-icon {
    width: 44px; height: 44px;
    border-radius: 12px;
    background: #f0eaf9;
    border: 1px solid #e2d5f4;
    display: flex; align-items: center; justify-content: center;
    color: #6b2fa0;
    flex-shrink: 0;
}
.ar-header-title {
    font-size: 17px;
    font-weight: 800;
    color: #1a0638;
    margin: 0 0 2px;
    letter-spacing: -.3px;
}
.ar-header-sub { font-size: 12.5px; color: #a090bc; margin: 0; }
.ar-back-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 16px;
    background: #fff;
    border: 1.5px solid #e2d5f4;
    border-radius: 50px;
    font-size: 13px; font-weight: 600;
    color: #6b2fa0; text-decoration: none;
    transition: all .15s;
}
.ar-back-btn:hover { background: #f4f0fc; border-color: #c4a8e8; }

/* ── ALERT ────────────────────────────────────── */
.ar-alert-error {
    display: flex; align-items: flex-start; gap: 10px;
    margin: 20px 32px 0;
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 12px;
    padding: 14px 16px;
    color: #b91c1c; font-size: 13px;
}
.ar-alert-error svg { flex-shrink: 0; margin-top: 1px; }
.ar-alert-error p { margin: 0 0 3px; }
.ar-alert-error p:last-child { margin: 0; }

/* ── FORM ─────────────────────────────────────── */
.ar-form { padding: 28px 32px; }

/* SECTION LABELS */
.ar-section-label {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #6b2fa0;
    margin: 24px 0 16px;
    padding-bottom: 10px;
    border-bottom: 1px solid #f0eaf9;
}
.ar-section-label:first-child { margin-top: 0; }
.ar-section-num {
    width: 22px; height: 22px;
    border-radius: 50%;
    background: #f0eaf9;
    border: 1.5px solid #e0d4f5;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 11px; font-weight: 800;
    color: #6b2fa0;
    flex-shrink: 0;
}

/* ROWS */
.ar-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}
.ar-span-2 { grid-column: 1 / -1; }

/* GROUPS */
.ar-form-group {
    display: flex;
    flex-direction: column;
    margin-bottom: 16px;
}
.ar-form-group label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .7px;
    color: #5b3d8a;
    margin-bottom: 7px;
}
.ar-required { color: #e53e3e; }
.ar-form-group input[type="text"],
.ar-form-group input[type="number"],
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
.ar-form-group textarea { resize: vertical; line-height: 1.6; }
.ar-form-group input:focus,
.ar-form-group select:focus,
.ar-form-group textarea:focus {
    outline: none;
    border-color: #7c3aed;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(124,58,237,.1);
}

/* DISABLED SELECT */
.ar-form-group select:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* INPUT HINT */
.ar-input-hint-wrap { position: relative; }
.ar-input-hint {
    position: absolute;
    right: 12px; top: 50%;
    transform: translateY(-50%);
    font-size: 11px;
    color: #c0aee0;
    pointer-events: none;
}

/* FILE ZONE */
.ar-file-zone {
    border: 2px dashed #d4c5ed;
    border-radius: 14px;
    background: #faf8ff;
    padding: 32px 20px;
    text-align: center;
    cursor: pointer;
    transition: border-color .15s, background .15s;
}
.ar-file-zone:hover, .ar-file-zone.dragging {
    border-color: #7c3aed;
    background: #f4f0fc;
}
.ar-file-zone-inner { pointer-events: none; }
.ar-file-icon {
    width: 52px; height: 52px;
    border-radius: 14px;
    background: #f0eaf9;
    border: 1.5px solid #e2d5f4;
    display: flex; align-items: center; justify-content: center;
    color: #7c3aed;
    margin: 0 auto 12px;
}
.ar-file-label { font-size: 14px; font-weight: 600; color: #3b0f7a; margin: 0 0 4px; }
.ar-file-sub { font-size: 12px; color: #b0a0cc; margin: 0; }

/* NOTICE */
.ar-notice {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    background: #f0fdf4;
    border: 1px solid #86efac;
    border-radius: 12px;
    padding: 14px 18px;
    margin-bottom: 24px;
    font-size: 13.5px;
    color: #15803d;
}
.ar-notice-icon {
    width: 28px; height: 28px;
    border-radius: 50%;
    background: #dcfce7;
    border: 1.5px solid #86efac;
    display: flex; align-items: center; justify-content: center;
    color: #16a34a;
    flex-shrink: 0;
}
.ar-notice p { margin: 0; line-height: 1.6; }
.ar-notice strong { font-weight: 700; }

/* FORM ACTIONS */
.ar-form-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    padding-top: 20px;
    border-top: 1px solid #f0eaf9;
}
.ar-btn {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 11px 22px;
    border-radius: 50px;
    font-size: 13.5px; font-weight: 700;
    cursor: pointer; text-decoration: none; border: none;
    transition: all .16s ease;
}
.ar-btn-primary {
    background: #3b0f7a; color: #fff;
    box-shadow: 0 4px 16px rgba(59,15,122,.28);
}
.ar-btn-primary:hover {
    background: #2d0a5e;
    transform: translateY(-1px);
    box-shadow: 0 7px 22px rgba(59,15,122,.38);
}
.ar-btn-ghost {
    background: #fff; color: #8b7aaa;
    border: 1.5px solid #e8dff5;
}
.ar-btn-ghost:hover { background: #f4f0fc; color: #3b0f7a; }

/* RESPONSIVE */
@media (max-width: 640px) {
    .ar-card-header, .ar-form { padding-left: 20px; padding-right: 20px; }
    .ar-form-row { grid-template-columns: 1fr; }
    .ar-span-2 { grid-column: auto; }
}
</style>

@push('scripts')
<script>
const deptData = {
    'College of Accountancy and Business Education': {
        courses: ['Accountancy','Business Administration-Marketing Mngt.','Hospitality Management','Tourism Management'],
        types:   ['Thesis','Feasibility Study','Descriptive Research','Correlational Research','Quantitative Research']
    },
    'College of Computer Studies': {
        courses: ['Computer Science','Information Technology'],
        types:   ['Capstone 1','Capstone 2','Thesis','Applied Research']
    },
    'College of Criminal Justice Education': {
        courses: ['Criminology'],
        types:   ['Thesis','Descriptive Research','Qualitative Research','Mixed Methods Research']
    },
    'College of Education': {
        courses: ['Elementary Education','Secondary Education-General Science'],
        types:   ['Thesis','Action Research','Descriptive Research','Experimental Research']
    },
    'College of Engineering and Architecture': {
        courses: ['Civil Engineering','Computer Engineering','Electrical Engineering','Electronics Engineering','Mechanical Engineering'],
        types:   ['Capstone 1','Capstone 2','Thesis','Applied Research','Experimental Research']
    },
    'College of Maritime Studies': {
        courses: ['Marine Engineering','Transportation'],
        types:   ['Thesis','Applied Research','Descriptive Research','Quantitative Research']
    },
};
const journalTypes = @json($journalTypes);

function updateCoursesAndTypes() {
    const deptSelect  = document.getElementById('department');
    const category    = document.getElementById('submission_category').value;
    const dept        = deptSelect.value;
    const courseSelect = document.getElementById('course');
    const typeSelect   = document.getElementById('type');
    const typeLabel    = document.getElementById('typeLabel');

    courseSelect.innerHTML = '<option value="">-- Select Program --</option>';
    typeSelect.innerHTML   = '<option value="">-- Select Type --</option>';
    typeLabel.textContent  = category === 'journal' ? 'Journal Type' : 'Research Type';

    if (dept && deptData[dept]) {
        deptData[dept].courses.forEach(function(c) {
            courseSelect.innerHTML += '<option value="' + c + '">' + c + '</option>';
        });
        courseSelect.disabled = false;

        const types = category === 'journal' ? journalTypes : deptData[dept].types;
        types.forEach(function(t) {
            typeSelect.innerHTML += '<option value="' + t + '">' + t + '</option>';
        });
        typeSelect.disabled = false;
    } else {
        courseSelect.innerHTML = '<option value="">-- Select Department First --</option>';
        typeSelect.innerHTML   = '<option value="">-- Select Department First --</option>';
        courseSelect.disabled  = true;
        typeSelect.disabled    = true;
    }
}

function updateFileName(input) {
    const label = document.getElementById('fileName');
    if (input.files && input.files[0]) {
        label.textContent = input.files[0].name;
        label.style.color = '#3b0f7a';
    }
}

function handleDrop(e) {
    e.preventDefault();
    document.getElementById('fileUploadArea').classList.remove('dragging');
    const file = e.dataTransfer.files[0];
    if (file) {
        const input = document.getElementById('fileInput');
        const dt = new DataTransfer();
        dt.items.add(file);
        input.files = dt.files;
        updateFileName(input);
    }
}

// On page load: restore old() values after validation errors
(function() {
    const deptSelect = document.getElementById('department');
    if (deptSelect.value) {
        updateCoursesAndTypes();

        const oldCourse = "{{ old('course') }}";
        const oldType   = "{{ old('type') }}";
        if (oldCourse) document.getElementById('course').value = oldCourse;
        if (oldType)   document.getElementById('type').value   = oldType;
    } else if (document.getElementById('submission_category').value === 'journal') {
        document.getElementById('typeLabel').textContent = 'Journal Type';
    }
})();
</script>
@endpush

@endsection
