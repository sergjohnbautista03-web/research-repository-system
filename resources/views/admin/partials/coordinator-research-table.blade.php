@php
    $mode = $mode ?? 'monitoring';
@endphp

<div class="coord-card">
    <div class="coord-table-wrap">
        <table class="coord-table">
            <thead>
                <tr>
                    <th>Research</th>
                    <th>Department</th>
                    <th>Type</th>
                    <th>Term</th>
                    <th>Status / Stage</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($researches as $research)
                    <tr>
                        <td>
                            <span class="coord-title">{{ $research->title }}</span>
                            <span class="coord-muted">{{ $research->authorListLabel() }}</span>
                            @if($research->rejection_reason)
                                <span class="coord-muted">Return reason: {{ Str::limit($research->rejection_reason, 120) }}</span>
                            @endif
                        </td>
                        <td>{{ $research->department ?: 'Unassigned' }}</td>
                        <td>
                            {{ $research->getTypeLabel() }}
                            <span class="coord-muted">{{ $research->getSubmissionCategoryLabel() }}</span>
                        </td>
                        <td>
                            {{ $research->semester?->label ?? 'Not set' }}
                            <span class="coord-muted">{{ $research->year_published ?: 'No year' }}</span>
                        </td>
                        <td>
                            <span class="coord-pill coord-pill-{{ $research->status }}">{{ $research->coordinatorStageLabel() }}</span>
                        </td>
                        <td>
                            <div class="coord-actions">
                                @if($research->file_path)
                                    <a href="{{ route('admin.research.view-file', $research) }}" target="_blank" rel="noopener" class="coord-btn coord-btn-soft">View File</a>
                                @endif

                                @if(in_array($research->status, [\App\Models\Research::STATUS_DRAFT, \App\Models\Research::STATUS_REJECTED], true))
                                    <a href="{{ route('admin.coordinator.summaries.edit', $research) }}" class="coord-btn coord-btn-primary">Edit Summary</a>
                                    <form method="POST" action="{{ route('admin.coordinator.summaries.submit', $research) }}">
                                        @csrf
                                        <button type="submit" class="coord-btn coord-btn-success">Submit to Admin</button>
                                    </form>
                                @elseif($mode === 'archive')
                                    <span class="coord-muted">Processed {{ optional($research->approved_at ?? $research->updated_at)->format('M d, Y') }}</span>
                                @else
                                    <span class="coord-muted">{{ $research->status === \App\Models\Research::STATUS_PENDING ? 'Waiting for Admin review' : $research->coordinatorStageLabel() }}</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="coord-empty">
                                <strong>No research records found.</strong>
                                <span>Try changing the filters or check another module.</span>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(method_exists($researches, 'links'))
        <div class="coord-pagination">{{ $researches->links() }}</div>
    @endif
</div>
