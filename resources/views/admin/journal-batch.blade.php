@extends($embedded ? 'layouts.journal-batch-modal' : 'layouts.admin')
@section('title', 'Academic Year Journal Batch')
@section('page-title', 'Journal Batch — AY '.$batch->school_year)
@section('content')
@include('admin.partials.coordinator-styles')
@push('styles')<link rel="stylesheet" href="{{ asset('css/journal-batches.css') }}?v={{ filemtime(public_path('css/journal-batches.css')) }}">@endpush
@php
    $actor = auth()->user();
    $uploaded = $batch->journals->filter(fn ($journal) => $journal->file_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($journal->file_path))->count();
    $showEditor = $editable && (request()->boolean('add') || $editingJournal || ($errors->any() && ! $errors->has('batch')) || $batch->journals->isEmpty());
    $oldAuthors = old('authors', $editingJournal?->authorNames() ?? ['']);
    if (! is_array($oldAuthors) || $oldAuthors === []) {
        $oldAuthors = [''];
    }
    $isStudentJournal = old('submission_category', $editingJournal?->submission_category ?? $batch->submission_category) === \App\Models\Research::SUBMISSION_CATEGORY_STUDENT_JOURNAL;
    $batchRouteParameters = ['batch' => $batch];
    if ($embedded) $batchRouteParameters['embedded'] = 1;
@endphp
<div class="coord-shell jb-shell">
    <section class="coord-card">
        <div class="coord-head"><div><h2>{{ $batch->title }}</h2><p>AY {{ $batch->school_year }} · {{ $batch->department }}</p><p>Program: {{ $batch->program ?? 'Not assigned' }}</p><p>Assigned by {{ $batch->dean?->name ?? 'Department Dean' }} to {{ $batch->coordinator?->name ?? 'Research Coordinator' }}</p></div>@unless($embedded)<a class="coord-btn coord-btn-soft" href="{{ route('admin.journal-batches') }}">All Batches</a>@endunless</div>
        <div class="coord-card-pad jb-summary"><span class="coord-pill coord-pill-{{ $batch->batch_stage }}">{{ $batch->batchLabel() }}</span><span><strong>{{ $batch->journals->count() }}</strong> journals prepared</span><span><strong>{{ $uploaded }}/{{ $batch->journals->count() }}</strong> journal PDFs uploaded</span></div>
        @if($batch->notes)<div class="coord-card-pad jb-instructions"><strong>Task instructions:</strong> {{ $batch->notes }}</div>@endif
    </section>
    @if($errors->any())<div class="jb-errors" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif

    @if($editable)
        <section class="coord-card"><div class="coord-head"><div><h2>Completed Journals</h2><p>Upload the journals you prepared for AY {{ $batch->school_year }}. Add each journal with its details and PDF, then submit the entire batch to the Dean.</p></div><a class="coord-btn coord-btn-primary" href="{{ route('admin.journal-batches.show', $batchRouteParameters + ['add' => 1]) }}#journal-editor">Add Journal</a></div></section>
    @endif

    @if($showEditor)
        <section class="coord-card" id="journal-editor">
            <div class="coord-head"><h2>{{ $editingJournal ? 'Edit Journal in Batch' : 'Add Completed Journal to Batch' }}</h2><span class="coord-muted">AY {{ $batch->school_year }}</span></div>
            <form method="POST" action="{{ $editingJournal ? route('admin.journal-batches.journals.update', [$batch, $editingJournal]) : route('admin.journal-batches.journals.store', $batch) }}" enctype="multipart/form-data" class="coord-card-pad jb-editor">
                @csrf @if($editingJournal)@method('PATCH')@endif
                @if($embedded)<input type="hidden" name="embedded" value="1">@endif
                <div class="coord-field"><label for="journal-title">Journal Title</label><input id="journal-title" name="title" maxlength="500" required value="{{ old('title', $editingJournal?->title) }}">@if($editingJournal)@include('components.research-id', ['research' => $editingJournal])@endif</div>
                <div class="jb-editor-grid">
                    <div class="coord-field"><label for="journal-category">Journal Category</label><select id="journal-category" name="submission_category" required>@foreach(\App\Models\Research::adminSubmissionCategories() as $value => $label)<option value="{{ $value }}" @selected(old('submission_category', $editingJournal?->submission_category ?? $batch->submission_category) === $value)>{{ $label }}</option>@endforeach</select></div>
                    <div class="coord-field"><label for="journal-type">Journal Type</label><select id="journal-type" name="type" required>@foreach(\App\Models\Research::journalTypeOptions() as $type)<option value="{{ $type }}" @selected(old('type', $editingJournal?->type) === $type)>{{ $type }}</option>@endforeach</select></div>
                    <div class="coord-field"><label for="journal-year">Year Published</label><input id="journal-year" name="year_published" type="number" min="2022" max="{{ \App\Models\Research::maxPublicationYear() }}" required value="{{ old('year_published', $editingJournal?->year_published ?? (int) substr($batch->school_year, 0, 4)) }}"></div>
                    <div class="coord-field">
                        <label for="journal-course">{{ $batch->program ? 'Program (assigned by Dean)' : 'Program (required for student journals)' }}</label>
                        @if($batch->program)
                            <input id="journal-course" name="course" value="{{ $batch->program }}" readonly>
                        @else
                            <select id="journal-course" name="course"><option value="">Select Program</option>@foreach(\App\Models\Research::programsByDepartment()[$batch->department] ?? [] as $program)<option value="{{ $program }}" @selected(old('course', $editingJournal?->course) === $program)>{{ $program }}</option>@endforeach</select>
                        @endif
                    </div>
                </div>
                <div class="coord-field" id="journal-authors" data-student-category="{{ \App\Models\Research::SUBMISSION_CATEGORY_STUDENT_JOURNAL }}">
                    <label id="journal-author-label">{{ $isStudentJournal ? 'Researchers' : 'Co-author(s)' }} <span aria-hidden="true">*</span></label>
                    <div class="jb-author-list" id="journal-author-list" role="group" aria-labelledby="journal-author-label">
                        @foreach($oldAuthors as $author)
                            @include('admin.partials.journal-batch-author-row', ['author' => $author, 'student' => $isStudentJournal, 'single' => count($oldAuthors) <= 1])
                        @endforeach
                    </div>
                    <button type="button" class="jb-add-author" id="journal-add-author" @disabled(count($oldAuthors) >= 12)>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        <span>{{ $isStudentJournal ? 'Add Researcher' : 'Add Co-author' }}</span>
                    </button>
                    <template id="journal-author-template">@include('admin.partials.journal-batch-author-row', ['author' => '', 'student' => $isStudentJournal, 'single' => false])</template>
                </div>
                <div class="coord-field"><label for="journal-abstract">Journal Abstract</label><textarea id="journal-abstract" name="abstract" rows="5" required>{{ old('abstract', $editingJournal?->abstract) }}</textarea></div>
                <div class="coord-field"><label for="journal-keywords">Keywords</label><input id="journal-keywords" type="text" name="keywords" maxlength="500" value="{{ old('keywords', $editingJournal?->keywords) }}" placeholder="keyword one, keyword two"><span class="coord-muted">Separate keywords with commas.</span></div>
                <div class="coord-field"><label for="journal-file">Completed Journal PDF</label><input id="journal-file" type="file" name="file" accept="application/pdf,.pdf"><span class="coord-muted">{{ $editingJournal?->file_name ?? 'PDF up to 30MB. A completed PDF is required before submitting the batch.' }}</span></div>
                <div class="coord-actions"><button class="coord-btn coord-btn-primary">Save Journal in Batch</button><a class="coord-btn coord-btn-soft" href="{{ route('admin.journal-batches.show', $batchRouteParameters) }}">Cancel</a></div>
            </form>
        </section>
    @endif

    @if($batch->journals->isNotEmpty())
        <section class="coord-card">
            <div class="coord-head"><div><h2>Journals in This Batch</h2>@unless($editable)<p>Scroll down to review every journal and its PDF in this batch.</p>@endunless</div></div>
            <div class="coord-list {{ $editable ? '' : 'jb-review-list' }}">
                @foreach($batch->journals as $journal)
                    <article class="{{ $editable ? 'coord-item' : 'jb-review-journal' }}" id="journal-{{ $journal->id }}">
                        <div class="{{ $editable ? 'jb-editable-heading' : 'jb-review-heading' }}">
                            <div class="coord-item-main">
                                @unless($editable)<span class="coord-eyebrow">Journal {{ $loop->iteration }} of {{ $batch->journals->count() }}</span>@endunless
                                <h3>{{ $journal->title }}</h3>@include('components.research-id', ['research' => $journal])<p>{{ $journal->authorListLabel() }}</p><p>{{ $journal->abstract }}</p>
                            </div>
                            @if($editable)
                                <div class="coord-actions">
                                    @if($journal->file_path)<a class="coord-btn coord-btn-soft" href="{{ route('admin.research.view-file', $journal) }}" target="_blank" rel="noopener">Review Journal PDF</a>@endif
                                    <a class="coord-btn coord-btn-primary" href="{{ route('admin.journal-batches.show', $batchRouteParameters + ['edit' => $journal->id]) }}#journal-editor">Edit Journal</a>
                                </div>
                            @endif
                        </div>
                        @unless($editable)
                            @if($journal->file_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($journal->file_path))
                                <iframe class="jb-review-pdf" data-journal-review-frame src="{{ route('admin.research.view-file', ['research' => $journal, 'embedded' => 1]) }}" title="Journal {{ $loop->iteration }}: {{ $journal->title }} — PDF review" loading="lazy" scrolling="no"></iframe>
                            @else
                                <p class="jb-review-missing" role="status">The PDF for this journal is unavailable. Restore the PDF before proceeding with the batch.</p>
                            @endif
                        @endunless
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    <section class="coord-card"><div class="coord-card-pad coord-actions">
        @if($editable)
            <form method="POST" action="{{ route('admin.journal-batches.transition', [$batch, 'submit']) }}">@csrf @if($embedded)<input type="hidden" name="embedded" value="1">@endif<button type="submit" class="coord-btn coord-btn-primary">Submit Completed Batch to Dean</button></form>
            <span class="coord-muted">Upload all completed journals with their PDFs before submitting the batch.</span>
        @elseif($actor->isDepartmentDean() && in_array($batch->batch_stage, ['dean_review', 'dean_approved'], true))
            <form method="POST" action="{{ route('admin.journal-batches.transition', [$batch, 'forward']) }}">@csrf @if($embedded)<input type="hidden" name="embedded" value="1">@endif<button class="coord-btn coord-btn-primary">Forward Batch to Admin</button></form>
        @elseif($actor->isGlobalAdmin() && $batch->batch_stage === 'pending')
            <form method="POST" action="{{ route('admin.journal-batches.transition', [$batch, 'publish']) }}">@csrf @if($embedded)<input type="hidden" name="embedded" value="1">@endif<button class="coord-btn coord-btn-primary">Publish All Journals in Batch</button></form>
            <span class="coord-muted">Each journal will appear as a separate entry in the repository.</span>
        @else
            <span class="coord-muted">{{ $batch->batchLabel() }}</span>
        @endif
    </div></section>
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/journal-batches.js') }}?v={{ filemtime(public_path('js/journal-batches.js')) }}" defer></script>
<script src="{{ asset('js/journal-batch-review.js') }}?v={{ filemtime(public_path('js/journal-batch-review.js')) }}" defer></script>
@endpush
