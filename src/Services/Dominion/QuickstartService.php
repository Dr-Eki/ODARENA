<?php

namespace OpenDominion\Services\Dominion;

use Auth;

use OpenDominion\Models\Dominion;
use OpenDominion\Models\DominionInsight;

use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\ImprovementHelper;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Helpers\LandImprovementHelper;
use OpenDominion\Helpers\RaceHelper;
use OpenDominion\Helpers\TitleHelper;

use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\LandImprovementCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\ResourceCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;

use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Services\Dominion\ProtectionService;
use OpenDominion\Services\Dominion\StatsService;


class QuickstartService
{

    public function __construct()
    {
        $this->buildingHelper = app(BuildingHelper::class);
        $this->improvementHelper = app(ImprovementHelper::class);
        $this->landHelper = app(LandHelper::class);
        $this->landImprovementHelper = app(LandImprovementHelper::class);
        $this->raceHelper = app(RaceHelper::class);
        $this->titleHelper = app(TitleHelper::class);

        $this->buildingCalculator = app(BuildingCalculator::class);
        $this->improvementCalculator = app(ImprovementCalculator::class);
        $this->landCalculator = app(LandCalculator::class);
        $this->landImprovementCalculator = app(LandImprovementCalculator::class);
        $this->militaryCalculator = app(MilitaryCalculator::class);
        $this->networthCalculator = app(NetworthCalculator::class);
        $this->populationCalculator = app(PopulationCalculator::class);
        $this->resourceCalculator = app(ResourceCalculator::class);
        $this->spellCalculator = app(SpellCalculator::class);

        $this->statsService = app(StatsService::class);
        $this->protectionService = app(ProtectionService::class);
        $this->queueService = app(QueueService::class);
    }

    public function generateQuickstartFile(Dominion $dominion)
    {

        $basics = sprintf(
"
name: %s (%s) Quickstart by %s
description: 
race: %s
title: %s
deity: %s
offensive_power: 0
defensive_power: 0\n",
            $dominion->race->name,
            $dominion->title->name,
            Auth::user()->display_name,
            $dominion->race->name,
            $dominion->title->name,
            $dominion->hasDeity() ? $dominion->deity->name : null,
          );

        $parameters = sprintf(
"draft_rate: %s
morale: %s
prestige: %s
peasants: %s
protection_ticks: %s
devotion_ticks: %s
spy_strength: %s
wizard_strength: %s
xp: %s\n",
            $dominion->draft_rate,
            $dominion->morale,
            $dominion->prestige,
            $dominion->peasants,
            $dominion->protection_ticks,
            $dominion->hasDeity() ? $dominion->devotion->duration : 0,
            $dominion->spy_strength,
            $dominion->wizard_strength,
            $dominion->xp
          );

        $buildings = "\nbuildings:\n";
        foreach ($this->buildingHelper->getBuildingsByRace($dominion->race) as $building)
        {
            $buildings .= "    {$building->key}: {$this->buildingCalculator->getBuildingAmountOwned($dominion, $building)}\n";
        }

        $improvements = "\nimprovements:\n";
        foreach($this->improvementHelper->getImprovementsByRace($dominion->race) as $improvement)
        {
            $improvements .= "    $improvement->key: {$this->improvementCalculator->getDominionImprovementAmountInvested($dominion, $improvement)}\n";
        }

        $land = "\nland:\n";
        foreach ($this->landHelper->getLandTypes() as $landType)
        {
            $land .= "    $landType: " . $dominion->{'land_' . $landType} . "\n";
        }

        $resources = "\nresources:\n";
        foreach($dominion->race->resources as $resourceKey)
        {
            $resources .= "    $resourceKey: {$this->resourceCalculator->getAmount($dominion, $resourceKey)}\n";
        }

        $spells = "\nspells:\n";
        foreach($this->spellCalculator->getActiveSpells($dominion) as $dominionSpell)
        {
            $spells .= "    {$dominionSpell->spell->key}: {$dominionSpell->duration},{$dominionSpell->cooldown}\n";
        }

        $techs = "\ntechs:\n";
        foreach($dominion->techs->sortBy('key') as $tech)
        {
            $techs .= "    - {$tech->key}\n";
        }

        $units = "\nunits:\n";
        $units .= sprintf(
            "    draftees: %s\n    unit1: %s\n    unit2: %s\n    unit3: %s\n    unit4: %s\n    spies: %s\n    wizards: %s\n    archmages: %s",
            $dominion->military_draftees,
            $dominion->military_unit1,
            $dominion->military_unit2,
            $dominion->military_unit3,
            $dominion->military_unit4,
            $dominion->military_spies,
            $dominion->military_wizards,
            $dominion->military_archmages,
        );

        $credits = "\n\n# Generated by " . Auth::user()->display_name . ", " . now() . ".";

        $string = $basics . $parameters . $buildings . $improvements . $land . $resources . $spells . $techs . $units . $credits;
        
        return $string;
    }
}