<?php

namespace OpenDominion\Jobs;

use DB;
use Log;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
#use Illuminate\Support\Str;

use OpenDominion\Models\Artefact;
use OpenDominion\Models\Deity;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\DominionSpell;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\Improvement;
use OpenDominion\Models\Spell;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Tech;
#use OpenDominion\Models\Dominion\Tick;

use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\EspionageCalculator;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Calculators\Dominion\MoraleCalculator;
use OpenDominion\Calculators\Dominion\ResourceCalculator;
use OpenDominion\Calculators\Dominion\TickCalculator;
use OpenDominion\Calculators\Dominion\UnitCalculator;


use OpenDominion\Services\BarbarianService;
use OpenDominion\Services\NotificationService;
use OpenDominion\Services\Dominion\ArtefactService;
use OpenDominion\Services\Dominion\DeityService;
#use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Services\Dominion\InsightService;
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
    protected $espionageCalculator;
    protected $improvementCalculator;
    protected $moraleCalculator;
    protected $resourceCalculator;
    protected $tickCalculator;
    protected $unitCalculator;
    
    protected $artefactService;
    protected $barbarianService;
    protected $deityService;
    protected $insightService;
    protected $notificationService;
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
        $this->espionageCalculator = app(EspionageCalculator::class);
        $this->improvementCalculator = app(ImprovementCalculator::class);
        $this->moraleCalculator = app(MoraleCalculator::class);
        $this->resourceCalculator = app(ResourceCalculator::class);
        $this->tickCalculator = app(TickCalculator::class);
        $this->unitCalculator = app(UnitCalculator::class);

        $this->artefactService = app(ArtefactService::class);
        $this->barbarianService = app(BarbarianService::class);
        $this->deityService = app(DeityService::class);
        $this->insightService = app(InsightService::class);
        $this->notificationService = app(NotificationService::class);
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

        Log::debug('* Processing dominion ' . $this->dominion->name . ' (# ' . $this->dominion->realm->number . '), ID ' . $this->dominion->id);
        dump('* Processing dominion ' . $this->dominion->name . ' (# ' . $this->dominion->realm->number . '), ID ' . $this->dominion->id);
        # Make a DB transaction
        DB::transaction(function () use ($round)
        {    
            $this->temporaryData[$round->id][$this->dominion->id] = [];

            #$this->temporaryData[$round->id][$this->dominion->id]['units_generated'] = $this->unitCalculator->getUnitsGenerated($this->dominion);
            $this->temporaryData[$round->id][$this->dominion->id]['units_attrited'] = $this->unitCalculator->getUnitsAttrited($this->dominion);

            if(config('game.extended_logging')) { Log::debug('** Handle Barbarian stuff (if this dominion is a Barbarian)'); }
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

            if(config('game.extended_logging')){ Log::debug('** Updating devotion'); }
            $this->handleDevotion($this->dominion);

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

            if(config('game.extended_logging')) { Log::debug('** Sending notifications (hourly_dominion)'); }
            $this->notificationService->sendNotifications($this->dominion, 'hourly_dominion');

            if(config('game.extended_logging')) { Log::debug('** Precalculate tick'); }
            $this->tickCalculator->precalculateTick($this->dominion, true);
        });

        # Cannot be a part of the DB transaction because it might cause deadlocks
        if(config('game.extended_logging')) { Log::debug('** Audit and repair terrain'); }
        $this->terrainService->auditAndRepairTerrain($this->dominion);
            
        # Also cannot be a part of the DB transaction because it might cause deadlocks
        if(config('game.extended_logging')) { Log::debug('** Cleaning up queues'); }
        $this->cleanupQueues($this->dominion);
        if(config('game.extended_logging')) { Log::debug('** Cleaning up active spells'); }
        $this->cleanupActiveSpells($this->dominion);
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

        # Handle self-destruct BUGGY
        #if($buildingsDestroyed = $dominion->tick->buildings_destroyed)
        #{
        #    $this->buildingCalculator->removeBuildings($dominion, $buildingsDestroyed);
        #}
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
        if($dominion->hasDeity())
        {
            return;
        }

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

    private function handleDevotion(Dominion $dominion): void
    {
        if(!$dominion->hasDeity())
        {
            return;
        }

        $dominion->dominionDeity->increment('duration', 1);
        $dominion->dominionDeity->save();
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

    protected function handleBarbarians(Dominion $barbarian): void
    {
        if($barbarian->race->name !== 'Barbarian')
        {
            return;
        }

        if(config('game.extended_logging')) { Log::debug('*** Handle Barbarian invasions'); }
        $this->barbarianService->handleBarbarianInvasion($barbarian);

        if(config('game.extended_logging')) { Log::debug('*** Handle Barbarian construction'); }
        $this->barbarianService->handleBarbarianConstruction($barbarian);

        if(config('game.extended_logging')) { Log::debug('*** Handle Barbarian improvements'); }
        $this->barbarianService->handleBarbarianImprovements($barbarian);

        if(config('game.extended_logging')) { Log::debug('*** Handle Barbarian training'); }
        $this->barbarianService->handleBarbarianTraining($barbarian);
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

    public function handlePestilence(Dominion $afflicted): void
    {
        if($afflicted->race->key !== 'afflicted')
        {
            return;
        }

        $unitsGenerated = [];

        $pestilenceIds = Spell::whereIn('key', ['pestilence', 'lesser_pestilence'])->pluck('id');
        $pestilences = DominionSpell::with('spell')
            ->whereIn('spell_id', $pestilenceIds)
            ->where('caster_id', $afflicted->id)
            ->where('duration','>',0)
            ->get()
            ->sortByDesc('created_at');

        if(config('game.extended_logging')) { Log::debug('*** 🦠 Has ' . $pestilences->count() . ' active pestilence(s)'); dump('*** 🦠 Has ' . $pestilences->count() . ' active pestilence(s)'); }

        foreach($pestilences as $dominionSpellPestilence)
        {
            $target = Dominion::find($dominionSpellPestilence->dominion_id);

            if($target->peasants <= 100)
            {
                continue;
            }

            $pestilence = $dominionSpellPestilence->spell->getActiveSpellPerkValues($dominionSpellPestilence->spell->key, 'kill_peasants_and_converts_for_caster_unit');
            $ratio = $pestilence[0] / 100;
            $slot = $pestilence[1];

            if($ratio and $slot)
            {
                $peasantsKilled = (int)floor($target->peasants * $ratio);
                $unitsGenerated[$slot] = isset($unitsGenerated[$slot]) ? $unitsGenerated[$slot] + $peasantsKilled : $peasantsKilled;

                Log::info('*** Pestilence: ' . $target->name . ' lost ' . $peasantsKilled . ' peasants to pestilence.');
            }
        }

        if(!empty($unitsGenerated))
        {
            if(config('game.extended_logging')) { Log::debug('*** Queuing units generated from pestilence.'); dump('*** Queuing units generated from pestilence.'); }

            foreach($unitsGenerated as $slot => $amount)
            {
                if(config('game.extended_logging')) { Log::debug('**** ' . $amount . ' unit' . $slot . ' queued.'); dump('**** ' . $amount . ' unit' . $slot . ' queued.'); }
                try {
                    DB::transaction(function () use ($unitsGenerated, $afflicted) {
                        foreach ($unitsGenerated as $slot => $amount) {
                            $this->queueService->queueResources('summoning', $afflicted, [('military_unit' . $slot) => $amount], 12);
                            Log::info("Successfully queued {$amount} units of type {$slot} for {$afflicted->name}.");
                        }
                    });
                } catch (\Exception $e) {
                    Log::error("Failed to queue units: " . $e->getMessage());
                }
                #$this->queueService->queueResources('summoning', $afflicted, [('military_unit' . $slot) => $amount], 12);
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
        if($this->resourceCalculator->isOnBrinkOfStarvation($dominion) and !$dominion->isAbandoned())
        {
            if(config('game.extended_logging')) { Log::debug('** Queue starvation notifications'); }
            $this->notificationService->queueNotification('starvation_occurred');
        }

        if(array_sum($this->temporaryData[$dominion->round->id][$dominion->id]['units_attrited']) > 0 and !$dominion->isAbandoned())
        {
            if(config('game.extended_logging')) { Log::debug('** Queue attrition notifications '); }
            $this->notificationService->queueNotification('attrition_occurred', $this->temporaryData[$dominion->round->id][$dominion->id]['units_attrited']);
        }
    }

    protected function updateSpells(Dominion $dominion): void
    {

        foreach($dominion->dominionSpells as $dominionSpell)
        {
            if($dominionSpell->duration > 0)
            {
                $dominionSpell->decrement('duration');
            }

            if($dominionSpell->cooldown > 0)
            {
                $dominionSpell->decrement('cooldown');
            }

            $dominionSpell->save();
        }
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
    
}