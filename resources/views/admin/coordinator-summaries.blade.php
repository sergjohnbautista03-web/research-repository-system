@extends('layouts.admin')
@section('title', 'Research Summarization')
@section('page-title', 'Research Summarization')

@section('content')
@include('admin.partials.coordinator-styles')

<div class="coord-shell">
    <div class="coord-card">
        <div class="coord-head">
            <div>
                <h2>Research Summarization</h2>
                <p>Edit draft summaries, fix returned summaries, preview details, and submit them to Admin.</p>
            </div>
            <a href="{{ route('admin.coordinator.dean-submissions') }}" class="coord-btn coord-btn-primary">Open Dean Submissions</a>
        </div>
    </div>

    @include('admin.partials.coordinator-research-filters', ['showStatusFilter' => false])
    @include('admin.partials.coordinator-research-table', ['mode' => 'summaries'])
</div>
@endsection
