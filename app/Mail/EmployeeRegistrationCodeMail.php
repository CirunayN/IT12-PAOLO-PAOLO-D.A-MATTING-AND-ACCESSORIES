<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmployeeRegistrationCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $code,
        public string $employeeName
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Paolo Paolo Employee Registration Confirmation Code',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.employee-registration-code',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
