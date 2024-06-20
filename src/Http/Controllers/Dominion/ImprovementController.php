<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Illuminate\Http\Request;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Calculators\Dominion\ResourceCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\ImprovementHelper;
use OpenDominion\Helpers\ResourceHelper;
use OpenDominion\Http\Requests\Dominion\Actions\ImproveActionRequest;
use OpenDominion\Services\Dominion\Actions\ImproveActionService;
use OpenDominion\Services\Dominion\QueueService;

class ImprovementController extends AbstractDominionController
{
    public function getImprovements(Request $request)
    {

        return view('pages.dominion.improvements', [
            'improvementCalculator' => app(ImprovementCalculator::class),
            'improvementHelper' => app(ImprovementHelper::class),
            'resourceHelper' => app(ResourceHelper::class),
            'selectedResource' => $request->query('resource', 'gems'),
            'queueService' => app(QueueService::class),
            'resourceCalculator' => app(ResourceCalculator::class),
        ]);
    }

    public function postImprovements(ImproveActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $improveActionService = app(ImproveActionService::class);
       
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

        return redirect()->route('dominion.improvements', [
            'resource' => $request->get('resource'),
        ]);
    }
}
