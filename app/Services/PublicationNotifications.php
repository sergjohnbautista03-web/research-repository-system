<?php

namespace App\Services;

use App\Models\Research;
use App\Models\ResearchHandoff;
use App\Models\User;
use App\Notifications\JournalBatchPublished;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class PublicationNotifications
{
    public function sendForBatch(ResearchHandoff $batch): void
    {
        abort_unless($batch->is_batch && $batch->batch_stage === Research::STATUS_APPROVED && $batch->batch_published_at, 409);
        $publishedAt = $batch->batch_published_at;
        $notification = new JournalBatchPublished([
            'batch_id' => $batch->id,
            'title' => $batch->title,
            'department' => $batch->department,
            'published_at' => $publishedAt->toIso8601String(),
            'message' => 'Published: '.$batch->title.' — '.$batch->department.'. Published on '
                .$publishedAt->copy()->timezone('Asia/Manila')->format('F j, Y').'.',
            'url' => route('research.journal-batch', $batch),
        ]);

        User::publicationRecipients()->where('department', $batch->department)
            ->chunkById(200, function ($recipients) use ($batch, $notification, $publishedAt) {
                $rows = $recipients->map(fn (User $recipient) => [
                    // A stable primary key makes retries/concurrent sends safe for each publication and recipient.
                    'id' => Uuid::uuid5(Uuid::NAMESPACE_URL, 'ube:journal-batch:'.$batch->id.':'.$recipient->getMorphClass().':'.$recipient->id)->toString(),
                    'type' => JournalBatchPublished::class,
                    'notifiable_type' => $recipient->getMorphClass(),
                    'notifiable_id' => $recipient->id,
                    'data' => json_encode($notification->toDatabase($recipient), JSON_THROW_ON_ERROR),
                    'read_at' => null,
                    'created_at' => $publishedAt,
                    'updated_at' => $publishedAt,
                ])->all();

                DB::table('notifications')->insertOrIgnore($rows);
            });
    }
}
