<?php

namespace App\Services;

use App\Models\Research;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class JournalWorkflow
{
    public function transition(Research $research, User $actor, string $action, ?string $remarks = null): void
    {
        DB::transaction(function () use ($research, $actor, $action, $remarks) {
            $record = Research::whereKey($research->id)->lockForUpdate()->firstOrFail();
            $handoff = $record->handoff;
            abort_if($handoff?->is_batch, 409, 'Submit, forward, and publish this journal through its complete Academic Year batch.');
            if ($action === 'submit') {
                abort_unless($actor->isResearchCoordinator() && filled($actor->department)
                    && $record->department === $actor->department && $record->coordinator_id === $actor->id
                    && $handoff && $handoff->coordinator_id === $actor->id, 403);
                abort_unless(in_array($record->status, [Research::STATUS_DRAFT, Research::STATUS_REJECTED], true), 409);
                $this->ensureComplete($record);
                $changes = ['status' => Research::STATUS_DEAN_REVIEW, 'submitted_to_dean_at' => now(),
                    'rejection_reason' => null, 'dean_approved_by' => null, 'dean_approved_at' => null, 'forwarded_to_admin_at' => null];
            } elseif (in_array($action, ['return', 'approve', 'forward'], true)) {
                abort_unless($actor->isDepartmentDean() && filled($actor->department)
                    && $record->department === $actor->department && $handoff && $handoff->dean_id === $actor->id, 403);
                abort_unless($action === 'forward', 400, 'The Dean forwards completed journals directly to Admin.');
                abort_unless(in_array($record->status, [Research::STATUS_DEAN_REVIEW, Research::STATUS_DEAN_APPROVED], true), 409);
                $this->ensureComplete($record);
                $changes = ['status' => Research::STATUS_PENDING, 'forwarded_to_admin_at' => now()];
            } elseif ($action === 'publish') {
                abort_unless($actor->isGlobalAdmin(), 403);
                abort_unless($record->status === Research::STATUS_PENDING && $record->forwarded_to_admin_at, 409,
                    'Only journals forwarded by the Dean to Admin can be published.');
                $this->ensureComplete($record);
                $changes = ['status' => Research::STATUS_APPROVED, 'approved_by' => $actor->id, 'approved_at' => now()];
            } else {
                abort(400);
            }
            $record->update($changes);
            $research->setRawAttributes($record->getAttributes(), true);
        }, 3);
    }

    private function ensureComplete(Research $research): void
    {
        $handoff = $research->handoff;
        $year = (string) $research->school_year;
        if (! preg_match('/^(\d{4})-(\d{4})$/', $year, $parts) || (int) $parts[2] !== (int) $parts[1] + 1
            || ! $handoff || $handoff->school_year !== $year || $handoff->research_id !== $research->id
            || $handoff->department !== $research->department) {
            throw ValidationException::withMessages(['school_year' => 'The journal must use the Academic Year assigned by the Dean.']);
        }
        if (blank($research->title) || blank($research->abstract) || $research->authorNames() === []
            || blank($research->type) || blank($research->submission_category)) {
            throw ValidationException::withMessages(['journal' => 'Complete the journal title, authors, type, category, and abstract before submitting.']);
        }
        if (! $research->file_path || ! Storage::disk('public')->exists($research->file_path)) {
            throw ValidationException::withMessages(['file' => 'Upload the completed journal PDF before submitting.']);
        }
    }
}
