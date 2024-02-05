<?php

namespace OpenDominion\Services;

use OpenDominion\Factories\DominionFactory;
use OpenDominion\Models\Pack;
use OpenDominion\Models\Race;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;

class RealmFinderService
{
    public function findRealm(Round $round, Race $race, Pack $pack = null): Realm
    {
        if($round->mode == 'standard' or $round->mode == 'standard-duration' or $round->mode == 'artefacts')
        {
            return Realm::query()
                ->where('round_id', '=', $round->id)
                ->where('alignment', '=', $race->alignment)
                ->first();
        }

        if($round->mode == 'deathmatch' or $round->mode == 'deathmatch-duration')
        {
            if($race->alignment == 'npc')
            {
                return Realm::query()
                    ->where('round_id', '=', $round->id)
                    ->where('alignment', '=', 'npc')
                    ->first();
            }

            return Realm::query()
                ->where('round_id', '=', $round->id)
                ->where('alignment', '=', 'players')
                ->first();
        }

        if($round->mode == 'factions' or $round->mode == 'factions-duration')
        {
            if($race->alignment == 'npc')
            {
                return Realm::query()
                    ->where('round_id', '=', $round->id)
                    ->where('alignment', '=', 'npc')
                    ->first();
            }

            return Realm::query()
                ->where('round_id', '=', $round->id)
                ->where('alignment', '=', $race->key)
                ->first();
        }

        if($round->mode == 'packs' or $round->mode == 'packs-duration' or $round->mode == 'artefacts-packs')
        {
            if($race->alignment == 'npc')
            {
                return Realm::query()
                    ->where('round_id', '=', $round->id)
                    ->where('alignment', '=', 'npc')
                    ->first();
            }


            return $pack->realm;
        }

    }

}
