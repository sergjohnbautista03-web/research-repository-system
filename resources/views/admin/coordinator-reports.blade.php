@extends('layouts.admin')
@section('title', 'Journal Preparation Reports')
@section('page-title', 'Reports')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/coordinator-reports.css') }}?v={{ filemtime(public_path('css/coordinator-reports.css')) }}">
@endpush

@section('content')
@include('admin.partials.coordinator-styles')

<div class="coord-shell coordinator-reports">
    <div class="coord-grid coord-grid-4 report-summary" aria-label="Journal preparation totals">
        @foreach(\App\Services\CoordinatorJournalReport::METRICS as $key => $label)
            <article class="coord-stat">
                <h2>{{ $label }}</h2>
                <strong>{{ number_format($totals[$key]) }}</strong>
                <small>{{ ['tasks' => 'Assigned tasks matching your filters', 'journals' => 'Prepared journals in these tasks', 'batches' => 'Journal batch assignments matching your filters', 'submitted' => 'Matching batches submitted to the Dean'][$key] }}</small>
            </article>
        @endforeach
    </div>

    <section class="coord-card">
        <div class="coord-head">
            <div>
                <h2>Journal Preparation Reports</h2>
                <p>Track your assigned journal preparation tasks and submissions to the Dean.</p>
            </div>
            <div class="coord-actions">
                <button type="submit" form="coordinator-report-filters" formaction="{{ route('admin.coordinator.reports.export') }}" name="format" value="pdf" class="coord-btn coord-btn-soft">Export PDF</button>
                <button type="submit" form="coordinator-report-filters" formaction="{{ route('admin.coordinator.reports.export') }}" name="format" value="excel" class="coord-btn coord-btn-primary">Export Excel</button>
            </div>
        </div>
        <form id="coordinator-report-filters" class="coord-filter report-filters" action="{{ route('admin.coordinator.reports') }}" method="GET">
            <div class="coord-field">
                <label for="report-academic-year">Academic Year</label>
                <select id="report-academic-year" name="school_year">
                    <option value="">All Academic Years</option>
                    @foreach($schoolYears as $year)
                        <option value="{{ $year }}" @selected($filters['school_year'] === $year)>{{ $year }}</option>
                    @endforeach
                </select>
            </div>
            <div class="coord-field">
                <label for="report-program">Program</label>
                <select id="report-program" name="program">
                    <option value="">All Programs</option>
                    @foreach($programs as $value => $program)
                        <option value="{{ $value }}" @selected($filters['program'] === $value)>{{ $program }}</option>
                    @endforeach
                </select>
            </div>
            <div class="coord-field">
                <label for="report-category">Journal Category</label>
                <select id="report-category" name="submission_category">
                    <option value="">All Categories</option>
                    @foreach($categories as $value => $category)
                        <option value="{{ $value }}" @selected($filters['submission_category'] === $value)>{{ $category }}</option>
                    @endforeach
                </select>
            </div>
            <div class="coord-field">
                <label for="report-status">Status</label>
                <select id="report-status" name="status">
                    <option value="">All Statuses</option>
                    @foreach($statuses as $value => $status)
                        <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $status }}</option>
                    @endforeach
                </select>
            </div>
            <div class="coord-actions">
                <button type="submit" class="coord-btn coord-btn-primary">Generate Report</button>
                <a href="{{ route('admin.coordinator.reports') }}" class="coord-btn coord-btn-soft">Clear</a>
            </div>
        </form>
        @if($errors->any())
            <div class="report-errors" role="alert">
                @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            </div>
        @endif
    </section>

    <section class="coord-card" aria-labelledby="report-table-heading">
        <div class="coord-head">
            <div>
                <h2 id="report-table-heading">Journal Preparation Tasks</h2>
                <p>{{ number_format($tableRows->total()) }} task(s) · Generated {{ $generatedAt }}</p>
            </div>
            <span class="coord-pill">{{ $coordinator->department }}</span>
        </div>
        <div class="report-applied-filters">
            @foreach($filterLabels as $label => $value)
                <span><strong>{{ $label }}:</strong> {{ $value }}</span>
            @endforeach
        </div>
        <div class="coord-table-wrap">
            <table class="coord-table report-table">
                <thead><tr>
                    @foreach(\App\Services\CoordinatorJournalReport::HEADERS as $header)<th scope="col">{{ $header }}</th>@endforeach
                </tr></thead>
                <tbody>
                    @forelse($tableRows as $row)
                        <tr>
                            <td class="report-nowrap">{{ $row['school_year'] }}</td>
                            <td>{{ $row['program'] }}</td>
                            <td>{{ $row['category'] }}</td>
                            <td class="report-number">{{ number_format($row['journals']) }}</td>
                            <td><span class="coord-pill report-status-{{ $row['status_key'] }}">{{ $row['status'] }}</span></td>
                            <td class="report-nowrap">{{ $row['submitted_at'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="coord-empty"><strong>No journal preparation tasks found.</strong>Try another filter or clear your selections.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($tableRows->hasPages())<div class="coord-pagination">{{ $tableRows->links() }}</div>@endif
    </section>

</div>
@endsection
