@extends('layouts.admin')
@section('title', 'Department Monitoring')
@section('page-title', 'Department Monitoring')

@section('content')
@include('admin.partials.coordinator-styles')

<div class="coord-shell">
    <div class="coord-card">
        <div class="coord-head">
            <div>
                <h2>Department Research Monitoring</h2>
                <p>Track researches forwarded, summarized, approved, or returned within your assigned department.</p>
            </div>
        </div>
        <div class="coord-table-wrap">
            <table class="coord-table">
                <thead>
                    <tr>
                        <th>Department</th>
                        <th>Submitted</th>
                        <th>Pending Admin Review</th>
                        <th>Approved</th>
                        <th>Returned</th>
                        <th>Dean Handoffs</th>
                        <th>Received</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($departments as $department)
                        <tr>
                            <td>
                                <span class="coord-title">{{ $department['code'] }}</span>
                                <span class="coord-muted">{{ $department['name'] }}</span>
                            </td>
                            <td>{{ number_format($department['research_total']) }}</td>
                            <td>{{ number_format($department['pending_total']) }}</td>
                            <td>{{ number_format($department['approved_total']) }}</td>
                            <td>{{ number_format($department['returned_total']) }}</td>
                            <td>{{ number_format($department['handoff_total']) }}</td>
                            <td>{{ number_format($department['received_total']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
