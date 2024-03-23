<?php

namespace OpenDominion\Http\Controllers;

use Auth;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Image;
use OpenDominion\Helpers\NotificationHelper;
use OpenDominion\Helpers\WorldNewsHelper;
use OpenDominion\Helpers\SettingHelper;
use OpenDominion\Models\Race;
use OpenDominion\Models\User;
use OpenDominion\Services\Dominion\OpenAIService;
use OpenDominion\Services\Dominion\StabilityAIService;
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
            ->fit(config('user.avatar.fit_x'), config('user.avatar.fit_y'))
            ->encode('png');

        $data = (string)$image;
        $path = 'uploads/avatars';
        $fileName = (Str::slug($user->display_name) . '.png');

        if (!Storage::disk('public')->put(($path . '/' . $fileName), $data)) {
            throw new RuntimeException('Failed to upload avatar');
        }

        $user->avatar = $fileName;
        $user->save();
    }

    protected function getDeleteAvatar(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->avatar) {
            Storage::disk('public')->delete('uploads/avatars/' . $user->avatar);
            $user->avatar = null;
            $user->save();
        }

        return redirect()->route('settings');
    }

    protected function getGenerateAvatar(Request $request)
    {
        $user = Auth::user();
        $dimensions = config('user.avatar.generate_x') . 'x' . config('user.avatar.generate_y');


        if(config('user.avatar.generator') == 'stability')
        {
            $stabilityAIService = app(StabilityAIService::class);
            $randomRace = Race::all()->whereIn('playable', [1,2])->random();
            $prompt = "Draw an avatar of a $randomRace warrior. There should be no text in the image.";
            $result = $stabilityAIService->generateImagesFromText($prompt);

            if(!isset($result['artifacts'][0]['base64']))
            {
                throw new RuntimeException('Failed to generate avatar');
            }

            $imageBase64 = $result['artifacts'][0]['base64'];

        }
        elseif(config('user.avatar.generator') == 'openai')
        {
            $openAiService = app(OpenAIService::class);
            $result = $openAiService->generateAvatar($user, 1, $dimensions, ['fantasy', 'warrior', 'wizard', 'hero', 'champion']);

            if(!isset($result['data'][0]['b64_json']))
            {
                throw new RuntimeException('Failed to generate avatar');
            }

            $imageBase64 = $result['data'][0]['b64_json'];
        }
        else
        {
            throw new RuntimeException('Invalid avatar generator');
        }

        // Convert $image into an image and save it
        $image = Image::make(base64_decode($imageBase64))
            ->fit(config('user.avatar.generate_x'), config('user.avatar.generate_y'))
            ->encode('png');

           
        $data = (string)$image;
        $path = 'uploads/avatars';
        $fileName = (Str::slug($user->display_name) . '.png');

        if (!Storage::disk('public')->put(($path . '/' . $fileName), $data)) {
            throw new RuntimeException('Failed to upload avatar');
        }

        $user->avatar = $fileName;
        $user->save();

        return redirect()->route('settings');
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
                        Arr::set($newNotifications, "{$key}.{$type}.{$channel}", true);
                    }
                }
            }
        }

        // Set other types to false
        foreach ($notificationKeys as $key => $value) {
            if (!isset($enabledNotificationKeys[$key])) {
                Arr::set($newNotifications, $key, false);
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
                        Arr::set($newNotifications, "{$key}.{$type}.{$channel}", true);
                    }
                }
            }
        }

        // Set other types to false
        foreach ($notificationKeys as $key => $value)
        {
            if (!isset($enabledNotificationKeys[$key]))
            {
                Arr::set($newNotifications, $key, false);
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
