<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class AcademicPeriodActivatedMail extends Mailable
{
    public function __construct(public string $deanName, public array $period) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Active academic period: ' . $this->period['label']);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.academic-period-activated');
    }
}
