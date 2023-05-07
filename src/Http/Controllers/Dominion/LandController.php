<?php

namespace OpenDominion\Http\Controllers\Dominion;


use OpenDominion\Helpers\LandHelper;
use OpenDominion\Helpers\LandImprovementHelper;
use OpenDominion\Helpers\RaceHelper;


use OpenDominion\Calculators\Dominion\Actions\RezoningCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Http\Requests\Dominion\Actions\RezoneActionRequest;
use OpenDominion\Services\Dominion\Actions\RezoneActionService;

use OpenDominion\Services\Dominion\QueueService;

use OpenDominion\Http\Requests\Dominion\Actions\DailyBonusesLandActionRequest;
use OpenDominion\Services\Dominion\Actions\DailyBonusesActionService;

use OpenDominion\Calculators\Dominion\DominionCalculator;
use OpenDominion\Calculators\Dominion\LandImprovementCalculator;
use OpenDominion\Calculators\Dominion\ProductionCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;

class LandController extends AbstractDominionController
{
    public function getLand()
    {
        $raceHelper = app(RaceHelper::class);
        $dominion = $this->getSelectedDominion();
        $landImprovementPerks = [];

        if($raceHelper->hasLandImprovements($dominion->race))
        {
            foreach($dominion->race->land_improvements as $landImprovements)
            {
                foreach($landImprovements as $perkKey => $value)
                {
                    $landImprovementPerks[] = $perkKey;
                }
            }

            $landImprovementPerks = array_unique($landImprovementPerks, SORT_REGULAR);
            sort($landImprovementPerks);
        }

        return view('pages.dominion.land', [
            'dominionCalculator' => app(DominionCalculator::class),
            'landCalculator' => app(LandCalculator::class),
            'rezoningCalculator' => app(RezoningCalculator::class),
            'landHelper' => app(LandHelper::class),
            'landImprovementHelper' => app(LandImprovementHelper::class),
            'queueService' => app(QueueService::class),
            'raceHelper' => app(RaceHelper::class),
            'spellCalculator' => app(SpellCalculator::class),
            'productionCalculator' => app(ProductionCalculator::class),
            'landImprovementCalculator' => app(LandImprovementCalculator::class),
            'landImprovementPerks' => $landImprovementPerks,
        ]);
    }

    public function postLand(RezoneActionRequest $request)
    {

        $dominion = $this->getSelectedDominion();
        
        if($request->get('action') === 'rezone')
        {
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
        # Daily Bonus
        elseif($request->get('action') === 'daily_land')
        {
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
}
