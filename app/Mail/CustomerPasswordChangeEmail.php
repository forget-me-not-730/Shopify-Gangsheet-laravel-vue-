<?php

namespace App\Mail;

use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CustomerPasswordChangeEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $customer;
    public $password;
    public $isNewAccount;
    
    /**
     * Create a new message instance.
     */
    public function __construct(int $customerId, string $password, bool $isNewAccount = false)
    {
        $this->customer = Customer::findOrFail($customerId);
        $this->password = $password;
        $this->isNewAccount = $isNewAccount;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->isNewAccount ? 'Welcome - New Account Details' : 'Your Password Has Been Updated',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mails.customer_password_change',
            with: [
                'customer' => $this->customer,
                'password' => $this->password,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}