@extends('layouts.admin')
@section('title', 'Create User Account')
@section('page-title', 'Create User Account')

@section('content')
<div class="cu-wrap">
    <div class="cu-info-panel">
        <div class="cu-info-icon">U</div>
        <h2>Department User Setup</h2>
        <p>Create a ready-to-use account for a student or researcher. Accounts created here are activated immediately so the user can log in right away.</p>
        <ul class="cu-benefits">
            <li>Department deans can only create accounts for their own department.</li>
            <li>Researcher accounts can be assigned as student or faculty.</li>
            <li>Created accounts are automatically approved and active.</li>
        </ul>
        @if($isDepartmentScoped)
            <div class="cu-note">
                <strong>Assigned department:</strong>
                <span>{{ $fixedDepartment }}</span>
            </div>
        @endif
    </div>

    <div class="cu-form-panel">
        <div class="cu-panel-head">
            <div class="cu-panel-icon">+</div>
            <div>
                <h3>New User Account</h3>
                <p>Fill in the account details and assign the correct role.</p>
            </div>
        </div>

        @if($errors->any())
            <div class="cu-alert">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('admin.store-user') }}" class="cu-form">
            @csrf

            <div class="cu-grid">
                <div class="cu-field">
                    <label for="firstname">First Name</label>
                    <input type="text" id="firstname" name="firstname" value="{{ old('firstname') }}" required>
                </div>
                <div class="cu-field">
                    <label for="lastname">Last Name</label>
                    <input type="text" id="lastname" name="lastname" value="{{ old('lastname') }}" required>
                </div>
            </div>

            <div class="cu-field">
                <label for="middlename">Middle Name</label>
                <input type="text" id="middlename" name="middlename" value="{{ old('middlename') }}">
            </div>

            <div class="cu-grid">
                <div class="cu-field">
                    <label for="role">Account Type</label>
                    <select id="role" name="role" required onchange="toggleUserRoleFields()">
                        <option value="">Select role</option>
                        <option value="user" {{ old('role') === 'user' ? 'selected' : '' }}>Regular User</option>
                        <option value="researcher" {{ old('role') === 'researcher' ? 'selected' : '' }}>Researcher</option>
                    </select>
                </div>
                <div class="cu-field">
                    <label for="department">Department</label>
                    @if($isDepartmentScoped)
                        <input type="text" id="department_display" value="{{ $fixedDepartment }}" readonly>
                        <input type="hidden" name="department" value="{{ $fixedDepartment }}">
                    @else
                        <select id="department" name="department" required>
                            <option value="">Select department</option>
                            @foreach($departments as $department)
                                <option value="{{ $department }}" {{ old('department') === $department ? 'selected' : '' }}>{{ $department }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>
            </div>

            <div id="researcherFields" class="cu-block" style="{{ old('role') === 'researcher' ? '' : 'display:none;' }}">
                <div class="cu-grid">
                    <div class="cu-field">
                        <label for="member_type">Researcher Type</label>
                        <select id="member_type" name="member_type" onchange="toggleResearcherTypeFields()">
                            <option value="">Select type</option>
                            <option value="student" {{ old('member_type') === 'student' ? 'selected' : '' }}>Student</option>
                            <option value="faculty" {{ old('member_type') === 'faculty' ? 'selected' : '' }}>Faculty</option>
                        </select>
                    </div>
                    <div class="cu-field" id="yearLevelWrap" style="{{ old('member_type') === 'student' ? '' : 'display:none;' }}">
                        <label for="year_level">Year Level</label>
                        <select id="year_level" name="year_level">
                            <option value="">Select year level</option>
                            @foreach([1, 2, 3, 4] as $level)
                                <option value="{{ $level }}" {{ (string) old('year_level') === (string) $level ? 'selected' : '' }}>{{ $level }}{{ $level === 1 ? 'st' : ($level === 2 ? 'nd' : ($level === 3 ? 'rd' : 'th')) }} Year</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="cu-grid">
                    <div class="cu-field" id="researcherStudentIdWrap" style="{{ old('member_type') === 'student' ? '' : 'display:none;' }}">
                        <label for="student_id">Student ID</label>
                        <input type="text" id="student_id" name="student_id" value="{{ old('student_id') }}">
                    </div>
                    <div class="cu-field" id="employeeIdWrap" style="{{ old('member_type') === 'faculty' ? '' : 'display:none;' }}">
                        <label for="employee_id">Employee ID</label>
                        <input type="text" id="employee_id" name="employee_id" value="{{ old('employee_id') }}">
                    </div>
                </div>
            </div>

            <div id="regularUserFields" class="cu-block" style="{{ old('role') === 'user' ? '' : 'display:none;' }}">
                <div class="cu-field">
                    <label for="regular_student_id">Student ID</label>
                    <input type="text" id="regular_student_id" value="{{ old('role') === 'user' ? old('student_id') : '' }}" oninput="syncRegularStudentId(this.value)">
                </div>
            </div>

            <div class="cu-block">
                <div class="cu-grid">
                    <div class="cu-field">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required>
                    </div>
                    <div class="cu-field">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                </div>
                <div class="cu-field">
                    <label for="password_confirmation">Confirm Password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required>
                </div>
            </div>

            <div class="cu-actions">
                <a href="{{ route('admin.users') }}" class="cu-btn cu-btn-cancel">Cancel</a>
                <button type="submit" class="cu-btn cu-btn-submit">Create Account</button>
            </div>
        </form>
    </div>
</div>

<style>
.cu-wrap{display:grid;grid-template-columns:340px minmax(0,1fr);gap:24px;max-width:1100px}
.cu-info-panel{background:linear-gradient(170deg,#36125f 0%,#4f1b7f 100%);color:#fff;border-radius:22px;padding:28px;box-shadow:0 20px 40px rgba(59,15,122,.18)}
.cu-info-icon{width:54px;height:54px;border-radius:18px;background:rgba(255,255,255,.14);display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:800;margin-bottom:18px}
.cu-info-panel h2{margin:0 0 10px;font-size:30px;line-height:1.05}
.cu-info-panel p{margin:0 0 18px;color:rgba(255,255,255,.82);line-height:1.6}
.cu-benefits{margin:0;padding-left:18px;display:grid;gap:10px;color:rgba(255,255,255,.9);font-size:14px}
.cu-note{margin-top:20px;padding:14px 16px;border-radius:16px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.16)}
.cu-note strong{display:block;font-size:12px;text-transform:uppercase;letter-spacing:.08em;margin-bottom:5px;color:#d8c9f1}
.cu-note span{font-size:15px;font-weight:700}
.cu-form-panel{background:#fff;border:1px solid #eadff8;border-radius:22px;box-shadow:0 16px 36px rgba(59,15,122,.08);overflow:hidden}
.cu-panel-head{display:flex;align-items:center;gap:14px;padding:24px 26px;background:linear-gradient(180deg,#fdfbff 0%,#f7f2fe 100%);border-bottom:1px solid #efe7fb}
.cu-panel-icon{width:46px;height:46px;border-radius:16px;background:#fff;border:1px solid #eadff8;display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:800;color:#6b2fa0}
.cu-panel-head h3{margin:0 0 4px;font-size:20px;color:#220a3f}
.cu-panel-head p{margin:0;color:#8d7ba8;font-size:13px}
.cu-alert{margin:18px 26px 0;padding:13px 16px;border-radius:14px;background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;font-size:13px}
.cu-alert p{margin:0 0 4px}
.cu-alert p:last-child{margin-bottom:0}
.cu-form{padding:26px;display:flex;flex-direction:column;gap:18px}
.cu-block{padding:18px;border-radius:18px;border:1px solid #f0eaf9;background:#fcfbff}
.cu-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
.cu-field{display:flex;flex-direction:column;gap:7px}
.cu-field label{font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#6b5d85}
.cu-field input,.cu-field select{width:100%;padding:12px 14px;border-radius:14px;border:1.5px solid #e8dff5;background:#fff;font:inherit;color:#210b3d}
.cu-field input:focus,.cu-field select:focus{outline:none;border-color:#7c3aed;box-shadow:0 0 0 3px rgba(124,58,237,.1)}
.cu-field input[readonly]{background:#f7f2fe;color:#6b2fa0;font-weight:700}
.cu-actions{display:flex;justify-content:flex-end;gap:10px}
.cu-btn{display:inline-flex;align-items:center;justify-content:center;padding:11px 18px;border-radius:14px;font-size:13px;font-weight:700;text-decoration:none;border:none;cursor:pointer}
.cu-btn-cancel{background:#fff;color:#8b7aaa;border:1.5px solid #e8dff5}
.cu-btn-submit{background:#3b0f7a;color:#fff;box-shadow:0 8px 18px rgba(59,15,122,.22)}
@media (max-width: 980px){.cu-wrap{grid-template-columns:1fr}}
@media (max-width: 640px){.cu-grid{grid-template-columns:1fr}.cu-form,.cu-panel-head,.cu-info-panel{padding:18px}.cu-actions{flex-direction:column}.cu-btn{width:100%}}
</style>

<script>
function toggleUserRoleFields() {
    const role = document.getElementById('role').value;
    const researcherFields = document.getElementById('researcherFields');
    const regularUserFields = document.getElementById('regularUserFields');

    researcherFields.style.display = role === 'researcher' ? '' : 'none';
    regularUserFields.style.display = role === 'user' ? '' : 'none';

    if (role !== 'researcher') {
        document.getElementById('member_type').value = '';
        toggleResearcherTypeFields();
    }

    if (role !== 'user') {
        document.getElementById('regular_student_id').value = '';
        syncRegularStudentId('');
    }
}

function toggleResearcherTypeFields() {
    const memberType = document.getElementById('member_type').value;
    document.getElementById('yearLevelWrap').style.display = memberType === 'student' ? '' : 'none';
    document.getElementById('researcherStudentIdWrap').style.display = memberType === 'student' ? '' : 'none';
    document.getElementById('employeeIdWrap').style.display = memberType === 'faculty' ? '' : 'none';

    if (memberType !== 'student') {
        document.getElementById('student_id').value = '';
        document.getElementById('year_level').value = '';
    }

    if (memberType !== 'faculty') {
        document.getElementById('employee_id').value = '';
    }
}

function syncRegularStudentId(value) {
    const role = document.getElementById('role').value;
    if (role === 'user') {
        document.getElementById('student_id').value = value;
    }
}

toggleUserRoleFields();
toggleResearcherTypeFields();
syncRegularStudentId(document.getElementById('regular_student_id').value);
</script>
@endsection
