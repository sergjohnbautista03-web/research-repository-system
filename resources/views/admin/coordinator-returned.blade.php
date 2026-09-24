@extends('layouts.admin')
@section('title', 'Returned Researches')
@section('page-title', 'Returned Researches')

@section('content')
@include('admin.partials.coordinator-styles')

<div class="coord-shell">
    <div class="coord-card">
        <div class="coord-head">
            <div>
                <h2>Returned Researches</h2>
                <p>Review Admin return reasons, edit the summary, and resubmit.</p>
            </div>
        </div>
    </div>

    @include('admin.partials.coordinator-research-filters', ['showStatusFilter' => false])
    @include('admin.partials.coordinator-research-table', ['mode' => 'returned'])
</div>
@endsection
