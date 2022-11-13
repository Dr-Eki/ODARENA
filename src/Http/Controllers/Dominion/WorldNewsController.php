<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Models\Realm;
use OpenDominion\Models\Dominion;
#use OpenDominion\Services\GameEventService;
use OpenDominion\Services\Dominion\WorldNewsService;
use OpenDominion\Helpers\WorldNewsHelper;
#use OpenDominion\Helpers\RaceHelper;
#use OpenDominion\Helpers\RoundHelper;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\NetworthCalculator;

class WorldNewsController extends AbstractDominionController
{

    public function getIndex(int $realmNumber = null)
    {
        $worldNewsService = app(WorldNewsService::class);
        $dominion = $this->getSelectedDominion();

        $this->updateDominionNewsLastRead($dominion);

        if ($realmNumber !== null) {
            $realm = Realm::where([
                'round_id' => $dominion->round_id,
                'number' => $realmNumber,
            ])
            ->first();

            $worldNewsData = $worldNewsService->getWorldNewsForRealm($realm, $viewer = $dominion);
        }
        else
        {
            $realm = null;
            $worldNewsData = $worldNewsService->getWorldNewsForDominion($dominion);
        }

        $gameEvents = $worldNewsData;

        $realmCount = Realm::where('round_id', $dominion->round_id)->count();

        return view('pages.dominion.world-news', [
            'worldNewsHelper' => app(WorldNewsHelper::class),
            'gameEvents' => $gameEvents,
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
