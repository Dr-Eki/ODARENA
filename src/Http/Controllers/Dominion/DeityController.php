<?php

namespace OpenDominion\Http\Controllers\Dominion;

#use OpenDominion\Calculators\Dominion\GovernmentCalculator;
#use OpenDominion\Calculators\Dominion\LandCalculator;
#use OpenDominion\Calculators\Dominion\RangeCalculator;
#use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Helpers\DeityHelper;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Http\Requests\Dominion\Actions\GovernmentActionRequest;
#use OpenDominion\Services\Dominion\Actions\GovernmentActionService;
#use OpenDominion\Services\Dominion\GovernmentService;
use OpenDominion\Services\Dominion\DeityService;
#use OpenDominion\Calculators\RealmCalculator;
#use OpenDominion\Calculators\Dominion\MilitaryCalculator;

#use OpenDominion\Models\Decree;
#use OpenDominion\Models\DecreeState;
use OpenDominion\Models\Deity;


class DeityController extends AbstractDominionController
{
    public function getIndex()
    {
        $dominion = $this->getSelectedDominion();

        return view('pages.dominion.deity', [
            'deityHelper' => app(DeityHelper::class)
        ]);
    }

    public function postDeity(GovernmentActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $deityService = app(DeityService::class);
        $deityKey = $request->get('key');

        try {
            $deityService->submitToDeity($dominion, $deityKey);
        } catch (GameException $e) {
            return redirect()
                ->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $deity = Deity::where('key', $deityKey)->first();

        $request->session()->flash('alert-success', "You have successfully submitted your devotion to {$deity->name}!");
        return redirect()->route('dominion.deity');
    }

    public function postRenounce(GovernmentActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $deityService = app(DeityService::class);
        $deity = $dominion->deity;

        try {
            $deityService->renounceDeity($dominion, $deity);
        } catch (GameException $e) {
            return redirect()
                ->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-danger', "You have renounced your devotion to {$deity->name}.");
        return redirect()->route('dominion.deity');
    }

}
