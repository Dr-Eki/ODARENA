<?php

namespace OpenDominion\Http\Controllers;

use Auth;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Image;
use OpenDominion\Helpers\NotificationHelper;
use OpenDominion\Helpers\WorldNewsHelper;
use OpenDominion\Helpers\SettingHelper;
use OpenDominion\Models\User;
use RuntimeException;
use Storage;
use Throwable;

use Carbon;

class SettingsController extends AbstractController
{
    public function getIndex()
    {
        /** @var User $user */
        $user = Auth::user();

        /** @var NotificationHelper $notificationHelper */
        $notificationHelper = app(NotificationHelper::class);
        $settingHelper = app(SettingHelper::class);
        $worldNewsHelper = app(WorldNewsHelper::class);

        $notificationSettings = $user->settings['notifications'] ?? $notificationHelper->getDefaultUserNotificationSettings();
        $worldNewsSettings = $user->settings['world_news'] ?? $worldNewsHelper->getDefaultUserWorldNewsSettings();

        $worldNewsEventKeys = [];
        foreach($worldNewsHelper->getWorldNewsEventKeyDescriptions() as $eventKey => $eventDescription)
        {
            $worldNewsEventKeys[] = $eventKey;
        }

        return view('pages.settings', [
            'notificationHelper' => $notificationHelper,
            'notificationSettings' => $notificationSettings,
            'settingHelper' => $settingHelper,
            'worldNewsHelper' => app(WorldNewsHelper::class),
            'worldNewsEventKeys' => $worldNewsEventKeys,
            'worldNewsSettings' => $worldNewsSettings,
        ]);
    }

    public function postIndex(Request $request)
    {
        if ($newAvatar = $request->file('account_avatar')) {
            try {
                $this->handleAvatarUpload($newAvatar);

            } catch (Throwable $e) {
                $request->session()->flash('alert-danger', $e->getMessage());
                return redirect()->back();
            }
        }

        $this->updateUser($request->input());
        $this->updateNotifications($request->input());
        $this->updateSettings($request->input());
        $this->updateNotificationSettings($request->input());
        $this->updateWorldNewsSettings($request->input());

        $request->session()->flash('alert-success', 'Your settings have been updated.');
        return redirect()->route('settings');
    }

    protected function handleAvatarUpload(UploadedFile $file)
    {
        /** @var User $user */
        $user = Auth::user();

        // Convert image
        $image = Image::make($file)
            ->fit(200, 200)
            ->encode('png');

        $data = (string)$image;
        $path = 'uploads/avatars';
        $fileName = (str_slug($user->display_name) . '.png');

        if (!Storage::disk('public')->put(($path . '/' . $fileName), $data)) {
            throw new RuntimeException('Failed to upload avatar');
        }

        $user->avatar = $fileName;
        $user->save();
    }

    protected function updateUser(array $data)
    {
        if (!isset($data['skin']) || empty($data['skin'])) {
            return;
        }

        /** @var User $user */
        $user = Auth::user();

        if ($user->skin == $data['skin'] || !in_array($data['skin'], ['skin-red', 'skin-dark-red'])) {
            return;
        }

        $user->skin = $data['skin'];
        $user->save();
    }

    protected function updateNotifications(array $data)
    {
        if (!isset($data['notifications']) || empty($data['notifications'])) {
            return;
        }

        /** @var User $user */
        $user = Auth::user();

        /** @var NotificationHelper $notificationHelper */
        $notificationHelper = app(NotificationHelper::class);
        $notificationCategories = $notificationHelper->getNotificationCategories();

        $notificationKeys = [];
        $enabledNotificationKeys = [];
        $newNotifications = [];

        // Get list of all ingame notifications (for default values)
        foreach ($notificationCategories as $key => $types) {
            foreach ($types as $type => $channels) {
                $notificationKeys["{$key}.{$type}.ingame"] = false;
            }
        }

        // Set checked boxes to true
        foreach ($data['notifications'] as $key => $types) {
            foreach ($types as $type => $channels) {
                foreach ($channels as $channel => $enabled) {
                    if ($enabled === 'on') {
                        $enabledNotificationKeys["{$key}.{$type}.{$channel}"] = true;
                        array_set($newNotifications, "{$key}.{$type}.{$channel}", true);
                    }
                }
            }
        }

        // Set other types to false
        foreach ($notificationKeys as $key => $value) {
            if (!isset($enabledNotificationKeys[$key])) {
                array_set($newNotifications, $key, false);
            }
        }

        $settings = ($user->settings ?? []);
        $settings['notifications'] = $newNotifications;

        $user->settings = $settings;
        $user->save();
    }

    protected function updateSettings(array $data)
    {
        if (!isset($data['settings']) || empty($data['settings'])) {
            return;
        }

        $user = Auth::user();

        $settingHelper = app(SettingHelper::class);
        $settingCategories = $settingHelper->getSettingCategories();

        $settingKeys = [];
        $enabledSettingKeys = [];
        $newNotifications = [];

        // Get list of all ingame notifications (for default values)
        foreach ($settingCategories as $key => $types)
        {
            foreach ($types as $type => $channels)
            {
                $notificationKeys["{$key}.{$type}.ingame"] = false;
            }
        }

        // Set checked boxes to true
        foreach ($data['settings'] as $key => $types)
        {
            foreach ($types as $type => $channels)
            {
                foreach ($channels as $channel => $enabled)
                {
                    if ($enabled === 'on')
                    {
                        $enabledNotificationKeys["{$key}.{$type}.{$channel}"] = true;
                        array_set($newNotifications, "{$key}.{$type}.{$channel}", true);
                    }
                }
            }
        }

        // Set other types to false
        foreach ($notificationKeys as $key => $value)
        {
            if (!isset($enabledNotificationKeys[$key]))
            {
                array_set($newNotifications, $key, false);
            }
        }

        $settings = ($user->settings ?? []);
        $settings['settings'] = $newNotifications;

        $user->settings = $settings;
        $user->save();
    }

    protected function updateNotificationSettings(array $data)
    {
        /** @var User $user */
        $user = Auth::user();

        $settings = ($user->settings ?? []);

        if(!isset($data['notification_digest']))
        {
            $data['notification_digest'] = 'hourly';
        }

        $settings['notification_digest'] = $data['notification_digest'];


        $user->settings = $settings;
        $user->save();

    }

    protected function updateWorldNewsSettings(array $data)
    {
        if (!isset($data['world_news']) || empty($data['world_news'])) {
            return;
        }

        /** @var User $user */
        $user = Auth::user();

        /** @var NotificationHelper $notificationHelper */
        $worldNewsHelper = app(WorldNewsHelper::class);
        $eventScopes = ['own', 'other'];
 
        $defaultWorldEvents = $worldNewsHelper->getDefaultUserWorldNewsSettings();
        $newWorldNewsSettings = [];

        // Get list of all world events (for default values)

        foreach($defaultWorldEvents as $eventKey => $defaultValue)
        {
            if(isset($data['world_news'][$eventKey]))
            {
                $newWorldNewsSettings[$eventKey] = ($data['world_news'][$eventKey] == 'on' ? true : false);
            }
            else
            {
                $newWorldNewsSettings[$eventKey] = false;
            }
        }


        $settings = ($user->settings ?? []);
        $settings['world_news'] = $newWorldNewsSettings;

        $user->settings = $settings;
        $user->save();
    }
}
