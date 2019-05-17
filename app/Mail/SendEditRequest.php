<?php

namespace App\Mail;

use App\Enums\Queue;
use App\Models\Design;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class SendEditRequest extends AppMailable
{
    /**
     * Create a new message instance.
     */
    public function __construct(private $design_id)
    {
        $this->afterCommit();
        $this->onQueue(Queue::DEFAULT->value);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Send Edit Request',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $design = Design::withTrashed()->findOrFail($this->design_id);

        return new Content(
            view: 'mails.send-edit-request',
            with: [
                'design' => $design
            ]
        );
    }
}
