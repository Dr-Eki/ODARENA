<?php

namespace OpenDominion\Jobs;

use DB;
use Log;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use OpenDominion\Models\Artefact;
use OpenDominion\Models\Building;
use OpenDominion\Models\Deity;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\DominionSpell;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\Improvement;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Resource;
use OpenDominion\Models\Spell;
use OpenDominion\Models\Tech;
use OpenDominion\Models\TickChange;

use OpenDominion\Calculators\Dominion\EspionageCalculator;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Calculators\Dominion\MoraleCalculator;
use OpenDominion\Calculators\Dominion\ResourceCalculator;
use OpenDominion\Calculators\Dominion\TickCalculator;
use OpenDominion\Calculators\Dominion\UnitCalculator;

use OpenDominion\Services\BarbarianService;
use OpenDominion\Services\NotificationService;
use OpenDominion\Services\Dominion\ArtefactService;
use OpenDominion\Services\Dominion\BuildingService;
use OpenDominion\Services\Dominion\DeityService;
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

    protected $espionageCalculator;
    protected $improvementCalculator;
    protected $moraleCalculator;
    protected $resourceCalculator;
    protected $tickCalculator;
    protected $unitCalculator;
    
    protected $artefactService;
    protected $barbarianService;
    protected $buildingService;
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

        $this->espionageCalculator = app(EspionageCalculator::class);
        $this->improvementCalculator = app(ImprovementCalculator::class);
        $this->moraleCalculator = app(MoraleCalculator::class);
        $this->resourceCalculator = app(ResourceCalculator::class);
        $this->tickCalculator = app(TickCalculator::class);
        $this->unitCalculator = app(UnitCalculator::class);

        $this->artefactService = app(ArtefactService::class);
        $this->barbarianService = app(BarbarianService::class);
        $this->buildingService = app(buildingService::class);
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
        xtLog('* Processing dominion ' . $this->dominion->name . ' (# ' . $this->dominion->realm->number . '), ID ' . $this->dominion->id);

        # Do this first to populate dominion_tick
        # xtLog("[{$this->dominion->id}] ** Precalculate tick");
        # $this->tickCalculator->precalculateTick($this->dominion, true);
        
        # Make a DB transaction

        DB::transaction(function () use ($round)
        {  
            $this->temporaryData[$round->id][$this->dominion->id] = [];

            #$this->temporaryData[$round->id][$this->dominion->id]['units_generated'] = $this->unitCalculator->getUnitsGenerated($this->dominion);
            $this->temporaryData[$round->id][$this->dominion->id]['units_attrited'] = $this->unitCalculator->getUnitsAttrited($this->dominion);

            xtLog("[{$this->dominion->id}] ** Advancing queues (if in protection)");
            $this->advanceQueues($this->dominion);

            xtLog("[{$this->dominion->id}] ** Handle Barbarian stuff (if this dominion is a Barbarian)");
            $this->handleBarbarians($this->dominion);

            xtLog("[{$this->dominion->id}] ** Updating buildings");
            $this->handleCaptureInsight($this->dominion);

            xtLog("[{$this->dominion->id}] ** Updating buildings");
            $this->handleBuildings($this->dominion);

            xtLog("[{$this->dominion->id}] ** Updating terrain");
            $this->handleTerrain($this->dominion);

            xtLog("[{$this->dominion->id}] ** Updating improvements");
            $this->handleImprovements($this->dominion);

            xtLog("[{$this->dominion->id}] ** Updating deities");
            $this->handleDeities($this->dominion);

            xtLog("[{$this->dominion->id}] ** Updating devotion");
            $this->handleDevotion($this->dominion);

            xtLog("[{$this->dominion->id}] ** Updating artefacts");
            $this->handleArtefacts($this->dominion);

            xtLog("[{$this->dominion->id}] ** Updating research");
            $this->handleResearch($this->dominion);

            xtLog("[{$this->dominion->id}] ** Updating units");
            $this->handleUnits($this->dominion);

            xtLog("[{$this->dominion->id}] ** Updating resources");
            $this->handleResources($this->dominion);

            xtLog("[{$this->dominion->id}] ** Handle Pestilence");
            $this->handlePestilence($this->dominion);

            xtLog("[{$this->dominion->id}] ** Handle land generation");
            $this->handleLandGeneration($this->dominion);

            xtLog("[{$this->dominion->id}] ** Handle unit generation");
            $this->handleUnitGeneration($this->dominion);

            xtLog("[{$this->dominion->id}] ** Queue notifications");
            $this->queueNotifications($this->dominion);

            xtLog("[{$this->dominion->id}] ** Updating spells");
            $this->updateSpells($this->dominion);

            xtLog("[{$this->dominion->id}] ** Audit and repair terrain");
            $this->terrainService->auditAndRepairTerrain($this->dominion);
                
            xtLog("[{$this->dominion->id}] ** Handle finished queues");
            $this->handleFinishedQueues($this->dominion);

            #xtLog("[{$this->dominion->id}] ** Delete finished queues");
            #$this->deleteFinishedQueues($this->dominion);

            xtLog("[{$this->dominion->id}] ** Cleaning up active spells");
            $this->handleFinishedSpells($this->dominion);

            xtLog("[{$this->dominion->id}] ** Delete finished spells");
            $this->deleteFinishedSpells($this->dominion);

        });

        xtLog("[{$this->dominion->id}] ** Done processing dominion {$this->dominion->name} (# {$this->dominion->realm->number})");
        xtLog("[{$this->dominion->id}] ** Sending notifications (hourly_dominion)");
        $this->notificationService->sendNotifications($this->dominion, 'hourly_dominion');
    }

    protected function advanceQueues(Dominion $dominion): void
    {
        if($dominion->isAbandoned() or $dominion->isLocked() or $dominion->protection_ticks <= 0)
        {
            xtLog("[{$dominion->id}] *** Dominion is abandoned, locked, or in protection: skipping queue advancement");
            return;
        }

        xtLog("[{$dominion->id}] *** Advancing all dominion queues");
        $attempts = (int)config('ticking.deadlock_retry_attempts');
        $delay = (int)config('ticking.deadlock_retry_delay');

        for ($attempt = 1; $attempt <= $attempts; $attempt++)
        {
            try
            {
                DB::transaction(function () use ($dominion)
                {
                    $dominion->queues()
                    ->where('hours', '>', 0)
                    ->decrement('hours');
                });
                break; // If successful, exit the loop
            }
            catch (\Illuminate\Database\QueryException $e)
            {
                if ($e->getCode() == 1213 && $attempt < $attempts)
                { 
                    usleep($delay);
                    xtLog("[{$dominion->id}] **** Deadlock detected in ProcessDominionJob::advanceQueues(), retrying... (attempt $attempt/$attempts)");
                    continue;
                }
                throw $e; // Re-throw the exception if it's not a deadlock or attempts exceeded
            }
        }
    }

    protected function handleBarbarians(Dominion $barbarian): void
    {
        if($barbarian->race->name !== 'Barbarian')
        {
            return;
        }
        
        xtLog("[{$barbarian->id}] *** Handle Barbarian invasions");
        $this->barbarianService->handleBarbarianInvasion($barbarian);

        xtLog("[{$barbarian->id}] *** Handle Barbarian construction");
        $this->barbarianService->handleBarbarianConstruction($barbarian);

        xtLog("[{$barbarian->id}] *** Handle Barbarian improvements");
        $this->barbarianService->handleBarbarianImprovements($barbarian);

        xtLog("[{$barbarian->id}] *** Handle Barbarian training");
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
            #$this->queueService->setForTick(false); # Necessary as otherwise this-tick units are missing

            xtLog("[{$this->dominion->id}] ** Capturing insight for {$this->dominion->name}");
            $this->insightService->captureDominionInsight($this->dominion);

            #$this->queueService->setForTick(true); # Reset
        }
    }

    # Take artefacts that are one tick away from finished and create or increment RealmArtefact.
    private function handleArtefacts(Dominion $dominion): void
    {
        $finishedArtefactsInQueue = DB::table('dominion_queue')
                                        ->where('dominion_id',$dominion->id)
                                        ->where('source', 'artefact')
                                        ->where('hours', 0)
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
    private function handleBuildings(Dominion $dominion): void
    {
        $finishedBuildingsInQueue = DB::table('dominion_queue')
                                        ->where('dominion_id',$dominion->id)
                                        ->where('resource', 'like', 'building%')
                                        ->where('hours', 0)
                                        ->get();

        xtLog("[{$this->dominion->id}] ** Finished buildings in queue: " . $finishedBuildingsInQueue->count());

        if(!$finishedBuildingsInQueue->count())
        {
            return;
        }

        $buildingsToAdd = [];

        foreach($finishedBuildingsInQueue as $finishedBuildingInQueue)
        {
            $buildingKey = str_replace('building_', '', $finishedBuildingInQueue->resource);
            $amount = intval($finishedBuildingInQueue->amount);
            $buildingsToAdd[$buildingKey] = $amount;

            xtLog("[{$dominion->id}] *** {$amount} building {$buildingKey} finished.");

            if($amount > 0)
            {
                $building = Building::fromKey($buildingKey);

                TickChange::create([
                    'tick' => $dominion->round->ticks,
                    'source_type' => Building::class,
                    'source_id' => $building->id,
                    'target_type' => Dominion::class,
                    'target_id' => $dominion->id,
                    'amount' => $amount,
                    'status' => 0,
                    'type' => 'construction',
                ]);
            }
        }

        #$this->buildingService->update($dominion, $buildingsToAdd);
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
                    ->where('hours', 0)
                    ->get();

        xtLog("[{$this->dominion->id}] ** Finished deities in queue: " . $finishedDeitiesInQueue->count());

        if(!$finishedDeitiesInQueue->count())
        {
            return;
        }

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

    # Take improvements that are one tick away from finished and create or increment DominionImprovements.
    private function handleImprovements(Dominion $dominion): void
    {
        $finishedImprovementsInQueue = DB::table('dominion_queue')
                                        ->where('dominion_id',$dominion->id)
                                        ->where('resource', 'like', 'improvement%')
                                        ->where('hours', 0)
                                        ->get();

        xtLog("[{$this->dominion->id}] ** Finished improvements in queue: " . $finishedImprovementsInQueue->count());

        if(!$finishedImprovementsInQueue->count())
        {
            return;
        }

        $improvements = [];

        foreach($finishedImprovementsInQueue as $finishedImprovementInQueue)
        {
            $improvementKey = str_replace('improvement_', '', $finishedImprovementInQueue->resource);
            $amount = intval($finishedImprovementInQueue->amount);
            $improvement = Improvement::where('key', $improvementKey)->first();
            $improvements = [$improvement->key => $amount];
        }

        $this->improvementCalculator->createOrIncrementImprovements($dominion, $improvements);

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

    protected function handleLandGeneration(Dominion $dominion): void
    {
        if(!empty($dominion->tick->generated_land))
        {
            $this->queueService->queueResources('exploration', $dominion, ['land' => $dominion->tick->generated_land], 12);
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

        xtLog('*** 🦠 Has ' . $pestilences->count() . ' active pestilence(s)');

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

                xtLog("[{$afflicted->id}] *** {$dominionSpellPestilence->spell->name} :{$target->name} ({$target->id}) lost $peasantsKilled peasants to pestilence.");
            }
        }

        if(!empty($unitsGenerated))
        {
            xtLog("[{$afflicted->id}] *** Queuing units generated from pestilences.");

            foreach($unitsGenerated as $slot => $amount)
            {
                xtLog("[{$afflicted->id}] **** {$amount} unit$slot queued.");

                try {
                    DB::transaction(function () use ($unitsGenerated, $afflicted) {
                        foreach ($unitsGenerated as $slot => $amount) {
                            $this->queueService->queueResources('summoning', $afflicted, [('military_unit' . $slot) => $amount], 12);

                            xtLog("[{$afflicted->id}] *** Successfully queued {$amount} units of type {$slot} for {$afflicted->name}.");
                        }
                    });
                } catch (\Exception $e) {
                    Log::error("Failed to queue units: " . $e->getMessage());
                    xtLog("[{$afflicted->id}] *** Failed to queue units: " . $e->getMessage() . " ({$e->getLine()})");
                }
            }
        }
    }

    # Take research that is one tick away from finished and create DominionTech.
    private function handleResearch(Dominion $dominion): void
    {
        $finishedResearchesInQueue = DB::table('dominion_queue')
                                        ->where('dominion_id',$dominion->id)
                                        ->where('source', 'research')
                                        ->where('hours', 0)
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
        $finishedResourcesInQueue = DB::table('dominion_queue')
            ->where('dominion_id', $dominion->id)
            ->where('resource', 'like', 'resource%')
            ->whereIn('source', ['invasion', 'expedition', 'theft', 'desecration'])
            ->where('hours', 0)
            ->get();

        foreach($finishedResourcesInQueue as $finishedResourceInQueue)
        {

            $finishedResourceKey = str_replace($finishedResourceInQueue->resource, 'resource_', '');
            $finishedResource = Resource::fromKey($finishedResourceKey);
            $type = $finishedResourceInQueue->source;
            $amount = (int)$finishedResourceInQueue->amount;

            if(!$finishedResource)
            {
                xtLog("[{$dominion->id}] *** Resource {$finishedResourceKey} not found.", 'error');
                continue;
            }

            TickChange::create([
                'tick' => $dominion->round->ticks,
                'source_type' => Resource::class,
                'source_id' => $finishedResource->id,
                'target_type' => Dominion::class,
                'target_id' => $dominion->id,
                'amount' => $amount,
                'status' => 0,
                'type' => $type,
            ]);
        }

        foreach ($dominion->race->resources as $resourceKey)
        {
            $resource = Resource::fromKey($resourceKey);

            if(!$resource)
            {
                xtLog("[{$dominion->id}] *** Resource {$resourceKey} not found.");
                continue;
            }

            #$resourcesProduced = $finishedResourcesInQueue
            #    ->where('resource', 'resource_' . $resourceKey)
            #    ->sum('amount');


            #$resourcesNetChange[$resourceKey] = $resourcesProduced;

            $netProduction = $this->resourceCalculator->getNetProduction($dominion, $resourceKey);
            $production = $this->resourceCalculator->getProduction($dominion, $resourceKey);
            $consumption = $this->resourceCalculator->getConsumption($dominion, $resourceKey);

            xtLog("[{$dominion->id}] *** Resource: {$production} {$resourceKey} raw produced.");
            xtLog("[{$dominion->id}] *** Resource: {$consumption} {$resourceKey} consumed.");
            xtLog("[{$dominion->id}] *** Resource: {$netProduction} {$resourceKey} net produced.");

            if($production != 0)
            {
                TickChange::create([
                    'tick' => $dominion->round->ticks,
                    'source_type' => Resource::class,
                    'source_id' => $resource->id,
                    'target_type' => Dominion::class,
                    'target_id' => $dominion->id,
                    'amount' => $production,
                    'status' => 0,
                    'type' => 'production',
                ]);
            }

            if($consumption != 0)
            {
                TickChange::create([
                    'tick' => $dominion->round->ticks,
                    'source_type' => Resource::class,
                    'source_id' => $resource->id,
                    'target_type' => Dominion::class,
                    'target_id' => $dominion->id,
                    'amount' => -$consumption,
                    'status' => 0,
                    'type' => 'consumption',
                ]);
            }
        }

        #$this->resourceService->update($dominion, $resourcesNetChange);
    }

    # Take buildings that are one tick away from finished and create or increment DominionBuildings.
    private function handleTerrain(Dominion $dominion): void
    {
        $finishedTerrainsInQueue = DB::table('dominion_queue')
            ->where('dominion_id', $dominion->id)
            ->where('resource', 'like', 'terrain%')
            ->where('hours', 0)
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

    private function handleUnits(Dominion $dominion): void
    {
        return;
    }

    public function handleUnitGeneration(Dominion $dominion): void
    {
        foreach($dominion->race->units as $unit)
        {
            if(!empty($dominion->tick->{'generated_unit' . $unit->slot}))
            {
                $this->queueService->queueResources('summoning', $dominion, [('military_unit' . $unit->slot) => $dominion->tick->{'generated_unit' . $unit->slot}], ($unit->training_time + 0));
            }
        }
    }

    public function queueNotifications(Dominion $dominion): void
    {
        if($this->resourceCalculator->isOnBrinkOfStarvation($dominion) and !$dominion->isAbandoned())
        {
            xtLog("[{$this->dominion->id}] ** Queue starvation notifications");
            $this->notificationService->queueNotification('starvation_occurred');
        }

        if(array_sum($this->temporaryData[$dominion->round->id][$dominion->id]['units_attrited']) > 0 and !$dominion->isAbandoned())
        {
            xtLog("[{$this->dominion->id}] ** Queue attrition notifications ");
            $this->notificationService->queueNotification('attrition_occurred', $this->temporaryData[$dominion->round->id][$dominion->id]['units_attrited']);
        }
    }

    protected function updateSpells(Dominion $dominion): void
    {
        foreach($dominion->dominionSpells as $dominionSpell)
        {
            $updates = [];
    
            if($dominionSpell->duration > 0) {
                $updates['duration'] = DB::raw('GREATEST(0, duration - 1)');
            }
    
            if($dominionSpell->cooldown > 0) {
                $updates['cooldown'] = DB::raw('GREATEST(0, cooldown - 1)');
            }
    
            if (!empty($updates)) {
                $dominionSpell->update($updates);
            }
        }
    }

    protected function handleFinishedSpells(Dominion $dominion)
    {

        $finished = $dominion->dominionSpells()
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
            
            xtLog("[{$dominion->id}] ** Spell {$spell->name} has dissipated.");
        }

        if(!$dominion->isAbandoned())
        {
            if (!empty($beneficialSpells))
            {
                $this->notificationService->queueNotification('beneficial_magic_dissipated', $beneficialSpells);
            }

            if (!empty($harmfulSpells))
            {
                $this->notificationService->queueNotification('harmful_magic_dissipated', $harmfulSpells);
            }
        }
    }

    protected function handleFinishedQueues(Dominion $dominion)
    {
        if($dominion->isAbandoned() or $dominion->isLocked())
        {
            xtLog("[{$dominion->id}] *** Dominion is abandoned or locked, skipping queue handling");
            return;
        }

        $finished = DB::table('dominion_queue')
            ->where('dominion_id', $dominion->id)
            ->where('hours', '=', 0)
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

            if (!isset($dominion->tick->{$row->resource}))
            {
                xtLog("[{$dominion->id}] *** Resource does not exist on dominion->tick: {$row->resource} with amount {$row->amount} and source {$source}.");
                continue;
            }
            
            $dominion->tick->{$row->resource} += $row->amount;

            xtLog("[{$dominion->id}] *** {$row->amount} {$row->resource} completed and set for tick.");
        }
    }

    # Delete finished spells
    protected function deleteFinishedSpells(Dominion $dominion): void
    {
        $deletedSpells = DB::table('dominion_spells')
            ->where('dominion_id', $dominion->id)
            ->where('duration', '<=', 0)
            ->where('cooldown', '<=', 0)
            ->delete();
        
        xtLog("[{$dominion->id}] *** Deleted {$deletedSpells} finished spells.");
    }

    public function deleteFinishedQueues(Dominion $dominion)
    {
        $deletedQueueItems = DB::table('dominion_queue')
            ->where('dominion_id', $dominion->id)
            ->where('hours', '<=', 0)
            ->delete();

        xtLog("[{$dominion->id}] *** Deleted {$deletedQueueItems} finished queue items.");
    }

}