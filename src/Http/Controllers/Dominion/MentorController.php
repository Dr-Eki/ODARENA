<?php

namespace OpenDominion\Http\Controllers\Dominion;

# Calculators
use OpenDominion\Calculators\Dominion\Actions\BankingCalculator;
use OpenDominion\Calculators\Dominion\Actions\ConstructionCalculator;
use OpenDominion\Calculators\Dominion\Actions\ExplorationCalculator;
use OpenDominion\Calculators\Dominion\Actions\RezoningCalculator;
use OpenDominion\Calculators\Dominion\Actions\TechCalculator;
use OpenDominion\Calculators\Dominion\Actions\TrainingCalculator;

use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\EspionageCalculator;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\ProductionCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;

use OpenDominion\Calculators\NetworthCalculator;

# Helpers
use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\EspionageHelper;
use OpenDominion\Helpers\ImprovementHelper;
use OpenDominion\Helpers\RaceHelper;
use OpenDominion\Helpers\SpellHelper;
use OpenDominion\Helpers\TechHelper;
use OpenDominion\Helpers\TitleHelper;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Helpers\MentorHelper;

# Services
use OpenDominion\Services\Dominion\QueueService;

class MentorController extends AbstractDominionController
{

    public function getMentor()
    {
        return redirect()->route('dominion.mentor.general');
    }


    public function getMentorGeneral()
    {
        return view('pages.dominion.mentor.general',
        [
            'constructionCalculator' => app(ConstructionCalculator::class),
            'buildingCalculator' => app(BuildingCalculator::class),
            'buildingHelper' => app(BuildingHelper::class),
        ]);
    }

    public function getMentorAdvancements()
    {

        $dominion = $this->getSelectedDominion();
        $techs = $dominion->techs->sortBy(function ($tech, $key)
        {
            return $tech['name'] . str_pad($tech['level'], 2, '0', STR_PAD_LEFT);
        });

        return view('pages.dominion.mentor.advancements',
        [
            'techs' => $techs,
            'techCalculator' => app(TechCalculator::class),
            'productionCalculator' => app(ProductionCalculator::class),
            'techHelper' => app(TechHelper::class),
        ]);
    }

    public function getMentorBuildings()
    {
        return view('pages.dominion.mentor.buildings',
        [
            'constructionCalculator' => app(ConstructionCalculator::class),
            'buildingCalculator' => app(BuildingCalculator::class),
            'buildingHelper' => app(BuildingHelper::class),
        ]);
    }

    public function getMentorEspionage()
    {
        return view('pages.dominion.mentor.espionage',
        [
            'espionageCalculator' => app(EspionageCalculator::class),
            'espionageHelper' => app(EspionageHelper::class),
        ]);
    }

    public function getMentorExplore()
    {
        return view('pages.dominion.mentor.explore',
        [
            'explorationCalculator' => app(ExplorationCalculator::class),
            'landCalculator' => app(LandCalculator::class),
        ]);
    }

    public function getMentorInvade()
    {
        return view('pages.dominion.mentor.invade',
        [
            'militaryCalculator' => app(MilitaryCalculator::class),
            'unitHelper' => app(UnitHelper::class),
        ]);
    }

    public function getMentorMagic()
    {
        return view('pages.dominion.mentor.magic',
        [
            'spellCalculator' => app(SpellCalculator::class),
            'spellHelper' => app(SpellHelper::class),
        ]);
    }

    public function getMentorMilitary()
    {
        return view('pages.dominion.mentor.military',
        [
            'productionCalculator' => app(ProductionCalculator::class),
            'militaryCalculator' => app(MilitaryCalculator::class),
            'unitHelper' => app(UnitHelper::class),
            'queueService' => app(QueueService::class),
        ]);
    }


}
