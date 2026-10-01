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
    <section class="dh-stats" aria-label="Handoff summary">
        @foreach([
            ['Total Handoffs', $totalCount, 'Files submitted by Dean', 'file', '', 'total'],
            ['Forwarded', $pendingCount, 'Waiting for Coordinator to receive', 'clock', 'pending', 'pending'],
            ['Received by Coordinator', $receivedCount, 'Accepted by Coordinator', 'check', 'received', 'received'],
            ['Submitted to Admin', $submittedCount, 'Awaiting Admin review', 'clock', 'submitted', 'pending'],
            ['Published', $publishedCount, 'Available in the repository', 'check', 'published', 'received'],
        ] as [$label, $count, $description, $icon, $filter, $tone])
            <a class="dh-stat dh-stat-{{ $tone }}" href="{{ route('admin.research-handoffs', $filter ? ['status' => $filter] : []) }}">
                <span class="dh-stat-icon"><svg><use href="#dh-{{ $icon }}"/></svg></span>
                <span class="dh-stat-copy"><span>{{ $label }}</span><strong>{{ number_format($count) }}</strong><small>{{ $description }}</small></span>
                <span class="dh-chevron" aria-hidden="true">&#8250;</span>
            </a>
        @endforeach
    </section>

    <form id="dh-filters" class="dh-filters" method="GET" action="{{ route('admin.research-handoffs') }}">
        <div class="dh-search-field"><label for="dh-search">Search</label><div class="dh-search-input"><svg><use href="#dh-search-icon"/></svg><input id="dh-search" type="search" name="search" value="{{ request('search') }}" placeholder="Search by research title..." data-auto-filter autocomplete="off"></div></div>
        <div><label for="dh-year">Year</label><select id="dh-year" name="year"><option value="">All Years</option>@foreach($yearOptions as $year)<option value="{{ $year }}" @selected((string) request('year') === (string) $year)>{{ $year }}</option>@endforeach</select></div>
        <div><label for="dh-category">Submission Category</label><select id="dh-category" name="submission_category"><option value="">All Categories</option>@foreach($submissionCategories as $value => $label)<option value="{{ $value }}" @selected(request('submission_category') === $value)>{{ $label }}</option>@endforeach</select></div>
        <div><label for="dh-status">Status</label><select id="dh-status" name="status"><option value="">All Status</option>@foreach(\App\Models\ResearchHandoff::workflowLabels() as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></div>
        <a id="dh-clear" class="dh-button dh-clear" href="{{ route('admin.research-handoffs') }}">Clear</a>
    </form>
    <p id="dh-feedback" class="dh-feedback" role="status" aria-live="polite"></p>

    <section id="dh-results" class="dh-records" aria-labelledby="dh-records-title">
        <header class="dh-records-header">
            <span class="dh-records-icon"><svg><use href="#dh-file"/></svg></span>
            <div><h2 id="dh-records-title">Handoff Records</h2><p>Research files submitted by the Dean to the Research Coordinator.</p></div>
        </header>
        <div class="dh-table-scroll" tabindex="0" role="region" aria-label="Handoff records table">
            <table class="dh-table">
                <thead><tr><th scope="col">#</th><th scope="col">Research Title</th><th scope="col">Submission Category</th><th scope="col">Year</th><th scope="col">Date Forwarded</th><th scope="col">Status</th><th scope="col">Actions</th></tr></thead>
                <tbody>
                @forelse($handoffs as $handoff)
                    @php
                        $category = $handoff->research?->submission_category ?? $handoff->submission_category;
                    @endphp
                    <tr>
                        <td>{{ $handoffs->firstItem() + $loop->index }}</td>
                        <td class="dh-title" data-search-label="{{ $handoff->title }}" data-search-detail="{{ $submissionCategories[$category] ?? 'Not yet assigned' }}">{{ $handoff->title }}</td>
                        <td><span class="dh-category {{ $category === \App\Models\Research::SUBMISSION_CATEGORY_FACULTY_JOURNAL ? 'dh-faculty' : '' }} {{ ! $category ? 'dh-unassigned' : '' }}">{{ $submissionCategories[$category] ?? 'Not yet assigned' }}</span></td>
                        <td class="dh-muted">{{ $handoff->research?->year_published ?? $handoff->year_published ?? '—' }}</td>
                        <td class="dh-date"><time datetime="{{ $handoff->created_at->toIso8601String() }}">{{ $handoff->created_at->timezone('Asia/Manila')->format('M d, Y') }}<br>{{ $handoff->created_at->timezone('Asia/Manila')->format('h:i A') }}</time></td>
                        <td><span class="dh-status dh-stage-{{ $handoff->workflowStage() }}">{{ $handoff->workflowLabel() }}</span></td>
                        <td><a class="dh-button dh-view" href="{{ route('admin.research-handoffs.file', $handoff) }}" target="_blank" rel="noopener" aria-label="View {{ $handoff->title }} (opens in a new tab)"><svg><use href="#dh-eye"/></svg>View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="dh-empty"><strong>{{ request()->hasAny(['search', 'year', 'submission_category', 'status']) ? 'No matching handoffs.' : 'No research handoffs yet.' }}</strong><p>Submitted research files will appear here. Try clearing the filters to see all records.</p><a class="dh-button" href="{{ route('admin.research-handoffs.create') }}">Submit research file</a></td></tr>
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
