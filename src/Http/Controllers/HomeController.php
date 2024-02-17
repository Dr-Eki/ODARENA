<?php

namespace OpenDominion\Http\Controllers;

use Auth;
use DB;
use OpenDominion\Models\Advancement;
use OpenDominion\Models\Building;
use OpenDominion\Models\Improvement;
use OpenDominion\Models\Round;
use OpenDominion\Models\Race;
use OpenDominion\Models\Resource;
use OpenDominion\Models\Spell;
use OpenDominion\Models\Spyop;
use OpenDominion\Models\Tech;
use OpenDominion\Services\Dominion\SelectorService;
use OpenDominion\Calculators\Dominion\LandCalculator;

class HomeController extends AbstractController
{
    public function getIndex()
    {
        // Only redirect to status/dashboard if we have no referer
        // todo: this shit is still wonky. either fix or remove
        if (Auth::check() && (request()->server('HTTP_REFERER') !== '') && (url()->previous() === url()->current())) {
            $dominionSelectorService = app(SelectorService::class);

            if ($dominionSelectorService->tryAutoSelectDominionForAuthUser()) {
                return redirect()->route('dominion.status');
            }

            return redirect()->route('dashboard');
        }

        $landCalculator = app(LandCalculator::class);

        // Always assume last round is the most active one
        $currentRound = Round::query()
            ->with(['dominions', 'realms'])
            ->orderBy('created_at', 'desc')
            ->first();

        foreach(Round::all() as $round)
        {
            if(!$round->hasEnded())
            {
                $currentRound = $round;
            }
        }

        $rankingsRound = $currentRound;

        $previousRoundNumber = $currentRound->number - 1;

        if(!$currentRound->hasStarted() && $previousRoundNumber > 0)
        {
            $rankingsRound = Round::query()
            ->where('number', $previousRoundNumber)
            ->orderBy('created_at', 'desc')
            ->first();
        }

        $dominions = $round->activeDominions()->get();
        $largestDominion = 0;
        foreach($dominions as $dominion)
        {
            $largestDominion = max($largestDominion, $dominion->land);
        }

        $factions = Race::all()->where('playable',1)->count();
        $buildings = Building::all()->where('enabled',1)->count();
        $spells = Spell::all()->where('enabled',1)->count();
        $sabotage = Spyop::all()->where('enabled',1)->count();
        $advancements = Advancement::all()->where('enabled',1)->count();
        $techs = Tech::all()->where('enabled',1)->count();
        $improvements = Improvement::all()->where('enabled',1)->count();
        $resources = Resource::all()->where('enabled',1)->count();



        $currentRankings = null;

        return view('pages.home', [
            'currentRound' => $currentRound,
            'currentRankings' => $currentRankings,
            'largestDominion' => $largestDominion,
            'factions' => $factions,
            'advancements' => $advancements,
            'buildings' => $buildings,
            'spells' => $spells,
            'sabotage' => $sabotage,
            'techs' => $techs,
            'improvements' => $improvements,
            'resources' => $resources,

        ]);
    }
}
