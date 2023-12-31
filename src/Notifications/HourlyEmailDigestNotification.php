<?php

namespace OpenDominion\Notifications;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use OpenDominion\Helpers\NotificationHelper;
use OpenDominion\Models\Dominion;

class HourlyEmailDigestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /** @var NotificationHelper */
    protected $notificationHelper;

    /** @var array */
    protected $notifications;

    /** @var Carbon */
    protected $now;

    /**
     * HourlyEmailDigestNotification constructor.
     *
     * @param array $notifications
     */
    public function __construct(array $notifications)
    {
        $this->notificationHelper = app(NotificationHelper::class);
        $this->notifications = $notifications;
        $this->now = now();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param Dominion $dominion
     * @return array
     */
    public function via(Dominion $dominion): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param Dominion $dominion
     * @return MailMessage
     */
    public function toMail(Dominion $dominion): MailMessage
    {
        $mailMessage = (new MailMessage)
            ->replyTo('info@odarena.com', 'ODARENA')
            ->subject($this->getSubject())
            ->greeting('Hourly Report for ' . $this->now->format('D, M j, Y H:00'))
            ->line('Hi, ' . $dominion->user->display_name . '!')
            ->line('The following hourly events just occurred in your dominion *' . $dominion->name . '*:');

        foreach ($this->notifications as $notification) {
            $mailMessage = $mailMessage->line('- ' . $this->notificationHelper->getNotificationMessage(
                    $notification['category'],
                    $notification['type'],
                    $notification['data'],
                    $dominion
                ));
        }

        $mailMessage = $mailMessage->line('You are receiving this email because you have turned on email notifications for one or more of the above events.')
            ->line('To unsubscribe, please update your notification settings at: ' . route('settings'))
            ->salutation('-ODARENA');

        return $mailMessage;
    }

    // todo: move to parent abstract class
    protected function getSubject(): string
    {
        $subjectParts[] = '[OD]';

        $amountNotifications = count($this->notifications);
        if ($amountNotifications > 1) {
            $subjectParts[] = ('(+' . ($amountNotifications - 1) . ')');
        }

        $firstNotification = array_first($this->notifications);

        $subjectParts[] = $this->notificationHelper->getNotificationMessage(
            $firstNotification['category'],
            $firstNotification['type'],
            $firstNotification['data'],
            null
        );

        return implode(' ', $subjectParts);
    }
}
