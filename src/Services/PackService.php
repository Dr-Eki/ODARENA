<?php

namespace OpenDominion\Services;

use Illuminate\Support\Collection;
use OpenDominion\Models\Pack;
use OpenDominion\Models\Round;
use OpenDominion\Models\User;

class PackService
{
    public function __construct()
    {
    }

    public function canDeletePack(User $user, Pack $pack): bool
    {
        return ($user->id === $pack->user->id and $pack->dominions->count() === 0);
    }

    public function canEditPack(User $user, Pack $pack): bool
    {
        return ($user->id === $pack->user->id);
    }

    public function getPacksCreatedByUserInRound(User $user, Round $round): Collection
    {
        return $user->packs()->where('round_id', $round->id)->get();
    }

    public function changePackStatus(Pack $pack, int $status): void
    {
        $pack->status = $status;
        $pack->save();
    }

}
