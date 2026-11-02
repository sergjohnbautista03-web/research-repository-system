@extends(request()->boolean('embedded') ? 'layouts.journal-batch-modal' : 'layouts.admin')
@section('title', 'Journal Submissions')
@section('page-title', 'Submitted Journals')
@if(request()->boolean('embedded'))
@push('styles')
<link rel="stylesheet" href="{{ asset('css/journal-batches.css') }}?v={{ filemtime(public_path('css/journal-batches.css')) }}">
@endpush
@endif

@section('content')
@include('admin.partials.coordinator-styles')

<div class="coord-shell">
    @include('admin.partials.coordinator-research-filters')
    @include('admin.partials.coordinator-research-table', ['mode' => 'submitted'])
</div>
@endsection
