@extends('layouts.admin')
@section('title', 'Assigned Journal Tasks')
@section('page-title', 'Assigned Journal Tasks')
@push('scripts')
<script src="{{ asset('js/coordinator-task-filters.js') }}?v={{ is_file(public_path('js/coordinator-task-filters.js')) ? filemtime(public_path('js/coordinator-task-filters.js')) : '1' }}" defer></script>
@endpush

@section('content')
@include('admin.partials.coordinator-styles')

<style>
.coord-filter.dean-submission-filters{grid-template-columns:repeat(4,minmax(150px,1fr)) auto}
.coord-task-feedback{margin:0;color:#79628f;font-size:13px}.coord-task-feedback:empty{display:none}
#ct-results[aria-busy=true]{opacity:.65}
@media(max-width:1120px){.coord-filter.dean-submission-filters{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:720px){.coord-filter.dean-submission-filters{grid-template-columns:1fr}}
.dean-submission{padding:0;display:block;overflow:hidden;border:1px solid #e9dff5;border-radius:14px;background:#fff;box-shadow:0 3px 12px rgba(76,29,149,.04)}
.dean-task-list .coord-list{padding:12px;gap:10px}
.dean-submission-body{padding:14px 18px}
.dean-submission-heading{display:flex;align-items:flex-start;justify-content:space-between;gap:14px}
.dean-submission-title{min-width:0}
.dean-submission-label{display:block;margin-bottom:4px;font-size:10px;font-weight:700;letter-spacing:.09em;text-transform:uppercase;color:#79628f}
.dean-submission h3{margin:0;color:#321061;font-size:17px;font-weight:600;line-height:1.4;overflow-wrap:anywhere}
.dean-submission-status{flex-shrink:0;text-align:right}
.dean-submission-meta{display:grid;grid-template-columns:minmax(0,.7fr) minmax(0,1.2fr) minmax(0,1fr);gap:12px 20px;margin:14px 0 0}
.dean-submission-meta>div{min-width:0}
.dean-submission-meta dt{margin:0 0 4px;color:#79628f;font-size:12px}
.dean-submission-meta dd{margin:0;color:#352345;font-size:14px;line-height:1.5;overflow-wrap:anywhere}
.dean-submission-body>p{margin:10px 0 0;overflow-wrap:anywhere;white-space:pre-line}
.dean-submission-actions{display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;padding:10px 18px;border-top:1px solid #eee6fa;background:#fdfbff}
.dean-submission-actions .coord-actions{justify-content:flex-end}
.dean-submission-actions form{margin:0}
.dean-submission-actions .coord-btn{box-sizing:border-box;font-size:13px;font-weight:600;line-height:1.3;min-height:36px;white-space:normal;text-align:center;padding:8px 12px}
.dean-submission-actions .coord-btn-primary{box-shadow:none}
.dean-submission-actions .coord-btn:focus-visible{outline:2px solid #7c3aed;outline-offset:3px}
@media(max-width:1000px){.dean-submission-meta{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:720px){
    .dean-submission-body{padding:12px 14px}
    .dean-submission-heading{flex-direction:column;gap:8px}
    .dean-submission-status{text-align:left}
    .dean-submission-meta{grid-template-columns:1fr;gap:10px;margin-top:12px}
    .dean-submission-actions{padding:10px 14px;align-items:stretch;flex-direction:column}
    .dean-submission-actions .coord-actions{flex-direction:column;align-items:stretch}
    .dean-submission-actions form,.dean-submission-actions .coord-btn{width:100%;box-sizing:border-box}
}
</style>

<div class="coord-shell">
    <form id="ct-filters" method="GET" action="{{ route('admin.coordinator.dean-submissions') }}" class="coord-card coord-filter dean-submission-filters">
        <div class="coord-field">
            <label for="handoff-program">Program</label>
            <select id="handoff-program" name="program">
                <option value="">All Programs</option>
                @foreach($programs as $program)
                    <option value="{{ $program }}" @selected(request('program') === $program)>{{ $program }}</option>
                @endforeach
            </select>
        </div>
        <div class="coord-field">
            <label for="handoff-academic-year">Academic Year</label>
            <select id="handoff-academic-year" name="academic_year">
                <option value="">All Academic Years</option>
                @foreach($academicYears as $academicYear)
                    <option value="{{ $academicYear }}" @selected(request('academic_year') === $academicYear)>{{ $academicYear }}</option>
                @endforeach
            </select>
        </div>
        <div class="coord-field">
            <label for="handoff-category">Journal Category</label>
            <select id="handoff-category" name="submission_category">
                <option value="">All Categories</option>
                @foreach($submissionCategories as $value => $label)
                    <option value="{{ $value }}" @selected(request('submission_category') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="coord-field">
            <label for="handoff-status">Status</label>
            <select id="handoff-status" name="status">
                <option value="">All Statuses</option>
                @foreach($workflowStatusOptions as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="coord-actions">
            <a id="ct-clear" href="{{ route('admin.coordinator.dean-submissions') }}" class="coord-btn coord-btn-soft">Clear</a>
            <noscript><button type="submit" class="coord-btn coord-btn-primary">Apply</button></noscript>
        </div>
    </form>

    <p id="ct-feedback" class="coord-task-feedback" role="status" aria-live="polite"></p>
    <div id="ct-results" class="coord-card dean-task-list">
        <div class="coord-list">
            @forelse($handoffs as $handoff)
                @php($preparationStatus = $handoff->status === \App\Models\ResearchHandoff::STATUS_PENDING && ! $handoff->research && $handoff->batch_stage !== \App\Models\Research::STATUS_REJECTED ? 'pending' : 'preparing')
                <article class="dean-submission" aria-labelledby="submission-title-{{ $handoff->id }}">
                    <div class="dean-submission-body">
                        <div class="dean-submission-heading">
                            <div class="dean-submission-title">
                                <span class="dean-submission-label">Journal task</span>
                                <h3 id="submission-title-{{ $handoff->id }}" data-search-label="{{ $handoff->title }}" data-search-detail="Journal task">{{ $handoff->title }}</h3>
                                @if($handoff->research)@include('components.research-id', ['research' => $handoff->research])@endif
                            </div>
                            <div class="dean-submission-status">
                                <span class="dean-submission-label">Status</span>
                                <span class="coord-pill coord-pill-{{ $preparationStatus === 'pending' ? 'pending' : 'draft' }}">{{ $workflowStatusOptions[$preparationStatus] }}</span>
                            </div>
                        </div>
                        <dl class="dean-submission-meta">
                            <div><dt>Academic Year</dt><dd>AY {{ $handoff->school_year ?? 'Not assigned' }}</dd>@if($handoff->program)<dd class="coord-muted">Program: {{ $handoff->program }}</dd>@endif</div>
                            <div>
                                <dt>Assigned by Dean</dt>
                                <dd>{{ $handoff->dean?->name ?? 'Department Dean' }}</dd>
                                <dd class="coord-muted">{{ $handoff->department }}</dd>
                            </div>
                            <div>
                                <dt>Date received</dt>
                                <dd><time datetime="{{ ($handoff->received_at ?? $handoff->created_at)->toIso8601String() }}">{{ ($handoff->received_at ?? $handoff->created_at)->timezone('Asia/Manila')->format('M d, Y \a\t h:i A') }}</time></dd>
                                @if(! $handoff->received_at)
                                    <dd class="coord-muted">Awaiting receipt confirmation</dd>
                                @endif
                            </div>
                        </dl>
                        @if($handoff->notes)<p class="coord-muted">Task instructions: {{ $handoff->notes }}</p>@endif
                    </div>
                    @if($handoff->is_batch || in_array($handoff->status, [\App\Models\ResearchHandoff::STATUS_PENDING, \App\Models\ResearchHandoff::STATUS_RECEIVED], true))
                        <div class="dean-submission-actions">
                            <div class="coord-actions">
                            @if($handoff->status === \App\Models\ResearchHandoff::STATUS_PENDING)
                                <form method="POST" action="{{ route('admin.coordinator.dean-submissions.receive', $handoff) }}">
                                    @csrf
                                    <button type="submit" class="coord-btn coord-btn-success">Mark as Received</button>
                                </form>
                            @endif
                            <a href="{{ $handoff->is_batch ? route('admin.journal-batches.show', $handoff) : route('admin.research-handoffs.add-research', $handoff) }}" class="coord-btn coord-btn-primary" @if($handoff->is_batch) data-open-journal-batch data-batch-title="Journal Batch — AY {{ $handoff->school_year }}" aria-haspopup="dialog" aria-controls="journalBatchModal" @endif>{{ $handoff->is_batch ? 'Prepare Batch' : 'Prepare Journal' }}</a>
                            </div>
                        </div>
                    @endif
                </article>
            @empty
                <div class="coord-empty">
                    <strong>No journal tasks found.</strong>
                    <span>Journal tasks assigned by your Dean will appear here.</span>
                </div>
            @endforelse
        </div>
        @if($handoffs->hasPages())
            <div class="coord-pagination">{{ $handoffs->links() }}</div>
        @endif
    </div>
</div>
@include('admin.partials.journal-batch-modal')
@endsection
