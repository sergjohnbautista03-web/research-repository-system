@extends('layouts.admin')
@section('title', 'User Activity Logs')
@section('page-title', 'User Activity Logs')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/user-activity-logs.css') }}?v={{ filemtime(public_path('css/user-activity-logs.css')) }}">
@endpush
@section('content')
<form id="ual-filters" class="ual-filters" method="GET" action="{{ route('admin.user-activity-logs') }}">
    <label class="ual-search"><span>Search User</span><input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Name, email, or ID" autocomplete="off"></label>
    <label><span>Role</span><select name="role"><option value="">All Roles</option><option value="student" @selected(($filters['role'] ?? '') === 'student')>Student</option><option value="faculty" @selected(($filters['role'] ?? '') === 'faculty')>Faculty</option></select></label>
    <label><span>Action</span><select name="action"><option value="">All Actions</option>@foreach($actions as $value => $label)<option value="{{ $value }}" @selected(($filters['action'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></label>
    <label><span>Date</span><input type="date" name="date" value="{{ $filters['date'] ?? '' }}"></label>
    <a id="ual-clear" class="ual-clear" href="{{ route('admin.user-activity-logs') }}">Clear</a>
</form>
<p id="ual-feedback" class="ual-feedback" role="status" aria-live="polite"></p>

<section id="ual-results" class="ual-card">
    <div class="ual-card-head"><h3>Activity History</h3><span>{{ number_format($logs->total()) }} log(s)</span></div>
    <div class="ual-table-wrap"><table class="ual-table">
        <thead><tr><th>Date &amp; Time</th><th>User</th><th>Role</th><th>Action</th><th>Details</th></tr></thead>
        <tbody>@forelse($logs as $log)<tr>
            <td><time datetime="{{ $log->created_at?->toIso8601String() }}">{{ $log->created_at?->timezone('Asia/Manila')->format('M d, Y') }}<small>{{ $log->created_at?->timezone('Asia/Manila')->format('g:i A') }}</small></time></td>
            <td><strong>{{ $log->user?->name ?? $log->user_name }}</strong><small>{{ $log->user?->email }}</small></td>
            <td><span class="ual-role ual-role-{{ $log->role }}">{{ ucfirst($log->role) }}</span></td>
            <td><span class="ual-action">{{ $actions[$log->action] ?? $log->action }}</span></td>
            <td>{{ $log->details }}</td>
        </tr>@empty<tr><td colspan="5" class="ual-empty">No activities match the selected filters.</td></tr>@endforelse</tbody>
    </table></div>
    <div class="ual-pagination">{{ $logs->links('vendor.pagination.custom') }}</div>
</section>
@endsection
@push('scripts')
<script src="{{ asset('js/user-activity-logs.js') }}?v={{ filemtime(public_path('js/user-activity-logs.js')) }}" defer></script>
@endpush
