<?php

namespace OpenDominion\Http\Controllers;

use Illuminate\Http\Response;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\ProductionCalculator;
use OpenDominion\Calculators\Dominion\ResourceCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Calculators\Dominion\UnitCalculator;

use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;
use OpenDominion\Models\Round;
use OpenDominion\Models\User;

use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\ChroniclesHelper;
use OpenDominion\Helpers\DeityHelper;
use OpenDominion\Helpers\DominionHelper;
use OpenDominion\Helpers\ImprovementHelper;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Helpers\LandImprovementHelper;
use OpenDominion\Helpers\RaceHelper;
use OpenDominion\Helpers\RealmHelper;
use OpenDominion\Helpers\RoundHelper;
use OpenDominion\Helpers\SpellHelper;
use OpenDominion\Helpers\StatsHelper;
use OpenDominion\Helpers\TechHelper;
use OpenDominion\Helpers\TitleHelper;
use OpenDominion\Helpers\UserHelper;
use OpenDominion\Helpers\UnitHelper;

use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Services\Dominion\RoundService;
use OpenDominion\Services\Dominion\StatsService;

class ChroniclesController extends AbstractController
{
    public function getIndex()
    {
        $rounds = Round::with('league')->orderBy('start_date', 'desc')->get();

        $users = User::orderBy('display_name')->get();

        return view('pages.chronicles.index', [
            'userHelper' => app(UserHelper::class),
            'rounds' => $rounds,
            'users' => $users,
        ]);
    }

    public function getRounds()
    {
        return view('pages.chronicles.rounds', [
            'rounds' => Round::with('league')->orderBy('start_date', 'desc')->get(),
        ]);
    }

    public function getRulers()
    {
        return view('pages.chronicles.rulers', [
            'userHelper' => app(UserHelper::class),
            'chroniclesHelper' => app(ChroniclesHelper::class),
            'users' => User::where('users.email', 'not like', 'barbarian%@odarena.com')->orderBy('display_name')->get(),
        ]);
    }

    public function getFactions()
    {
        return view('pages.chronicles.factions', [
            'raceHelper' => app(RaceHelper::class),
            'chroniclesHelper' => app(ChroniclesHelper::class),
            'races' => Race::where('playable',1)->orderBy('name')->get(),
        ]);
    }

    public function getRound(Round $round)
    {
        if ($response = $this->guardAgainstActiveRound($round))
        {
            return $response;
        }

        $landCalculator = app(LandCalculator::class);

        $roundHelper = app(RoundHelper::class);
        $statsHelper = app(StatsHelper::class);

        $topLargestDominions = $roundHelper->getRoundDominionsByLand($round, 3);

        $gloryStats = [
            'invasion_victories',
            'op_sent_max',
            'land_conquered'
          ];

        $allDominionStatKeysForRound = $statsHelper->getAllDominionStatKeysForRound($round);

        $races = $round->dominions
            ->sortBy('race.name')
            ->pluck('race.name', 'race.id')
            ->unique();

        return view('pages.chronicles.round', [
            'gloryStats' => $gloryStats,
            'allDominionStatKeysForRound' => $allDominionStatKeysForRound,
            'round' => $round,
            'topLargestDominions' => $topLargestDominions,

            'landCalculator' => $landCalculator,

            'chroniclesHelper' => app(ChroniclesHelper::class),
            'roundHelper' => $roundHelper,
            'statsHelper' => $statsHelper,
        ]);
    }

    public function getRoundStat(Round $round, string $statKey)
    {
        if ($response = $this->guardAgainstActiveRound($round))
        {
            return $response;
        }

        $statsHelper = app(StatsHelper::class);
        $dominionStats = $statsHelper->getDominionsForRoundForStat($round, $statKey);

        return view('pages.chronicles.round-stat', [
            'round' => $round,
            'statKey' => $statKey,

            'dominionStats' => $dominionStats,

            'chroniclesHelper' => app(ChroniclesHelper::class),
            'statsHelper' => app(StatsHelper::class),
        ]);
    }

    public function getRoundRankings(Round $round)
    {
        if ($response = $this->guardAgainstActiveRound($round))
        {
            return $response;
        }

        $roundHelper = app(RoundHelper::class);

        $allDominions = $roundHelper->getRoundDominions($round, false, true);

        return view('pages.chronicles.round-rankings', [
            'round' => $round,
            'allDominions' => $allDominions,

            'landCalculator' => app(LandCalculator::class),
            'networthCalculator' => app(NetworthCalculator::class),

            'roundHelper' => $roundHelper,

        ]);
    }

    public function getRuler(string $userDisplayName)
    {
        $user = User::where('display_name', $userDisplayName)->first();

        if(!$user)
        {
            return redirect()->back()
                ->withErrors(['No such ruler found (' . $userDisplayName .').']);
        }

        $chroniclesHelper = app(ChroniclesHelper::class);
        $userHelper = app(UserHelper::class);
        $dominions = $userHelper->getUserDominions($user, false, $chroniclesHelper->getMaxRoundNumber());

        $militarySuccessStats = ['invasion_victories', 'invasion_bottomfeeds', 'op_sent_total', 'land_conquered', 'land_discovered', 'units_killed', 'units_converted'];
        $militaryFailureStats = ['defense_failures', 'land_lost', 'invasion_razes', 'invasion_failures'];

        $topRaces = $userHelper->getTopRaces($user, 10);
        $topPlacements = $userHelper->getTopPlacementsForUser($user);

        return view('pages.chronicles.ruler', [
            'landCalculator' => app(LandCalculator::class),
            'networthCalculator' => app(NetworthCalculator::class),

            'chroniclesHelper' => $chroniclesHelper,
            'roundHelper' => app(RoundHelper::class),
            'statsHelper' => app(StatsHelper::class),
            'userHelper' => $userHelper,

            'roundService' => app(RoundService::class),

            'dominions' => $dominions,
            'militarySuccessStats' => $militarySuccessStats,
            'militaryFailureStats' => $militaryFailureStats,
            'topRaces' => $topRaces,
            'topPlacements' => $topPlacements,
            'user' => $user,
        ]);
    }

    public function getDominion(Dominion $dominion)
    {
        $raceHelper = app(RaceHelper::class);

        if ($response = $this->guardAgainstActiveRound($dominion->round))
        {
            return $response;
        }

        $advancements = [];
        $techs = $dominion->techs->sortBy('key');
        $techs = $techs->sortBy(function ($tech, $key)
        {
            return $tech['name'] . str_pad($tech['level'], 2, '0', STR_PAD_LEFT);
        });

        foreach($techs as $tech)
        {
            $advancement = $tech['name'];
            $key = $tech['key'];
            $level = (int)$tech['level'];
            $advancements[$advancement] = [
                'key' => $key,
                'name' => $advancement,
                'level' => (int)$level,
                ];
        }

        return view('pages.chronicles.dominion', [
            'dominion' => $dominion,

            'advancements' => $advancements,

            'buildingCalculator' => app(BuildingCalculator::class),
            'improvementCalculator' => app(ImprovementCalculator::class),
            'landCalculator' => app(LandCalculator::class),
            'militaryCalculator' => app(MilitaryCalculator::class),
            'networthCalculator' => app(NetworthCalculator::class),
            'populationCalculator' => app(PopulationCalculator::class),
            'productionCalculator' => app(ProductionCalculator::class),
            'resourceCalculator' => app(ResourceCalculator::class),
            'spellCalculator' => app(SpellCalculator::class),
            'unitCalculator' => app(UnitCalculator::class),

            'buildingHelper' => app(BuildingHelper::class),
            'deityHelper' => app(DeityHelper::class),
            'dominionHelper' => app(DominionHelper::class),
            'improvementHelper' => app(ImprovementHelper::class),
            'landHelper' => app(LandHelper::class),
            'landImprovementHelper' => app(LandImprovementHelper::class),
            'raceHelper' => $raceHelper,
            'chroniclesHelper' => app(ChroniclesHelper::class),
            'spellHelper' => app(SpellHelper::class),
            'statsHelper' => app(StatsHelper::class),
            'techHelper' => app(TechHelper::class),
            'titleHelper' => app(TitleHelper::class),
            'unitHelper' => app(UnitHelper::class),

            'queueService' => app(QueueService::class),
            'statsService' => app(StatsService::class),
        ]);
    }

    public function getFaction(string $raceName)
    {
        $race = Race::where('name', $raceName)->firstOrFail();
        $raceHelper = app(RaceHelper::class);
        $chroniclesHelper = app(ChroniclesHelper::class);

        $militarySuccessStats = ['invasion_victories', 'invasion_bottomfeeds', 'op_sent_total', 'land_conquered', 'land_discovered', 'units_killed', 'units_converted'];
        $militaryFailureStats = ['defense_failures', 'land_lost', 'invasion_razes', 'invasion_failures'];

        return view('pages.chronicles.faction', [
            'race' => $race,

            'landCalculator' => app(LandCalculator::class),
            'networthCalculator' => app(NetworthCalculator::class),

            'chroniclesHelper' => $chroniclesHelper,
            'raceHelper' => $raceHelper,
            'statsHelper' => app(StatsHelper::class),
            'unitHelper' => app(UnitHelper::class),

            'dominions' => $raceHelper->getRaceDominions($race, false, $chroniclesHelper->getMaxRoundNumber()),
            'militarySuccessStats' => $militarySuccessStats,
            'militaryFailureStats' => $militaryFailureStats,
        ]);
    }

    // todo: search user

    /**
     * @param Round $round
     * @return Response|null
     */
    protected function guardAgainstActiveRound(Round $round)
    {
        if (!$round->hasEnded())
        {
            return redirect()->back()
                ->withErrors(['The chronicles for this round have not been finished yet. Come back after the round has ended.']);
        }

        return null;
    }

}
