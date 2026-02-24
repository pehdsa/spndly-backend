<?php

namespace App\Notifications;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Invitation $invitation) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $registerUrl = config('app.frontend_url').'/register?token='.$this->invitation->token;

        return (new MailMessage)
            ->subject('You have been invited')
            ->greeting('Hello!')
            ->line('You have been invited to join '.config('app.name').'.')
            ->line('This invitation will expire in 48 hours.')
            ->action('Accept Invitation', $registerUrl)
            ->line('If you did not expect this invitation, you may ignore this email.');
    }
}
