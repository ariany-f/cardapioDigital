<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PlatformMailTestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $sentByName) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Teste SMTP — '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.platform.test',
            with: ['sentByName' => $this->sentByName],
        );
    }
}
