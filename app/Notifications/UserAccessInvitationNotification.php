<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;

class UserAccessInvitationNotification extends Notification
{
    use Queueable;

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $token = Password::broker()->createToken($notifiable);
        $invitationUrl = URL::temporarySignedRoute(
            'users.invitation.complete',
            now()->addMinutes(config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60)),
            [
                'user' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
                'token' => $token,
            ],
        );

        return (new MailMessage)
            ->subject('Seu acesso DPC está pronto')
            ->view('emails.user-access-invitation', [
                'name' => $notifiable->name,
                'invitationUrl' => $invitationUrl,
            ]);
    }
}
