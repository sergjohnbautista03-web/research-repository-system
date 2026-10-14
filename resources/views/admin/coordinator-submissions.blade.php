@extends('layouts.admin')
@section('title', 'Journal Submissions')
@section('page-title', 'Journal Submissions')

@section('content')
@include('admin.partials.coordinator-styles')

<div class="coord-shell">
    @include('admin.partials.coordinator-research-filters')
    @include('admin.partials.coordinator-research-table', ['mode' => 'submitted'])
</div>
@endsection
