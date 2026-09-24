@extends('layouts.admin')
@section('title', 'Dean Submissions')
@section('page-title', 'Dean Submissions')

@section('content')
@include('admin.partials.coordinator-styles')

<style>
.coord-filter.dean-submission-filters{grid-template-columns:minmax(200px,1.5fr) minmax(150px,1fr) minmax(150px,1fr) auto}
@media(max-width:1120px){.coord-filter.dean-submission-filters{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:720px){.coord-filter.dean-submission-filters{grid-template-columns:1fr}}
.dean-submission{padding:0;display:block;overflow:hidden;border:1px solid #e9dff5;border-radius:14px;background:#fff;box-shadow:0 3px 12px rgba(76,29,149,.04)}
.dean-submission-body{padding:22px 24px}
.dean-submission-heading{display:flex;align-items:flex-start;justify-content:space-between;gap:20px}
.dean-submission-title{min-width:0}
.dean-submission-label{display:block;margin-bottom:7px;font-size:10px;font-weight:700;letter-spacing:.09em;text-transform:uppercase;color:#79628f}
.dean-submission h3{margin:0;color:#321061;font-size:18px;font-weight:600;line-height:1.5;overflow-wrap:anywhere}
.dean-submission-status{flex-shrink:0;text-align:right}
.dean-submission-meta{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin:22px 0 18px}
.dean-submission-meta dt{margin:0 0 6px;color:#79628f;font-size:12px}
.dean-submission-meta dd{margin:0;color:#352345;font-size:14px;line-height:1.5;overflow-wrap:anywhere}
.dean-submission-file{display:flex;align-items:center;gap:12px;padding:13px 15px;border:1px solid #ece3f7;border-radius:10px;background:#faf7fe;min-width:0}
.dean-submission-file svg{width:23px;height:23px;color:#7c3aed;flex-shrink:0}
.dean-submission-file div{min-width:0}
.dean-submission-file .dean-submission-label{margin-bottom:3px;letter-spacing:.04em}
.dean-submission-file-name{color:#503269;font-size:13px;line-height:1.5;overflow-wrap:anywhere}
.dean-submission-actions{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:16px 24px;border-top:1px solid #eee6fa;background:#fdfbff}
.dean-submission-actions .coord-actions{justify-content:flex-end}
.dean-submission-actions form{margin:0}
.dean-submission-actions .coord-btn{font-size:13px;font-weight:600;min-height:42px;white-space:normal;text-align:center;padding:9px 14px}
.dean-submission-actions .coord-btn-primary{box-shadow:none}
.dean-submission-actions .coord-btn:focus-visible{outline:2px solid #7c3aed;outline-offset:3px}
@media(max-width:720px){
    .dean-submission-body{padding:18px}
    .dean-submission-heading{flex-direction:column;gap:14px}
    .dean-submission-status{text-align:left}
    .dean-submission-meta{grid-template-columns:1fr;gap:14px;margin-top:18px}
    .dean-submission-actions{padding:16px 18px;align-items:stretch;flex-direction:column}
    .dean-submission-actions .coord-actions{flex-direction:column;align-items:stretch}
    .dean-submission-actions form,.dean-submission-actions .coord-btn{width:100%;box-sizing:border-box}
}
</style>

<div class="coord-shell">
    <form method="GET" class="coord-card coord-filter dean-submission-filters">
        <div class="coord-field">
            <label for="handoff-search">Search</label>
            <input id="handoff-search" type="text" name="search" value="{{ request('search') }}" placeholder="Research title or researcher name">
        </div>
        <div class="coord-field">
            <label for="handoff-academic-year">Academic Year</label>
            <select id="handoff-academic-year" name="academic_year">
                <option value="">All academic years</option>
                @foreach($academicYears as $academicYear)
                    <option value="{{ $academicYear }}" @selected(request('academic_year') === $academicYear)>{{ $academicYear }}</option>
                @endforeach
            </select>
        </div>
        <div class="coord-field">
            <label for="handoff-status">Status</label>
            <select id="handoff-status" name="status">
                <option value="">All statuses</option>
                @foreach($handoffStatusOptions as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}{{ $value === \App\Models\ResearchHandoff::STATUS_PENDING ? ' (unconfirmed)' : ($value === \App\Models\ResearchHandoff::STATUS_RECEIVED ? ' (confirmed)' : '') }}</option>
                @endforeach
            </select>
        </div>
        <div class="coord-actions">
            <button type="submit" class="coord-btn coord-btn-primary">Filter</button>
            <a href="{{ route('admin.coordinator.dean-submissions') }}" class="coord-btn coord-btn-soft">Clear</a>
        </div>
    </form>

    <div class="coord-card">
        <div class="coord-list">
            @forelse($handoffs as $handoff)
                <article class="dean-submission" aria-labelledby="submission-title-{{ $handoff->id }}">
                    <div class="dean-submission-body">
                        <div class="dean-submission-heading">
                            <div class="dean-submission-title">
                                <span class="dean-submission-label">Research title</span>
                                <h3 id="submission-title-{{ $handoff->id }}">{{ $handoff->title }}</h3>
                            </div>
                            <div class="dean-submission-status">
                                <span class="dean-submission-label">Status</span>
                                <span class="coord-pill coord-pill-{{ $handoff->research?->status ?? $handoff->status }}">{{ $handoff->research?->coordinatorStageLabel() ?? ($handoffStatusOptions[$handoff->status] ?? ucfirst($handoff->status)) }}</span>
                            </div>
                        </div>
                        <dl class="dean-submission-meta">
                            <div>
                                <dt>Submitted by Dean</dt>
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
                        <div class="dean-submission-file">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h5"/></svg>
                            <div>
                                <span class="dean-submission-label">File name</span>
                                <span class="dean-submission-file-name">{{ $handoff->file_name ?: basename($handoff->file_path) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="dean-submission-actions">
                        <a href="{{ route('admin.research-handoffs.file', ['handoff' => $handoff]) }}" target="_blank" rel="noopener" class="coord-btn coord-btn-soft" aria-label="View Document: {{ $handoff->file_name }} (opens in a new tab)">View Document</a>
                        <div class="coord-actions">
                        @if(in_array($handoff->status, [\App\Models\ResearchHandoff::STATUS_PENDING, \App\Models\ResearchHandoff::STATUS_RECEIVED], true))
                            @if($handoff->status === \App\Models\ResearchHandoff::STATUS_PENDING)
                                <form method="POST" action="{{ route('admin.coordinator.dean-submissions.receive', $handoff) }}">
                                    @csrf
                                    <button type="submit" class="coord-btn coord-btn-success">Confirm Received</button>
                                </form>
                            @endif
                            <a href="{{ route('admin.research-handoffs.add-research', $handoff) }}" class="coord-btn coord-btn-primary">Create Research Record</a>
                        @elseif($handoff->research)
                            <a href="{{ route('admin.coordinator.research-monitoring', ['search' => $handoff->research->title]) }}" class="coord-btn coord-btn-soft">View Summary</a>
                        @endif
                        </div>
                    </div>
                </article>
            @empty
                <div class="coord-empty">
                    <strong>No Dean submissions found.</strong>
                    <span>Forwarded research documents will appear here.</span>
                </div>
            @endforelse
        </div>
        <div class="coord-pagination">{{ $handoffs->links() }}</div>
    </div>
</div>
@endsection
