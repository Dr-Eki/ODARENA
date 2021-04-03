<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\ProductionCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Helpers\SpellHelper;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Services\Dominion\QueueService;

use OpenDominion\Calculators\Dominion\Actions\BankingCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Http\Requests\Dominion\Actions\BankActionRequest;
use OpenDominion\Services\Analytics\AnalyticsEvent;
use OpenDominion\Services\Analytics\AnalyticsService;
use OpenDominion\Services\Dominion\Actions\BankActionService;

# ODA
use OpenDominion\Calculators\RealmCalculator;
use OpenDominion\Helpers\RaceHelper;
use OpenDominion\Calculators\Dominion\LandImprovementCalculator;
use OpenDominion\Models\Spell;

class ResourcesController extends AbstractDominionController
{
    public function getResources()
    {
          return view('pages.dominion.resources', [
              'populationCalculator' => app(PopulationCalculator::class),
              'productionCalculator' => app(ProductionCalculator::class),
              'landCalculator' => app(LandCalculator::class),
              'realmCalculator' => app(RealmCalculator::class),
              'raceHelper' => app(RaceHelper::class),
              'bankingCalculator' => app(BankingCalculator::class),
          ]);
    }

    public function postResources(BankActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $bankActionService = app(BankActionService::class);

        try {
            $result = $bankActionService->exchange(
                $dominion,
                $request->get('source'),
                $request->get('target'),
                $request->get('amount')
            );

        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        // todo: fire laravel event
        $analyticsService = app(AnalyticsService::class);
        $analyticsService->queueFlashEvent(new AnalyticsEvent(
            'dominion',
            'bank',
            '', // todo: make null?
            $request->get('amount')
        ));

        $request->session()->flash('alert-success', $result['message']);
        return redirect()->route('dominion.resources');
    }

}
