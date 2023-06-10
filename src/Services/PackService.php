<?php

namespace OpenDominion\Services;

use Illuminate\Database\Eloquent\Builder;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Pack;
use OpenDominion\Models\Race;
use OpenDominion\Models\Round;

class PackService
{
    /**
     * Creates a new pack for a Dominion.
     *
     * @param Dominion $dominion
     * @param string $packName
     * @param string $packPassword
     * @param int $packSize
     * @return Pack
     * @throws GameException
     */
    public function createPack(Dominion $dominion, string $packName, string $packPassword): Pack
    {

        // todo: check if pack already exists with same name and password, and
        // throw exception if that's the case

        return Pack::create([
            'round_id' => $dominion->round->id,
            'realm_id' => $dominion->realm->id,
            'creator_dominion_id' => $dominion->id,
            'name' => $packName,
            'password' => $packPassword
        ]);

        // todo: set $dominion->pack_id = $pack->id here?
    }

    /**
     * Gets a pack based on pack based on round, alignment, pack name and password.
     *
     * @param Round $round
     * @param string $packName
     * @param string $packPassword
     * @param Race $race
     * @return Pack
     * @throws GameException
     */
    public function getPack(Round $round, string $packName, string $packPassword, Race $race): Pack
    {
        $otherRaceId = null;

        if (((int)$round->players_per_race !== 0)) {
            if ($race->name === 'Spirit') {
                // Count Undead with Spirit
                $otherRaceId = Race::where('name', 'Undead')->firstOrFail()->id;
            } elseif ($race->name === 'Undead') {
                // Count Spirit with Undead
                $otherRaceId = Race::where('name', 'Spirit')->firstOrFail()->id;
            } elseif ($race->name === 'Nomad') {
                // Count Human with Nomad
                $otherRaceId = Race::where('name', 'Human')->firstOrFail()->id;
            } elseif ($race->name === 'Human') {
                // Count Nomad with Human
                $otherRaceId = Race::where('name', 'Nomad')->firstOrFail()->id;
            }
        }

        $pack = Pack::where([
            'round_id' => $round->id,
            'name' => $packName,
            'password' => $packPassword,
        ])->withCount([
            'dominions',
            'dominions AS players_with_race' => static function (Builder $query) use ($race, $otherRaceId) {
                $query->where('race_id', $race->id);

                if ($otherRaceId) {
                    $query->orWhere('race_id', $otherRaceId);
                }
            }
        ])->first();

        if (!$pack) {
            throw new GameException('Pack with specified name/password was not found.');
        }

        return $pack;
    }
}
