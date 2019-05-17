<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Mailer\Exception\TransportException;

class AppMailable extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    public function send($mailer)
    {
        try {
            return parent::send($mailer);
        } catch (\Exception $exception) {

            if ($exception instanceof TransportException) {
                return null;
            }

            report($exception);

            return null;
        }
    }
}
