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
use OpenDominion\Services\Dominion\QueueService;

class ArtefactService
{

    protected $artefactHelper;
    protected $artefactCalculator;
    protected $queueService;

    public function __construct()
    {
        $this->artefactHelper = app(ArtefactHelper::class);
        $this->artefactCalculator = app(ArtefactCalculator::class);
        $this->queueService = app(QueueService::class);
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

    public function getDiscoveredArtefacts(Round $round)
    {
        // Get the IDs of the artefacts in use
        $usedArtefactIds = RealmArtefact::join('realms', 'realms.id', '=', 'realm_artefacts.realm_id')
            ->where('realms.round_id', $round->id)
            ->pluck('artefact_id');

        #$queuedArtefactIds = $this->getArtefactsInQueue($round);

        // Get the artefacts that are in use
        $artefacts = Artefact::where('enabled', 1)
            ->whereIn('id', $usedArtefactIds)
            #->orWhereIn('id', $queuedArtefactIds)
            ->pluck('id');

        return $artefacts;

    }

    public function getUndiscoveredArtefacts(Round $round)
    {
        return Artefact::where('enabled', 1)
            ->where('round_id', $round->id)
            ->whereNotIn('id', $this->getDiscoveredArtefacts($round))
            ->get();
    }

    public function getRandomUndiscoveredArtefact(Round $round): Artefact
    {
        return $this->getUndiscoveredArtefacts($round)->random();
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
                Log::info('[AEGIS] Added ' . number_format($aegisRestoration) . ' power to ' . $realmArtefact->artefact->name . ' in realm ' . $realm->name . ' (ID: ' . $realm->id . '), from ' . number_format($realmArtefact->power) . ' to ' . number_format($realmArtefact->power + $aegisRestoration) . ' (max: ' . number_format($realmArtefact->max_power) . ')');
                $realmArtefact->power += $aegisRestoration;
                $realmArtefact->save();
            }
        });
    }
    

    public function getArtefactsInQueue(Round $round)
    {
        # Raw query to get all queues where source = artefact
        $queues = DB::table('dominion_queues')
            ->where('round_id', $round->id)
            ->where('source', 'artefact')
            ->pluck('resource');

        $artefacts = [];

        foreach($queues as $queue)
        {
            $artefacts[] = Artefact::where('key', $queue->resource)->first()->id;
        }
           
        return $artefacts;
    }
}
