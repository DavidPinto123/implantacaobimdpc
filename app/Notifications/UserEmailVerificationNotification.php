<?php

namespace App\Notifications;

use Filament\Facades\Filament;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserEmailVerificationNotification extends Notification
{
    use Queueable;

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Verifique seu e-mail - DPC')
            ->view('emails.user-email-verification', [
                'name' => $notifiable->name,
                'verificationUrl' => Filament::getVerifyEmailUrl($notifiable),
            ]);
    }
}
