<?php

namespace OpenDominion\Services\Dominion;

use Auth;

use OpenDominion\Models\Advancement;
use OpenDominion\Models\Decree;
use OpenDominion\Models\DecreeState;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\DominionDecreeState;
use OpenDominion\Models\DominionTickState;

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


class DominionTickStateService
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

    public function saveDominionTickState(Dominion $dominion)
    {
        $dominionTickState = $this->generateDominionTickState($dominion);

        DominionTickState::create();
    }

    public function restoreDominionTickState(DominionTickState $dominionTickState)
    {
        //
    }

    protected function generateDominionTickState(Dominion $dominion)
    {

        $basics = sprintf(
"
tick: %s
daily_land: %s
daily_gold: %s
monarchy_vote_for_dominion_id: %s
tick_voted: %s
most_recent_improvement_resource: %s
most_recent_exchange_from: %s
most_recent_exchange_to: %s
notes: %s
deity: %s
devotion_ticks: %s
draft_rate: %s
morale: %s
peasants: %s
peasants_last_hour: %s
prestige: %s
xp: %s
spy_strength: %s
wizard_strength: %s
protection_ticks: %s
\n",
            $dominion->round->tick,
            $dominion->daily_land,
            $dominion->daily_gold,
            $dominion->monarchy_vote_for_dominion_id,
            $dominion->tick_voted,
            $dominion->most_recent_improvement_resource,
            $dominion->most_recent_exchange_from,
            $dominion->most_recent_exchange_to,
            $dominion->notes,
            $dominion->hasDeity() ? $dominion->deity->name : null,
            $dominion->hasDeity() ? $dominion->devotion->duration : 0,
            $dominion->draft_rate,
            $dominion->morale,
            $dominion->peasants,
            $dominion->peasants_last_hour,
            $dominion->prestige,
            $dominion->xp,
            $dominion->spy_strength,
            $dominion->wizard_strength,
            $dominion->protection_ticks,
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

        $advancements = "\nadvancements:\n";
        foreach($dominion->advancements->sortBy('key') as $dominionAdvancement)
        {
            $advancement = Advancement::findOrFail($dominionAdvancement->pivot->advancement_id);
            $advancements .= "    {$advancement->key}: {$dominionAdvancement->pivot->level}\n";
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

        $string = $basics . $buildings . $improvements . $land . $resources . $spells . $advancements . $decreeStates . $units . $queues;
        
        return $string;
    }
}
