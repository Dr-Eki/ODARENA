<?php

namespace OpenDominion\Services\Dominion;

use Auth;
use DB;
use Symfony\Component\Yaml\Yaml;
use OpenDominion\Exceptions\GameException;

use OpenDominion\Models\Advancement;
use OpenDominion\Models\Decree;
use OpenDominion\Models\DecreeState;
use OpenDominion\Models\Deity;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\DominionAdvancement;
use OpenDominion\Models\DominionDecreeState;
use OpenDominion\Models\DominionDeity;
use OpenDominion\Models\DominionSpell;
use OpenDominion\Models\DominionState;
use OpenDominion\Models\Spell;

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

use OpenDominion\Services\Dominion\ProtectionService;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Services\Dominion\ResourceService;
use OpenDominion\Services\Dominion\StatsService;

class DominionStateService
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

        $this->protectionService = app(ProtectionService::class);
        $this->queueService = app(QueueService::class);
        $this->resourceService = app(ResourceService::class);
        $this->statsService = app(StatsService::class);
    }

    public function saveDominionState(Dominion $dominion): bool
    {
        $dominionState = $this->generateDominionState($dominion);

        $stateData = Yaml::parse($dominionState, Yaml::PARSE_OBJECT_FOR_MAP);
        
        $dominionState = DominionState::updateOrCreate(['dominion_id' => $dominion->id, 'dominion_protection_tick' => $dominion->protection_ticks],
        [
            'dominion_id' => $dominion->id,
            'dominion_protection_tick' => $dominion->protection_ticks,
            
            'daily_land' => object_get($stateData, 'daily_land', 0),
            'daily_gold' => object_get($stateData, 'daily_gold', 0),
            'monarchy_vote_for_dominion_id' => object_get($stateData, 'monarchy_vote_for_dominion_id', null),
            'tick_voted' => object_get($stateData, 'tick_voted', null),
            'most_recent_improvement_resource' => object_get($stateData, 'most_recent_improvement_resource', 'gems'),
            'most_recent_exchange_from' => object_get($stateData, 'most_recent_exchange_from', 'gold'),
            'most_recent_exchange_to' => object_get($stateData, 'most_recent_exchange_from', 'gold'),
            'notes' => object_get($stateData, 'notes', null),
            'deity' => object_get($stateData, 'deity', null),
            'devotion_ticks' => object_get($stateData, 'devotion_ticks'),
            'draft_rate' => object_get($stateData, 'draft_rate'),
            'morale' => object_get($stateData, 'morale'),
            'peasants' => object_get($stateData, 'peasants'),
            'peasants_last_hour' => object_get($stateData, 'peasants_last_hour'),
            'prestige' => object_get($stateData, 'prestige'),
            'xp' => object_get($stateData, 'xp'),
            'spy_strength' => object_get($stateData, 'spy_strength'),
            'wizard_strength' => object_get($stateData, 'wizard_strength'),
            'protection_ticks' => object_get($stateData, 'protection_ticks'),
            'ticks' => object_get($stateData, 'ticks'),
    
            'buildings' => object_get($stateData, 'buildings', []),
            'improvements' => object_get($stateData, 'improvements', []),
            'land' => object_get($stateData, 'land', []),
            'resources' => object_get($stateData, 'resources', []),
            'spells' => object_get($stateData, 'spells', []),
            'advancements' => object_get($stateData, 'advancements', []),
            'decree_states' => object_get($stateData, 'decree_states', []),
            'units' => object_get($stateData, 'units', []),
            'queues' => object_get($stateData, 'queues', []),
        ]);

        return (is_a($dominionState, 'OpenDominion\Models\DominionState') ? true : false);
    }

    public function restoreDominionState(Dominion $dominion, DominionState $dominionState)
    {
        DB::transaction(function () use ($dominion, $dominionState) {
            
            // Validate
            if($dominion->id !== $dominionState->dominion_id)
            {
                throw new GameException('The dominion state does not match the selected dominion.');
            }

            if(!$this->protectionService->canDelete($dominion))
            {
                throw new GameException('You can no longer revert to previous states.');
            }

            if(!in_array(request()->getHost(),['odarena.local', 'odarena.virtual', 'sim.odarena.com']))
            {
                throw new GameException('You can only restore a previous dominion state on the sim or local test environments.');
            }

            // Delete all subsequent dominionStates
            DominionState::where('dominion_id', $dominion->id)->where('dominion_protection_tick', '<', $dominionState->dominion_protection_tick)->delete();

            // Basics
            $dominion->daily_land = $dominionState->daily_land;
            $dominion->daily_gold = $dominionState->daily_gold;
            $dominion->monarchy_vote_for_dominion_id = $dominionState->monarchy_vote_for_dominion_id;
            $dominion->tick_voted = $dominionState->tick_voted;
            $dominion->most_recent_improvement_resource = $dominionState->most_recent_improvement_resource;
            $dominion->most_recent_exchange_from = $dominionState->most_recent_exchange_from;
            $dominion->most_recent_exchange_to = $dominionState->most_recent_exchange_to;
            $dominion->notes = $dominionState->notes;
            $dominion->draft_rate = $dominionState->draft_rate;
            $dominion->morale = $dominionState->morale;
            $dominion->peasants = $dominionState->peasants;
            $dominion->peasants_last_hour = $dominionState->peasants_last_hour;
            $dominion->prestige = $dominionState->prestige;
            $dominion->xp = $dominionState->xp;
            $dominion->spy_strength = $dominionState->spy_strength;
            $dominion->wizard_strength = $dominionState->wizard_strength;
            $dominion->protection_ticks = $dominionState->protection_ticks;
            $dominion->ticks = $dominionState->ticks;

            // Delete buildings
            foreach($dominion->buildings as $dominionBuilding)
            {
                $this->buildingCalculator->removeBuildings($dominion, [$dominionBuilding->key => ['builtBuildingsToDestroy' => $dominionBuilding->pivot->owned]]);
            }

            // Add buildings
            foreach($dominionState->buildings as $buildingKey => $amount)
            {
                $this->buildingCalculator->createOrIncrementBuildings($dominion, [$buildingKey => $amount]);
            }
    
            // Delete improvements
            foreach($dominion->improvements as $dominionImprovement)
            {
                $this->improvementCalculator->decreaseImprovements($dominion, [$dominionImprovement->key => $dominionImprovement->pivot->invested]);
            }

            // Add improvements
            foreach($dominionState->improvements as $improvementKey => $amount)
            {
                $this->improvementCalculator->createOrIncrementImprovements($dominion, [$improvementKey => $amount]);
            }
    
            // Delete resources
            foreach($dominion->resources as $resource)
            {
                $amountOwned = $this->resourceCalculator->getAmount($dominion, $resource->key);
                $amountToRemove = $amountOwned * -1;
                $this->resourceService->updateResources($dominion, [$resource->key => $amountToRemove]);
            }

            // Add resources
            foreach($dominionState->resources as $resourceKey => $amount)
            {
                $this->resourceService->updateResources($dominion, [$resourceKey => $amount]);
            }
    
            // Delete spells
            DB::table('dominion_spells')->where('dominion_id', '=', $dominion->id)->delete();
            DB::table('dominion_spells')->where('caster_id', '=', $dominion->id)->delete();

            // Add spells
            foreach($dominionState->spells as $spellKey => $spellRow)
            {
                $spellData = explode(',', $spellRow);
                $duration = (int)$spellData[0];
                $cooldown = (int)$spellData[1];
                $spell = Spell::where('key', $spellKey)->first();

                DominionSpell::updateOrCreate(['dominion_id' => $dominion->id, 'spell_id' => $spell->id],
                [
                    'caster_id' => $dominion->id,
                    'duration' => $duration,
                    'cooldown' => $cooldown
                ]);
            }

            // Delete deity
            DB::table('dominion_deity')->where('dominion_id', '=', $dominion->id)->delete();

            // Add deity
            if($dominionState->deity)
            {
                $deity = Deity::where('name', $dominionState->deity)->first();
                DominionDeity::updateOrCreate(['dominion_id' => $dominion->id, 'deity_id' => $deity->id],
                [
                    'duration' => $dominionState->devotion_ticks
                ]);
            }

            // Delete advancements
            DB::table('dominion_advancements')->where('dominion_id', '=', $dominion->id)->delete();

            // Add advancements
            foreach($dominionState->advancements as $advancementKey => $level)
            {
                $advancement = Advancement::where('key', $advancementKey)->first();

                DominionAdvancement::updateOrCreate(['dominion_id' => $dominion->id, 'advancement_id' => $advancement->id],
                [
                    'level' => $level
                ]);
            }
    
            // Delete queues
            DB::table('dominion_queue')->where('dominion_id', '=', $dominion->id)->delete();
    
            // Add queues
            foreach($dominionState->queues as $queueRow)
            {
                $rowData = explode(',', $queueRow);
                $source = (string)$rowData[0];
                $resource = (string)$rowData[1];
                $ticks = (int)$rowData[2];
                $amount = (int)$rowData[3];

                $this->queueService->queueResources($source, $dominion, [$resource => $amount], $ticks);
            }
    
            // Delete decree states
            DB::table('dominion_decree_states')->where('dominion_id', '=', $dominion->id)->delete();
    
            // Add decree states
            foreach($dominionState->decree_states as $dominionDecreeStateRow)
            {
                $dominionDecreeStateData = explode(',', $dominionDecreeStateRow);
                $decreeKey = $dominionDecreeStateData[0];
                $decreeStateKey = $dominionDecreeStateData[1];
                $decreeIssueTick = $dominionDecreeStateData[2];

                $decree = Decree::where('key', $decreeKey)->first();
                $decreeState = DecreeState::where('key', $decreeStateKey)->first();

                DominionDecreeState::updateOrCreate(['dominion_id' => $dominion->id, 'decree_id' => $decree->id],
                [
                    'decree_state_id' => $decreeState->id,
                    'tick' => $decreeIssueTick
                ]);
            }
    
            // Update units
            foreach($dominionState->units as $unitType => $amount)
            {
                $dominion->{'military_' . $unitType} = $amount;
            }
    
            // Update land
            foreach($dominionState->land as $landType => $amount)
            {
                $dominion->{'land_' . $landType} = $amount;
            }

            $dominion->save();
        });

        return [
            'message' => sprintf('You have restored your dominion as it was when you had %s %s left.',
                    $dominionState->dominion_protection_tick,
                    str_plural('tick', $dominionState->dominion_protection_tick)
                ),
            'alert-type' => 'success',
            'redirect' => route('dominion.status')
        ];

    }

    public function generateDominionState(Dominion $dominion)
    {
        $basics = sprintf(
"
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
ticks: %s
\n",
            (int)$dominion->daily_land,
            (int)$dominion->daily_gold,
            $dominion->monarchy_vote_for_dominion_id,
            $dominion->tick_voted,
            $dominion->most_recent_improvement_resource,
            $dominion->most_recent_exchange_from,
            $dominion->most_recent_exchange_to,
            $dominion->notes,
            $dominion->hasDeity() ? $dominion->deity->name : null,
            $dominion->hasDeity() ? (int)$dominion->devotion->duration : (int)0,
            (int)$dominion->draft_rate,
            (int)$dominion->morale,
            (int)$dominion->peasants,
            $dominion->peasants_last_hour,
            (float)$dominion->prestige,
            $dominion->xp,
            (int)$dominion->spy_strength,
            (int)$dominion->wizard_strength,
            (int)$dominion->protection_ticks,
            (int)$dominion->ticks,
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