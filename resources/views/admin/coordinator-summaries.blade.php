@extends('layouts.admin')
@section('title', 'Journal Preparation')
@section('page-title', 'Journal Preparation')

@section('content')
@include('admin.partials.coordinator-styles')

<div class="coord-shell">
    <div class="coord-card">
        <div class="coord-head">
            <div>
                <h2>Journal Preparation</h2>
                <p>Prepare draft journals, revise returned journals, preview details, and submit completed journals to the Department Dean.</p>
            </div>
            <a href="{{ route('admin.coordinator.dean-submissions') }}" class="coord-btn coord-btn-primary">Open Assigned Journal Tasks</a>
        </div>
    </div>

    @include('admin.partials.coordinator-research-filters')
    @include('admin.partials.coordinator-research-table', ['mode' => 'summaries'])
</div>
@endsection
