<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public string $statusLabel,
        public ?string $restaurantName = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pedido '.$this->order->order_number.' — '.$this->statusLabel,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.orders.status-updated',
            with: [
                'order' => $this->order,
                'statusLabel' => $this->statusLabel,
                'restaurantName' => $this->restaurantName,
                'statusIntro' => \App\Support\PlatformCommunicationDisclaimer::emailStatusIntro($this->restaurantName),
            ],
        );
    }
}
