<?php

namespace OpenDominion\Http\Controllers\Dominion;


use OpenDominion\Helpers\LandHelper;
use OpenDominion\Helpers\TerrainHelper;
use OpenDominion\Helpers\RaceHelper;

use OpenDominion\Calculators\Dominion\DominionCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\ProductionCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Calculators\Dominion\TerrainCalculator;
use OpenDominion\Calculators\Dominion\Actions\RezoningCalculator;

use OpenDominion\Exceptions\GameException;

use OpenDominion\Http\Requests\Dominion\Actions\RezoneActionRequest;
#use OpenDominion\Http\Requests\Dominion\Actions\DailyBonusesLandActionRequest;

use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Services\Dominion\Actions\DailyBonusesActionService;
use OpenDominion\Services\Dominion\Actions\RezoneActionService;

class LandController extends AbstractDominionController
{
    public function getLand()
    {
        return view('pages.dominion.land', [
            'dominionCalculator' => app(DominionCalculator::class),
            'terrainCalculator' => app(TerrainCalculator::class),
            'landCalculator' => app(LandCalculator::class),
            'rezoningCalculator' => app(RezoningCalculator::class),
            'landHelper' => app(LandHelper::class),
            'terrainHelper' => app(TerrainHelper::class),
            'queueService' => app(QueueService::class),
            'raceHelper' => app(RaceHelper::class),
            'spellCalculator' => app(SpellCalculator::class),
            'productionCalculator' => app(ProductionCalculator::class)
        ]);
    }

    public function postRezone(RezoneActionRequest $request)
    {

        $dominion = $this->getSelectedDominion();
        
        $rezoneActionService = app(RezoneActionService::class);

        try {
            $result = $rezoneActionService->rezone(
                $dominion,
                $request->get('remove'),
                $request->get('add')
            );

        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', $result['message']);
        return redirect()->route('dominion.land');
        
    }

    public function postDailyBonus(RezoneActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        
        $dailyBonusesActionService = app(DailyBonusesActionService::class);

        try {
            $result = $dailyBonusesActionService->claimLand($dominion);
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', $result['message']);
        return redirect()->route('dominion.land');
    }
}
