<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GuestOrderAccessMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public Tenant $tenant,
        public string $trackUrl,
        public string $lookupUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pedido '.$this->order->order_number.' — código de acompanhamento',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.orders.guest-access',
            with: [
                'order' => $this->order,
                'tenant' => $this->tenant,
                'trackUrl' => $this->trackUrl,
                'lookupUrl' => $this->lookupUrl,
            ],
        );
    }
}
