<?php

namespace OpenDominion\Services\Dominion;

use Auth;

use OpenDominion\Models\Advancement;
use OpenDominion\Models\Decree;
use OpenDominion\Models\DecreeState;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\DominionDecreeState;

use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\ImprovementHelper;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Helpers\RaceHelper;
use OpenDominion\Helpers\TitleHelper;

use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;

use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Services\Dominion\ProtectionService;
use OpenDominion\Services\Dominion\StatsService;


class QuickstartService
{

    /** @var BuildingHelper */
    protected $buildingHelper;

    /** @var ImprovementHelper */
    protected $improvementHelper;

    /** @var LandHelper */
    protected $landHelper;


    /** @var RaceHelper */
    protected $raceHelper;

    /** @var TitleHelper */
    protected $titleHelper;

    /** @var BuildingCalculator */
    protected $buildingCalculator;

    /** @var ImprovementCalculator */
    protected $improvementCalculator;

    /** @var LandCalculator */
    protected $landCalculator;


    /** @var MilitaryCalculator */
    protected $militaryCalculator;

    /** @var NetworthCalculator */
    protected $networthCalculator;

    /** @var PopulationCalculator */
    protected $populationCalculator;

    /** @var SpellCalculator */
    protected $spellCalculator;

    /** @var ProtectionService */
    protected $protectionService;

    /** @var QueueService */
    protected $queueService;

    /** @var ResourceService */
    protected $resourceService;

    /** @var StatsService */
    protected $statsService;

    public function __construct()
    {
        $this->buildingHelper = app(BuildingHelper::class);
        $this->improvementHelper = app(ImprovementHelper::class);
        $this->landHelper = app(LandHelper::class);
        $this->raceHelper = app(RaceHelper::class);
        $this->titleHelper = app(TitleHelper::class);

        $this->buildingCalculator = app(BuildingCalculator::class);
        $this->improvementCalculator = app(ImprovementCalculator::class);
        $this->landCalculator = app(LandCalculator::class);
        $this->militaryCalculator = app(MilitaryCalculator::class);
        $this->networthCalculator = app(NetworthCalculator::class);
        $this->populationCalculator = app(PopulationCalculator::class);
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
land: %s
offensive_power: 0
defensive_power: 0\n",
            $dominion->race->name,
            $dominion->title->name,
            $dominion->user->display_name,
            $dominion->race->name,
            $dominion->title->name,
            $dominion->hasDeity() ? $dominion->deity->name : null,
            $dominion->land,
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
        foreach ($this->buildingCalculator->getDominionBuildingsAvailableAndOwned($dominion) as $building)
        {
            $buildings .= "    {$building->key}: " . $dominion->{'building_' . $building->key} . "\n";# {$this->buildingCalculator->getBuildingAmountOwned($dominion, $building)}\n";
        }

        $improvements = "\nimprovements:\n";
        foreach($this->improvementHelper->getImprovementsByRace($dominion->race) as $improvement)
        {
            $improvements .= "    $improvement->key: {$this->improvementCalculator->getDominionImprovementAmountInvested($dominion, $improvement)}\n";
        }

        $resources = "\nresources:\n";
        foreach($dominion->race->resources as $resourceKey)
        {
            $resources .= "    $resourceKey: {$dominion->{'resource_' . $resourceKey}}\n";
        }

        $spells = "\nspells:\n";
        foreach($this->spellCalculator->getActiveSpells($dominion) as $dominionSpell)
        {
            $spells .= "    {$dominionSpell->spell->key}: {$dominionSpell->duration},{$dominionSpell->cooldown}\n";
        }

        $advancements = "\nadvancements:\n";
        foreach($dominion->advancements->sortBy('key') as $dominionAdvancement)
        {
            $advancement = Advancement::findOrFail($dominionAdvancement->pivot->advancement_id);
            $advancements .= "    {$advancement->key}: {$dominionAdvancement->pivot->level}\n";
        }

        $techs = "\ntechs:\n";
        foreach($dominion->techs->sortBy('key') as $dominionTech)
        {
            $techs .= "    - {$dominionTech->key}\n";
        }

        $terrains = "\nterrains:\n";
        foreach($dominion->terrains->sortBy('key') as $dominionTerrain)
        {
            $terrains .= "   {$dominionTerrain->key}: {$dominionTerrain->pivot->amount}\n";
        }

        $decreeStates = "\ndecree_states:\n";
        foreach(DominionDecreeState::where('dominion_id', $dominion->id)->get() as $dominionDecreeState)
        {
            $decree = Decree::find($dominionDecreeState->decree_id);
            $decreeState = DecreeState::findOrFail($dominionDecreeState->decree_state_id);

            $decreeStates .= "    - {$decree->key},{$decreeState->key},0\n";
        }

        $units = "\nunits:\n";
        $units .= sprintf(
            "    draftees: %s\n    unit1: %s\n    unit2: %s\n    unit3: %s\n    unit4: %s\n    unit5: %s\n    unit6: %s\n    unit7: %s\n    unit8: %s\n    unit9: %s\n    unit10: %s\n    spies: %s\n    wizards: %s\n    archmages: %s\n",
            $dominion->military_draftees,
            $dominion->military_unit1,
            $dominion->military_unit2,
            $dominion->military_unit3,
            $dominion->military_unit4,
            $dominion->military_unit5,
            $dominion->military_unit6,
            $dominion->military_unit7,
            $dominion->military_unit8,
            $dominion->military_unit9,
            $dominion->military_unit10,
            $dominion->military_spies,
            $dominion->military_wizards,
            $dominion->military_archmages,
        );

        $queues = "\nqueues:\n";
        foreach($dominion->queues as $index => $queue)
        {
            $queues .= "    - {$queue->source},{$queue->resource},{$queue->hours},{$queue->amount}\n";
        }

        if(Auth::user())
        {
            $credits = "\n\n# Generated by " . Auth::user()->display_name . ", " . now() . ".";
        }
        else
        {
            $credits = "\n\n# Generated by command at " . now() . ".";
        }

        $string = $basics . $parameters . $buildings . $improvements . $resources . $spells . $advancements . $techs . $terrains . $decreeStates . $units . $queues . $credits;
        
        return $string;
    }
}
