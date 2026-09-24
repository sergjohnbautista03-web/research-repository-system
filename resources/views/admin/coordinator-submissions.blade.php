@extends('layouts.admin')
@section('title', 'Submission to Admin')
@section('page-title', 'Submission to Admin')

@section('content')
@include('admin.partials.coordinator-styles')

<div class="coord-shell">
    <div class="coord-card">
        <div class="coord-head">
            <div>
                <h2>For Admin Review</h2>
                <p>Summaries already forwarded to the Research Office/Admin.</p>
            </div>
        </div>
    </div>

    @include('admin.partials.coordinator-research-filters', ['showStatusFilter' => false])
    @include('admin.partials.coordinator-research-table', ['mode' => 'submitted'])
</div>
@endsection
