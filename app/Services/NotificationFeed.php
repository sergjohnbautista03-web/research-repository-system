<?php

namespace App\Services;

use App\Models\NotificationRead;
use App\Models\Research;
use App\Models\ResearchHandoff;
use App\Models\User;
use App\Notifications\AcademicPeriodActivated;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class NotificationFeed
{
    public function events(User $user): Collection
    {
        abort_unless($user->isAdmin(), 403);
        if ($user->isDepartmentDean()) {
            abort_unless(filled($user->department), 403);
            return $this->deanEvents($user);
        }
        if ($user->isResearchCoordinator()) {
            abort_unless(filled($user->department), 403);
            return $this->coordinatorEvents($user);
        }

        return Research::pending()->whereNotNull('coordinator_id')->get(['id', 'title', 'updated_at'])->map(fn ($research) => [
            'id' => 'review:' . $research->id . ':' . $research->updated_at->toIso8601String(),
            'message' => 'Ready for final review: ‘' . $research->title . '’. Research submitted by the Research Coordinator is ready for final checking.',
            'date' => $research->updated_at->toIso8601String(),
            'url' => route('admin.research.show', $research),
        ]);
    }

    private function deanEvents(User $dean): Collection
    {
        $receipts = ResearchHandoff::where('department', $dean->department)->where('dean_id', $dean->id)
            ->whereNotNull('received_at')->get(['id', 'title', 'received_at'])->map(fn ($handoff) => [
                'id' => 'received:' . $handoff->id,
                'message' => 'The Research Coordinator has received and reviewed your submitted research titled ‘' . $handoff->title . '’.',
                'date' => $handoff->received_at->toIso8601String(),
                'url' => route('admin.research-handoffs', ['search' => $handoff->title]),
            ]);
        $publications = Research::where('department', $dean->department)->whereNotNull('approved_at')
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

        return $receipts->concat($publications)->concat($academicPeriods);
    }

    private function coordinatorEvents(User $coordinator): Collection
    {
        $handoffs = ResearchHandoff::where('department', $coordinator->department)
            ->where('status', ResearchHandoff::STATUS_PENDING)->get(['id', 'title', 'created_at'])->map(fn ($handoff) => [
                'id' => 'handoff:' . $handoff->id,
                'message' => 'New Dean submission: ‘' . $handoff->title . '’ is ready for receipt.',
                'date' => $handoff->created_at->toIso8601String(),
                'url' => route('admin.coordinator.dean-submissions'),
            ]);
        $reviews = Research::where('department', $coordinator->department)
            ->whereIn('status', [Research::STATUS_REJECTED, Research::STATUS_APPROVED])
            ->get(['id', 'title', 'status', 'rejection_reason', 'approved_at', 'updated_at'])
            ->map(function ($research) {
                $approved = $research->status === Research::STATUS_APPROVED;
                $date = $approved ? ($research->approved_at ?? $research->updated_at) : $research->updated_at;
                return [
                    'id' => 'coordinator-' . $research->status . ':' . $research->id . ':' . $date->toIso8601String(),
                    'message' => $approved
                        ? 'Approved: ‘' . $research->title . '’ has been accepted by Admin.'
                        : 'Returned for revision: ‘' . $research->title . '’. ' . ($research->rejection_reason ?: 'Review the administrator’s remarks.'),
                    'date' => $date->toIso8601String(),
                    'url' => $approved ? route('admin.coordinator.archive') : route('admin.coordinator.returned'),
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
        });
    }
}
