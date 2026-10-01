<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class AcademicPeriodActivated extends Notification
{
    public function __construct(public array $period) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return $this->period;
    }
}
