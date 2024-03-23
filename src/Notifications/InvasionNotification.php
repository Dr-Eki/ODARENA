<?php

namespace OpenDominion\Notifications;

use Illuminate\Bus\Queueable;
#use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class InvasionNotification extends Notification
{
    use Queueable;

    private $invasionEvent;
    private $extraData;

    public function __construct($invasionEvent, $extraData)
    {
        $this->invasionEvent = $invasionEvent;
        $this->extraData = $extraData;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via($notifiable)
    {
        return [WebPushChannel::class];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->line('The introduction to the notification.')
                    ->action('Notification Action', url('/'))
                    ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }

    public function toWebPush($notifiable, $notification)
    {
        return (new WebPushMessage)
            ->title($this->extraData['title'] ?? 'Invasion!')
            ->icon('/assets/app/images/odarena-icon.png')
            ->body($this->extraData['body'] ?? 'You have been invaded!')
            ->data(['url' => route('dominion.status')])
            ->action('Check Status', 'view_app')
            ->options(['TTL' => 1000]);
    }
}
