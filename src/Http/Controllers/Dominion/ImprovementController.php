<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Illuminate\Http\Request;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\ImprovementHelper;
use OpenDominion\Http\Requests\Dominion\Actions\ImproveActionRequest;
use OpenDominion\Services\Analytics\AnalyticsEvent;
use OpenDominion\Services\Analytics\AnalyticsService;
use OpenDominion\Services\Dominion\Actions\ImproveActionService;
use OpenDominion\Services\Dominion\QueueService;

class ImprovementController extends AbstractDominionController
{
    public function getImprovements(Request $request)
    {
        return view('pages.dominion.improvements', [
            'improvementCalculator' => app(ImprovementCalculator::class),
            'improvementHelper' => app(ImprovementHelper::class),
            'selectedResource' => $request->query('resource', 'gems'),
            'queueService' => app(QueueService::class)
        ]);
    }

    public function postImprovements(ImproveActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $improveActionService = app(ImproveActionService::class);

        if($request->get('imps2') == 1)
        {
          try {
              $result = $improveActionService->improve2(
                  $dominion,
                  $request->get('resource'),
                  $request->get('improve')
              );

          } catch (GameException $e) {
              return redirect()->back()
                  ->withInput($request->all())
                  ->withErrors([$e->getMessage()]);
          }
        }
        else
        {

            try {
                $result = $improveActionService->improve(
                    $dominion,
                    $request->get('resource'),
                    $request->get('improve')
                );

            } catch (GameException $e) {
                return redirect()->back()
                    ->withInput($request->all())
                    ->withErrors([$e->getMessage()]);
            }

        }

        // todo: fire laravel event
        #$analyticsService = app(AnalyticsService::class);
        #$analyticsService->queueFlashEvent(new AnalyticsEvent(
        #    'dominion',
        #    'improve',
        #    null,
        #    array_sum($request->get('improve'))
        #));

        $request->session()->flash('alert-success', $result['message']);
        return redirect()->route('dominion.improvements', [
            'resource' => $request->get('resource'),
        ]);
    }
}
