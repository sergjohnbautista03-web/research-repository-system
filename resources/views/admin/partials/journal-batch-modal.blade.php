@once
@push('styles')
<link rel="stylesheet" href="{{ asset('css/journal-batches.css') }}?v={{ filemtime(public_path('css/journal-batches.css')) }}">
@endpush
<dialog id="journalBatchModal" class="jbm-dialog" aria-labelledby="journalBatchModalTitle">
    <header class="jbm-header">
        <h2 id="journalBatchModalTitle">Journal Batch</h2>
        <button type="button" class="jbm-close" data-close-journal-batch aria-label="Close journal batch">&times;</button>
    </header>
    <p class="jbm-status" id="journalBatchModalStatus" role="status">Loading batch…</p>
    <iframe id="journalBatchModalFrame" class="jbm-frame" title="Journal batch details and PDF review" src="about:blank" hidden></iframe>
</dialog>
@push('scripts')
<script src="{{ asset('js/journal-batch-modal.js') }}?v={{ filemtime(public_path('js/journal-batch-modal.js')) }}" defer></script>
@endpush
@endonce
