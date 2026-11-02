<?php

namespace App\Services;

use App\Models\Research;
use App\Models\ResearchHandoff;
use App\Models\User;
use App\Notifications\JournalWorkflowNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class JournalWorkflowNotifications
{
    public function assigned(ResearchHandoff $batch): void
    {
        $this->queue($batch, 'assigned');
    }

    public function transitioned(ResearchHandoff $batch, string $action): void
    {
        if (in_array($action, ['submit', 'forward'], true)) {
            $this->queue($batch, $action === 'submit' ? 'submitted' : 'forwarded');
        }
    }

    private function queue(ResearchHandoff $batch, string $event): void
    {
        $recipients = User::query()->where('role', 'admin')->where('is_active', true);
        match ($event) {
            'assigned' => $recipients->whereKey($batch->coordinator_id),
            'submitted' => $recipients->whereKey($batch->dean_id),
            'forwarded' => $recipients->where('is_department_dean', false)->where('is_research_coordinator', false),
        };
        $recipients = $recipients->get()->filter(fn (User $user) => JournalWorkflowNotification::canReceive($user, $batch, $event));
        $batch->loadMissing(['dean', 'coordinator']);
        $stage = match ($event) {
            'assigned' => ['Journal preparation task assigned', 'Task Assigned', 'Date Assigned', $batch->created_at,
                'Your Dean has assigned a journal preparation task to you.', 'Open Assigned Task'],
            'submitted' => ['Completed journal batch awaiting review', 'Submitted to Dean for Review', 'Date Submitted', $batch->batch_submitted_at,
                'The Research Coordinator has submitted completed journals for your review and approval.', 'Review Journal Batch'],
            'forwarded' => ['Journal batch ready for publication', 'Forwarded by Dean to Admin', 'Date Forwarded', $batch->batch_forwarded_at,
                'The Dean has reviewed and forwarded a completed journal batch for publication.', 'Open Journal Batch'],
        };
        // Snapshot this event so later workflow changes cannot rewrite an email waiting in the queue.
        $details = [
            'event' => $event, 'batch_id' => $batch->id, 'heading' => $stage[0], 'status' => $stage[1],
            'date_label' => $stage[2], 'date' => $stage[3]->copy()->timezone('Asia/Manila')->format('F j, Y g:i A').' (Asia/Manila)',
            'message' => $stage[4], 'button' => $stage[5],
            'title' => $batch->title, 'school_year' => $batch->school_year,
            'category' => Research::adminSubmissionCategories()[$batch->submission_category] ?? 'Research Journal',
            'department' => $batch->department,
            'program' => $batch->program ?: 'All Programs',
            'dean' => $batch->dean?->name ?? 'Department Dean',
            'coordinator' => $batch->coordinator?->name ?? 'Research Coordinator',
            'assigned_at' => $batch->created_at->copy()->timezone('Asia/Manila')->format('F j, Y g:i A').' (Asia/Manila)',
            'deadline' => $batch->deadline?->format('F j, Y') ?? 'Not specified',
            'notes' => $batch->notes,
            'journals' => $event === 'assigned' ? [] : $batch->journals()->orderBy('id')->pluck('title')->all(),
            'url' => route('admin.journal-batches.show', $batch),
        ];

        DB::afterCommit(function () use ($recipients, $details) {
            if ($recipients->isEmpty()) {
                Log::warning('Journal workflow email has no eligible recipients.', [
                    'batch_id' => $details['batch_id'], 'event' => $details['event'],
                ]);
            }
            foreach ($recipients as $recipient) {
                try {
                    $recipient->notify(new JournalWorkflowNotification($details));
                } catch (\Throwable $exception) {
                    // A queue outage or a synchronous SMTP failure must not undo a saved workflow action.
                    report($exception);
                }
            }
        });
    }
}
