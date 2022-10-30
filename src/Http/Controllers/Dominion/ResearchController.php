<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Exceptions\GameException;
use OpenDominion\Http\Requests\Dominion\Actions\ResearchActionRequest;

use OpenDominion\Calculators\Dominion\ResearchCalculator;

use OpenDominion\Helpers\ResearchHelper;

use OpenDominion\Models\Tech;

use OpenDominion\Services\Dominion\ResearchService;

class ResearchController extends AbstractDominionController
{
    public function getResearch()
    {
        $dominion = $this->getSelectedDominion();
        $researchCalculator = app(ResearchCalculator::class);
        $researchHelper = app(ResearchHelper::class);

        return view('pages.dominion.research', [
            'techs' => $researchHelper->getTechsByRace($dominion->race), 
            'researchHelper' => $researchHelper,
            'researchCalculator' => $researchCalculator
        ]);
    }

    public function postResearch(ResearchActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $researchService = app(ResearchService::class);
        $tech = Tech::findOrFail($request->get('tech_id'));

        try {
            $result = $researchService->beginResearch($dominion, $tech);
        } catch (GameException $e) {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', "You have started researching {$tech->name}.");

        return redirect()->route('dominion.research');
    }
}
