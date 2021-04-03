<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\ProductionCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Helpers\SpellHelper;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Services\Dominion\QueueService;

# ODA
use DB;
use OpenDominion\Helpers\HistoryHelper;
use OpenDominion\Calculators\RealmCalculator;
use OpenDominion\Helpers\RaceHelper;
use OpenDominion\Calculators\Dominion\LandImprovementCalculator;
use OpenDominion\Models\Spell;

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
            'realmCalculator' => app(RealmCalculator::class),
            'raceHelper' => app(RaceHelper::class),
        ]);
    }

    public function getAdvisorsMilitary()
    {
        return view('pages.dominion.advisors.military', [
            'queueService' => app(QueueService::class),
            'unitHelper' => app(UnitHelper::class),
        ]);
    }

    public function getAdvisorsLand()
    {
        return view('pages.dominion.advisors.land', [
            'landCalculator' => app(LandCalculator::class),
            'landHelper' => app(LandHelper::class),
            'queueService' => app(QueueService::class),
            'landImprovementCalculator' => app(LandImprovementCalculator::class),
            'militaryCalculator' => app(MilitaryCalculator::class),
        ]);
    }

    public function getAdvisorsConstruction()
    {
        return view('pages.dominion.advisors.construction', [
            'buildingCalculator' => app(BuildingCalculator::class),
            'buildingHelper' => app(BuildingHelper::class),
            'landCalculator' => app(LandCalculator::class),
            'queueService' => app(QueueService::class),
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
        ]);
    }

    public function getAdvisorsStatistics()
    {
        return view('pages.dominion.advisors.statistics', [
            'landCalculator' => app(LandCalculator::class),
            'militaryCalculator' => app(MilitaryCalculator::class),
            'populationCalculator' => app(PopulationCalculator::class),
            'unitHelper' => app(UnitHelper::class),
            'raceHelper' => app(RaceHelper::class),
        ]);
    }

    public function getHistory()
    {
        $resultsPerPage = 25;
        $selectedDominion = $this->getSelectedDominion();

        $history = DB::table('dominion_history')
                            ->where('dominion_history.dominion_id', '=', $selectedDominion->id)
                            ->orderBy('dominion_history.created_at', 'desc')
                            ->get();


        return view('pages.dominion.advisors.history', [
            'historyHelper' => app(HistoryHelper::class),
            'history' => $history
        ]);
    }

}
