@extends('layouts.admin')
@section('title', 'Research Monitoring')
@section('page-title', 'Research Monitoring')

@section('content')
@include('admin.partials.coordinator-styles')

<div class="coord-shell">
    @include('admin.partials.coordinator-research-filters')
    @include('admin.partials.coordinator-research-table', ['mode' => 'monitoring'])
</div>
@endsection
