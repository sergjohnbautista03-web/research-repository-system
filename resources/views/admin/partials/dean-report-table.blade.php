<div class="dr-table-wrap">
    <table class="dr-table">
        <thead><tr><th scope="col">#</th><th scope="col">Title</th><th scope="col">Author/s</th><th scope="col">Program</th><th scope="col">Submission Category</th><th scope="col">Research Type</th><th scope="col">Year</th></tr></thead>
        <tbody>
        @forelse($rows as $research)
            <tr>
                <td>{{ $offset + $loop->iteration }}</td>
                <td><a href="{{ route('admin.research.show', $research) }}">{{ $research->title }}</a></td>
                <td>{{ implode(', ', $research->authorNames()) ?: '—' }}</td>
                <td>{{ $research->program ?: $research->course ?: '—' }}</td>
                <td>{{ $research->getSubmissionCategoryLabel() }}</td>
                <td>{{ $research->getTypeLabel() }}</td>
                <td>{{ $research->year_published ?: '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="7" class="dr-empty">No research records match the selected filters.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
