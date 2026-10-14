@extends($embedded ? 'layouts.journal-batch-modal' : 'layouts.admin')
@section('title', 'Assign Journal Task')
@section('page-title', 'Assign Journal Task')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/journal-batches.css') }}?v={{ filemtime(public_path('css/journal-batches.css')) }}">
@endpush

@section('content')
<div class="rhf-shell">
    <section class="rhf-card">
        <div class="rhf-header">
            <div>
                <span>Department Dean</span>
            </div>
            @unless($embedded)<a href="{{ route('admin.research-handoffs') }}" class="rhf-back">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                Back
            </a>@endunless
        </div>

        @if(! $coordinator)
            <div class="rhf-alert rhf-alert-warning">
                <strong>No active Research Coordinator found.</strong>
                <span>Create or activate a Research Coordinator for {{ $department }} before assigning a task.</span>
            </div>
        @endif

        @if($errors->any())
            <div class="rhf-alert rhf-alert-error">
                @foreach($errors->all() as $error)
                    <span>{{ $error }}</span>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('admin.research-handoffs.store') }}" class="rhf-form">
            @csrf
            @if($embedded)<input type="hidden" name="embedded" value="1">@endif

            <div class="rhf-grid">
                <div class="rhf-field">
                    <label>Assigned Department</label>
                    <div class="rhf-readonly">{{ $department }}</div>
                </div>
                <div class="rhf-field">
                    <label>Research Coordinator</label>
                    <div class="rhf-readonly">{{ $coordinator?->name ?? 'Not assigned' }}</div>
                </div>
            </div>

            <div class="rhf-grid">
                <div class="rhf-field">
                    <label for="submission_category">Journal Category <span>*</span></label>
                    <select id="submission_category" name="submission_category" required>
                        @foreach(\App\Models\Research::adminSubmissionCategories() as $value => $label)
                            <option value="{{ $value }}" @selected(old('submission_category', \App\Models\Research::SUBMISSION_CATEGORY_STUDENT_JOURNAL) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="rhf-field">
                    <label for="school_year">Academic Year <span>*</span></label>
                    <select id="school_year" name="school_year" required>
                        <option value="">Select Academic Year</option>
                        @foreach($schoolYears as $schoolYear)
                            <option value="{{ $schoolYear }}" @selected(old('school_year') === $schoolYear)>{{ $schoolYear }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="rhf-field">
                <label for="program">Program <span>*</span></label>
                <select id="program" name="program" required>
                    <option value="">Select Program</option>
                    @foreach($programs as $program)
                        <option value="{{ $program }}" @selected(old('program') === $program)>{{ $program }}</option>
                    @endforeach
                </select>
            </div>

            <div class="rhf-field">
                <label for="notes">Task Instructions <span>*</span></label>
                <textarea id="notes" name="notes" rows="4" required maxlength="5000" placeholder="Prepare and upload all completed journals for the selected Academic Year, then submit them as one batch for review.">{{ old('notes') }}</textarea>
            </div>

            <div class="rhf-actions">
                @if($embedded)<button type="button" class="rhf-btn rhf-btn-ghost" data-close-embedded>Cancel</button>@else<a href="{{ route('admin.research-handoffs') }}" class="rhf-btn rhf-btn-ghost">Cancel</a>@endif
                <button type="submit" class="rhf-btn rhf-btn-primary" {{ ! $coordinator ? 'disabled' : '' }}>
                    Assign Journal Preparation
                </button>
            </div>
        </form>
    </section>
</div>

<style>
.rhf-shell{max-width:920px;}
.rhf-card{background:#fff;border:1px solid rgba(107,47,160,.1);border-radius:18px;box-shadow:0 10px 30px rgba(57,26,101,.06);overflow:hidden;}
.rhf-header{display:flex;justify-content:space-between;gap:18px;align-items:flex-start;padding:26px 30px;background:linear-gradient(160deg,#fdfbff 0%,#f8f4fe 100%);border-bottom:1px solid #f0eaf9;}
.rhf-header span{display:inline-flex;margin-bottom:8px;color:#6d28d9;font-size:11px;font-weight:800;letter-spacing:.14em;text-transform:uppercase;}
.rhf-back,.rhf-btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:42px;padding:0 16px;border-radius:12px;text-decoration:none;font:800 13px/1 inherit;border:1px solid transparent;cursor:pointer;}
.rhf-back{background:#fff;color:#5b348f;border-color:#e8dff5;}
.rhf-back svg{width:15px;height:15px;}
.rhf-alert{display:grid;gap:4px;margin:18px 30px 0;padding:14px 16px;border-radius:14px;font-size:13px;line-height:1.5;}
.rhf-alert-warning{background:#fff8e8;border:1px solid #fde2a8;color:#8a5300;}
.rhf-alert-error{background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;}
.rhf-form{display:grid;gap:18px;padding:28px 30px 30px;}
.rhf-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;}
.rhf-field{display:grid;gap:8px;}
.rhf-field label{font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#5f418f;}
.rhf-field label span{color:#dc2626;}
.rhf-field input,.rhf-field textarea{width:100%;border:1.5px solid #e7ddf9;border-radius:14px;background:#fff;color:#211143;font:inherit;box-sizing:border-box;}
.rhf-field input{height:48px;padding:0 14px;}
.rhf-field select{height:48px;padding:0 14px;border:1.5px solid #e7ddf9;border-radius:14px;background:#f8f4fe;color:#211143;font:inherit;width:100%;}
.rhf-field textarea{padding:14px;resize:vertical;line-height:1.6;}
.rhf-field input:focus,.rhf-field textarea:focus{outline:none;border-color:#7c3aed;box-shadow:0 0 0 4px rgba(124,58,237,.11);}
.rhf-readonly{display:flex;align-items:center;min-height:48px;padding:0 14px;border-radius:14px;background:#f8f4fe;border:1.5px solid #e7ddf9;color:#211143;font-weight:700;}
.rhf-actions{display:flex;justify-content:flex-end;gap:10px;padding-top:16px;border-top:1px solid #f0eaf9;}
.rhf-btn-ghost{background:#fff;color:#826fa8;border-color:#eadffd;}
.rhf-btn-primary{background:#4c1d95;color:#fff;box-shadow:0 12px 24px rgba(76,29,149,.18);}
.rhf-btn:disabled{opacity:.55;cursor:not-allowed;box-shadow:none;}
@media(max-width:760px){.rhf-header,.rhf-actions{flex-direction:column}.rhf-form,.rhf-header{padding-left:20px;padding-right:20px}.rhf-grid{grid-template-columns:1fr}.rhf-btn,.rhf-back{width:100%;}}
</style>

@endsection
