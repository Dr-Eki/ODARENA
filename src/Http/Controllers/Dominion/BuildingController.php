<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Illuminate\Support\Collection;

use OpenDominion\Calculators\Dominion\Actions\ConstructionCalculator;
use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Http\Requests\Dominion\Actions\ConstructActionRequest;
use OpenDominion\Http\Requests\Dominion\Actions\DemolishActionRequest;
use OpenDominion\Services\Analytics\AnalyticsEvent;
use OpenDominion\Services\Analytics\AnalyticsService;
use OpenDominion\Services\Dominion\Actions\ConstructActionService;
use OpenDominion\Services\Dominion\Actions\DemolishActionService;
use OpenDominion\Services\Dominion\QueueService;


use OpenDominion\Models\Building;
use OpenDominion\Services\Dominion\Actions\BuildActionService;
use OpenDominion\Http\Requests\Dominion\Actions\BuildActionRequest;
use OpenDominion\Helpers\RaceHelper;

class BuildingController extends AbstractDominionController
{
    public function getBuildings()
    {
        $dominion = $this->getSelectedDominion();

        return view('pages.dominion.buildings', [
            'buildingCalculator' => app(BuildingCalculator::class),
            'buildingHelper' => app(BuildingHelper::class),
            'constructionCalculator' => app(ConstructionCalculator::class),
            'landCalculator' => app(LandCalculator::class),
            'queueService' => app(QueueService::class),
            'raceHelper' => app(RaceHelper::class),
            'landHelper' => app(LandHelper::class),
        ]);
    }

    public function postBuildings(BuildActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $buildActionService = app(BuildActionService::class);

        try {
            $result = $buildActionService->build($dominion, $request->get('build'));

        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', $result['message']);
        return redirect()->route('dominion.buildings');
    }

    public function getDemolish()
    {
        return view('pages.dominion.demolish', [
            'buildingCalculator' => app(BuildingCalculator::class),
            'buildingHelper' => app(BuildingHelper::class),
            'constructionCalculator' => app(ConstructionCalculator::class),
            'landCalculator' => app(LandCalculator::class),
            'queueService' => app(QueueService::class),
            'raceHelper' => app(RaceHelper::class),
            'landHelper' => app(LandHelper::class),
        ]);
    }

    public function postDemolish(DemolishActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $demolishActionService = app(DemolishActionService::class);

        try {
            $result = $demolishActionService->demolish($dominion, $request->get('demolish'));

        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', $result['message']);
        return redirect()->route('dominion.demolish');
    }
}
