<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class CustomerPasswordResetNotification extends ResetPassword
{
    protected function resetUrl($notifiable)
    {
        return url(route('builder.reset-password', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]));
    }
}