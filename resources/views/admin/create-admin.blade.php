@extends('layouts.admin')
@section('title', 'Create Department Account')
@section('page-title', 'Create Department Account')

@section('content')
@php
    $selectedAccountType = old('account_type', 'dean');
    $passwordSuffix = $selectedAccountType === 'coordinator' ? 'Coordinator' : 'Dean';
@endphp
<div class="dean-shell">
    <section class="dean-form-card">
            <div class="dean-form-header">
                <div class="dean-form-icon" id="accountTypeIcon">{{ $selectedAccountType === 'coordinator' ? 'C' : 'D' }}</div>
                <div>
                    <h3 id="accountTypeTitle">New {{ $selectedAccountType === 'coordinator' ? 'Research Coordinator' : 'Department Dean' }}</h3>
                    <p id="accountTypeSubtitle">Enter the account profile and assigned department.</p>
                </div>
                <a href="{{ route('admin.users') }}" class="dean-back-btn">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="19" y1="12" x2="5" y2="12"/>
                        <polyline points="12 19 5 12 12 5"/>
                    </svg>
                    Back
                </a>
            </div>

            @if($errors->any())
                <div class="dean-alert">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    <div>
                        @foreach($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.store-admin') }}" class="dean-form">
                @csrf

                <div class="dean-section-label"><span>1</span> Account Assignment</div>
                <div class="dean-field">
                    <label for="account_type">Account Type <span>*</span></label>
                    <select id="account_type" name="account_type" required>
                        <option value="dean" {{ $selectedAccountType === 'dean' ? 'selected' : '' }}>Department Dean</option>
                        <option value="coordinator" {{ $selectedAccountType === 'coordinator' ? 'selected' : '' }}>Research Coordinator</option>
                    </select>
                </div>

                <div class="dean-field">
                    <label for="department">Assigned Department <span>*</span></label>
                    <select id="department" name="department" required>
                        <option value="">Select department</option>
                        @foreach($departments as $department)
                            <option value="{{ $department }}" {{ old('department') === $department ? 'selected' : '' }}>
                                {{ $department }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="dean-section-label"><span>2</span> Personal Information</div>
                <div class="dean-grid dean-grid-2">
                    <div class="dean-field">
                        <label for="firstname">First Name <span>*</span></label>
                        <input type="text" id="firstname" name="firstname" value="{{ old('firstname') }}"
                            placeholder="e.g. Maria" required autocomplete="off"
                            oninput="validateDeanName(this)"
                            style="{{ $errors->has('firstname') ? 'border-color:#ef4444;' : '' }}">
                    </div>

                    <div class="dean-field">
                        <label for="lastname">Last Name <span>*</span></label>
                        <input type="text" id="lastname" name="lastname" value="{{ old('lastname') }}"
                            placeholder="e.g. Santos" required autocomplete="off"
                            oninput="validateDeanName(this)"
                            style="{{ $errors->has('lastname') ? 'border-color:#ef4444;' : '' }}">
                    </div>
                </div>

                <div class="dean-grid dean-grid-2">
                    <div class="dean-field">
                        <label for="middlename">Middle Name</label>
                        <input type="text" id="middlename" name="middlename" value="{{ old('middlename') }}"
                            placeholder="Optional" autocomplete="off"
                            oninput="validateDeanName(this)"
                            style="{{ $errors->has('middlename') ? 'border-color:#ef4444;' : '' }}">
                    </div>

                    <div class="dean-field">
                        <label for="dean_id" id="accountIdLabel">Dean ID <span>*</span></label>
                        <input type="text" id="dean_id" name="dean_id" value="{{ old('dean_id') }}"
                            placeholder="{{ $selectedAccountType === 'coordinator' ? 'e.g. RC-2026-001' : 'e.g. DEAN-2026-001' }}" required autocomplete="off"
                            oninput="validateDeanId(this)"
                            style="{{ $errors->has('dean_id') ? 'border-color:#ef4444;' : '' }}">
                        @error('dean_id')<small class="dean-field-error">{{ $message }}</small>@enderror
                    </div>
                </div>

                <div class="dean-generated-box">
                    <div class="dean-generated-copy">
                        <strong>Auto-generated account access</strong>
                    </div>
                    <div class="dean-generated-grid">
                        <div class="dean-generated-item">
                            <span>Login ID</span>
                            <input type="text" id="dean_generated_login" value="{{ old('dean_id') }}" readonly>
                        </div>
                        <div class="dean-generated-item">
                            <span>Default Password</span>
                            <input type="text" id="dean_generated_password" value="{{ old('dean_id') ? old('dean_id') . '_' . $passwordSuffix . '@1' : '' }}" readonly>
                        </div>
                    </div>
                </div>

                <div class="dean-notice">
                    <div class="dean-notice-icon">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="8" x2="12" y2="12"/>
                            <line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                    </div>
                    <p id="accountTypeNotice">This account will be created as a <strong>{{ $selectedAccountType === 'coordinator' ? 'Research Coordinator' : 'Department Dean' }}</strong> with access limited to the selected department.</p>
                </div>

                <div class="dean-actions">
                    <a href="{{ route('admin.users') }}" class="dean-btn dean-btn-ghost">Cancel</a>
                    <button type="submit" class="dean-btn dean-btn-primary">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                            <path d="M22 10v6M2 10l10-5 10 5-10 5-10-5z"/>
                            <path d="M6 12v5c0 2 2 3 6 3s6-1 6-3v-5"/>
                        </svg>
                        <span id="submitAccountLabel">Create {{ $selectedAccountType === 'coordinator' ? 'Coordinator' : 'Dean' }} Account</span>
                    </button>
                </div>
            </form>
    </section>
</div>

<style>
.dean-shell{
    display:grid;
    gap:22px;
    max-width:1040px;
}

.dean-form-card{
    background:#fff;
    border-radius:24px;
    border:1px solid rgba(117,83,182,.12);
    box-shadow:0 14px 34px rgba(57,26,101,.06);
}

.dean-form-header{
    display:flex;
    align-items:center;
    gap:14px;
    padding:24px 28px;
    background:linear-gradient(160deg, #fdfbff 0%, #f7f1ff 100%);
    border-bottom:1px solid #eee6fa;
}

.dean-form-icon{
    display:grid;
    place-items:center;
    width:50px;
    height:50px;
    border-radius:16px;
    background:#fff;
    border:1.5px solid #e6daf8;
    color:#6d28d9;
    font-weight:900;
    font-size:19px;
    box-shadow:0 10px 20px rgba(124,58,237,.08);
}

.dean-form-header h3{
    margin:0 0 4px;
    font-size:19px;
    color:#201044;
}

.dean-form-header p{
    margin:0;
    color:#9486b8;
    font-size:13px;
}

.dean-back-btn{
    margin-left:auto;
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:9px 15px;
    border-radius:999px;
    background:#fff;
    border:1.5px solid #e6daf8;
    color:#6d28d9;
    font-size:13px;
    font-weight:700;
    text-decoration:none;
}

.dean-back-btn:hover{
    background:#f6f0ff;
}

.dean-alert{
    display:flex;
    align-items:flex-start;
    gap:10px;
    margin:18px 28px 0;
    padding:14px 16px;
    border-radius:16px;
    background:#fef2f2;
    border:1px solid #fecaca;
    color:#b91c1c;
    font-size:13px;
}

.dean-alert p{
    margin:0 0 4px;
}

.dean-alert p:last-child{
    margin-bottom:0;
}

.dean-form{
    display:grid;
    gap:18px;
    padding:28px;
}

.dean-section-label{
    display:flex;
    align-items:center;
    gap:10px;
    margin-top:4px;
    padding-bottom:10px;
    border-bottom:1px solid #f0eaf9;
    font-size:11px;
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:.16em;
    color:#7a58b0;
}

.dean-section-label span{
    display:grid;
    place-items:center;
    width:24px;
    height:24px;
    border-radius:999px;
    background:#f1eaff;
    border:1px solid #e5d8fa;
    color:#6d28d9;
    font-size:11px;
}

.dean-grid{
    display:grid;
    gap:16px;
}

.dean-grid-2{
    grid-template-columns:repeat(2, minmax(0, 1fr));
}

.dean-field{
    display:flex;
    flex-direction:column;
    gap:8px;
}

.dean-field label{
    font-size:12px;
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:.08em;
    color:#5f418f;
}

.dean-field label span{
    color:#dc2626;
}

.dean-field input,
.dean-field select{
    width:100%;
    height:50px;
    padding:0 15px;
    border:1.5px solid #e7ddf9;
    border-radius:14px;
    background:linear-gradient(180deg, #fefcff 0%, #faf7ff 100%);
    font-size:14px;
    color:#211143;
    font-family:inherit;
    box-sizing:border-box;
    transition:border-color .16s ease, box-shadow .16s ease, transform .16s ease;
}

.dean-field input:focus,
.dean-field select:focus{
    outline:none;
    border-color:#7c3aed;
    box-shadow:0 0 0 4px rgba(124,58,237,.11);
    transform:translateY(-1px);
    background:#fff;
}

.dean-field-error{
    color:#ef4444;
    font-size:12px;
}

.dean-generated-box{
    display:grid;
    gap:14px;
    padding:18px 20px;
    border-radius:18px;
    border:1px solid #e6d8ff;
    background:linear-gradient(180deg, #fcfaff 0%, #f7f2ff 100%);
}

.dean-generated-copy strong{
    display:block;
    color:#512b89;
    font-size:14px;
}

.dean-generated-grid{
    display:grid;
    grid-template-columns:repeat(2, minmax(0, 1fr));
    gap:14px;
}

.dean-generated-item{
    display:grid;
    gap:7px;
}

.dean-generated-item span{
    font-size:11px;
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:.08em;
    color:#6d4fa0;
}

.dean-generated-item input{
    width:100%;
    height:44px;
    padding:0 13px;
    border:1.5px solid #dccbf8;
    border-radius:13px;
    background:#fff;
    color:#211143;
    font:700 13.5px/1.2 inherit;
    box-sizing:border-box;
}

.dean-notice{
    display:flex;
    align-items:flex-start;
    gap:12px;
    padding:15px 18px;
    border-radius:18px;
    background:#fff8e8;
    border:1px solid #fde2a8;
    color:#9a5b00;
}

.dean-notice-icon{
    display:grid;
    place-items:center;
    width:28px;
    height:28px;
    border-radius:999px;
    background:#fff1c8;
    border:1px solid #f9d77e;
    color:#c27b00;
    flex-shrink:0;
}

.dean-notice p{
    margin:0;
    line-height:1.65;
    font-size:13.5px;
}

.dean-actions{
    display:flex;
    justify-content:flex-end;
    gap:10px;
    padding-top:20px;
    border-top:1px solid #f0eaf9;
}

.dean-btn{
    display:inline-flex;
    align-items:center;
    gap:7px;
    height:46px;
    padding:0 20px;
    border-radius:999px;
    text-decoration:none;
    font-size:13.5px;
    font-weight:800;
    border:none;
    cursor:pointer;
    transition:transform .16s ease, box-shadow .16s ease, background .16s ease;
}

.dean-btn:hover{
    transform:translateY(-1px);
}

.dean-btn-ghost{
    background:#fff;
    color:#826fa8;
    border:1.5px solid #eadffd;
}

.dean-btn-primary{
    background:linear-gradient(135deg, #5b21b6 0%, #43188e 100%);
    color:#fff;
    box-shadow:0 14px 24px rgba(91,33,182,.22);
}

@media (max-width: 1080px){
    .dean-layout{
        grid-template-columns:1fr;
    }

    .dean-side-card{
        position:static;
    }
}

@media (max-width: 760px){
    .dean-hero{
        padding:22px 20px;
    }

    .dean-hero h2{
        font-size:25px;
    }

    .dean-hero-grid,
    .dean-grid-2,
    .dean-generated-grid{
        grid-template-columns:1fr;
    }

    .dean-form-header{
        flex-wrap:wrap;
        padding:20px;
    }

    .dean-back-btn{
        margin-left:0;
    }

    .dean-form{
        padding:20px;
    }

    .dean-actions{
        flex-direction:column;
    }

    .dean-btn{
        width:100%;
        justify-content:center;
    }
}
</style>

@push('scripts')
<script>
function setDeanFieldError(input, message) {
    input.style.borderColor = '#ef4444';
    const container = input.closest('.dean-field');
    let msg = container.querySelector('.dean-field-error-live');
    if (!msg) {
        msg = document.createElement('small');
        msg.className = 'dean-field-error dean-field-error-live';
        container.appendChild(msg);
    }
    msg.textContent = message;
}

function clearDeanFieldError(input) {
    input.style.borderColor = '';
    const container = input.closest('.dean-field');
    const msg = container.querySelector('.dean-field-error-live');
    if (msg) msg.remove();
}

function validateDeanName(input) {
    if (!input.value.length) { clearDeanFieldError(input); return; }
    if (/[0-9]/.test(input.value) || /[^a-zA-Z\s\-\.]/.test(input.value)) {
        input.style.borderColor = '#ef4444';
    } else {
        clearDeanFieldError(input);
    }
}

function validateDeanId(input) {
    updateDeanGeneratedAccess(input.value);

    if (!input.value.length) { clearDeanFieldError(input); return; }
    if (!/^[A-Za-z0-9\-]+$/.test(input.value)) {
        setDeanFieldError(input, 'Account ID may only contain letters, numbers, and hyphens.');
    } else {
        clearDeanFieldError(input);
        input.style.borderColor = '#10b981';
    }
}

function selectedAccountMeta() {
    const accountType = document.getElementById('account_type');
    const isCoordinator = accountType && accountType.value === 'coordinator';

    return {
        isCoordinator,
        label: isCoordinator ? 'Research Coordinator' : 'Department Dean',
        shortLabel: isCoordinator ? 'Coordinator' : 'Dean',
        icon: isCoordinator ? 'C' : 'D',
        placeholder: isCoordinator ? 'e.g. RC-2026-001' : 'e.g. DEAN-2026-001',
    };
}

function syncAccountTypeText() {
    const meta = selectedAccountMeta();
    const icon = document.getElementById('accountTypeIcon');
    const title = document.getElementById('accountTypeTitle');
    const idLabel = document.getElementById('accountIdLabel');
    const idInput = document.getElementById('dean_id');
    const notice = document.getElementById('accountTypeNotice');
    const submitLabel = document.getElementById('submitAccountLabel');

    if (icon) icon.textContent = meta.icon;
    if (title) title.textContent = 'New ' + meta.label;
    if (idLabel) idLabel.innerHTML = meta.shortLabel + ' ID <span>*</span>';
    if (idInput) idInput.placeholder = meta.placeholder;
    if (notice) notice.innerHTML = 'This account will be created as a <strong>' + meta.label + '</strong> with access limited to the selected department.';
    if (submitLabel) submitLabel.textContent = 'Create ' + meta.shortLabel + ' Account';

    updateDeanGeneratedAccess(idInput ? idInput.value : '');
}

function updateDeanGeneratedAccess(deanId) {
    const login = document.getElementById('dean_generated_login');
    const password = document.getElementById('dean_generated_password');
    const meta = selectedAccountMeta();
    const cleanDeanId = deanId.trim();

    if (!login || !password) return;

    login.value = cleanDeanId;
    password.value = cleanDeanId ? cleanDeanId + '_' + meta.shortLabel + '@1' : '';
}

document.addEventListener('DOMContentLoaded', function () {
    const deanId = document.getElementById('dean_id');
    const accountType = document.getElementById('account_type');
    if (accountType) accountType.addEventListener('change', syncAccountTypeText);
    syncAccountTypeText();
    if (deanId) updateDeanGeneratedAccess(deanId.value);
});

</script>
@endpush

@endsection
