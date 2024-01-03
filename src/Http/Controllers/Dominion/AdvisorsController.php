<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Calculators\RealmCalculator;
#use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MagicCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\ProductionCalculator;
use OpenDominion\Calculators\Dominion\ResourceCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
#use OpenDominion\Helpers\BuildingHelper;
#use OpenDominion\Helpers\LandHelper;
use OpenDominion\Helpers\SpellHelper;
use OpenDominion\Helpers\StatsHelper;
use OpenDominion\Helpers\UnitHelper;
#use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Services\Dominion\StatsService;

#use DB;
use OpenDominion\Helpers\HistoryHelper;
use OpenDominion\Helpers\RaceHelper;

use OpenDominion\Models\DominionHistory;
use OpenDominion\Models\DominionStat;
use OpenDominion\Models\Spell;
#use OpenDominion\Models\Stat;

class AdvisorsController extends AbstractDominionController
{
    public function getAdvisors()
    {
        return redirect()->route('dominion.advisors.production');
    }

    public function getAdvisorsProduction()
    {
        return view('pages.dominion.advisors.production', [
            'populationCalculator' => app(PopulationCalculator::class),
            'productionCalculator' => app(ProductionCalculator::class),
            'landCalculator' => app(LandCalculator::class),
            'magicCalculator' => app(LandCalculator::class),
            'realmCalculator' => app(RealmCalculator::class),
            'raceHelper' => app(RaceHelper::class),
            'resourceCalculator' => app(ResourceCalculator::class),
            'statsService' => app(StatsService::class),
        ]);
    }

    public function getAdvisorsMagic()
    {
        $spellHelper = app(SpellHelper::class);
        $spellCalculator = app(SpellCalculator::class);
        $activeSpells = $spellCalculator->getActiveSpells($this->getSelectedDominion());

        foreach($activeSpells as $spell)
        {
            $activeSpellKeys[] = $spell->spell;
        }

        if(count($activeSpells) > 0)
        {
            $activeSpells = Spell::all()->whereIn('key',$activeSpellKeys)->keyBy('key');
        }
        else
        {
            $activeSpells = null;
        }

        return view('pages.dominion.advisors.magic', [
            'spellCalculator' => $spellCalculator,
            'spellHelper' => $spellHelper,
            'activeSpells' => $activeSpells,
            'magicCalculator' => app(LandCalculator::class),
        ]);
    }

    public function getAdvisorsMilitary()
    {
        return view('pages.dominion.advisors.military', [
            'landCalculator' => app(LandCalculator::class),
            'magicCalculator' => app(LandCalculator::class),
            'militaryCalculator' => app(MilitaryCalculator::class),
            'populationCalculator' => app(PopulationCalculator::class),
            'unitHelper' => app(UnitHelper::class),
            'raceHelper' => app(RaceHelper::class),
            'statsService' => app(StatsService::class),
        ]);
    }

    public function getAdvisorsStatistics()
    {
        $selectedDominion = $this->getSelectedDominion();
        $statsHelper = app(StatsHelper::class);

        $dominionStats = DominionStat::where('dominion_id', $selectedDominion->id)->get();


        return view('pages.dominion.advisors.statistics', [
            'dominionStats' => $dominionStats,
            'landCalculator' => app(LandCalculator::class),
            'magicCalculator' => app(LandCalculator::class),
            'militaryCalculator' => app(MilitaryCalculator::class),
            'populationCalculator' => app(PopulationCalculator::class),
            'unitHelper' => app(UnitHelper::class),
            'raceHelper' => app(RaceHelper::class),
            'statsService' => app(StatsService::class),
        ]);
    }

    public function getHistory()
    {
        $resultsPerPage = 25;
        $selectedDominion = $this->getSelectedDominion();

        $dominionHistory = DominionHistory::where('dominion_id', $selectedDominion->id)->orderBy('created_at','desc')->paginate(20);

        /*
        $history = DB::table('dominion_history')
                            ->where('dominion_history.dominion_id', '=', $selectedDominion->id)
                            ->orderBy('dominion_history.created_at', 'desc')
                            ->get();
        */

        $history = $dominionHistory;


        return view('pages.dominion.advisors.history', [
            'historyHelper' => app(HistoryHelper::class),
            'history' => $history
        ]);
    }

}
