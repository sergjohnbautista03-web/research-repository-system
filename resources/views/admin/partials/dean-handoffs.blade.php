@push('styles')
<link rel="stylesheet" href="{{ asset('css/dean-handoffs.css') }}?v={{ filemtime(public_path('css/dean-handoffs.css')) }}">
@endpush

<svg class="dh-symbols" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><defs>
    <symbol id="dh-file" viewBox="0 0 24 24"><path d="M14 2H5v20h14V7zM14 2v6h5M8 12h8M8 16h6"/></symbol>
    <symbol id="dh-check" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m7 12 3 3 7-7"/></symbol>
    <symbol id="dh-clock" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 6v6l4 2"/></symbol>
    <symbol id="dh-eye" viewBox="0 0 24 24"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></symbol>
    <symbol id="dh-export" viewBox="0 0 24 24"><path d="M12 16V3m-5 5 5-5 5 5M4 14v7h16v-7"/></symbol>
    <symbol id="dh-filter" viewBox="0 0 24 24"><path d="M3 4h18l-7 8v8l-4-2v-6z"/></symbol>
    <symbol id="dh-search-icon" viewBox="0 0 24 24"><circle cx="10" cy="10" r="7"/><path d="m15 15 6 6"/></symbol>
    <symbol id="dh-reset" viewBox="0 0 24 24"><path d="M20 7a9 9 0 1 0 1 8M20 2v6h-6"/></symbol>
</defs></svg>
<div class="dh-page">
    <form id="dh-filters" class="dh-filters" method="GET" action="{{ route('admin.research-handoffs') }}">
        <div><label for="dh-school-year">Academic Year</label><select id="dh-school-year" name="school_year"><option value="">All Academic Years</option>@foreach($schoolYears as $schoolYear)<option value="{{ $schoolYear }}" @selected(request('school_year') === $schoolYear)>{{ $schoolYear }}</option>@endforeach</select></div>
        <div><label for="dh-program">Program</label><select id="dh-program" name="program"><option value="">All Programs</option>@foreach($programs as $program)<option value="{{ $program }}" @selected(request('program') === $program)>{{ match ($program) { 'Information Technology' => 'BSIT', 'Computer Science' => 'BSCS', default => $program } }}</option>@endforeach</select></div>
        <div><label for="dh-category">Journal Category</label><select id="dh-category" name="submission_category"><option value="">All Categories</option>@foreach($submissionCategories as $value => $label)<option value="{{ $value }}" @selected(request('submission_category') === $value)>{{ $label }}</option>@endforeach</select></div>
        <div><label for="dh-status">Status</label><select id="dh-status" name="status"><option value="">All Statuses</option>@foreach(\Illuminate\Support\Arr::only(\App\Models\ResearchHandoff::workflowLabels(), ['pending', 'received', 'submitted', 'forwarded']) as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></div>
        <a id="dh-clear" class="dh-button dh-clear" href="{{ route('admin.research-handoffs') }}">Clear</a>
    </form>
    <p id="dh-feedback" class="dh-feedback" role="status" aria-live="polite"></p>

    <section id="dh-results" class="dh-records" aria-labelledby="dh-records-title">
        <header class="dh-records-header">
            <span class="dh-records-icon"><svg><use href="#dh-file"/></svg></span>
            <div><h2 id="dh-records-title">Journal Task Records</h2><p>Assign a task, view the completed journals, and forward them to Admin for publishing.</p></div>
            <a class="dh-button dh-primary dh-assign-task" href="{{ route('admin.research-handoffs.create') }}" data-open-journal-batch data-batch-mode="assignment" data-batch-title="Assign Journal Task" aria-haspopup="dialog" aria-controls="journalBatchModal">Assign Journal Task</a>
        </header>
        <div class="dh-table-scroll" tabindex="0" role="region" aria-label="Handoff records table">
            <table class="dh-table dh-preparation-table">
                <thead><tr><th scope="col">#</th><th scope="col">Journal Category</th><th scope="col">Program</th><th scope="col">Academic Year</th><th scope="col">Date Assigned</th><th scope="col">Status</th><th scope="col">Actions</th></tr></thead>
                <tbody>
                @forelse($handoffs as $handoff)
                    @php
                        $category = $handoff->research?->submission_category ?? $handoff->submission_category;
                        $program = $handoff->program ?: ($handoff->research?->program ?: $handoff->research?->course);
                        $programLabel = match ($program) {
                            'Information Technology' => 'BSIT',
                            'Computer Science' => 'BSCS',
                            default => $program ?: ($category === \App\Models\Research::SUBMISSION_CATEGORY_FACULTY_JOURNAL ? 'All Programs' : 'Not assigned'),
                        };
                        $stage = $handoff->workflowStage();
                        $actionLabel = match ($stage) {
                            'submitted' => 'Review Submission',
                            'dean_approved' => 'Forward to Admin',
                            'forwarded', 'published', 'archived' => 'View Details',
                            default => 'View Task',
                        };
                    @endphp
                    <tr>
                        <td>{{ $handoffs->firstItem() + $loop->index }}</td>
                        <td>{{ $submissionCategories[$category] ?? 'Not yet assigned' }}@if($handoff->research)@include('components.research-id', ['research' => $handoff->research])@endif</td>
                        <td title="{{ $program }}">{{ $programLabel }}</td>
                        <td>{{ $handoff->school_year ?: $handoff->research?->school_year ?: 'Not assigned' }}</td>
                        <td class="dh-date"><time datetime="{{ $handoff->created_at->toIso8601String() }}">{{ $handoff->created_at->timezone('Asia/Manila')->format('M j, Y') }}</time></td>
                        <td>{{ $handoff->workflowLabel() }}</td>
                        <td>
                            @if($handoff->is_batch)
                                <a class="dh-task-action" href="{{ route('admin.journal-batches.show', $handoff) }}" data-open-journal-batch data-batch-title="Journal Batch — AY {{ $handoff->school_year }}" aria-haspopup="dialog" aria-controls="journalBatchModal">{{ $actionLabel }}</a>
                            @elseif($handoff->research)
                                <a class="dh-task-action" href="{{ route('admin.research.show', $handoff->research) }}">{{ $actionLabel }}</a>
                            @else
                                <details class="dh-task-details"><summary class="dh-task-action">View Task</summary><strong>{{ $handoff->title }}</strong><p>{{ $handoff->notes ?: 'No task instructions recorded.' }}</p></details>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="dh-empty"><strong>{{ request()->anyFilled(['program', 'school_year', 'submission_category', 'status']) ? 'No matching journal tasks.' : 'No journal tasks yet.' }}</strong><p>Assign a journal task for an Academic Year. Your Research Coordinator will use the approved papers already available to prepare the journal.</p><a class="dh-button" href="{{ route('admin.research-handoffs.create') }}" data-open-journal-batch data-batch-mode="assignment" data-batch-title="Assign Journal Task" aria-haspopup="dialog" aria-controls="journalBatchModal">Assign Journal Task</a></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <footer class="dh-footer">
            <p>Showing {{ $handoffs->firstItem() ?? 0 }} to {{ $handoffs->lastItem() ?? 0 }} of {{ $handoffs->total() }} records</p>
            @if($handoffs->hasPages())
                <nav class="dh-pagination" aria-label="Handoff pagination">
                    @if($handoffs->onFirstPage())<span aria-disabled="true">&#8249;</span>@else<a href="{{ $handoffs->previousPageUrl() }}" aria-label="Previous page">&#8249;</a>@endif
                    @foreach($handoffs->getUrlRange(max(1, $handoffs->currentPage() - 2), min($handoffs->lastPage(), $handoffs->currentPage() + 2)) as $page => $url)
                        <a href="{{ $url }}" @if($page === $handoffs->currentPage()) aria-current="page" @endif>{{ $page }}</a>
                    @endforeach
                    @if($handoffs->hasMorePages())<a href="{{ $handoffs->nextPageUrl() }}" aria-label="Next page">&#8250;</a>@else<span aria-disabled="true">&#8250;</span>@endif
                </nav>
            @endif
        </footer>
    </section>
</div>

@push('scripts')
<script src="{{ asset('js/dean-handoffs.js') }}?v={{ filemtime(public_path('js/dean-handoffs.js')) }}" defer></script>
@endpush
@include('admin.partials.journal-batch-modal')
