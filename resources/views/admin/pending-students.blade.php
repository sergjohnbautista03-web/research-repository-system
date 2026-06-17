@extends('layouts.admin')
@section('title', 'Pending Students')
@section('page-title', 'Pending Student Approvals')

@section('content')
<form method="GET" class="ps-toolbar">
    <div class="ps-search">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search student name or email">
    </div>

    <div class="ps-filter">
        <select name="department">
            <option value="">All departments</option>
            @foreach($departments as $department)
                <option value="{{ $department }}" {{ $selectedDepartment === $department ? 'selected' : '' }}>
                    {{ $department }}
                </option>
            @endforeach
        </select>
    </div>

    <button type="submit" class="ps-btn ps-btn-primary">Filter</button>
    <a href="{{ route('admin.pending-students') }}" class="ps-btn ps-btn-ghost">Clear</a>
</form>

<div class="ps-department-grid">
    @foreach($departmentCounts as $departmentCount)
        <a href="{{ route('admin.pending-students', ['department' => $departmentCount->department]) }}"
            class="ps-department-card {{ $selectedDepartment === $departmentCount->department ? 'active' : '' }}">
            <span class="ps-department-name">{{ $departmentCount->department }}</span>
            <strong>{{ $departmentCount->total }}</strong>
            <small>pending student{{ $departmentCount->total > 1 ? 's' : '' }}</small>
        </a>
    @endforeach
</div>

<div class="ps-card">
    <div class="ps-card-head">
        <div>
            <h3>Student Registration Queue</h3>
            <p>{{ $selectedDepartment ? 'Showing only ' . $selectedDepartment : 'Review pending student registrations by department.' }}</p>
        </div>
        <span class="ps-badge">{{ $pendingStudents->total() }} pending</span>
    </div>

    <div class="ps-table-wrap">
        <table class="ps-table">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Email</th>
                    <th>Department</th>
                    <th>Registered</th>
                    <th>Assign Student ID</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pendingStudents as $student)
                    <tr>
                        <td data-label="Student">
                            <div class="ps-name-cell">
                                <span class="ps-avatar">{{ strtoupper(substr($student->name, 0, 1)) }}</span>
                                <div>
                                    <strong>{{ $student->name }}</strong>
                                    <small>Pending approval</small>
                                </div>
                            </div>
                        </td>
                        <td data-label="Email">{{ $student->email }}</td>
                        <td data-label="Department">{{ $student->department ?? '—' }}</td>
                        <td data-label="Registered">{{ $student->created_at->format('M d, Y') }}</td>
                        <td data-label="Assign Student ID">
                            <form method="POST" action="{{ route('admin.approve-student', $student) }}" class="ps-approve-form" id="approve-student-{{ $student->id }}">
                                @csrf
                                @method('PATCH')
                                <input
                                    type="text"
                                    name="student_id"
                                    value="{{ old('student_id') && (string) old('approval_user') === (string) $student->id ? old('student_id') : '' }}"
                                    placeholder="e.g. 2026-0001"
                                    autocomplete="off"
                                    required
                                >
                                @if($errors->has('student_id') && (string) old('approval_user') === (string) $student->id)
                                    <small class="ps-error">{{ $errors->first('student_id') }}</small>
                                @endif
                                <input type="hidden" name="approval_user" value="{{ $student->id }}">
                            </form>
                        </td>
                        <td data-label="Actions">
                            <div class="ps-actions">
                                <button type="submit" form="approve-student-{{ $student->id }}" class="ps-btn ps-btn-approve">Approve</button>
                                <form method="POST" action="{{ route('admin.reject-student', $student) }}" onsubmit="return confirm('Reject and delete this student registration?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="ps-btn ps-btn-reject">Reject</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="ps-empty">No pending student registrations found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($pendingStudents->hasPages())
        <div class="ps-pagination">
            {{ $pendingStudents->links('vendor.pagination.custom') }}
        </div>
    @endif
</div>

<style>
.ps-toolbar{display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin-bottom:18px}
.ps-search{flex:1;min-width:240px}
.ps-search input,.ps-filter select,.ps-approve-form input{width:100%;padding:11px 14px;border:1.5px solid #e8dff5;border-radius:12px;background:#fff;color:#1a0638;font:inherit}
.ps-search input:focus,.ps-filter select:focus,.ps-approve-form input:focus{outline:none;border-color:#7c3aed;box-shadow:0 0 0 3px rgba(124,58,237,.1)}
.ps-btn{display:inline-flex;align-items:center;justify-content:center;padding:10px 16px;border-radius:999px;font-size:13px;font-weight:700;text-decoration:none;border:none;cursor:pointer}
.ps-btn-primary{background:#3b0f7a;color:#fff}
.ps-btn-ghost{background:#fff;border:1px solid #e8dff5;color:#7b6a96}
.ps-btn-approve{background:#dcfce7;color:#166534}
.ps-btn-reject{background:#fee2e2;color:#b91c1c}
.ps-department-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-bottom:18px}
.ps-department-card{display:flex;flex-direction:column;gap:6px;padding:16px;border:1px solid #ece3f8;border-radius:16px;background:#fff;text-decoration:none;color:#1a0638;box-shadow:0 6px 22px rgba(59,15,122,.04)}
.ps-department-card.active{border-color:#7c3aed;background:#faf5ff;box-shadow:0 0 0 2px rgba(124,58,237,.08)}
.ps-department-name{font-size:13px;font-weight:700;color:#5b3d8a}
.ps-department-card strong{font-size:24px;line-height:1;color:#2d0a5e}
.ps-department-card small{color:#8f7cae}
.ps-card{background:#fff;border:1px solid rgba(107,47,160,.1);border-radius:20px;overflow:hidden;box-shadow:0 8px 28px rgba(59,15,122,.05)}
.ps-card-head{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:22px 24px;border-bottom:1px solid #f0eaf9;background:linear-gradient(180deg,#fdfbff 0%,#f7f3fd 100%)}
.ps-card-head h3{margin:0 0 4px;font-size:18px;color:#1a0638}
.ps-card-head p{margin:0;font-size:13px;color:#8f7cae}
.ps-badge{padding:6px 12px;border-radius:999px;background:#fef3c7;color:#92400e;font-size:12px;font-weight:700}
.ps-table-wrap{overflow-x:auto}
.ps-table{width:100%;border-collapse:collapse}
.ps-table th,.ps-table td{padding:14px 18px;text-align:left;border-bottom:1px solid #f7f2fd;vertical-align:top}
.ps-table th{font-size:11px;text-transform:uppercase;letter-spacing:.08em;color:#9d8ab8;background:#fcfaff}
.ps-name-cell{display:flex;gap:10px;align-items:center}
.ps-name-cell strong{display:block;color:#1a0638}
.ps-name-cell small{color:#9d8ab8}
.ps-avatar{display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:50%;background:#f3e8ff;color:#6b21a8;font-weight:800}
.ps-approve-form{display:flex;flex-direction:column;gap:6px;min-width:180px}
.ps-actions{display:flex;gap:8px;align-items:flex-start}
.ps-error{font-size:12px;color:#b91c1c}
.ps-empty{text-align:center;color:#9d8ab8;padding:40px 16px}
.ps-pagination{padding:16px 24px}
@media (max-width: 900px){
    .ps-table thead{display:none}
    .ps-table,.ps-table tbody,.ps-table tr,.ps-table td{display:block;width:100%}
    .ps-table-wrap{padding:12px}
    .ps-table tbody{display:grid;gap:12px}
    .ps-table tr{border:1px solid #eee4fb;border-radius:16px;overflow:hidden}
    .ps-table td{border-bottom:none;padding:12px 14px}
    .ps-table td::before{content:attr(data-label);display:block;margin-bottom:6px;font-size:10px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#9d8ab8}
    .ps-table td:first-child::before{display:none}
    .ps-actions{flex-direction:column}
    .ps-actions form,.ps-actions .ps-btn,.ps-btn{width:100%}
}
</style>
@endsection
