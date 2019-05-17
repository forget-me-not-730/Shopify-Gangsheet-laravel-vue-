<?php

namespace App\Mail;

use App\Enums\Queue;
use App\Models\Order;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class MerchantNewOrderEmail extends AppMailable
{

    /**
     * Create a new message instance.
     */
    public function __construct(private $order_id)
    {
        $this->onQueue(Queue::DEFAULT->value);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Order on Build A Gang Sheet',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $order = Order::with(['user', 'product', 'designs'])->findOrFail($this->order_id);

        return new Content(
            view: 'mails.merchant_new_order',
            with: [
                'order' => $order
            ]
        );
    }
}
