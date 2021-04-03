<?php

namespace OpenDominion\Notifications\User;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use OpenDominion\Models\User;

class RegisteredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Get the notification's delivery channels.
     *
     * @param User $user
     * @return array
     */
    public function via(User $user): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param User $user
     * @return MailMessage
     */
    public function toMail(User $user): MailMessage
    {
        return (new MailMessage)
            ->replyTo('info@odarena.com', 'ODARENA')
            ->subject('ODARENA Registration')
            ->greeting('ODARENA Registration')
            ->line('Welcome to ODARENA, ' . $user->display_name, '!')
            ->line('Please click the activation link below to activate your account.')
            ->action('Activate your account', route('auth.activate', $user->activation_code))
            ->line('You can find ODARENA at: ' . route('home'))
            ->line('Thank you for playing, and have fun!')
            ->salutation('-ODARENA');
    }
}
