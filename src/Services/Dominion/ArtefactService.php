<?php

namespace OpenDominion\Services\Dominion;

use DB;
use Log;
use Illuminate\Support\Collection;
use OpenDominion\Models\Artefact;
use OpenDominion\Models\Dominion;
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

    public function getRealmArtefactsArtefacts(Round $round): Collection
    {
        $realmArtefacts = RealmArtefact::whereHas('realm', function ($query) use ($round) {
            $query->where('round_id', $round->id);
        })->get();

        $artefacts = collect();

        foreach($realmArtefacts as $realmArtefact)
        {
            $artefacts->push($realmArtefact->artefact);
        }

        return $artefacts;
    }

    public function getDiscoveredArtefacts(Round $round): Collection
    {
        return $this->getRealmArtefactsArtefacts($round)->merge($this->getArtefactsInQueue($round));
    }

    public function getUndiscoveredArtefacts(Round $round): Collection
    {
        return collect(Artefact::where('enabled', 1)->whereNotIn('id', $this->getDiscoveredArtefacts($round)->pluck('id'))->get());
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
    
    public function getArtefactsInQueue(Round $round): Collection
    {
        $queues = DB::table('dominion_queue')
            ->join('dominions', 'dominions.id', '=', 'dominion_queue.dominion_id')
            ->where('dominions.round_id', $round->id)
            ->where('dominion_queue.source', 'artefact')
            ->pluck('resource');
    
        $artefacts = collect();
    
        foreach($queues as $index => $artefactKey)
        {
            $artefact = Artefact::where('key', $artefactKey)->first();
            if ($artefact) {
                $artefacts->push($artefact);
            }
        }
    
        return $artefacts;
    }

    # Effectively, the destination is a realm but functionally it's a dominion because of how the queue system works.
    public function getArtefactInQueueDestination(Round $round, Artefact $artefact): Dominion
    {
        $dominionId = DB::table('dominion_queue')
            ->join('dominions', 'dominions.id', '=', 'dominion_queue.dominion_id')
            ->where('dominions.round_id', $round->id)
            ->where('resource', $artefact->key)
            ->where('source', 'artefact')
            ->pluck('dominion_id')
            ->first();
    
        return Dominion::find($dominionId);
    }

    public function getArtefactInQueueTicksRemaining(Round $round, Artefact $artefact): int
    {
        return DB::table('dominion_queue')
            ->join('dominions', 'dominions.id', '=', 'dominion_queue.dominion_id')
            ->where('dominions.round_id', $round->id)
            ->where('resource', $artefact->key)
            ->where('source', 'artefact')
            ->pluck('hours')
            ->first();
    }

    public function getArtefactRealm(Round $round, Artefact $artefact): Realm
    {
        return Realm::where('round_id', $round->id)
            ->whereHas('artefacts', function ($query) use ($artefact) {
                $query->where('artefact_id', $artefact->id);
            })
            ->first();
    }
}
