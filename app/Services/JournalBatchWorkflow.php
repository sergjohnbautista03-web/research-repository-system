<?php

namespace App\Services;

use App\Models\Research;
use App\Models\ResearchHandoff;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class JournalBatchWorkflow
{
    public function authorize(ResearchHandoff $batch, User $actor): void
    {
        abort_unless($batch->is_batch, 404);
        if ($actor->isGlobalAdmin()) {
            abort_unless(in_array($batch->batch_stage, [Research::STATUS_PENDING, Research::STATUS_APPROVED], true)
                && $batch->batch_forwarded_at, 403);

            return;
        }
        abort_unless(filled($actor->department) && $actor->department === $batch->department
            && (($actor->isDepartmentDean() && $actor->id === $batch->dean_id)
                || ($actor->isResearchCoordinator() && $actor->id === $batch->coordinator_id)), 403);
    }

    public function ensureEditable(ResearchHandoff $batch, User $actor): void
    {
        $this->authorize($batch, $actor);
        abort_unless($actor->isResearchCoordinator()
            && in_array($batch->batch_stage, [Research::STATUS_DRAFT, Research::STATUS_REJECTED], true), 409,
            'Journals can be edited only before the batch is submitted to the Dean.');
    }

    public function transition(ResearchHandoff $batch, User $actor, string $action, ?string $remarks = null): void
    {
        DB::transaction(function () use ($batch, $actor, $action, $remarks) {
            $batch = ResearchHandoff::whereKey($batch->id)->lockForUpdate()->firstOrFail();
            $this->authorize($batch, $actor);
            $expected = ['forward' => [Research::STATUS_DEAN_REVIEW, Research::STATUS_DEAN_APPROVED],
                'publish' => [Research::STATUS_PENDING]];
            if ($action === 'submit') {
                $this->ensureEditable($batch, $actor);
            } else {
                abort_unless($action === 'publish' ? $actor->isGlobalAdmin() : $actor->isDepartmentDean(), 403);
                abort_unless(isset($expected[$action]), 400);
                abort_unless(in_array($batch->batch_stage, $expected[$action], true), 409, 'The batch has already changed stage.');
            }
            $journals = $batch->journals()->lockForUpdate()->get();
            $this->ensureComplete($batch, $journals);
            $changes = [];
            $journalChanges = [];
            switch ($action) {
                case 'submit':
                    $stage = Research::STATUS_DEAN_REVIEW;
                    $changes = ['batch_submitted_at' => now(), 'batch_remarks' => null, 'batch_dean_approved_by' => null,
                        'batch_dean_approved_at' => null, 'batch_forwarded_at' => null];
                    $journalChanges = ['submitted_to_dean_at' => now(), 'rejection_reason' => null,
                        'dean_approved_by' => null, 'dean_approved_at' => null, 'forwarded_to_admin_at' => null];
                    break;
                case 'forward':
                    $stage = Research::STATUS_PENDING;
                    $changes = ['batch_forwarded_at' => now()];
                    $journalChanges = ['forwarded_to_admin_at' => now()];
                    break;
                case 'publish':
                    $stage = Research::STATUS_APPROVED;
                    $changes = ['batch_published_by' => $actor->id, 'batch_published_at' => now()];
                    $journalChanges = ['approved_by' => $actor->id, 'approved_at' => now()];
                    break;
            }
            // Every member and the batch move together through forwarding and publication.
            foreach ($journals as $journal) {
                $journal->update(['status' => $stage] + $journalChanges);
            }
            $batch->update(['batch_stage' => $stage, 'status' => ResearchHandoff::STATUS_ADDED] + $changes);
            if ($action === 'publish') {
                // Publication and recipient notifications commit together, without a queue dependency.
                app(PublicationNotifications::class)->sendForBatch($batch);
            }
        }, 3);
    }

    private function ensureComplete(ResearchHandoff $batch, $journals): void
    {
        if ($journals->isEmpty()) {
            throw ValidationException::withMessages(['batch' => 'Upload at least one completed journal before submitting the batch.']);
        }
        $allowedStatuses = $batch->batch_stage === Research::STATUS_REJECTED
            ? [Research::STATUS_REJECTED, Research::STATUS_DRAFT] : [$batch->batch_stage];
        foreach ($journals as $journal) {
            if (! $journal->file_path || ! Storage::disk('public')->exists($journal->file_path)) {
                $message = in_array($batch->batch_stage, [Research::STATUS_DRAFT, Research::STATUS_REJECTED], true)
                    ? 'Upload the completed PDF for "'.$journal->title.'" through Edit Journal, then submit the batch again.'
                    : 'The completed PDF for "'.$journal->title.'" is missing. The batch cannot proceed until the PDF is restored.';
                throw ValidationException::withMessages(['batch' => $message]);
            }
            if (! in_array($journal->status, $allowedStatuses, true) || $journal->department !== $batch->department
                || $journal->school_year !== $batch->school_year || $journal->coordinator_id !== $batch->coordinator_id
                || ($batch->program && ($journal->program !== $batch->program || $journal->course !== $batch->program))
                || blank($journal->title) || blank($journal->abstract) || $journal->authorNames() === []
                || blank($journal->type) || blank($journal->submission_category)) {
                throw ValidationException::withMessages(['batch' => 'Every journal must have complete details and an uploaded journal PDF.']);
            }
        }
    }
}
