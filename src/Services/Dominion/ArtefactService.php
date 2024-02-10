<?php

namespace OpenDominion\Services\Dominion;

use DB;
use Log;
use OpenDominion\Models\Artefact;
use OpenDominion\Models\Realm;
use OpenDominion\Models\RealmArtefact;
use OpenDominion\Models\Round;
use OpenDominion\Calculators\Dominion\ArtefactCalculator;
use OpenDominion\Helpers\ArtefactHelper;

class ArtefactService
{

    protected $artefactHelper;
    protected $artefactCalculator;

    public function __construct()
    {
        $this->artefactHelper = app(ArtefactHelper::class);
        $this->artefactCalculator = app(ArtefactCalculator::class);
    }

    public function addArtefactToRealm(Realm $realm, Artefact $artefact): void
    {
        $power = $this->artefactCalculator->getNewPower($realm, $artefact);

        DB::transaction(function () use ($realm, $artefact, $power)
        {
            RealmArtefact::create([
                'realm_id' => $realm->id,
                'artefact_id' => $artefact->id,
                'power' => $power,
                'max_power' => $power
            ]);
        });
    }

    public function removeArtefactFromRealm(Realm $realm, Artefact $artefact): void
    {
        DB::transaction(function () use ($realm, $artefact)
        {
            RealmArtefact::where('realm_id', $realm->id)
                ->where('artefact_id', $artefact->id)
                ->delete();
        });
    }

    public function getAvailableArtefacts(Round $round)
    {
        // Get the IDs of the artefacts in use
        $usedArtefactIds = RealmArtefact::join('realms', 'realms.id', '=', 'realm_artefacts.realm_id')
            ->where('realms.round_id', $round->id)
            ->pluck('artefact_id');
    
        // Get the artefacts that are not in use
        $artefacts = Artefact::where('enabled', 1)
            ->whereNotIn('id', $usedArtefactIds)
            ->get();
    
        return $artefacts;
    }

    public function getRandomArtefact(Round $round): Artefact
    {
        return $this->getAvailableArtefacts($round)->random();

        /*
        $artefacts = $this->getAvailableArtefacts($round);
        $artefact = $artefacts->random();
        return $artefact;
        */
    }

    public function moveArtefactFromRealmToRealm(Realm $fromRealm, Realm $toRealm, Artefact $artefact): void
    {
        $this->removeArtefactFromRealm($fromRealm, $artefact);
        $this->addArtefactToRealm($toRealm, $artefact);
    }

    public function updateRealmArtefactPower(Realm $realm, Artefact $artefact, int $powerChange): void
    {
        DB::transaction(function () use ($realm, $artefact, $powerChange) {
            $method = $powerChange >= 0 ? 'increment' : 'decrement';
    
            RealmArtefact::where('realm_id', $realm->id)
                ->where('artefact_id', $artefact->id)
                ->$method('power', abs($powerChange));
        });
    }

    public function updateArtefactAegis(Realm $realm): void
    {
        if(!$realm->artefacts->count())
        {
            return;
        }
    
        $realmArtefacts = $realm->realmArtefacts()->whereColumn('power', '<', 'max_power')->get();
    
        DB::transaction(function () use ($realmArtefacts, $realm) {
            foreach($realmArtefacts as $realmArtefact)
            {
                $aegisRestoration = $this->artefactCalculator->getAegisRestoration($realmArtefact);
                Log::info('[AEGIS] Added ' . $aegisRestoration . ' power to ' . $realmArtefact->artefact->name . ' in realm ' . $realm->name . ' (ID: ' . $realm->id . '), from ' . $realmArtefact->power . ' to ' . ($realmArtefact->power + $aegisRestoration) . ' (max: ' . $realmArtefact->max_power . ')');
                $realmArtefact->power += $aegisRestoration;
                $realmArtefact->save();
            }
        });
    }
    

}
