<?php

namespace App\Mail;

use App\Enums\Queue;
use App\Models\User;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class WelcomeEmail extends AppMailable
{
    /**
     * Create a new message instance.
     */
    public function __construct(public readonly User $merchant)
    {
        $this->onQueue(Queue::DEFAULT->value);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to Build a Gang Sheet – Get Started Now!',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mails.welcome',
        );
    }
}
