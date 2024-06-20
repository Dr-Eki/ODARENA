<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Helpers\DecreeHelper;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Http\Requests\Dominion\Actions\DecreeActionRequest;
use OpenDominion\Services\Dominion\DecreeService;
use OpenDominion\Calculators\Dominion\DecreeCalculator;

use OpenDominion\Models\Decree;
use OpenDominion\Models\DecreeState;
use OpenDominion\Models\DominionDecreeState;


class DecreesController extends AbstractDominionController
{
    public function getIndex()
    {
        $dominion = $this->getSelectedDominion();
        $decreeHelper = app(DecreeHelper::class);
        $decrees = $decreeHelper->getDecreesByRace($dominion->race);

        return view('pages.dominion.decrees', [
            'decreeCalculator' => app(DecreeCalculator::class),
            'decreeHelper' => $decreeHelper,
            'decrees' => $decrees
        ]);
    }


    public function postRevokeDecree(DecreeActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $decreeService = app(DecreeService::class);
        $dominionDecreeState = DominionDecreeState::findOrFail($request->get('dominionDecreeState'));
        $decree = Decree::findOrFail($dominionDecreeState->decree->id);

        try {
            $decreeService->revokeDominionDecree($dominion, $decree);
        } catch (GameException $e) {
            return redirect()
                ->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        return redirect()->route('dominion.decrees');
    }

    public function postIssueDecree(DecreeActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $decreeService = app(DecreeService::class);
        $decreeId = $request->get('decree');
        $decreeStateId = $request->get('decreeState');

        $decree = Decree::findOrfail($decreeId);
        $decreeState = DecreeState::findOrfail($decreeStateId);

        try {
            $decreeService->issueDominionDecree($dominion, $decree, $decreeState);
        } catch (GameException $e) {
            return redirect()
                ->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-primary', "Your new {$decree->name} decree has been enacted.");
        return redirect()->route('dominion.decrees');
    }

}
