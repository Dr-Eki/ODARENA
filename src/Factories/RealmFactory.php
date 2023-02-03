<?php

namespace OpenDominion\Factories;

#use Atrox\Haikunator;
use DB;
use LogicException;
use OpenDominion\Models\Race;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;

class RealmFactory
{
    /**
     * Creates and returns a new Realm in a Round based on alignment.
     *
     * @param Round $round
     * @param string|null $alignment
     * @param Pack|null $pack
     * @return Realm
     * @throws LogicException
     */
    public function create(Round $round, ?string $alignment = null, ?Pack $pack = null): Realm
    {
        $results = DB::table('realms')
            ->select(DB::raw('MAX(realms.number) AS max_realm_number'))
            ->where('round_id', $round->id)
            ->limit(1)
            ->get();

        if ($results === null) {
            $number = 1;
        } else {
            $number = ((int)$results[0]->max_realm_number + 1);
        }

        $defaultRealmName = [
            'npc' => 'The Barbarian Horde',
            'good' => 'The Commonwealth',
            'evil' => 'The Empire',
            'independent' => 'The Independent',
            'players' => 'The Players',
        ];

        foreach(Race::where('playable',1)->get() as $race)
        {
            $defaultRealmName[$race->key] = $race->name;
        }

        $realmName = $defaultRealmName[$alignment];

        $realm = Realm::create([
            'round_id' => $round->id,
            'alignment' => $alignment,
            'number' => $number,
            'name' => $realmName
        ]);

        if ($pack !== null) {
            $pack->update(['realm_id' => $realm->id]);
            $pack->load('realm');
        }

        return $realm;
    }
}
