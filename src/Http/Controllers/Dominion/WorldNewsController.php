<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Models\Realm;
use OpenDominion\Models\Dominion;
#use OpenDominion\Services\GameEventService;
use OpenDominion\Services\Dominion\WorldNewsService;
use OpenDominion\Helpers\WorldNewsHelper;
#use OpenDominion\Helpers\RaceHelper;
use OpenDominion\Helpers\EventHelper;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\NetworthCalculator;

use OpenDominion\Traits\DominionGuardsTrait;

class WorldNewsController extends AbstractDominionController
{
    use DominionGuardsTrait;

    public function getIndex(int $realmNumber = null)
    {
        $dominion = $this->getSelectedDominion();

        $this->guardActionsDuringTick($dominion);
        
        $worldNewsService = app(WorldNewsService::class);

        $this->updateDominionNewsLastRead($dominion);

        $realm = Realm::where([
            'round_id' => $dominion->round_id,
            'number' => $realmNumber,
        ])
        ->first();

        if ($realm)
        {
            $worldNewsData = $worldNewsService->getWorldNewsForRealm($realm, $dominion);
        }
        else
        {
            $realm = null;
            $worldNewsData = $worldNewsService->getWorldNewsForDominion($dominion);
        }

        $realmCount = Realm::where('round_id', $dominion->round_id)->count();

        return view('pages.dominion.world-news', [
            'eventHelper' => app(EventHelper::class),
            'worldNewsHelper' => app(WorldNewsHelper::class),
            'gameEvents' => $worldNewsData,
            'realm' => $realm,
            'realmCount' => $realmCount,
            'landCalculator' => app(LandCalculator::class),
            'militaryCalculator' => app(MilitaryCalculator::class),
            'networthCalculator' => app(NetworthCalculator::class),
            ]
        );
    }

    protected function updateDominionNewsLastRead(Dominion $dominion): void
    {
        $dominion->news_last_read = now();
        $dominion->save();
    }

}
