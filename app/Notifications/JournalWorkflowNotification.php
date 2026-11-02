<?php

namespace App\Notifications;

use App\Models\ResearchHandoff;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class JournalWorkflowNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $timeout = 30;

    public function __construct(public array $details)
    {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function backoff(): array
    {
        return [60, 300, 900, 1800];
    }

    public function shouldSend(object $notifiable, string $channel): bool
    {
        // Recheck access at delivery: queued recipients may have been deactivated or reassigned.
        $batch = ResearchHandoff::find($this->details['batch_id']);

        return $batch && self::canReceive($notifiable, $batch, $this->details['event']);
    }

    public static function canReceive(User $user, ResearchHandoff $batch, string $event): bool
    {
        if (! $user->is_active || ! filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        return match ($event) {
            'assigned' => $user->isResearchCoordinator() && $user->id === $batch->coordinator_id
                && $user->department === $batch->department,
            'submitted' => $user->isDepartmentDean() && $user->id === $batch->dean_id
                && $user->department === $batch->department,
            'forwarded' => $user->isGlobalAdmin(),
            default => false,
        };
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mailer = config('mail.default');

        return (new MailMessage)
            // A log fallback must not consume a failed SMTP delivery; let the queue retry it.
            ->mailer($mailer === 'failover' ? 'smtp' : $mailer)
            ->subject($this->details['heading'].' - AY '.$this->details['school_year'])
            ->view(['html' => 'emails.journal-workflow', 'text' => 'emails.journal-workflow-text'], [
                'recipientName' => $notifiable->name,
                'details' => $this->details,
            ]);
    }
}
