<?php

namespace OpenDominion\Http\Controllers;

use Auth;
use Illuminate\Http\Request;
use Image;
use OpenDominion\Helpers\NotificationHelper;
use OpenDominion\Models\User;
use RuntimeException;
use Storage;
use Throwable;


use LogicException;
use Log;

class PatreonController extends AbstractController
{
    public function getIndex()
    {
        /** @var User $user */
        $user = Auth::user();

        /** @var NotificationHelper $notificationHelper */
        $notificationHelper = app(NotificationHelper::class);

        $notificationSettings = $user->settings['notifications'] ?? $notificationHelper->getDefaultUserNotificationSettings();

        return view('pages.patreon', [
            'notificationHelper' => $notificationHelper,
            'notificationSettings' => $notificationSettings,
        ]);
    }

    public function getPatreonAccessToken(Request $request)
    {
        #Log::debug('Attempting to get Patreon access token for user ' . $user->display_name . '('. $user->id .')');
        /** @var User $user */
        $user = Auth::user();

        $curl = curl_init();

        $token = $_GET['code'];

        #Log::debug('Token is ' . $token);

        if(request()->getHost() == 'odarena.local')
        {
            $redirectUri = 'http://odarena.local/patreon';
        }
        else
        {
            $redirectUri = 'https://odarena.com/patreon';
        }

        $data = [
            'code' => $token,
            'grant_type' => 'authorization_code',
            'client_id' => 'zmWG1cS3XRWBJeBZThMpC-YEBt0rRv-1wfuKnXs7hEh5tNzkBpSs5gHYDkegI3Gd',
            'client_secret' => env('PATREON_CLIENT_SECRET'),
            'redirect_uri' => $redirectUri,
        ];

        #Log::debug('Redirect URI is ' . $redirectUri);

        $data = http_build_query($data);

        #Log::debug('HTTP query is ' . $data);

        curl_setopt($curl, CURLOPT_URL, 'https://www.patreon.com/api/oauth2/token');
        curl_setopt($curl, CURLOPT_POST, TRUE);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        #curl_setopt($curl, CURLOPT_HTTPHEADER, ['content-type: application/x-www-form-urlencoded']);

        $response = curl_exec($curl);
        $response = json_decode($response, true);
        if(isset($response['access_token']))
        {
            #Log::debug('Patreon access token obtained.');
            $user->patreon_access_token = $response['access_token'];
            $user->patreon_access_token_last_updated = now();
            $user->save();

            session()->flash('alert-success', 'Your account has been associated with your Patreon account.');
            return redirect()->route('settings');
        }
        else
        {
            #Log::debug('Something went wrong.');
            session()->flash('alert-warning', 'Something went wrong when associating your Patreon account.');
            return redirect()->route('settings');
        }
    }

    public function getPatreonPledge(): void
    {
        /** @var User $user */
        $user = Auth::user();
        $accessToken = $user->patreon_access_token;

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, 'https://www.patreon.com/api/oauth2/api/current_user');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['content-type: application/x-www-form-urlencoded', 'authorization: Bearer ' . $user->patreon_access_token]);

        $response = curl_exec($curl);
        $response = json_decode($response, true);
        dd($response);
    }
}
