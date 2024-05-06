<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\ProductionCalculator;
use OpenDominion\Calculators\Dominion\ResourceCalculator;
use OpenDominion\Calculators\Dominion\TheftCalculator;
use OpenDominion\Helpers\ResourceHelper;

use OpenDominion\Exceptions\GameException;
use OpenDominion\Http\Requests\Dominion\Actions\BankActionRequest;
use OpenDominion\Services\Analytics\AnalyticsEvent;
use OpenDominion\Services\Analytics\AnalyticsService;
use OpenDominion\Services\Dominion\Actions\BankActionService;

# ODA
use OpenDominion\Calculators\RealmCalculator;
use OpenDominion\Helpers\RaceHelper;
use OpenDominion\Models\Resource;

class ResourcesController extends AbstractDominionController
{
    public function getResources()
    {
          $dominion = $this->getSelectedDominion();
          $resourceCalculator = app(ResourceCalculator::class);

          $resources = [];


          foreach($dominion->race->resources as $resourceKey)
          {
              $resource = Resource::where('key', $resourceKey)->first();

              $resources[$resourceKey] = [
                  'label' => $resource->name,
                  'buy' => (float)$resource->buy,
                  'sell' => (float)$resource->sell * $resourceCalculator->getExchangeRatePerkMultiplier($dominion),
                  'max' => (int)$dominion->{'resource_' . $resourceKey}
              ];

          }

          return view('pages.dominion.resources', [
              'populationCalculator' => app(PopulationCalculator::class),
              'productionCalculator' => app(ProductionCalculator::class),
              'landCalculator' => app(LandCalculator::class),
              'resourceCalculator' => $resourceCalculator,
              'realmCalculator' => app(RealmCalculator::class),
              'raceHelper' => app(RaceHelper::class),
              'resourceHelper' => app(ResourceHelper::class),
              'resources' => $resources,
              'theftCalculator' => app(TheftCalculator::class),
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
