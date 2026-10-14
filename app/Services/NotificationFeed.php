<?php

namespace App\Services;

use App\Models\NotificationRead;
use App\Models\Research;
use App\Models\ResearchHandoff;
use App\Models\User;
use App\Notifications\AcademicPeriodActivated;
use App\Notifications\JournalBatchPublished;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class NotificationFeed
{
    public function events(User $user): Collection
    {
        abort_unless($user->is_active, 403);
        if (! $user->isAdmin()) {
            abort_unless(User::publicationRecipients()->whereKey($user->id)->exists(), 403);

            return $user->notifications()->where('type', JournalBatchPublished::class)->get()
                ->filter(fn ($notification) => ($notification->data['department'] ?? null) === $user->department)
                ->map(fn ($notification) => [
                    'id' => 'publication:'.$notification->id,
                    'message' => $notification->data['message'],
                    'title' => $notification->data['title'],
                    'department' => $notification->data['department'],
                    'published_at' => $notification->data['published_at'],
                    'date' => $notification->data['published_at'],
                    'url' => $notification->data['url'],
                    'read_at' => $notification->read_at?->toIso8601String(),
                ])->values();
        }
        if ($user->isDepartmentDean()) {
            abort_unless(filled($user->department), 403);
            return $this->deanEvents($user)->concat($this->batchEvents($user));
        }
        if ($user->isResearchCoordinator()) {
            abort_unless(filled($user->department), 403);
            return $this->coordinatorEvents($user)->concat($this->batchEvents($user));
        }

        return Research::pending()->forAdmin()->whereNotNull('coordinator_id')
            ->whereDoesntHave('handoff', fn ($task) => $task->where('is_batch', true))
            ->get(['id', 'title', 'school_year', 'updated_at'])->map(fn ($research) => [
            'id' => 'review:' . $research->id . ':' . $research->updated_at->toIso8601String(),
            'message' => 'Ready to publish: ‘' . $research->title . '’ (AY ' . $research->school_year . '). The Department Dean has forwarded this journal.',
            'date' => $research->updated_at->toIso8601String(),
            'url' => route('admin.research.show', $research),
        ])->concat($this->batchEvents($user));
    }

    private function batchEvents(User $actor): Collection
    {
        $query = ResearchHandoff::where('is_batch', true)->withCount('journals');
        if ($actor->isGlobalAdmin()) {
            $query->where('batch_stage', Research::STATUS_PENDING)->whereNotNull('batch_forwarded_at');
        } elseif ($actor->isDepartmentDean()) {
            $query->where('department', $actor->department)->where('dean_id', $actor->id)
                ->whereIn('batch_stage', [Research::STATUS_DEAN_REVIEW, Research::STATUS_APPROVED]);
        } else {
            $query->where('department', $actor->department)->where('coordinator_id', $actor->id)
                ->whereIn('batch_stage', [Research::STATUS_REJECTED, Research::STATUS_APPROVED]);
        }
        return $query->get()->map(function ($batch) {
            $date = match ($batch->batch_stage) {
                Research::STATUS_DEAN_REVIEW => $batch->batch_submitted_at,
                Research::STATUS_PENDING => $batch->batch_forwarded_at,
                Research::STATUS_APPROVED => $batch->batch_published_at,
                default => $batch->updated_at,
            } ?? $batch->updated_at;
            return ['id' => 'journal-batch:'.$batch->id.':'.$batch->batch_stage.':'.$date->toIso8601String(),
                'message' => $batch->batchLabel().': '.$batch->title.' (AY '.$batch->school_year.', '.$batch->journals_count.' journals).'
                    .($batch->batch_stage === Research::STATUS_REJECTED ? ' '.$batch->batch_remarks : ''),
                'date' => $date->toIso8601String(), 'url' => route('admin.journal-batches.show', $batch)];
        });
    }

    private function deanEvents(User $dean): Collection
    {
        $receipts = ResearchHandoff::where('department', $dean->department)->where('dean_id', $dean->id)
            ->whereNotNull('received_at')->get(['id', 'title', 'received_at'])->map(fn ($handoff) => [
                'id' => 'received:' . $handoff->id,
                'message' => 'The Research Coordinator has received your journal task: ‘' . $handoff->title . '’.',
                'date' => $handoff->received_at->toIso8601String(),
                'url' => route('admin.research-handoffs', ['search' => $handoff->title]),
            ]);
        $publications = Research::where('department', $dean->department)->whereNotNull('approved_at')
            ->whereDoesntHave('handoff', fn ($task) => $task->where('is_batch', true))
            ->get(['id', 'title', 'status', 'approved_at'])->map(fn ($research) => [
                'id' => 'published:' . $research->id . ':' . $research->approved_at->toIso8601String(),
                'message' => 'Published: ‘' . $research->title . '’ is now available in the UBE Research Repository.',
                'date' => $research->approved_at->toIso8601String(),
                'url' => $research->status === Research::STATUS_APPROVED
                    ? route('research.show', $research)
                    : route('admin.research-handoffs', ['search' => $research->title]),
            ]);
        $academicPeriods = $dean->notifications()->where('type', AcademicPeriodActivated::class)
            ->get()->map(fn ($notification) => [
                'id' => 'academic-period:' . $notification->id,
                'message' => $notification->data['message'],
                'date' => $notification->data['activated_at'],
                'url' => $notification->data['url'],
                'read_at' => $notification->read_at?->toIso8601String(),
            ]);

        $submissions = Research::where('department', $dean->department)
            ->whereHas('handoff', fn ($task) => $task->where('dean_id', $dean->id)->where('is_batch', false))
            ->where('status', Research::STATUS_DEAN_REVIEW)->get()->map(fn ($research) => [
                'id' => 'dean-review:' . $research->id . ':' . $research->submitted_to_dean_at?->toIso8601String(),
                'message' => 'Journal submitted for your review: ‘' . $research->title . '’ (AY ' . $research->school_year . ').',
                'date' => ($research->submitted_to_dean_at ?? $research->updated_at)->toIso8601String(),
                'url' => route('admin.research.show', $research),
            ]);
        return $receipts->concat($publications)->concat($academicPeriods)->concat($submissions);
    }

    private function coordinatorEvents(User $coordinator): Collection
    {
        $handoffs = ResearchHandoff::where('department', $coordinator->department)
            ->where('coordinator_id', $coordinator->id)
            ->where('status', ResearchHandoff::STATUS_PENDING)->get(['id', 'title', 'school_year', 'created_at'])->map(fn ($handoff) => [
                'id' => 'handoff:' . $handoff->id,
                'message' => 'New journal task from Dean: ‘' . $handoff->title . '’ (AY ' . $handoff->school_year . ').',
                'date' => $handoff->created_at->toIso8601String(),
                'url' => route('admin.coordinator.dean-submissions'),
            ]);
        $reviews = Research::where('department', $coordinator->department)
            ->where('coordinator_id', $coordinator->id)
            ->whereDoesntHave('handoff', fn ($task) => $task->where('is_batch', true))
            ->whereIn('status', [Research::STATUS_REJECTED, Research::STATUS_APPROVED])
            ->get(['id', 'title', 'status', 'rejection_reason', 'approved_at', 'updated_at'])
            ->map(function ($research) {
                $approved = $research->status === Research::STATUS_APPROVED;
                $date = $approved ? ($research->approved_at ?? $research->updated_at) : $research->updated_at;
                return [
                    'id' => 'coordinator-' . $research->status . ':' . $research->id . ':' . $date->toIso8601String(),
                    'message' => $approved
                        ? 'Published: ‘' . $research->title . '’ is available in the UBE Research Repository.'
                        : 'Returned by Dean for correction: ‘' . $research->title . '’. ' . ($research->rejection_reason ?: 'Review the Dean’s remarks.'),
                    'date' => $date->toIso8601String(),
                    'url' => $approved ? route('admin.coordinator.archive') : route('admin.coordinator.reports', ['search' => $research->title]),
                ];
            });

        return $handoffs->concat($reviews);
    }

    public function snapshot(User $user, ?Collection $events = null, int $page = 1): array
    {
        $events ??= $this->events($user);
        $readTimes = NotificationRead::where('user_id', $user->id)->pluck('read_at', 'notification_key');
        $items = $events->map(function (array $event) use ($readTimes) {
            $event['read_at'] = $event['read_at'] ?? $readTimes->get($event['id'])?->toIso8601String();
            $event['is_read'] = $event['read_at'] !== null;
            return $event;
        })->sortByDesc('date')->values();
        $page = max(1, $page);

        return [
            'notifications' => $items->forPage($page, 50)->values()->all(),
            'unread_count' => $items->where('is_read', false)->count(),
            'total_count' => $items->count(),
            'page' => $page,
            'has_more' => $items->count() > $page * 50,
        ];
    }

    public function markRead(User $user, Collection $events): void
    {
        DB::transaction(function () use ($user, $events) {
            $readAt = now();
            // Unique keys and insertOrIgnore make repeats safe without changing the original read time.
            foreach ($events->chunk(200) as $chunk) {
                NotificationRead::insertOrIgnore($chunk->map(fn ($event) => [
                    'user_id' => $user->id, 'notification_key' => $event['id'], 'read_at' => $readAt,
                ])->all());
            }
            $academicIds = $events->pluck('id')->filter(fn ($id) => str_starts_with($id, 'academic-period:'))
                ->map(fn ($id) => substr($id, strlen('academic-period:')));
            if ($academicIds->isNotEmpty()) {
                $user->notifications()->where('type', AcademicPeriodActivated::class)
                    ->whereIn('id', $academicIds)->whereNull('read_at')->update(['read_at' => $readAt]);
            }
            $publicationIds = $events->pluck('id')->filter(fn ($id) => str_starts_with($id, 'publication:'))
                ->map(fn ($id) => substr($id, strlen('publication:')));
            if ($publicationIds->isNotEmpty()) {
                $user->notifications()->where('type', JournalBatchPublished::class)
                    ->whereIn('id', $publicationIds)->whereNull('read_at')->update(['read_at' => $readAt]);
            }
        });
    }
}
