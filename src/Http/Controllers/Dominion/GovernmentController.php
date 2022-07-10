<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Calculators\Dominion\GovernmentCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Helpers\DeityHelper;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Http\Requests\Dominion\Actions\GovernmentActionRequest;
use OpenDominion\Services\Dominion\Actions\GovernmentActionService;
use OpenDominion\Services\Dominion\GovernmentService;
use OpenDominion\Services\Dominion\DeityService;
use OpenDominion\Calculators\RealmCalculator;

use OpenDominion\Models\Decree;
use OpenDominion\Models\DecreeState;
use OpenDominion\Models\Deity;


class GovernmentController extends AbstractDominionController
{
    public function getIndex()
    {
        $dominion = $this->getSelectedDominion();
        #$governmentService = app(GovernmentService::class);

        $dominions = $dominion->realm->dominions()
            ->with([
                'race',
                'race.perks',
                'race.units',
                'race.units.perks',
                'monarchVote',
            ])
            ->get()
            ->sortByDesc(function ($dominion) {
                return app(LandCalculator::class)->getTotalLand($dominion);
            });

        return view('pages.dominion.government', [
            'dominions' => $dominions,
            'realms' => $dominion->round->realms,
            'monarch' => $dominion->realm->monarch,
            'governmentCalculator' => app(GovernmentCalculator::class),
            'landCalculator' => app(LandCalculator::class),
            'networthCalculator' => app(NetworthCalculator::class),
            'rangeCalculator' => app(RangeCalculator::class),
            'realmCalculator' => app(RealmCalculator::class),
            'deityHelper' => app(DeityHelper::class)
        ]);
    }

    public function postMonarch(GovernmentActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $governmentActionService = app(GovernmentActionService::class);

        $vote = $request->get('monarch');
        $governmentActionService->voteForMonarch($dominion, $vote);

        $request->session()->flash('alert-success', 'Your vote has been cast!');
        return redirect()->route('dominion.government');
    }

    public function postRealm(GovernmentActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $governmentActionService = app(GovernmentActionService::class);

        $motd = $request->get('realm_motd');
        $name = $request->get('realm_name');
        $contribution = $request->get('realm_contribution');
        $discordLink = $request->get('discord_link');

        try {
            $governmentActionService->updateRealm($dominion, $motd, $name, $contribution, $discordLink);
        } catch (GameException $e) {
            return redirect()
                ->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', 'Your realm has been updated!');
        return redirect()->route('dominion.government');
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
        return redirect()->route('dominion.government');
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
        return redirect()->route('dominion.government');
    }

}
