<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class JournalBatchPublished extends Notification
{
    public function __construct(public array $publication) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return $this->publication;
    }
}
