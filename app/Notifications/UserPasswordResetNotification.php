<?php

namespace App\Notifications;

use Filament\Facades\Filament;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserPasswordResetNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $token,
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Redefina sua senha - DPC')
            ->view('emails.user-password-reset', [
                'name' => $notifiable->name,
                'resetUrl' => Filament::getResetPasswordUrl($this->token, $notifiable),
            ]);
    }
}
