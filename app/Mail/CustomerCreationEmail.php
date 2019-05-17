<?php

namespace App\Mail;

use App\Enums\Queue;
use App\Models\Customer;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class CustomerCreationEmail extends AppMailable
{

    /**
     * Create a new message instance.
     */
    public function __construct(private $customer_id, private $is_new_customer, private $pwd_string)
    {
        $this->onQueue(Queue::DEFAULT->value);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your customer account has been created.',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $customer = Customer::findOrFail($this->customer_id);

        return new Content(
            view: 'mails.customer_new_creation',
            with: [
                'customer' => $customer,
                'is_new_customer' => $this->is_new_customer,
                'pwd_string' => $this->pwd_string
            ]
        );
    }
}
