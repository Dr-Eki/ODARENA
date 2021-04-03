<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Models\Realm;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\GameEventService;
use OpenDominion\Helpers\RaceHelper;
use OpenDominion\Calculators\Dominion\LandCalculator;

class WorldNewsController extends AbstractDominionController
{
    public function getIndex(int $realmNumber = null)
    {

        $gameEventService = app(GameEventService::class);
        $dominion = $this->getSelectedDominion();
        $this->updateDominionNewsLastRead($dominion);

        if ($realmNumber !== null) {
            $realm = Realm::where([
                'round_id' => $dominion->round_id,
                'number' => $realmNumber,
            ])
            ->first();
        } else {
            $realm = null;
        }

        $townCrierData = $gameEventService->getTownCrier($dominion, $realm);

        $gameEvents = $townCrierData['gameEvents'];
        $dominionIds = $townCrierData['dominionIds'];

        $realmCount = Realm::where('round_id', $dominion->round_id)->count();

        $raceHelper = app(RaceHelper::class);
        $landCalculator = app(LandCalculator::class);

        return view('pages.dominion.world-news', compact(
            'dominionIds',
            'gameEvents',
            'realm',
            'realmCount',
            'raceHelper',
            'landCalculator'
        ))->with('fromOpCenter', false);
    }


    protected function updateDominionNewsLastRead(Dominion $dominion): void
    {
        $dominion->news_last_read = now();
        $dominion->save();
    }

}
