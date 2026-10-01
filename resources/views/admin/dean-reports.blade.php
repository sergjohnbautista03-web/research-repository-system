@extends('layouts.admin')
@section('title', 'Research Reports')
@section('page-title', 'Research Reports')
@section('body-class', 'dean-reports-page')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/dean-reports.css') }}?v={{ filemtime(public_path('css/dean-reports.css')) }}">
@endpush
@section('content')
<div class="dr-actions">
    <button type="button" class="dr-button dr-primary" id="dr-print">Print Report</button>
</div>
<div class="dr-summary" id="dr-summary">
    @foreach(['Total Researches' => $allRows->count(), 'Student Research' => $studentCount, 'Faculty Research' => $facultyCount] as $label => $count)
        <div class="dr-stat"><span>{{ $label }}</span><strong>{{ number_format($count) }}</strong></div>
    @endforeach
</div>
<form id="dr-filters" class="dr-filters" method="GET" action="{{ route('admin.reports') }}">
    @php
        $fields = [
            'school_year' => ['Academic Year', 'All Academic Years', $schoolYears->mapWithKeys(fn ($year) => [$year => $year])->all()],
            'semester' => ['Semester', 'All Semesters', ['1st' => '1st Semester', '2nd' => '2nd Semester']],
            'program' => ['Program', 'All Programs', $programs->mapWithKeys(fn ($program) => [$program => $program])->all()],
            'submission_category' => ['Submission Category', 'All Categories', $categories],
            'type' => ['Research Type', 'All Research Types', $types->mapWithKeys(fn ($type) => [$type => $type])->all()],
        ];
    @endphp
    @foreach($fields as $name => [$label, $placeholder, $options])
        <div class="dr-field"><label for="dr-{{ $name }}">{{ $label }}</label><select id="dr-{{ $name }}" name="{{ $name }}">
            <option value="">{{ $placeholder }}</option>
            @foreach($options as $value => $option)
                <option value="{{ $value }}" @selected(($filters[$name] ?? '') === (string) $value)>{{ $option }}</option>
            @endforeach
        </select></div>
    @endforeach
    <a href="{{ route('admin.reports') }}" class="dr-button" id="dr-clear">Clear</a>
</form>
<p id="dr-feedback" class="dr-feedback" role="status" aria-live="polite"></p>
<div id="dr-results">
    <section class="dr-card">
        <div class="dr-card-heading"><h3>Research Records</h3><span>{{ $researches->firstItem() ?? 0 }}–{{ $researches->lastItem() ?? 0 }} of {{ number_format($researches->total()) }}</span></div>
        @include('admin.partials.dean-report-table', ['rows' => $researches, 'offset' => ($researches->firstItem() ?? 1) - 1])
        <div class="dr-pagination">{{ $researches->links('vendor.pagination.custom') }}</div>
    </section>
    <section class="dr-print-report">
        <img class="report-print-letterhead" src="{{ asset('images/report-letterhead.jpeg') }}" alt="PHILCST report header and footer">
        <h1>Research Reports</h1><h2>{{ $department ?: 'No department assigned' }}</h2>
        <p>Generated {{ now('Asia/Manila')->format('F j, Y g:i A') }}</p>
        @include('admin.partials.dean-report-table', ['rows' => $allRows, 'offset' => 0])
    </section>
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/dean-reports.js') }}?v={{ filemtime(public_path('js/dean-reports.js')) }}" defer></script>
@endpush
