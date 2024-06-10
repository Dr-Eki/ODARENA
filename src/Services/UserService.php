<?php

namespace OpenDominion\Services;

use Illuminate\Support\Str;

use OpenDominion\Models\User;

class UserService
{
    public function generateApiKey(User $user): void
    {
        $user->api_key = Str::random(60);
        $user->save();
    }

}
