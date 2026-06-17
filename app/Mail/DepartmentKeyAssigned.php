<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DepartmentKeyAssigned extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $dean,
        public string $department,
        public string $semester,
        public string $schoolYear,
        public string $accessKey,
        public string $expiresAt,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Department Access Key for ' . $this->department,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.department-key-assigned',
            with: [
                'dean'       => $this->dean,
                'department' => $this->department,
                'semester'   => $this->semester,
                'schoolYear' => $this->schoolYear,
                'accessKey'  => $this->accessKey,
                'expiresAt'  => $this->expiresAt,
            ],
        );
    }
}
