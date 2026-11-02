<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProfileAccountChangeVerificationCode extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $code,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Profile Change Verification Code - Ube Repository');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.profile-account-change-code',
            with: [
                'user' => $this->user,
                'code' => $this->code,
            ],
        );
    }
}
