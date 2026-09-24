@extends('layouts.admin')
@section('title', 'Submit Research File')
@section('page-title', 'Submit Research File')

@section('content')
<div class="rhf-shell">
    <section class="rhf-card">
        <div class="rhf-header">
            <div>
                <span>Department Dean</span>
                <h2>Send Research to Coordinator</h2>
                <p>Upload the final defended PDF so your department Research Coordinator can add the complete research information to the system.</p>
            </div>
            <a href="{{ route('admin.research-handoffs') }}" class="rhf-back">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                Back
            </a>
        </div>

        @if(! $coordinator)
            <div class="rhf-alert rhf-alert-warning">
                <strong>No active Research Coordinator found.</strong>
                <span>Create or activate a Research Coordinator for {{ $department }} before submitting a research file.</span>
            </div>
        @endif

        @if($errors->any())
            <div class="rhf-alert rhf-alert-error">
                @foreach($errors->all() as $error)
                    <span>{{ $error }}</span>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('admin.research-handoffs.store') }}" enctype="multipart/form-data" class="rhf-form">
            @csrf

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

            <div class="rhf-field">
                <label for="title">Research Title <span>*</span></label>
                <input type="text" id="title" name="title" value="{{ old('title') }}" required maxlength="500" placeholder="Enter the defended research title">
            </div>

            <div class="rhf-field">
                <label for="file">Final Defended PDF <span>*</span></label>
                <label class="rhf-file">
                    <input type="file" id="file" name="file" accept="application/pdf,.pdf" required {{ ! $coordinator ? 'disabled' : '' }}>
                    <span class="rhf-file-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    </span>
                    <span>
                        <strong id="rhfFileName">Choose PDF file</strong>
                        <small>PDF only, maximum 30MB</small>
                    </span>
                </label>
            </div>

            <div class="rhf-actions">
                <a href="{{ route('admin.research-handoffs') }}" class="rhf-btn rhf-btn-ghost">Cancel</a>
                <button type="submit" class="rhf-btn rhf-btn-primary" {{ ! $coordinator ? 'disabled' : '' }}>
                    Submit to Coordinator
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
.rhf-header h2{margin:0 0 8px;color:#1a0638;font-size:25px;}
.rhf-header p{margin:0;max-width:650px;color:#6f5a92;line-height:1.6;}
.rhf-back,.rhf-btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:42px;padding:0 16px;border-radius:12px;text-decoration:none;font:800 13px/1 inherit;border:1px solid transparent;cursor:pointer;}
.rhf-back{background:#fff;color:#5b348f;border-color:#e8dff5;}
.rhf-back svg,.rhf-file-icon svg{width:15px;height:15px;}
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
.rhf-field textarea{padding:14px;resize:vertical;line-height:1.6;}
.rhf-field input:focus,.rhf-field textarea:focus{outline:none;border-color:#7c3aed;box-shadow:0 0 0 4px rgba(124,58,237,.11);}
.rhf-readonly{display:flex;align-items:center;min-height:48px;padding:0 14px;border-radius:14px;background:#f8f4fe;border:1.5px solid #e7ddf9;color:#211143;font-weight:700;}
.rhf-file{display:flex!important;align-items:center;gap:12px;min-height:76px;padding:16px;border:1.5px dashed #cdb7ec;border-radius:16px;background:#fcfaff;cursor:pointer;text-transform:none!important;letter-spacing:0!important;}
.rhf-file input{position:absolute;opacity:0;pointer-events:none;}
.rhf-file-icon{display:grid;place-items:center;width:42px;height:42px;border-radius:12px;background:#ede9fe;color:#6d28d9;flex-shrink:0;}
.rhf-file strong{display:block;color:#211143;font-size:14px;margin-bottom:4px;}
.rhf-file small{display:block;color:#7a6a95;font-size:12px;}
.rhf-actions{display:flex;justify-content:flex-end;gap:10px;padding-top:16px;border-top:1px solid #f0eaf9;}
.rhf-btn-ghost{background:#fff;color:#826fa8;border-color:#eadffd;}
.rhf-btn-primary{background:#4c1d95;color:#fff;box-shadow:0 12px 24px rgba(76,29,149,.18);}
.rhf-btn:disabled{opacity:.55;cursor:not-allowed;box-shadow:none;}
@media(max-width:760px){.rhf-header,.rhf-actions{flex-direction:column}.rhf-form,.rhf-header{padding-left:20px;padding-right:20px}.rhf-grid{grid-template-columns:1fr}.rhf-btn,.rhf-back{width:100%;}}
</style>

@push('scripts')
<script>
const fileInput = document.getElementById('file');
const fileName = document.getElementById('rhfFileName');

if (fileInput && fileName) {
    fileInput.addEventListener('change', function () {
        const file = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
        fileName.textContent = file ? file.name : 'Choose PDF file';
    });
}
</script>
@endpush
@endsection
