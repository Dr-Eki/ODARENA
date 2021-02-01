<?php

namespace OpenDominion\Services;

use OpenDominion\Helpers\NotificationHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Notifications\HourlyEmailDigestNotification;
use OpenDominion\Notifications\IrregularDominionEmailNotification;
use OpenDominion\Notifications\WebNotification;

class NotificationService
{
    /** @var array */
    protected $notifications = [];

    /** @var NotificationHelper */
    protected $notificationHelper;

    /**
     * NotificationService constructor.
     */
    public function __construct()
    {
        $this->notificationHelper = app(NotificationHelper::class);
    }

    /**
     * Queues a notification, to be sent later with sendNotifications.
     *
     * @see sendNotifications
     *
     * @param string $type
     * @param array $data
     * @return NotificationService
     */
    public function queueNotification(string $type, array $data = []): self
    {
        $this->notifications[$type] = $data;

        return $this;
    }

    /**
     * Sends all queued notifications, added to the queue by queueNotification.
     *
     * @see queueNotification
     *
     * @param Dominion $dominion
     * @param string $category
     */
    public function sendNotifications(Dominion $dominion, string $category): void
    {

        if(!$dominion->isAbandoned())
        {

            $user = $dominion->user;

            $emailNotifications = [];
            $defaultSettings = $this->notificationHelper->getDefaultUserNotificationSettings();

            foreach ($this->notifications as $type => $data)
            {
                $ingameSetting = $user->getSetting("notifications.{$category}.{$type}.ingame");
                if ($ingameSetting === null) {
                    $ingameSetting = $defaultSettings[$category][$type]['ingame'];
                }
                if ($ingameSetting) {
                    $dominion->notify(new WebNotification($category, $type, $data));
                }

                $emailSetting = $user->getSetting("notifications.{$category}.{$type}.email");
                if ($emailSetting === null) {
                    $emailSetting = $defaultSettings[$category][$type]['email'];
                }
                if ($emailSetting) {
                    $emailNotifications[] = [
                        'category' => $category,
                        'type' => $type,
                        'data' => $data,
                    ];
                }
            }

            if (!empty($emailNotifications)) {
                switch ($category) {
                    case 'general':
                        throw new \LogicException('todo');
                    case 'hourly_dominion':
                        $dominion->notify(new HourlyEmailDigestNotification($emailNotifications));
                        break;

                    case 'irregular_dominion':
                        $dominion->notify(new IrregularDominionEmailNotification($emailNotifications));
                        break;

                }

            }

            $this->notifications = [];

        }

    }

//    public function addIrregularNotification(Dominion $dominion, string $notificationType, array $notificationData): void
//    {
//        // add notification to the db (notification_queue?)
//    }
//
//    public function processIrregularNotifications(): void
//    {
//        // ...
//    }
//
//    protected function sendIrregularNotification($notifiable /* ... */): void
//    {
//        // ...
//    }
}
