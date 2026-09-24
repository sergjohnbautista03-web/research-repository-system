@extends('layouts.admin')
@section('title', 'Research Archive')
@section('page-title', 'Research Archive')

@section('content')
@include('admin.partials.coordinator-styles')

<div class="coord-shell">
    <div class="coord-card">
        <div class="coord-head">
            <div>
                <h2>Research Archive</h2>
                <p>Browse approved and archived researches. Search by title, author, department, or year.</p>
            </div>
        </div>
    </div>

    @include('admin.partials.coordinator-research-filters')
    @include('admin.partials.coordinator-research-table', ['mode' => 'archive'])
</div>
@endsection
