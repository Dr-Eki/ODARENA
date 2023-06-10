<?php

namespace OpenDominion\Services\Dominion;

use Auth;
use Illuminate\Database\Eloquent\Collection;
use LogicException;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Pack;
use OpenDominion\Models\Round;
use OpenDominion\Models\User;
use RuntimeException;
use Session;

class RoundService
{
    public function hasUserDominionInRound(Round $round): bool
    {
        $user = Auth::user();

        return Dominion::where('user_id', $user->id)->where('round_id', $round->id)->first() ? true : false;
    }

    public function getUserDominionFromRound(Round $round): Dominion
    {
        $user = Auth::user();

        return Dominion::where('user_id', $user->id)->where('round_id', $round->id)->first();
    }

    public function getUserPackFromRound(User $user, Round $round)
    {
        return Dominion::where('round_id', $round->id)->get();
    }

}
