<?php

namespace App\Mail;

use App\Enums\Queue;
use App\Models\User;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ChargeUpEmail extends AppMailable
{
    /**
     * Create a new message instance.
     */
    public function __construct(private $user, private $amount)
    {
        $this->onQueue(Queue::DEFAULT ->value);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Credit Charged Up Successfully!',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mails.charge-up',
            with: [
                'user' => $this->user,
                'amount' => $this->amount,
            ]
        );
    }
}
