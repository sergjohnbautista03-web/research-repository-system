@extends('layouts.admin')
@section('title', 'Journal Batches')
@section('page-title', auth()->user()->isGlobalAdmin() ? 'Batches Ready to Publish' : 'Academic Year Journal Batches')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/journal-batch-list.css') }}?v={{ is_file(public_path('css/journal-batch-list.css')) ? filemtime(public_path('css/journal-batch-list.css')) : '1' }}">
@endpush
@push('scripts')
<script src="{{ asset('js/journal-batch-list.js') }}?v={{ is_file(public_path('js/journal-batch-list.js')) ? filemtime(public_path('js/journal-batch-list.js')) : '1' }}" defer></script>
@endpush
@section('content')
<div class="jbl-page">
    <form id="jbl-filters" method="GET" action="{{ route('admin.journal-batches') }}" class="jbl-filters {{ auth()->user()->isDepartmentDean() ? 'jbl-filters-dean' : '' }}">
        @unless(auth()->user()->isDepartmentDean())
        <div class="jbl-field"><label for="batch-department">Department</label><select id="batch-department" name="department"><option value="">All Departments</option>@foreach($departments as $department)<option value="{{ $department }}" @selected(request('department') === $department)>{{ $department }}</option>@endforeach</select></div>
        @endunless
        <div class="jbl-field"><label for="batch-program">Program</label><select id="batch-program" name="program"><option value="">All Programs</option>@foreach($programs as $program)<option value="{{ $program }}" @selected(request('program') === $program)>{{ $program }}</option>@endforeach</select></div>
        <div class="jbl-field"><label for="batch-ay">Academic Year</label><select id="batch-ay" name="school_year"><option value="">All Academic Years</option>@foreach($schoolYears as $year)<option value="{{ $year }}" @selected(request('school_year') === $year)>{{ $year }}</option>@endforeach</select></div>
        <a id="jbl-clear" class="jbl-clear" href="{{ route('admin.journal-batches') }}">Clear</a>
        <noscript><button class="jbl-action" type="submit">Apply</button></noscript>
    </form>
    <script type="application/json" id="jbl-programs">@json($programsByDepartment)</script>
    <p id="jbl-feedback" class="jbl-feedback" role="status" aria-live="polite"></p>
    <section id="jbl-results" class="jbl-results" aria-labelledby="jbl-heading">
        <header class="jbl-heading"><h2 id="jbl-heading">{{ auth()->user()->isDepartmentDean() ? 'Submitted Journal Batches' : 'Journal Batches' }}</h2><span class="jbl-count">{{ $batches->total() }} {{ $batches->total() === 1 ? 'batch' : 'batches' }}</span></header>
        <div class="jbl-list">
        @forelse($batches as $batch)
            <article class="jbl-item">
                <div class="jbl-item-main">
                    <div class="jbl-item-heading"><h3>{{ $batch->title }}</h3><span class="jbl-status jbl-status-{{ $batch->batch_stage }}">{{ $batch->batchLabel() }}</span></div>
                    <dl class="jbl-meta">
                        <div><dt>Academic Year</dt><dd>{{ $batch->school_year }}</dd></div>
                        <div><dt>Program</dt><dd>{{ $batch->program ?? 'Not assigned' }}</dd></div>
                        <div><dt>Journals</dt><dd>{{ $batch->journals_count }} {{ $batch->journals_count === 1 ? 'journal' : 'journals' }}</dd></div>
                        @if($batch->batch_submitted_at)<div><dt>Date Submitted</dt><dd><time datetime="{{ $batch->batch_submitted_at->toIso8601String() }}">{{ $batch->batch_submitted_at->timezone('Asia/Manila')->format('M j, Y') }}</time></dd></div>@endif
                        @if(auth()->user()->isGlobalAdmin())<div><dt>Department</dt><dd>{{ $batch->department }}</dd></div>@endif
                    </dl>
                </div>
                <a class="jbl-action" href="{{ route('admin.journal-batches.show', $batch) }}" data-open-journal-batch data-batch-title="Journal Batch — AY {{ $batch->school_year }}" aria-haspopup="dialog" aria-controls="journalBatchModal">Open Batch<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg></a>
            </article>
        @empty
            <div class="jbl-empty"><strong>No journal batches found.</strong><p>{{ request()->anyFilled(['department', 'program', 'school_year']) ? 'No batches match the selected filters.' : (auth()->user()->isGlobalAdmin() ? 'Batches forwarded by the Dean for publishing will appear here.' : (auth()->user()->isDepartmentDean() ? 'Completed journal batches submitted by the Research Coordinator will appear here.' : 'Completed journal batches you submit to the Dean will appear here.')) }}</p></div>
        @endforelse
        </div>
        @if($batches->hasPages())<div class="jbl-pagination">{{ $batches->links() }}</div>@endif
    </section>
</div>
@include('admin.partials.journal-batch-modal')
@endsection
