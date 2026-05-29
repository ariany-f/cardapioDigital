<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MarketingLeadMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public array $lead) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nova solicitação — '.$this->lead['restaurant_name'],
            replyTo: [$this->lead['email']],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.marketing.lead',
            with: ['lead' => $this->lead],
        );
    }
}
