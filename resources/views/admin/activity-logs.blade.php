@extends('layouts.admin')
@section('title', 'Activity Logs')
@section('page-title', 'Activity Logs')
@section('body-class', 'system-activity-page')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/user-activity-logs.css') }}?v={{ filemtime(public_path('css/user-activity-logs.css')) }}">
@endpush
@section('content')
<form id="ual-filters" class="ual-filters" method="GET" action="{{ route('admin.activity-logs') }}">
    <label class="ual-search"><span>Search Activity</span><input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Name or details" autocomplete="off"></label>
    <label><span>Role</span><select name="role"><option value="">All Roles</option>@foreach($roles as $value => $label)<option value="{{ $value }}" @selected(($filters['role'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></label>
    <label><span>Department</span><select name="department"><option value="">All Departments</option>@foreach($departments as $department)<option value="{{ $department }}" @selected(($filters['department'] ?? '') === $department)>{{ $department }}</option>@endforeach</select></label>
    <label data-year-level-filter @if(($filters['role'] ?? '') !== 'student') hidden @endif><span>Year Level</span><select name="year_level" @disabled(($filters['role'] ?? '') !== 'student')><option value="">All Year Levels</option>@foreach([1 => '1st Year', 2 => '2nd Year', 3 => '3rd Year', 4 => '4th Year'] as $value => $label)<option value="{{ $value }}" @selected((string) ($filters['year_level'] ?? '') === (string) $value)>{{ $label }}</option>@endforeach</select></label>
    <label><span>Activity</span><select name="action"><option value="">All Activities</option>@foreach($actions as $value => $label)<option value="{{ $value }}" @selected(($filters['action'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></label>
    <label><span>Date</span><input type="date" name="date" value="{{ $filters['date'] ?? '' }}"></label>
    <a id="ual-clear" class="ual-clear" href="{{ route('admin.activity-logs') }}">Clear</a>
</form>
<p id="ual-feedback" class="ual-feedback" role="status" aria-live="polite"></p>
<section id="ual-results" class="ual-card">
    <div class="ual-card-head"><h3>Activity History</h3><span>{{ number_format($logs->total()) }} log(s)</span></div>
    <div class="ual-table-wrap"><table class="ual-table">
        <thead><tr><th>Date &amp; Time</th><th>User/Role</th><th>Activity</th><th>Details</th></tr></thead>
        <tbody>@forelse($logs as $log)<tr>
            <td><time datetime="{{ $log->created_at?->toIso8601String() }}">{{ $log->created_at?->timezone('Asia/Manila')->format('M d, Y') }}<small>{{ $log->created_at?->timezone('Asia/Manila')->format('g:i A') }}</small></time></td>
            <td><strong>{{ $log->user_name }}</strong><small>{{ ['admin' => 'Admin', 'dean' => 'Department Dean', 'coordinator' => 'Research Coordinator', 'student' => 'Student', 'faculty' => 'Faculty'][$log->role] ?? ucfirst($log->role) }}</small></td>
            <td><span class="ual-action">{{ $actions[$log->action] }}</span></td>
            <td>{{ match ($log->action) { 'login' => 'User logged in to the system.', 'logout' => 'User logged out of the system.', default => $log->details } }}</td>
        </tr>@empty<tr><td colspan="4" class="ual-empty">No activities match the selected filters.</td></tr>@endforelse</tbody>
    </table></div>
    <div class="ual-pagination">{{ $logs->links('vendor.pagination.custom') }}</div>
</section>
@endsection
@push('scripts')
<script src="{{ asset('js/user-activity-logs.js') }}?v={{ filemtime(public_path('js/user-activity-logs.js')) }}" defer></script>
@endpush
