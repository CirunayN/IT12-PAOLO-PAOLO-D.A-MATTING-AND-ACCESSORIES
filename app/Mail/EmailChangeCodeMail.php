<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailChangeCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $code,
        public string $accountName,
        public string $newEmail,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Paolo Paolo Email Change Verification Code',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.email-change-code',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
