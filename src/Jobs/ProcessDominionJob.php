<?php

namespace OpenDominion\Jobs;

use DB;
use Log;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

use OpenDominion\Models\Artefact;
use OpenDominion\Models\Deity;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\Improvement;
use OpenDominion\Models\Spell;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Tech;
use OpenDominion\Models\Dominion\Tick;

use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\ConversionCalculator;
use OpenDominion\Calculators\Dominion\EspionageCalculator;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Calculators\Dominion\MoraleCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\ProductionCalculator;
use OpenDominion\Calculators\Dominion\ResourceCalculator;
use OpenDominion\Calculators\Dominion\SorceryCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Calculators\Dominion\UnitCalculator;

use OpenDominion\Services\BarbarianService;
use OpenDominion\Services\NotificationService;
use OpenDominion\Services\Dominion\ArtefactService;
use OpenDominion\Services\Dominion\DeityService;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Services\Dominion\InsightService;
use OpenDominion\Services\Dominion\ProtectionService;
use OpenDominion\Services\Dominion\ResourceService;
use OpenDominion\Services\Dominion\ResearchService;
use OpenDominion\Services\Dominion\TerrainService;
use OpenDominion\Services\Dominion\QueueService;


class ProcessDominionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $dominion;
    protected $temporaryData = [];
    protected $now;

    protected $buildingCalculator;
    protected $conversionCalculator;
    protected $espionageCalculator;
    protected $improvementCalculator;
    protected $moraleCalculator;
    protected $populationCalculator;
    protected $productionCalculator;
    protected $resourceCalculator;
    protected $sorceryCalculator;
    protected $spellCalculator;
    protected $unitCalculator;
    
    protected $artefactService;
    protected $barbarianService;
    protected $deityService;
    protected $insightService;
    protected $notificationService;
    protected $protectionService;
    protected $queueService;
    protected $researchService;
    protected $resourceService;
    protected $terrainService;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($dominion)
    {
        $this->now = now();
        $this->dominion = $dominion;

        $this->buildingCalculator = app(BuildingCalculator::class);
        $this->conversionCalculator = app(ConversionCalculator::class);
        $this->espionageCalculator = app(EspionageCalculator::class);
        $this->improvementCalculator = app(ImprovementCalculator::class);
        $this->moraleCalculator = app(MoraleCalculator::class);
        $this->populationCalculator = app(PopulationCalculator::class);
        $this->productionCalculator = app(ProductionCalculator::class);
        $this->resourceCalculator = app(ResourceCalculator::class);
        $this->sorceryCalculator = app(SorceryCalculator::class);
        $this->spellCalculator = app(SpellCalculator::class);
        $this->unitCalculator = app(UnitCalculator::class);

        $this->artefactService = app(ArtefactService::class);
        $this->barbarianService = app(BarbarianService::class);
        $this->deityService = app(DeityService::class);
        $this->insightService = app(InsightService::class);
        $this->notificationService = app(NotificationService::class);
        $this->protectionService = app(ProtectionService::class);
        $this->queueService = app(QueueService::class);
        $this->researchService = app(ResearchService::class);
        $this->resourceService = app(ResourceService::class);
        $this->terrainService = app(TerrainService::class);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $round = $this->dominion->round;

        Log::debug('** Processing dominion ' . $this->dominion->name . ' (# ' . $this->dominion->realm->number . ' ), ID ');
        # Make a DB transaction
        DB::transaction(function () use ($round)
        {    
            $this->temporaryData[$round->id][$this->dominion->id] = [];

            #$this->temporaryData[$round->id][$this->dominion->id]['units_generated'] = $this->unitCalculator->getUnitsGenerated($this->dominion);
            $this->temporaryData[$round->id][$this->dominion->id]['units_attrited'] = $this->unitCalculator->getUnitsAttrited($this->dominion);

            if(config('game.extended_logging')) { Log::debug('** Handle Barbarians stuff (if this dominion is a Barbarian)'); }
            $this->handleBarbarians($this->dominion);

            if(config('game.extended_logging')) { Log::debug('** Updating buildings'); }
            $this->handleCaptureInsight($this->dominion);

            if(config('game.extended_logging')) { Log::debug('** Updating buildings'); }
            $this->handleBuildings($this->dominion);

            if(config('game.extended_logging')) { Log::debug('** Updating terrain'); }
            $this->handleTerrain($this->dominion);

            if(config('game.extended_logging')){ Log::debug('** Updating improvments'); }
            $this->handleImprovements($this->dominion);

            if(config('game.extended_logging')){ Log::debug('** Updating deities'); }
            $this->handleDeities($this->dominion);

            if(config('game.extended_logging')){ Log::debug('** Updating artefacts'); }
            $this->handleArtefacts($this->dominion);

            if(config('game.extended_logging')){ Log::debug('** Updating research'); }
            $this->handleResearch($this->dominion);

            if(config('game.extended_logging')){ Log::debug('** Updating units'); }
            $this->handleUnits($this->dominion);

            if(config('game.extended_logging')) { Log::debug('** Updating resources'); }
            $this->handleResources($this->dominion);

            if(config('game.extended_logging')) { Log::debug('** Handle stasis'); }
            $this->handleStasis($this->dominion);

            if(config('game.extended_logging')) { Log::debug('** Handle Pestilence'); }
            $this->handlePestilence($this->dominion);

            if(config('game.extended_logging')) { Log::debug('** Handle land generation'); }
            $this->handleLandGeneration($this->dominion);

            if(config('game.extended_logging')) { Log::debug('** Handle unit generation'); }
            $this->handleUnitGeneration($this->dominion);

            if(config('game.extended_logging')) { Log::debug('** Queue notifications'); }
            $this->queueNotifications($this->dominion);

            if(config('game.extended_logging')) { Log::debug('** Updating spells'); }
            $this->updateSpells($this->dominion);
            
            if(config('game.extended_logging')) { Log::debug('** Cleaning up active spells'); }
            $this->cleanupActiveSpells($this->dominion);

            if(config('game.extended_logging')) { Log::debug('** Cleaning up queues'); }
            $this->cleanupQueues($this->dominion);

            if(config('game.extended_logging')) { Log::debug('** Sending notifications (hourly_dominion)'); }
            $this->notificationService->sendNotifications($this->dominion, 'hourly_dominion');

            if(config('game.extended_logging')) { Log::debug('** Precalculate tick'); }
            $this->precalculateTick($this->dominion, true);

        });

        if(config('game.extended_logging')) { Log::debug('** Audit and repair terrain'); }
        $this->terrainService->auditAndRepairTerrain($this->dominion);
    }

    # Take buildings that are one tick away from finished and create or increment DominionBuildings.
    private function handleBuildings(Dominion $dominion): void
    {
        $finishedBuildingsInQueue = DB::table('dominion_queue')
                                        ->where('dominion_id',$dominion->id)
                                        ->where('resource', 'like', 'building%')
                                        ->where('hours',1)
                                        ->get();

        foreach($finishedBuildingsInQueue as $finishedBuildingInQueue)
        {
            $buildingKey = str_replace('building_', '', $finishedBuildingInQueue->resource);
            $amount = intval($finishedBuildingInQueue->amount);
            #$building = Building::where('key', $buildingKey)->first();
            $this->buildingCalculator->createOrIncrementBuildings($dominion, [$buildingKey => $amount]);
        }

        # Handle self-destruct
        if($buildingsDestroyed = $dominion->tick->buildings_destroyed)
        {
            $this->buildingCalculator->removeBuildings($dominion, $buildingsDestroyed);
        }
    }

    # Take improvements that are one tick away from finished and create or increment DominionImprovements.
    private function handleImprovements(Dominion $dominion): void
    {
        $finishedImprovementsInQueue = DB::table('dominion_queue')
                                        ->where('dominion_id',$dominion->id)
                                        ->where('resource', 'like', 'improvement%')
                                        ->where('hours',1)
                                        ->get();
        foreach($finishedImprovementsInQueue as $finishedImprovementInQueue)
        {
            $improvementKey = str_replace('improvement_', '', $finishedImprovementInQueue->resource);
            $amount = intval($finishedImprovementInQueue->amount);
            $improvement = Improvement::where('key', $improvementKey)->first();
            $this->improvementCalculator->createOrIncrementImprovements($dominion, [$improvementKey => $amount]);
        }

        # Impterest
        if(
            ($improvementInterestPerk = $dominion->race->getPerkValue('improvements_interest')) or
            ($improvementInterestPerk = (mt_rand($dominion->race->getPerkValue('improvements_interest_random_min')*100, $dominion->race->getPerkValue('improvements_interest_random_max')*100))/100)
            )
        {
            $multiplier = 1;
            $multiplier += $dominion->getBuildingPerkMultiplier('improvements_interest');
            $multiplier += $dominion->getSpellPerkMultiplier('improvements_interest');
            $multiplier += $dominion->getImprovementPerkMultiplier('improvements_interest');
            $multiplier += $dominion->getAdvancementPerkMultiplier('improvements_interest');
            $multiplier += $dominion->getDeityPerkMultiplier('improvements_interest');
            $multiplier += $dominion->getTechPerkMultiplier('improvements_interest_mod');

            $improvementInterestPerk *= $multiplier;

            foreach($this->improvementCalculator->getDominionImprovements($dominion) as $dominionImprovement)
            {
                $improvement = Improvement::where('id', $dominionImprovement->improvement_id)->first();
                $interest = floor($dominionImprovement->invested * ($improvementInterestPerk / 100));
                if($interest > 0)
                {
                    $this->improvementCalculator->createOrIncrementImprovements($dominion, [$improvement->key => $interest]);
                }
                elseif($interest < 0)
                {
                    $this->improvementCalculator->decreaseImprovements($dominion, [$improvement->key => $interest*-1]);
                }
            }
        }
    }

    # Take deities that are one tick away from finished and create or increment DominionImprovements.
    private function handleDeities(Dominion $dominion): void
    {
        $finishedDeitiesInQueue = DB::table('dominion_queue')
                                        ->where('dominion_id',$dominion->id)
                                        ->where('source', 'deity')
                                        ->where('hours',1)
                                        ->get();
        foreach($finishedDeitiesInQueue as $finishedDeityInQueue)
        {
            $deityKey = $finishedDeityInQueue->resource;
            $deity = Deity::where('key', $deityKey)->first();
            $this->deityService->completeSubmissionToDeity($dominion, $deity);

            GameEvent::create([
                'round_id' => $dominion->round_id,
                'source_type' => Deity::class,
                'source_id' => $deity->id,
                'target_type' => Dominion::class,
                'target_id' => $dominion->id,
                'type' => 'deity_completed',
                'data' => NULL,
                'tick' => $dominion->round->ticks
            ]);
        }

    }

    # Take research that is one tick away from finished and create DominionTech.
    private function handleResearch(Dominion $dominion): void
    {
        $finishedResearchesInQueue = DB::table('dominion_queue')
                                        ->where('dominion_id',$dominion->id)
                                        ->where('source', 'research')
                                        ->where('hours',1)
                                        ->get();
        foreach($finishedResearchesInQueue as $finishedDeityInQueue)
        {
            $techKey = $finishedDeityInQueue->resource;
            $tech = Tech::where('key', $techKey)->first();
            $this->researchService->completeResearch($dominion, $tech);

            GameEvent::create([
                'round_id' => $dominion->round_id,
                'source_type' => Tech::class,
                'source_id' => $tech->id,
                'target_type' => Dominion::class,
                'target_id' => $dominion->id,
                'type' => 'research_completed',
                'data' => NULL,
                'tick' => $dominion->round->ticks
            ]);
        }

    }

    # Take resources that are one tick away from finished and create or increment DominionImprovements.
    private function handleResources(Dominion $dominion): void
    {
        $resourcesNetChange = [];

        $finishedResourcesInQueue = DB::table('dominion_queue')
            ->where('dominion_id', $dominion->id)
            ->where('resource', 'like', 'resource%')
            ->whereIn('source', ['invasion', 'expedition', 'theft', 'desecration'])
            ->where('hours', 1)
            ->get();

        foreach ($dominion->race->resources as $resourceKey) {
            $resourcesProduced = $finishedResourcesInQueue
                ->where('resource', 'resource_' . $resourceKey)
                ->sum('amount');

            $resourcesProduced += $this->resourceCalculator->getNetProduction($dominion, $resourceKey);

            $resourcesNetChange[$resourceKey] = $resourcesProduced;
        }

        $this->resourceService->updateResources($dominion, $resourcesNetChange);
    }

    # Take artefacts that are one tick away from finished and create or increment RealmArtefact.
    private function handleArtefacts(Dominion $dominion): void
    {
        $finishedArtefactsInQueue = DB::table('dominion_queue')
                                        ->where('dominion_id',$dominion->id)
                                        ->where('source', 'artefact')
                                        ->where('hours',1)
                                        ->get();
        foreach($finishedArtefactsInQueue as $finishedArtefactInQueue)
        {
            $artefactKey = $finishedArtefactInQueue->resource;
            $artefact = Artefact::where('key', $artefactKey)->first();

            $this->artefactService->addArtefactToRealm($dominion->realm, $artefact);

            GameEvent::create([
                'round_id' => $dominion->round_id,
                'source_type' => Artefact::class,
                'source_id' => $artefact->id,
                'target_type' => Realm::class,
                'target_id' => $dominion->realm->id,
                'type' => 'artefact_completed',
                'data' => ['dominion_id' => $dominion->id],
                'tick' => $dominion->round->ticks
            ]);
        }
    }

    # Take buildings that are one tick away from finished and create or increment DominionBuildings.
    private function handleTerrain(Dominion $dominion): void
    {
        $finishedTerrainsInQueue = DB::table('dominion_queue')
            ->where('dominion_id', $dominion->id)
            ->where('resource', 'like', 'terrain%')
            ->where('hours', 1)
            ->get();
    
        $terrainChanges = [];
    
        foreach ($finishedTerrainsInQueue as $finishedTerrainInQueue) {
            $terrainKey = str_replace('terrain_', '', $finishedTerrainInQueue->resource);
            $amount = intval($finishedTerrainInQueue->amount);
            $terrainChanges[$terrainKey] = $amount;
        }

        $this->terrainService->update($dominion, $terrainChanges);

        $this->terrainService->handleTerrainTransformation($dominion);
    }

    # This function handles queuing of evolved units (Vampires)
    private function handleUnits(Dominion $dominion): void
    {
        return;
        # Space reserved for units 2.0

        /*
        $units = $this->unitCalculator->getDominionUnitBlankArray($dominion);
        $evolvedUnitsTo = [];
        $evolvedUnitsFrom = [];
        $evolutionMultiplier = $this->unitCalculator->getEvolutionMultiplier($dominion);
    
        foreach($units as $slot => $zero)
        {

            $unitCapacityAvailable = $this->unitCalculator->getUnitCapacityAvailable($dominion, $slot);

            if($unitEvolutionPerk = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'evolves_into_unit'))
            {
                $targetSlot = (int)$unitEvolutionPerk[0];
                $evolutionRatio = (float)$unitEvolutionPerk[1];
    
                $unitCount = $dominion->{'military_unit' . $slot};
    
                $unitsEvolved = $unitCount * ($evolutionRatio / 100);
                $unitsEvolved = floor($unitsEvolved * $evolutionMultiplier);
                $unitsEvolved = (int)min($unitCount, $unitsEvolved);

                if($this->unitCalculator->unitHasCapacityLimit($dominion, $slot))
                {
                    $unitsEvolved = min($unitsEvolved, $unitCapacityAvailable);
                }
    
                if($unitsEvolved > 0)
                {
                    if(isset($evolvedUnitsTo[$targetSlot]))
                    {
                        $evolvedUnitsTo[$targetSlot] += $unitsEvolved;
                        $evolvedUnitsFrom[$targetSlot] += $unitsEvolved;
                    }
                    else
                    {
                        $evolvedUnitsTo[$targetSlot] = $unitsEvolved;
                        $evolvedUnitsFrom[$slot] = $unitsEvolved;
                    }
                }
            }
        }

    
        foreach($evolvedUnitsTo as $targetSlot => $evolvedUnitAmount)
        {
            $evolvedUnit = $dominion->race->units->where('slot', $targetSlot)->first();
    
            $this->queueService->queueResources('evolution', $dominion, ['military_unit' . $targetSlot => $evolvedUnitAmount], ($evolvedUnit->training_time + 0)); # trying +0, was +1 because 12 becomes 11 otherwise
        }

        foreach($evolvedUnitsFrom as $sourceSlot => $amountEvolved)
        {
            $dominion->{'military_unit' . $sourceSlot} -= $amountEvolved;
        }

        $dominion->save();
        */

    }

    // Scoot hour 1 Qur Stasis units back to hour 2
    public function handleStasis(Dominion $dominion): void
    {
        if(!$dominion->getSpellPerkValue('stasis'))
        {
            return;
        }

        $this->temporaryData[$dominion->round->id]['stasis_dominions'][] = $dominion->id;

        if(config('game.extended_logging')) { Log::debug('** Dominion is in stasis'); }
        $stasisDominion = Dominion::findorfail($dominion->id);

        ## Determine how many of each unit type is returning in $tick ticks
        $tick = 1;

        foreach (range(1, $stasisDominion->race->units->count()) as $slot)
        {
            $unitType = 'unit' . $slot;
            for ($i = 1; $i <= 12; $i++)
            {
                $invasionQueueUnits[$slot][$i] = $this->queueService->getInvasionQueueAmount($stasisDominion, "military_{$unitType}", $i);
            }
        }

        $this->queueService->setForTick(false);
        foreach($stasisDominion->race->units as $unit)
        {
            $units['unit' . $unit->slot] = $this->queueService->getInvasionQueueAmount($stasisDominion, ('military_unit'. $unit->slot), $tick);
        }
        
        $units['spies'] = $this->queueService->getInvasionQueueAmount($stasisDominion, "military_spies", $tick);
        $units['wizards'] = $this->queueService->getInvasionQueueAmount($stasisDominion, "military_wizards", $tick);
        $units['archmages'] = $this->queueService->getInvasionQueueAmount($stasisDominion, "military_archmages", $tick);

        foreach($units as $slot => $amount)
        {
            $unitType = 'military_'.$slot;
            # Dequeue the units from hour 1
            $this->queueService->dequeueResourceForHour('invasion', $stasisDominion, $unitType, $amount, $tick);
            #echo "\nUnits dequeued";

            # (Re-)Queue the units to hour 2
            $this->queueService->queueResources('invasion', $stasisDominion, [$unitType => $amount], ($tick+1));
            #echo "\nUnits requeued";
        }

        foreach($stasisDominion->race->units as $unit)
        {
            $units['unit' . $unit->slot] = $this->queueService->getExpeditionQueueAmount($stasisDominion, ('military_unit'. $unit->slot), $tick);
        }

        foreach($units as $slot => $amount)
        {
            $unitType = 'military_'.$slot;
            # Dequeue the units from hour 1
            $this->queueService->dequeueResourceForHour('invasion', $stasisDominion, $unitType, $amount, $tick);
            #echo "\nUnits dequeued";

            # (Re-)Queue the units to hour 2
            $this->queueService->queueResources('invasion', $stasisDominion, [$unitType => $amount], ($tick+1));
            #echo "\nUnits requeued";
        }

        foreach($stasisDominion->race->units as $unit)
        {
            $units['unit' . $unit->slot] = $this->queueService->getTheftQueueAmount($stasisDominion, ('military_unit'. $unit->slot), $tick);
        }
        
        $units['spies'] = $this->queueService->getTheftQueueAmount($stasisDominion, "military_spies", $tick);

        foreach($units as $slot => $amount)
        {
            $unitType = 'military_'.$slot;
            # Dequeue the units from hour 1
            $this->queueService->dequeueResourceForHour('theft', $stasisDominion, $unitType, $amount, $tick);

            # (Re-)Queue the units to hour 2
            $this->queueService->queueResources('theft', $stasisDominion, [$unitType => $amount], ($tick+1));
        }

        foreach($stasisDominion->race->units as $unit)
        {
            $units['unit' . $unit->slot] = $this->queueService->getSabotageQueueAmount($stasisDominion, ('military_unit'. $unit->slot), $tick);
        }

        $units['spies'] = $this->queueService->getSabotageQueueAmount($stasisDominion, "military_spies", $tick);

        foreach($units as $slot => $amount)
        {
            $unitType = 'military_'.$slot;
            # Dequeue the units from hour 1
            $this->queueService->dequeueResourceForHour('sabotage', $stasisDominion, $unitType, $amount, $tick);
            #echo "\nUnits dequeued";

            # (Re-)Queue the units to hour 2
            $this->queueService->queueResources('sabotage', $stasisDominion, [$unitType => $amount], ($tick+1));
            #echo "\nUnits requeued";
        }

        $this->queueService->setForTick(true);
    }

    protected function handleBarbarians(Dominion $dominion): void
    {
        if($dominion->race->name !== 'Barbarian')
        {
            return;
        }

        if(config('game.extended_logging')) { Log::debug('*** Handle Barbarian invasions for ' . $dominion->name); }
        $this->barbarianService->handleBarbarianInvasion($dominion);

        if(config('game.extended_logging')) { Log::debug('*** Handle Barbarian construction for ' . $dominion->name); }
        $this->barbarianService->handleBarbarianConstruction($dominion);

        if(config('game.extended_logging')) { Log::debug('*** Handle Barbarian improvements for ' . $dominion->name); }
        $this->barbarianService->handleBarbarianImprovements($dominion);
    }

    protected function handleCaptureInsight(Dominion $dominion): void
    {
        if(
            ($this->dominion->round->ticks % 4 == 0) and
            $dominion->protection_ticks == 0 and
            $this->dominion->round->hasStarted() and
            !$this->dominion->getSpellPerkValue('fog_of_war') and
            !$this->dominion->isAbandoned()
            )
        {
            $this->queueService->setForTick(false); # Necessary as otherwise this-tick units are missing

            if(config('game.extended_logging')) { Log::debug('** Capturing insight for ' . $this->dominion->name); }
            $this->insightService->captureDominionInsight($this->dominion);

            $this->queueService->setForTick(true); # Reset
        }
    }

    public function handlePestilence(Dominion $dominion): void
    {
        if(!empty($dominion->tick->pestilence_units))
        {
            $caster = Dominion::find($dominion->tick->pestilence_units['caster_dominion_id']);

            if(config('game.extended_logging')) { Log::debug('*** ' . $dominion->name . ' has pestilence from ' . $caster->name); }

            if ($caster)
            {
                $this->queueService->queueResources('summoning', $caster, ['military_unit1' => $dominion->tick->pestilence_units['units']['military_unit1']], 12);
            }
        }
    }

    protected function handleLandGeneration(Dominion $dominion): void
    {
        if(!empty($dominion->tick->generated_land))
        {
            $this->queueService->queueResources('exploration', $dominion, ['land' => $dominion->tick->generated_land], 12);
        }
    }

    public function handleUnitGeneration(Dominion $dominion): void
    {
        foreach($dominion->race->units as $unit)
        {
            if(!empty($dominion->tick->{'generated_unit' . $unit->slot}))
            {
                $this->queueService->queueResources('summoning', $dominion, [('military_unit' . $unit->slot) => $dominion->tick->{'generated_unit' . $unit->slot}], ($unit->training_time + 0)); # trying +0, was +1 because it's ticking
            }
        }
    }

    public function queueNotifications(Dominion $dominion): void
    {
        if(config('game.extended_logging')) { Log::debug('** Handle starvation for ' . $dominion->name); }
        if($this->resourceCalculator->isOnBrinkOfStarvation($dominion) and !$dominion->isAbandoned())
        {
            $this->notificationService->queueNotification('starvation_occurred');
            #Log::info('[STARVATION] ' . $dominion->name . ' (# ' . $dominion->realm->number . ') is starving.');
        }

        if(config('game.extended_logging')) { Log::debug('** Handle unit attrition for ' . $dominion->name); }
        
        if(array_sum($this->temporaryData[$dominion->round->id][$dominion->id]['units_attrited']) > 0 and !$dominion->isAbandoned())
        {
            $this->notificationService->queueNotification('attrition_occurred', $this->temporaryData[$dominion->round->id][$dominion->id]['units_attrited']);
        }
    }

    protected function updateSpells(Dominion $dominion): void
    {
        $dominion->spells->each(function ($spell) {
            if ($spell->duration > 0) {
                $spell->decrement('duration');
            }
    
            if ($spell->cooldown > 0) {
                $spell->decrement('cooldown');
            }
    
            $spell->touch();
        });
    }

    protected function cleanupActiveSpells(Dominion $dominion)
    {
        $finished = DB::table('dominion_spells')
            ->where('dominion_id', $dominion->id)
            ->where('duration', '<=', 0)
            ->where('cooldown', '<=', 0)
            ->get();

        $beneficialSpells = [];
        $harmfulSpells = [];

        foreach ($finished as $row)
        {
            $spell = Spell::where('id', $row->spell_id)->first();

            if ($row->caster_id == $dominion->id)
            {
                $beneficialSpells[] = $spell->key;
            }
            else
            {
                $harmfulSpells[] = $spell->key;
            }
        }

        if (!empty($beneficialSpells) and !$dominion->isAbandoned())
        {
            $this->notificationService->queueNotification('beneficial_magic_dissipated', $beneficialSpells);
        }

        if (!empty($harmfulSpells) and !$dominion->isAbandoned())
        {
            $this->notificationService->queueNotification('harmful_magic_dissipated', $harmfulSpells);
        }

        DB::table('dominion_spells')
            ->where('dominion_id', $dominion->id)
            ->where('duration', '<=', 0)
            ->where('cooldown', '<=', 0)
            ->delete();
    }

    protected function cleanupQueues(Dominion $dominion)
    {
        if($dominion->isAbandoned())
        {
            return;
        }

        $finished = DB::table('dominion_queue')
            ->where('dominion_id', $dominion->id)
            ->where('hours', '<=', 0)
            ->get();

        foreach ($finished->groupBy('source') as $source => $group)
        {
            $resources = [];
            foreach ($group as $row)
            {
                $resources[$row->resource] = $row->amount;
            }

            if ($source === 'invasion')
            {
                $notificationType = 'returning_completed';
            }
            else
            {
                $notificationType = "{$source}_completed";
            }

            $this->notificationService->queueNotification($notificationType, $resources);
        }

        DB::transaction(function () use ($dominion)
        {
            DB::table('dominion_queue')
                ->where('dominion_id', $dominion->id)
                ->where('hours', '<=', 0)
                ->delete();
        }, 10);

    }

    public function precalculateTick(Dominion $dominion, ?bool $saveHistory = false): void
    {
        /** @var Tick $tick */
        $tick = Tick::firstOrCreate(['dominion_id' => $dominion->id]);

        if ($saveHistory)
        {
            // Save a dominion history record
            $dominionHistoryService = app(HistoryService::class);

            $changes = array_filter($tick->getAttributes(), static function ($value, $key)
            {
                return (
                    !in_array($key, [
                        'id',
                        'dominion_id',
                        'created_at',
                        'updated_at'
                    ], true) &&
                    ($value != 0) // todo: strict type checking?
                );
            }, ARRAY_FILTER_USE_BOTH);

            $dominionHistoryService->record($dominion, $changes, HistoryService::EVENT_TICK);
        }

        // Reset tick values — I don't understand this. WaveHack magic. Leave (mostly) intact, only adapt, don't refactor.
        foreach ($tick->getAttributes() as $attr => $value)
        {
            # Values that become 0
            $zeroArray = [
                'id',
                'dominion_id',
                'updated_at',
                'pestilence_units',
                'generated_land',
                'generated_unit1',
                'generated_unit2',
                'generated_unit3',
                'generated_unit4',
                'generated_unit5',
                'generated_unit6',
                'generated_unit7',
                'generated_unit8',
                'generated_unit9',
                'generated_unit10',
            ];

            # Values that become []
            $emptyArray = [
                'starvation_casualties',
                'pestilence_units',
                'generated_land',
                'generated_unit1',
                'generated_unit2',
                'generated_unit3',
                'generated_unit4',
                'generated_unit5',
                'generated_unit6',
                'generated_unit4',
                'generated_unit7',
                'generated_unit8',
                'generated_unit9',
                'generated_unit10',
                'buildings_destroyed',
            ];

            #if (!in_array($attr, ['id', 'dominion_id', 'updated_at', 'pestilence_units', 'generated_land', 'generated_unit1', 'generated_unit2', 'generated_unit3', 'generated_unit4'], true))
            if (!in_array($attr, $zeroArray, true))
            {
                  $tick->{$attr} = 0;
            }
            #elseif (in_array($attr, ['starvation_casualties', 'pestilence_units', 'generated_land', 'generated_unit1', 'generated_unit2', 'generated_unit3', 'generated_unit4'], true))
            elseif (in_array($attr, $emptyArray, true))
            {
                  $tick->{$attr} = [];
            }
        }

        // Hacky refresh for dominion
        $dominion->refresh();

        // Define the excluded sources
        $excludedSources = ['construction', 'repair', 'restore', 'deity', 'artefact', 'research', 'rezoning'];

        // Get the incoming queue
        $incomingQueue = DB::table('dominion_queue')
            ->where('dominion_id', $dominion->id)
            ->whereNotIn('source', $excludedSources)
            ->where('hours', '=', 1)
            ->get();

        foreach ($incomingQueue as $row)
        {
            // Check if the resource is not a 'resource_' or 'terrain_'
            if (!Str::startsWith($row->resource, ['resource_', 'terrain_'])) {
                $tick->{$row->resource} += $row->amount;
                // Temporarily add next hour's resources for accurate calculations
                $dominion->{$row->resource} += $row->amount;
            }
        }

        /*
        // Queues
        $incomingQueue = DB::table('dominion_queue')
            ->where('dominion_id', $dominion->id)
            ->where('source', '!=', 'construction')
            ->where('source', '!=', 'repair')
            ->where('source', '!=', 'restore')
            ->where('hours', '=', 1)
            ->get();

        foreach ($incomingQueue as $row)
        {
            if(
                    $row->source !== 'deity'
                    and $row->source !== 'artefact'
                    and $row->source !== 'research'
                    and $row->source !== 'rezoning'
                    and substr($row->resource, 0, strlen('resource_')) !== 'resource_'
                    and substr($row->resource, 0, strlen('terrain_')) !== 'terrain_'
            )
            {
                $tick->{$row->resource} += $row->amount;
                // Temporarily add next hour's resources for accurate calculations
                $dominion->{$row->resource} += $row->amount;
            }
        }
        */

        if($dominion->race->name == 'Barbarian')
        {
            if(config('game.extended_logging')) { Log::debug('*** Handle Barbarian training for ' . $dominion->name); }
            $this->barbarianService->handleBarbarianTraining($dominion);
        }

        $tick->protection_ticks = 0;
        // Tick
        if($dominion->protection_ticks > 0)
        {
            $tick->protection_ticks += -1;
        }

        // Population
        $drafteesGrowthRate = $this->populationCalculator->getPopulationDrafteeGrowth($dominion);
        $populationPeasantGrowth = $this->populationCalculator->getPopulationPeasantGrowth($dominion);

        if ($this->spellCalculator->isSpellActive($dominion, 'pestilence'))
        {
            $spell = Spell::where('key', 'pestilence')->first();
            $pestilence = $spell->getActiveSpellPerkValues('pestilence', 'kill_peasants_and_converts_for_caster_unit');
            $ratio = $pestilence[0] / 100;
            $slot = $pestilence[1];
            $caster = $this->spellCalculator->getCaster($dominion, 'pestilence');

            $amountToDie = $dominion->peasants * $ratio * $this->sorceryCalculator->getDominionHarmfulSpellDamageModifier($dominion, null, Spell::where('key', 'pestilence')->first(), null);
            $amountToDie *= $this->conversionCalculator->getConversionReductionMultiplier($dominion);
            $amountToDie = (int)round($amountToDie);

            $tick->pestilence_units = ['caster_dominion_id' => $caster->id, 'units' => ['military_unit1' => $amountToDie]];

            $populationPeasantGrowth -= $amountToDie;
        }
        elseif ($this->spellCalculator->isSpellActive($dominion, 'lesser_pestilence'))
        {
            $spell = Spell::where('key', 'lesser_pestilence')->first();
            $lesserPestilence = $spell->getActiveSpellPerkValues('lesser_pestilence', 'kill_peasants_and_converts_for_caster_unit');
            $ratio = $lesserPestilence[0] / 100;
            $slot = $lesserPestilence[1];
            $caster = $this->spellCalculator->getCaster($dominion, 'lesser_pestilence');

            $amountToDie = $dominion->peasants * $ratio * $this->sorceryCalculator->getDominionHarmfulSpellDamageModifier($dominion, null, Spell::where('key', 'lesser_pestilence')->first(), null);
            $amountToDie *= $this->conversionCalculator->getConversionReductionMultiplier($dominion);
            $amountToDie = (int)round($amountToDie);

            $tick->pestilence_units = ['caster_dominion_id' => $caster->id, 'units' => ['military_unit1' => $amountToDie]];

            $populationPeasantGrowth -= $amountToDie;
        }

        # Check for peasants_conversion
        if($peasantConversionData = $dominion->getBuildingPerkValue('peasants_conversion'))
        {
            $multiplier = 1;
            $multiplier += $dominion->getSpellPerkMultiplier('peasants_converted');
            $multiplier += $dominion->getBuildingPerkMultiplier('peasants_converted');
            $multiplier += $dominion->getImprovementPerkMultiplier('peasants_converted');

            $populationPeasantGrowth -= $peasantConversionData['from']['peasants'];
        }
        # Check for peasants_conversions
        if($peasantConversionsData = $dominion->getBuildingPerkValue('peasants_conversions'))
        {
            $multiplier = 1;
            $multiplier += $dominion->getSpellPerkMultiplier('peasants_converted');
            $multiplier += $dominion->getBuildingPerkMultiplier('peasants_converted');
            $multiplier += $dominion->getImprovementPerkMultiplier('peasants_converted');

            $populationPeasantGrowth -= $peasantConversionsData['from']['peasants'];
        }
        # Check for units with peasants_conversions
        $peasantsConvertedByUnits = 0;
        foreach($dominion->race->units as $unit)
        {
            if($unitPeasantsConversionPerk = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, 'peasants_conversions'))
            {
                $multiplier = 1;
                $multiplier += $dominion->getSpellPerkMultiplier('peasants_converted');
                $multiplier += $dominion->getBuildingPerkMultiplier('peasants_converted');
                $multiplier += $dominion->getImprovementPerkMultiplier('peasants_converted');

                $peasantsConvertedByUnits += $unitPeasantsConversionPerk[0] * $dominion->{'military_unit' . $unit->slot} * $multiplier;
            }

            if($unitPeasantsConversionPerk = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, 'peasants_to_unit_conversions'))
            {
                $peasantsConvertedByUnits += $unitPeasantsConversionPerk[0] * $dominion->{'military_unit' . $unit->slot};
            }


        }
        $populationPeasantGrowth -= (int)round($peasantsConvertedByUnits);

        if(($dominion->peasants + $tick->peasants) <= 0)
        {
            $tick->peasants = ($dominion->peasants)*-1;
        }

        $tick->peasants = $populationPeasantGrowth;

        $tick->peasants_sacrificed = 0;

        $tick->military_draftees = $drafteesGrowthRate;

        // Production/generation
        $tick->xp += $this->productionCalculator->getXpGeneration($dominion);
        $tick->prestige += $this->productionCalculator->getPrestigeInterest($dominion);

        // Starvation
        $tick->starvation_casualties = false;

        if($this->resourceCalculator->canStarve($dominion->race))
        {
            #$foodProduction = $this->resourceCalculator->getProduction($dominion, 'food');
            $foodConsumed = $this->resourceCalculator->getConsumption($dominion, 'food');
            #$foodNetChange = $foodProduction - $foodConsumed;
            $foodNetChange = $this->resourceCalculator->getNetProduction($dominion, 'food');
            $foodOwned = $dominion->resource_food;


            if($foodConsumed > 0 and ($foodOwned + $foodNetChange) < 0)
            {
                $dominion->tick->starvation_casualties = true;
            }
        }

        // Morale
        $baseMorale = $this->moraleCalculator->getBaseMorale($dominion);
        $moraleChangeModifier = $this->moraleCalculator->moraleChangeModifier($dominion);

        if(($tick->starvation_casualties or $dominion->tick->starvation_casualties) and $this->resourceCalculator->canStarve($dominion->race))
        {
            $starvationMoraleChange = min(10, $dominion->morale)*-1;
            $tick->morale += $starvationMoraleChange;
        }
        else
        {
            if ($dominion->morale < 35)
            {
                $tick->morale = 7;
            }
            elseif ($dominion->morale < 70)
            {
                $tick->morale = 6;
            }
            elseif ($dominion->morale < $baseMorale)
            {
                $tick->morale = min(3, $baseMorale - $dominion->morale);
            }
            elseif($dominion->morale > $baseMorale)
            {
                $tick->morale -= min(2 * $moraleChangeModifier, $dominion->morale - $baseMorale);
            }
        }

        $spyStrengthBase = $this->espionageCalculator->getSpyStrengthBase($dominion);
        $wizardStrengthBase = $this->spellCalculator->getWizardStrengthBase($dominion);

        // Spy Strength
        if ($dominion->spy_strength < $spyStrengthBase)
        {
            $tick->spy_strength =  min($this->espionageCalculator->getSpyStrengthRecoveryAmount($dominion), $spyStrengthBase - $dominion->spy_strength);
        }

        // Wizard Strength
        if ($dominion->wizard_strength < $wizardStrengthBase)
        {
            $tick->wizard_strength =  min($this->spellCalculator->getWizardStrengthRecoveryAmount($dominion), $wizardStrengthBase - $dominion->wizard_strength);
        }

        # Tickly unit perks
        $generatedLand = $this->unitCalculator->getUnitLandGeneration($dominion);

        # Imperial Crypt: Rites of Zidur, Rites of Kinthys
        $tick->crypt_bodies_spent = 0;
        
        $unitsGenerated = $this->unitCalculator->getUnitsGenerated($dominion);
        $unitsAttrited = $this->unitCalculator->getUnitsAttrited($dominion);

        # Passive conversions
        $passiveConversions = $this->conversionCalculator->getPassiveConversions($dominion);
        if((array_sum($passiveConversions['units_converted']) + array_sum($passiveConversions['units_removed'])) > 0)
        {
            $unitsConverted = $passiveConversions['units_converted'];
            $unitsRemoved = $passiveConversions['units_removed'];

            foreach($dominion->race->units as $unit)
            {
                $unitsGenerated[$unit->slot] += $unitsConverted[$unit->slot];
                $unitsAttrited[$unit->slot] += $unitsRemoved[$unit->slot];
            }
        }
        
        # Use decimals as probability to round up
        $tick->generated_land += intval($generatedLand) + (rand()/getrandmax() < fmod($generatedLand, 1) ? 1 : 0);

        foreach($dominion->race->units as $unit)
        {
            $tick->{'generated_unit' . $unit->slot} += intval($unitsGenerated[$unit->slot]) + (rand()/getrandmax() < fmod($unitsGenerated[$unit->slot], 1) ? 1 : 0);
            $tick->{'attrition_unit' . $unit->slot} += intval($unitsAttrited[$unit->slot]);
        }

        # Handle building self-destruct
        if($selfDestruction = $dominion->getBuildingPerkValue('destroys_itself_and_land'))
        {
            $buildingKey = (string)$selfDestruction['building_key'];
            $amountToDestroy = (int)$selfDestruction['amount'];
            $landType = (string)$selfDestruction['land_type'];

            if($amountToDestroy > 0)
            {
                $tick->{'land_'.$landType} -= min($amountToDestroy, $dominion->{'land_'.$landType});
                $tick->buildings_destroyed = [$buildingKey => ['builtBuildingsToDestroy' => $amountToDestroy]];
            }
        }
        if($selfDestruction = $dominion->getBuildingPerkValue('destroys_itself'))
        {
            $buildingKey = (string)$selfDestruction['building_key'];
            $amountToDestroy = (int)$selfDestruction['amount'];

            if($amountToDestroy > 0)
            {
                $tick->buildings_destroyed = [$buildingKey => ['builtBuildingsToDestroy' => $amountToDestroy]];
            }
        }

        foreach ($incomingQueue as $row)
        {
            if(
                $row->source !== 'deity'
                and $row->source !== 'research'
                and $row->source !== 'artefact'
                and substr($row->resource, 0, strlen('resource_')) !== 'resource_'
                and substr($row->resource, 0, strlen('terrain_')) !== 'terrain_'
            )
                
            {
                // Reset current resources in case object is saved later
                $dominion->{$row->resource} -= $row->amount;
            }
        }

        $tick->save();
    }
    
}