<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Calculators\Dominion\Actions\ExplorationCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Http\Requests\Dominion\Actions\ExploreActionRequest;


use OpenDominion\Services\Dominion\Actions\ExploreActionService;
use OpenDominion\Services\Dominion\QueueService;

# ODA
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Services\Dominion\GuardMembershipService;
use OpenDominion\Calculators\Dominion\LandImprovementCalculator;

class ExplorationController extends AbstractDominionController
{
    public function getExplore()
    {
        return view('pages.dominion.explore', [
            'explorationCalculator' => app(ExplorationCalculator::class),
            'landCalculator' => app(LandCalculator::class),
            'landHelper' => app(LandHelper::class),
            'queueService' => app(QueueService::class),
            'spellCalculator' => app(SpellCalculator::class),
            'guardMembershipService' => app(GuardMembershipService::class),
            'landImprovementCalculator' => app(LandImprovementCalculator::class),
        ]);
    }

    public function postExplore(ExploreActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $exploreActionService = app(ExploreActionService::class);

        try {
            $result = $exploreActionService->explore($dominion, $request->get('explore'));

        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', $result['message']);
        return redirect()->route('dominion.explore');
    }
}
