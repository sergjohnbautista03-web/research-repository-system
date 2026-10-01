<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class DepartmentAccountCreated extends Mailable
{
    public function __construct(
        public User $account,
        public string $roleLabel,
        public string $initialPassword,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your UBE Repository account is ready');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.department-account-created');
    }
}
