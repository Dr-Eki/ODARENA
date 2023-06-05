<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Calculators\Dominion\AllianceCalculator;
use OpenDominion\Calculators\Dominion\GovernmentCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Helpers\DeityHelper;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Http\Requests\Dominion\Actions\GovernmentActionRequest;
use OpenDominion\Services\Dominion\Actions\GovernmentActionService;
#use OpenDominion\Services\Dominion\GovernmentService;
#use OpenDominion\Services\Dominion\DeityService;
use OpenDominion\Calculators\RealmCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;

use OpenDominion\Models\Dominion;
use OpenDominion\Models\Realm;
use OpenDominion\Models\AllianceOffer;
use OpenDominion\Models\ProtectorshipOffer;
use OpenDominion\Models\RealmAlliance;
#use OpenDominion\Services\Dominion\ProtectionService;

#use OpenDominion\Models\Deity;


class GovernmentController extends AbstractDominionController
{
    public function getIndex()
    {
        $dominion = $this->getSelectedDominion();

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
                return $dominion->land;
            });

        $allianceableRealms = Realm::where('round_id','=',$dominion->round->id)
        ->where('alignment', '!=', 'npc')
        ->where('id', '!=', $dominion->realm->id)
        ->get();

        return view('pages.dominion.government', [
            'allianceableRealms' => $allianceableRealms,
            'dominions' => $dominions,
            'realms' => $dominion->round->realms,
            'monarch' => $dominion->realm->monarch,
            'allianceCalculator' => app(AllianceCalculator::class),
            'governmentCalculator' => app(GovernmentCalculator::class),
            'landCalculator' => app(LandCalculator::class),
            'networthCalculator' => app(NetworthCalculator::class),
            'rangeCalculator' => app(RangeCalculator::class),
            'militaryCalculator' => app(MilitaryCalculator::class),
            'realmCalculator' => app(RealmCalculator::class),
            'deityHelper' => app(DeityHelper::class)
        ]);
    }

    public function postMonarch(GovernmentActionRequest $request)
    {
        $dominion = $this->getSelectedDominion();
        $governmentActionService = app(GovernmentActionService::class);

        $vote = $request->get('monarch');

        try
        {
            $result = $governmentActionService->voteForMonarch($dominion, $vote);
        }
        catch (GameException $e)
        {
            return redirect()->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

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

    public function postOfferProtectorship(GovernmentActionRequest $request)
    {
        $protector = $this->getSelectedDominion();
        $protected = Dominion::findOrFail($request->unprotected_dominion);
        $governmentActionService = app(GovernmentActionService::class);

        try {
            $governmentActionService->submitProtectorshipOffer($protector, $protected);
        } catch (GameException $e) {
            return redirect()
                ->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', 'Your offer has been sent.');
        return redirect()->route('dominion.government');
    }

    public function postRescindProtectorshipOffer(GovernmentActionRequest $request)
    {
        $responder = $this->getSelectedDominion();
        $protectorshipOffer = ProtectorshipOffer::findOrFail($request->protectorship_offer_id);
        $governmentActionService = app(GovernmentActionService::class);

        try {
            $governmentActionService->rescindProtectorshipOffer($protectorshipOffer, $responder);
        } catch (GameException $e) {
            return redirect()
                ->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-warning', 'Your offer has been rescinded.');
        return redirect()->route('dominion.government');
    }

    public function postAnswerProtectorshipOffer(GovernmentActionRequest $request)
    {
        $responder = $this->getSelectedDominion();
        $protectorshipOffer = ProtectorshipOffer::findOrFail($request->protectorship_offer_id);
        $governmentActionService = app(GovernmentActionService::class);
        $answer = $request->get('answer');

        try {
            $governmentActionService->answerProtectorshipOffer($protectorshipOffer, $answer, $responder);
        } catch (GameException $e) {
            return redirect()
                ->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', 'Your answer has been sent.');
        return redirect()->route('dominion.government');
    }
    # END PROTECTORSHIP

    # ALLIANCE
    public function postOfferAlliance(GovernmentActionRequest $request)
    {
        $inviter = $this->getSelectedDominion();
        $invitee = Realm::findOrFail($request->realm);
        $governmentActionService = app(GovernmentActionService::class);

        try {
            $governmentActionService->submitAllianceOffer($inviter, $inviter->realm, $invitee);
        } catch (GameException $e) {
            return redirect()
                ->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', 'Your offer has been sent.');
        return redirect()->route('dominion.government');
    }

    public function postRescindAllianceOffer(GovernmentActionRequest $request)
    {
        $inviter = $this->getSelectedDominion();
        $allianceOffer = AllianceOffer::findOrFail($request->alliance_offer_id);
        $governmentActionService = app(GovernmentActionService::class);

        try {
            $governmentActionService->rescindAllianceOffer($allianceOffer, $inviter);
        } catch (GameException $e) {
            return redirect()
                ->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-warning', 'Your offer has been rescinded.');
        return redirect()->route('dominion.government');
    }

    public function postAnswerAllianceOffer(GovernmentActionRequest $request)
    {
        $invitee = $this->getSelectedDominion();
        $allianceOffer = AllianceOffer::findOrFail($request->alliance_offer_id);
        $governmentActionService = app(GovernmentActionService::class);
        $answer = $request->get('answer');

        try {
            $governmentActionService->answerAllianceOffer($allianceOffer, $answer, $invitee);
        } catch (GameException $e) {
            return redirect()
                ->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-success', 'Your answer has been sent.');
        return redirect()->route('dominion.government');
    }

    public function postBreakAlliance(GovernmentActionRequest $request)
    {
        $breaker = $this->getSelectedDominion();
        $realmAlliance = RealmAlliance::findOrFail($request->realm_alliance_id);
        $governmentActionService = app(GovernmentActionService::class);

        # Check that user confirmed
        if ($request->get('confirm') !== 'on') {
            return redirect()
                ->back()
                ->withInput($request->all())
                ->withErrors(['You must confirm that you want to break the alliance.']);
        }

        try {
            $governmentActionService->submitBreakAlliance($realmAlliance, $breaker);
        } catch (GameException $e) {
            return redirect()
                ->back()
                ->withInput($request->all())
                ->withErrors([$e->getMessage()]);
        }

        $request->session()->flash('alert-danger', 'Alliance has been broken.');
        return redirect()->route('dominion.government');
    }

}
