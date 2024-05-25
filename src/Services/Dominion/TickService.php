<?php

// We want strict types here.
declare(strict_types=1);

namespace OpenDominion\Services\Dominion;

use DB;
use Exception;
use File;
use Log;
use Illuminate\Support\Facades\Redis;
use OpenDominion\Jobs\ProcessDominionJob;
use OpenDominion\Jobs\ProcessHoldJob;
use OpenDominion\Jobs\ProcessPrecalculationJob;
use OpenDominion\Jobs\ProcessTradeRouteJob;


use OpenDominion\Helpers\RoundHelper;

use OpenDominion\Models\Deity;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\Improvement;
use OpenDominion\Models\Spell;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Models\RoundWinner;
use OpenDominion\Models\Tech;

use OpenDominion\Calculators\Dominion\EspionageCalculator;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Calculators\Dominion\MoraleCalculator;
use OpenDominion\Calculators\Dominion\ResourceCalculator;
use OpenDominion\Calculators\Dominion\TickCalculator;
use OpenDominion\Calculators\Dominion\UnitCalculator;

use OpenDominion\Services\BarbarianService;
use OpenDominion\Services\HoldService;
use OpenDominion\Services\TradeService;
use OpenDominion\Services\NotificationService;
use OpenDominion\Services\Dominion\ArtefactService;
use OpenDominion\Services\Dominion\BuildingService;
use OpenDominion\Services\Dominion\DeityService;
use OpenDominion\Services\Dominion\InsightService;
use OpenDominion\Services\Dominion\ResourceService;
use OpenDominion\Services\Dominion\ResearchService;
use OpenDominion\Services\Dominion\TerrainService;
use OpenDominion\Services\Dominion\QueueService;
use Throwable;

class TickService
{
    /** @var Carbon */
    protected $now;
    protected $temporaryData = [];

    protected $espionageCalculator;
    protected $improvementCalculator;
    protected $magicCalculator;
    protected $militaryCalculator;
    protected $moraleCalculator;
    protected $notificationService;
    protected $prestigeCalculator;
    protected $productionCalculator;
    protected $realmCalculator;
    protected $resourceCalculator;
    protected $sorceryCalculator;
    protected $spellCalculator;
    protected $tickCalculator;
    protected $unitCalculator;
    protected $roundHelper;

    protected $artefactService;
    protected $barbarianService;
    protected $buildingService;
    protected $dominionStateService;
    protected $deityService;
    protected $holdService;
    protected $insightService;
    protected $queueService;
    protected $researchService;
    protected $resourceService;
    protected $terrainService;
    protected $tradeService;

    /**
     * TickService constructor.
     */
    public function __construct()
    {
        $this->now = now();
        $this->improvementCalculator = app(ImprovementCalculator::class);
        $this->notificationService = app(NotificationService::class);
        $this->resourceCalculator = app(ResourceCalculator::class);
        $this->espionageCalculator = app(EspionageCalculator::class);
        $this->moraleCalculator = app(MoraleCalculator::class);
        $this->tickCalculator = app(TickCalculator::class);
        $this->unitCalculator = app(UnitCalculator::class);
        
        $this->roundHelper = app(RoundHelper::class);

        $this->artefactService = app(ArtefactService::class);
        $this->buildingService = app(BuildingService::class);
        $this->barbarianService = app(BarbarianService::class);
        $this->dominionStateService = app(DominionStateService::class);
        $this->deityService = app(DeityService::class);
        $this->holdService = app(HoldService::class);
        $this->insightService = app(InsightService::class);
        $this->queueService = app(QueueService::class);
        $this->researchService = app(ResearchService::class);
        $this->resourceService = app(ResourceService::class);
        $this->terrainService = app(TerrainService::class);
        $this->tradeService = app(TradeService::class);

        /* These calculators need to ignore queued resources for the following tick */
        #$this->populationCalculator->setForTick(true);
        #$this->queueService->setForTick(true);
        /* OK, get it? */
    }

    /**
     * Does an hourly tick on all active dominions.
     *
     * @throws Exception|Throwable
     */
    public function tickHourly()
    {
        if (File::exists('storage/framework/down')) {
            xtLog('Tick at ' . $this->now . ' skipped.');
            return;
        }
    
        xtLog('Scheduled tick started at ' . $this->now . '.');
    
        foreach (Round::active()->get() as $round) {
            xtLog('Round ' . $round->number . ' tick started at ' . $this->now . '.');
    
            $round->is_ticking = 1;
            $round->save();
    
            xtLog('* Queue, process, and wait for dominion precalculations.');
            $this->processPrecalculationJobs($round);
    
            // One transaction for all of these
            DB::transaction(function () use ($round) {
                $this->temporaryData[$round->id] = [];
    
                $this->temporaryData[$round->id]['stasis_dominions'] = [];
    
                xtLog('* Checking for win conditions');
                $this->handleWinConditions($round);
    
                #xtLog('* Update invasion queues');
                #$this->updateAllInvasionQueues($round);
    
                #xtLog('* Update all other queues');
                #$this->updateAllOtherQueues($round, $this->temporaryData[$round->id]['stasis_dominions']);
    
                xtLog('* Update all artefact aegises');
                $this->updateArtefactsAegises($round);
    
                xtLog('* Handle barbarian spawn');
                $this->handleBarbarianSpawn($round);
    
                xtLog('* Handle body decay');
                $this->handleBodyDecay($round);
    
                xtLog('* Update all dominions');
                $this->updateDominions($round, $this->temporaryData[$round->id]['stasis_dominions']);
            });
    
            // Each job is a DB transaction
            xtLog('* Queue, process, and wait for dominion jobs.');
            $this->processDominionJobs($round);
    
            #xtLog('* Clear out all finished queues');
            #$this->clearFinishedQueues($round);

            // Separate DB transaction for trade routes
            DB::transaction(function () use ($round) {
                xtLog('* Update all trade routes');
                $this->handleHoldsAndTradeRoutes($round);
            });
    
            $this->now = now();
    
            xtLog('* Repeat queue, process, and wait for dominion precalculations.');
            $this->processPrecalculationJobs($round);
    
            unset($this->temporaryData[$round->id]);
    
            $round->fill([
                'ticks' => ($round->ticks + 1),
                'is_ticking' => 0,
                'has_ended' => isset($round->end_tick) ? (($round->ticks + 1) >= $round->end_tick) : false,
            ])->save();
        }
    
        $finishedAt = now();
        xtLog('Scheduled tick finished at ' . $finishedAt . '.');
    }

    /**
     * Does a daily tick on all active dominions and rounds.
     *
     * @throws Exception|Throwable
     */
    public function tickDaily()
    {
        xtLog('Scheduled daily tick started at ' . $this->now . '.');

        DB::transaction(function () {
            foreach (Round::with('dominions')->active()->get() as $round) {
                // toBase required to prevent ambiguous updated_at column in query
                $round->dominions()->toBase()->update([
                    'daily_land' => false,
                ], [
                    'event' => 'tick',
                ]);
            }
        });

        xtLog('Scheduled daily tick finished at ' . now() . '.');
    }

    protected function cleanupActiveSpells(Dominion $dominion)
    {
        #$finished = DB::table('dominion_spells')
        #    ->where('dominion_id', $dominion->id)
        #    ->where('duration', '<=', 0)
        #    ->where('cooldown', '<=', 0)
        #    ->get();

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

        DB::table('dominion_spells')
            ->where('dominion_id', $dominion->id)
            ->where('duration', '<=', 0)
            ->where('cooldown', '<=', 0)
            ->delete();
    }

    protected function cleanupQueues(Dominion $dominion)
    {
        $finished = DB::table('dominion_queue')
            ->where('dominion_id', $dominion->id)
            ->where('hours', '<=', 0)
            ->get();

        foreach ($finished->groupBy('source') as $source => $group)
        {
            if(!$dominion->isAbandoned())
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
        }

        DB::transaction(function () use ($dominion)
        {
            DB::table('dominion_queue')
                ->where('dominion_id', $dominion->id)
                ->where('hours', '<=', 0)
                ->delete();
        });
    }

    public function clearFinishedQueues(Round $round)
    {
        $attempts = 10; // Number of attempts to retry
        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                DB::transaction(function () use ($round) {
                    $round->dominionQueues()->where('hours', '<=', 0)->delete();
                });
                break; // If successful, exit the loop
            } catch (\Illuminate\Database\QueryException $e) {
                if ($e->getCode() == 1213 && $attempt < $attempts) { // Deadlock
                    sleep(1); // Wait a bit before retrying
                    continue;
                }
                throw $e; // Re-throw the exception if it's not a deadlock or attempts exceeded
            }
        }
    }

    /**
     * Does an hourly tick on all active dominions.
     *
     * @throws Exception|Throwable
     */
    public function tickManually(Dominion $dominion): void
    {

        xtLog("[{$dominion->id}] * Manual tick started for {$dominion->name} at {$this->now}.");

        if($dominion->protection_ticks <= 0)
        {
            xtLog("[{$dominion->id}] ** Manual tick skipped, protection ticks are <=0.");
            return;
        }

        xtLog("[{$dominion->id}] ** Precalculating tick for dominion");
        $this->tickCalculator->precalculateTick($dominion);

        DB::transaction(function () use ($dominion) {
            $this->temporaryData[$dominion->round->id][$dominion->id] = [];
            $this->temporaryData[$dominion->round->id][$dominion->id]['stasis_dominions'] = [];
            $this->temporaryData[$dominion->round->id][$dominion->id]['units_attrited'] = $this->unitCalculator->getUnitsAttrited($dominion);

            #xtLog("[{$dominion->id}] ** Updating queues");
            #$this->updateDominionQueues($dominion);

            xtLog("[{$dominion->id}] ** Update dominion (from dominion_tick)");
            $this->updateDominion($dominion);
        });

        xtLog("[{$dominion->id}] ** Audit and repair terrain");
        $this->terrainService->auditAndRepairTerrain($dominion);

        xtLog("[{$dominion->id}] ** Queuing up manual tick in ProcessDominionJob");
        ProcessDominionJob::dispatch($dominion)->onQueue('manual_tick');

        // Wait for queue to clear
        $attempts = (int)config('ticking.queue_retry_attempts');
        $delay = (int)config('ticking.queue_check_delay');
        
        retry($attempts, function () use ($delay, $dominion) {
            $i = isset($i) ? $i + 1 : 1;
        
            $infoString = sprintf(
                '[%s] *** Waiting for queued ProcessDominionJob (queue:manual_tick) to finish. Current queue: %s. Next check in: %s ms.',
                $dominion->id,
                Redis::llen('queues:manual_tick'),
                number_format($delay)
            );
        
            if (Redis::llen('queues:manual_tick') !== 0) {
                xtLog($infoString);
                throw new Exception('Tick queue not finish');
            }
        }, $delay);

        $this->now = now();
        
        xtLog("[{$dominion->id}] ** Saving dominion state");
        $this->dominionStateService->saveDominionState($dominion);
        
        xtLog("[{$dominion->id}] ** Sending notificaitons");
        $this->notificationService->sendNotifications($dominion, 'hourly_dominion');

        xtLog("[{$dominion->id}] ** Precalculating tick for dominion again");
        $this->tickCalculator->precalculateTick($dominion, true);

        $dominion->save();

        $this->now = now();

        xtLog("[{$dominion->id}] * Manual tick finished for {$dominion->name} at {$this->now}.");
        
    }

    // Update dominions
    private function updateDominions(Round $round, array $stasisDominions)
    {
        DB::table('dominions')
            ->join('dominion_tick', 'dominions.id', '=', 'dominion_tick.dominion_id')
            ->where('dominions.round_id', $round->id)
            ->where('dominions.is_locked', false)
            ->whereNotIn('dominion_tick.dominion_id', $stasisDominions)
            ->where('dominions.protection_ticks', '=', 0)
            ->update([
                'dominions.prestige' => DB::raw('dominions.prestige + dominion_tick.prestige'),
                'dominions.xp' => DB::raw('dominions.xp + dominion_tick.xp'),
                'dominions.peasants' => DB::raw('GREATEST(0, dominions.peasants + dominion_tick.peasants)'),
                'dominions.peasants_last_hour' => DB::raw('dominion_tick.peasants'),
                'dominions.morale' => DB::raw('dominions.morale + dominion_tick.morale'),
                'dominions.spy_strength' => DB::raw('dominions.spy_strength + dominion_tick.spy_strength'),
                'dominions.wizard_strength' => DB::raw('dominions.wizard_strength + dominion_tick.wizard_strength'),

                'dominions.military_draftees' => DB::raw('dominions.military_draftees + dominion_tick.military_draftees'),
                'dominions.military_unit1' => DB::raw('dominions.military_unit1 + dominion_tick.military_unit1 - dominion_tick.attrition_unit1'),
                'dominions.military_unit2' => DB::raw('dominions.military_unit2 + dominion_tick.military_unit2 - dominion_tick.attrition_unit2'),
                'dominions.military_unit3' => DB::raw('dominions.military_unit3 + dominion_tick.military_unit3 - dominion_tick.attrition_unit3'),
                'dominions.military_unit4' => DB::raw('dominions.military_unit4 + dominion_tick.military_unit4 - dominion_tick.attrition_unit4'),
                'dominions.military_unit5' => DB::raw('dominions.military_unit5 + dominion_tick.military_unit5 - dominion_tick.attrition_unit5'),
                'dominions.military_unit6' => DB::raw('dominions.military_unit6 + dominion_tick.military_unit6 - dominion_tick.attrition_unit6'),
                'dominions.military_unit7' => DB::raw('dominions.military_unit7 + dominion_tick.military_unit7 - dominion_tick.attrition_unit7'),
                'dominions.military_unit8' => DB::raw('dominions.military_unit8 + dominion_tick.military_unit8 - dominion_tick.attrition_unit8'),
                'dominions.military_unit9' => DB::raw('dominions.military_unit9 + dominion_tick.military_unit9 - dominion_tick.attrition_unit9'),
                'dominions.military_unit10' => DB::raw('dominions.military_unit10 + dominion_tick.military_unit10 - dominion_tick.attrition_unit10'),
                'dominions.military_spies' => DB::raw('dominions.military_spies + dominion_tick.military_spies'),
                'dominions.military_wizards' => DB::raw('dominions.military_wizards + dominion_tick.military_wizards'),
                'dominions.military_archmages' => DB::raw('dominions.military_archmages + dominion_tick.military_archmages'),

                'dominions.land' => DB::raw('dominions.land + dominion_tick.land'),
                #'dominions.land_plain' => DB::raw('dominions.land_plain + dominion_tick.land_plain'),
                #'dominions.land_mountain' => DB::raw('dominions.land_mountain + dominion_tick.land_mountain'),
                #'dominions.land_swamp' => DB::raw('dominions.land_swamp + dominion_tick.land_swamp'),
                #'dominions.land_cavern' => DB::raw('dominions.land_cavern + dominion_tick.land_cavern'),
                #'dominions.land_forest' => DB::raw('dominions.land_forest + dominion_tick.land_forest'),
                #'dominions.land_hill' => DB::raw('dominions.land_hill + dominion_tick.land_hill'),
                #'dominions.land_water' => DB::raw('dominions.land_water + dominion_tick.land_water'),

                'dominions.protection_ticks' => 0,#DB::raw('dominions.protection_ticks + dominion_tick.protection_ticks'),
                'dominions.ticks' => DB::raw('dominions.ticks + 1'),

                'dominions.last_tick_at' => DB::raw('now()')
            ]);
    }

    // Update dominion: used for tickManually
    private function updateDominion(Dominion $dominion)
    {
        DB::table('dominions')
            ->join('dominion_tick', 'dominions.id', '=', 'dominion_tick.dominion_id')
            ->where('dominions.id', $dominion->id)
            ->where('dominions.protection_ticks', '>', 0)
            ->where('dominions.is_locked', false)
            ->update([
                'dominions.prestige' => DB::raw('dominions.prestige + dominion_tick.prestige'),
                'dominions.xp' => DB::raw('dominions.xp + dominion_tick.xp'),
                'dominions.peasants' => DB::raw('GREATEST(0, dominions.peasants + dominion_tick.peasants)'),
                'dominions.peasants_last_hour' => DB::raw('dominion_tick.peasants'),
                'dominions.morale' => DB::raw('dominions.morale + dominion_tick.morale'),
                'dominions.spy_strength' => DB::raw('dominions.spy_strength + dominion_tick.spy_strength'),
                'dominions.wizard_strength' => DB::raw('dominions.wizard_strength + dominion_tick.wizard_strength'),

                'dominions.military_draftees' => DB::raw('dominions.military_draftees + dominion_tick.military_draftees'),
                'dominions.military_unit1' => DB::raw('dominions.military_unit1 + dominion_tick.military_unit1 - dominion_tick.attrition_unit1'),
                'dominions.military_unit2' => DB::raw('dominions.military_unit2 + dominion_tick.military_unit2 - dominion_tick.attrition_unit2'),
                'dominions.military_unit3' => DB::raw('dominions.military_unit3 + dominion_tick.military_unit3 - dominion_tick.attrition_unit3'),
                'dominions.military_unit4' => DB::raw('dominions.military_unit4 + dominion_tick.military_unit4 - dominion_tick.attrition_unit4'),
                'dominions.military_unit5' => DB::raw('dominions.military_unit5 + dominion_tick.military_unit5 - dominion_tick.attrition_unit5'),
                'dominions.military_unit6' => DB::raw('dominions.military_unit6 + dominion_tick.military_unit6 - dominion_tick.attrition_unit6'),
                'dominions.military_unit7' => DB::raw('dominions.military_unit7 + dominion_tick.military_unit7 - dominion_tick.attrition_unit7'),
                'dominions.military_unit8' => DB::raw('dominions.military_unit8 + dominion_tick.military_unit8 - dominion_tick.attrition_unit8'),
                'dominions.military_unit9' => DB::raw('dominions.military_unit9 + dominion_tick.military_unit9 - dominion_tick.attrition_unit9'),
                'dominions.military_unit10' => DB::raw('dominions.military_unit10 + dominion_tick.military_unit10 - dominion_tick.attrition_unit10'),
                'dominions.military_spies' => DB::raw('dominions.military_spies + dominion_tick.military_spies'),
                'dominions.military_wizards' => DB::raw('dominions.military_wizards + dominion_tick.military_wizards'),
                'dominions.military_archmages' => DB::raw('dominions.military_archmages + dominion_tick.military_archmages'),

                'dominions.land' => DB::raw('dominions.land + dominion_tick.land'),
                #'dominions.land_plain' => DB::raw('dominions.land_plain + dominion_tick.land_plain'),
                #'dominions.land_mountain' => DB::raw('dominions.land_mountain + dominion_tick.land_mountain'),
                #'dominions.land_swamp' => DB::raw('dominions.land_swamp + dominion_tick.land_swamp'),
                #'dominions.land_cavern' => DB::raw('dominions.land_cavern + dominion_tick.land_cavern'),
                #'dominions.land_forest' => DB::raw('dominions.land_forest + dominion_tick.land_forest'),
                #'dominions.land_hill' => DB::raw('dominions.land_hill + dominion_tick.land_hill'),
                #'dominions.land_water' => DB::raw('dominions.land_water + dominion_tick.land_water'),

                'dominions.protection_ticks' => DB::raw('dominions.protection_ticks + dominion_tick.protection_ticks'),
                'dominions.ticks' => DB::raw('dominions.ticks + 1'),

                'dominions.last_tick_at' => DB::raw('now()')
            ]);
    }

    // Update spells for a specific dominion
    #private function updateDominionSpells(Dominion $dominion): void
    #{
    #    DB::table('dominion_spells')
    #        ->where('dominion_id', $dominion->id)
    #        ->update([
    #            'duration' => DB::raw('GREATEST(0, `duration` - 1)'),
    #            'cooldown' => DB::raw('GREATEST(0, `cooldown` - 1)'),
    #            'dominion_spells.updated_at' => $this->now,
    #        ]);
    #}

    // Update deity duration for a specific dominion
    #private function updateDominionDeity(Dominion $dominion): void
    #{
    #    DB::table('dominion_deity')
    #        ->join('dominions', 'dominion_deity.dominion_id', '=', 'dominions.id')
    #        ->where('dominions.id', $dominion->id)
    #        ->update([
    #            'duration' => DB::raw('`duration` + 1'),
    #            'dominion_deity.updated_at' => $this->now,
    #        ]);
    #}

    // Update queues for a specific dominion
    // We don't have to worry about stasis here because the dominion is in protection
    private function updateDominionQueues(Dominion $dominion): void
    {
        $dominion->queues()
            ->where('hours', '>', 0)
            ->decrement('hours');
    }



    // Update spells for all dominions
    private function updateAllSpells(Round $round): void
    {
        # Update spells where cooldown is >0 and duration is >0
        DB::table('dominion_spells')
            ->join('dominions', 'dominion_spells.dominion_id', '=', 'dominions.id')
            ->where('dominions.round_id', $round->id)
            ->where('dominions.protection_ticks', '=', 0)
            ->update([
                'duration' => DB::raw('GREATEST(0, `duration` - 1)'),
                'cooldown' => DB::raw('GREATEST(0, `cooldown` - 1)'),
                'dominion_spells.updated_at' => $this->now,
            ]);
    }

    // Update deities duration for all dominions
    private function updateAllDeities(Round $round): void
    {
        DB::table('dominion_deity')
            ->join('dominions', 'dominion_deity.dominion_id', '=', 'dominions.id')
            ->where('dominions.round_id', $round->id)
            ->update([
                'duration' => DB::raw('`duration` + 1'),
                'dominion_deity.updated_at' => $this->now,
            ]);
    }

    // Update invasion queues for all dominions
    private function updateAllInvasionQueues(Round $round): void
    {
        DB::table('dominion_queue')
            ->join('dominions', 'dominion_queue.dominion_id', '=', 'dominions.id')
            ->where('dominions.round_id', $round->id)
            ->where('dominions.protection_ticks', '=', 0)
            ->where('source', '=', 'invasion')
            ->update([
                'hours' => DB::raw('`hours` - 1'),
                'dominion_queue.updated_at' => $this->now,
            ]);
    }

    // Update other queues (with stasis dominions) for all dominions
    private function updateAllOtherQueues(Round $round, array $stasisDominions)
    {
        DB::table('dominion_queue')
            ->join('dominions', 'dominion_queue.dominion_id', '=', 'dominions.id')
            ->where('dominions.round_id', $round->id)
            ->where('dominions.protection_ticks', '=', 0)
            ->whereNotIn('dominions.id', $stasisDominions)
            ->where('source', '!=', 'invasion')
            ->update([
                'hours' => DB::raw('`hours` - 1'),
                'dominion_queue.updated_at' => $this->now,
            ]);
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
            $this->buildingService->update($dominion, [$buildingKey => $amount]);
        }

        # Handle self-destruct
        #if($buildingsDestroyed = $dominion->tick->buildings_destroyed)
        #{
        #    $this->buildingService->update($dominion, $buildingsDestroyed);
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
            ($improvementInterestPerk = (mt_rand((int)$dominion->race->getPerkValue('improvements_interest_random_min')*100, (int)$dominion->race->getPerkValue('improvements_interest_random_max')*100))/100)
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
            ->whereIn('source', ['exploration', 'invasion', 'expedition', 'theft', 'desecration'])
            ->where('hours', 1)
            ->get();

        foreach ($dominion->race->resources as $resourceKey) {
            $resourcesProduced = $finishedResourcesInQueue
                ->where('resource', 'resource_' . $resourceKey)
                ->sum('amount');

            #$resourcesProduced += $this->resourceCalculator->getProduction($dominion, $resourceKey);
            #$resourcesConsumed = $this->resourceCalculator->getConsumption($dominion, $resourceKey);

            $resourcesProduced += $this->resourceCalculator->getNetProduction($dominion, $resourceKey);

            $resourcesNetChange[$resourceKey] = $resourcesProduced;
        }

        $this->resourceService->updateResources($dominion, $resourcesNetChange);
    }
    /*
    private function handleResources(Dominion $dominion): void
    {
        $resourcesProduced = [];
        $resourcesConsumed = [];
        $resourcesNetChange = [];
    
        foreach($dominion->race->resources as $resourceKey)
        {
            $resourcesProduced[$resourceKey] = 0;
            $resourcesConsumed[$resourceKey] = 0;
            $resourcesNetChange[$resourceKey] = 0;
        }
    
        $finishedResourcesInQueue = DB::table('dominion_queue')
                                        ->where('dominion_id',$dominion->id)
                                        ->where('resource', 'like', 'resource%')
                                        ->whereIn('source', ['exploration', 'invasion', 'expedition', 'theft', 'desecration'])
                                        ->where('hours',1)
                                        ->get();
    
        foreach($finishedResourcesInQueue as $finishedResourceInQueue)
        {
            $resourceKey = str_replace('resource_', '', $finishedResourceInQueue->resource);
            $amount = intval($finishedResourceInQueue->amount);
    
            # Silently discard resources this faction doesn't use, if we somehow have any incoming from queue.
            if(in_array($resourceKey, $dominion->race->resources))
            {
                $resourcesProduced[$resourceKey] += $amount;
            }
        }
    
        # Add production.
        foreach($dominion->race->resources as $resourceKey)
        {
            $resourcesProduced[$resourceKey] += $this->resourceCalculator->getProduction($dominion, $resourceKey);
            $resourcesConsumed[$resourceKey] += $this->resourceCalculator->getConsumption($dominion, $resourceKey);
            $resourcesNetChange[$resourceKey] += $resourcesProduced[$resourceKey] - $resourcesConsumed[$resourceKey];
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
    */

    private function updateArtefactsAegises(Round $round): void
    {
        if(!in_array($round->mode, ['artefacts', 'artefacts-pack']))
        {
            return;
        }

        # Update artefact aegis
        foreach($round->realms as $realm)
        {
            $this->artefactService->updateArtefactAegis($realm);
        }
    }

    # Take buildings that are one tick away from finished and create or increment DominionBuildings.
    private function handleTerrain(Dominion $dominion): void
    {
        $finishedTerrainsInQueue = DB::table('dominion_queue')
                                        ->where('dominion_id',$dominion->id)
                                        ->where('resource', 'like', 'terrain%')
                                        ->where('hours',1)
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
    
            $this->queueService->queueResources('evolution', $dominion, ['military_unit' . $targetSlot => $evolvedUnitAmount], ($evolvedUnit->training_time + 1)); # +1 because 12 becomes 11 otherwise
        }

        foreach($evolvedUnitsFrom as $sourceSlot => $amountEvolved)
        {
            $dominion->{'military_unit' . $sourceSlot} -= $amountEvolved;
        }

        $dominion->save();

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

        #$this->queueService->setForTick(false);
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

        #$this->queueService->setForTick(true);
    }

    public function handleWinConditions(Round $round): void
    {

        # If end tick is set and this is the last tick, declareWinner()
        if(isset($round->end_tick) and $round->end_tick == ($round->ticks + 1))
        {
            $this->declareWinner($round);
        }

        # If we don't already have a countdown, see if any dominion triggers it.
        if(in_array($round->mode, ['artefacts','artefacts-packs']))
        {
            $goal = $round->goal;

            # Grab realms from $round have count of realmArtefacts >= $round->goal
            $realms = $round->realms()->whereHas('artefacts', function($query) use ($goal) {
                $query->havingRaw('COUNT(*) >= ?', [$goal]);
            })->get();

            if($realms->count() and !$round->hasCountdown())
            {
                $round->end_tick = $round->ticks + $this->roundHelper->getRoundCountdownTickLength() * 2;
                $round->save();

                $realms->load('artefacts');

                $data['end_tick'] = $round->end_tick;
                foreach($realms as $realm)
                {
                    $data['realms'][] = ['realm_id' => $realm->id, 'artefacts' => $realm->artefacts->pluck('key')->toArray()];
                }

                GameEvent::create([
                    'round_id' => $round->id,
                    'source_type' => Round::class,
                    'source_id' => $round->id,
                    'target_type' => NULL,
                    'target_id' => NULL,
                    'type' => 'round_countdown_artefacts',
                    'data' => $data,
                    'tick' => $round->ticks
                ]);
                $round->save(['event' => HistoryService::EVENT_ROUND_COUNTDOWN]);

                if(config('game.extended_logging')) { Log::debug('*** Countdown triggered by ' . $realms->count() . ' realm(s)'); }
            }

            if(!$realms->count() and $round->hasCountdown())
            {
                # Reset the end
                $round->end_tick = NULL;
                $round->save();

                # Create a cancelled event
                GameEvent::create([
                    'round_id' => $round->id,
                    'source_type' => Round::class,
                    'source_id' => $round->id,
                    'target_type' => NULL,
                    'target_id' => NULL,
                    'type' => 'round_countdown_artefacts_cancelled',
                    'data' => NULL,
                    'tick' => $round->ticks
                ]);
            }
        }
        elseif(!$round->hasCountdown())
        {
            # For fixed length rounds, show a countdown when there are COUNTDOWN_DURATION_TICKS ticks left.
            if(in_array($round->mode, ['standard-duration', 'deathmatch-duration', 'factions-duration','packs-duration']))
            {
                if($round->ticks >= ($round->end_tick - $this->roundHelper->getRoundCountdownTickLength()))
                {
                    GameEvent::create([
                        'round_id' => $round->id,
                        'source_type' => Round::class,
                        'source_id' => $round->id,
                        'target_type' => NULL,
                        'target_id' => NULL,
                        'type' => 'round_countdown_duration',
                        'data' => ['end_tick' => $round->end_tick],
                        'tick' => $round->ticks
                    ]);
                    $round->save(['event' => HistoryService::EVENT_ROUND_COUNTDOWN]);

                    if(config('game.extended_logging')) { Log::debug('*** Countdown triggered by ticks'); }
                }
            }
            # For indefinite rounds, create a countdown.
            if(in_array($round->mode, ['standard', 'deathmatch', 'factions', 'packs']))
            {
                # Grab a random dominion from $round where land >= $round->goal
                if($dominion = $round->dominions()->where('land', '>=', $round->goal)->inRandomOrder()->first())
                {
                    $endTick = $round->ticks + $this->roundHelper->getRoundCountdownTickLength();

                    GameEvent::create([
                        'round_id' => $dominion->round_id,
                        'source_type' => Dominion::class,
                        'source_id' => $dominion->id,
                        'target_type' => Realm::class,
                        'target_id' => $dominion->realm_id,
                        'type' => 'round_countdown',
                        'data' => ['end_tick' => $endTick],
                        'tick' => $dominion->round->ticks
                    ]);
                    $dominion->save(['event' => HistoryService::EVENT_ROUND_COUNTDOWN]);
                    $round->end_tick = $endTick;
                    $round->save();

                    if(config('game.extended_logging')) { Log::debug('*** Countdown triggered by ' . $dominion->name . ' in realm #' . $dominion->realm->number); }
                }
            }
        }
    }

    private function declareWinner(Round $round): void
    {
        $winners = [];
    
        if(in_array($round->mode, ['artefacts', 'artefacts-packs']))
        {
            $goal = $round->goal;
    
            $winnerRealms = $round->realms()->whereHas('artefacts', function($query) use ($goal) {
                $query->havingRaw('COUNT(*) >= ?', [$goal]);
            })->get();
    
            $data['count'] = $winnerRealms->count();
    
            foreach($winnerRealms as $winnerRealm)
            {
                $winners[] = [
                    'round_id' => $round->id,
                    'winner_type' => Realm::class,
                    'winner_id' => $winnerRealm->id,
                    'type' => $data['count'] > 1 ? 'draw' : 'win',
                    'data' => json_encode($data)
                ];
            }
        }
        else
        {
            $winnerDominions = Dominion::where('round_id', $round->id)
                ->whereRaw('land = (select max(land) from dominions where round_id = ?)', [$round->id])
                ->get();
    
            $data['count'] = $winnerDominions->count();
    
            foreach($winnerDominions as $winnerDominion)
            {
                $data['winners'][$winnerDominion->id]['land'] = $winnerDominion->land;
                $data['winners'][$winnerDominion->id]['networth'] = $winnerDominion->land;
    
                $winners[] = [
                    'round_id' => $round->id,
                    'winner_type' => Dominion::class,
                    'winner_id' => $winnerDominion->id,
                    'type' => $data['count'] > 1 ? 'draw' : 'win',
                    'data' => json_encode($data)
                ];
            }
        }
    
        RoundWinner::insert($winners);
    }

    public function handleBarbarianSpawn(Round $round): void
    {
        if(!$round->getSetting('barbarians'))
        {
            return;
        }

        $spawnBarbarian = rand(1, (int)config('barbarians.settings.ONE_IN_CHANCE_TO_SPAWN'));

        Log::Debug('* Barbarian spawn chance value: '. $spawnBarbarian . ' (spawn if this value is 1).');

        if($spawnBarbarian === 1)
        {
            $this->barbarianService->createBarbarian($round);
        }
    }

    public function handleBodyDecay(Round $round)
    {
        # Calculate body decay
        if(($decay = (int)$this->resourceCalculator->getRoundResourceDecay($round, 'body')))
        {
            Log::info('* Body decay: ' . number_format($decay) . ' / ' . number_format($round->resource_body));
            $this->resourceService->updateRoundResources($round, ['body' => (-$decay)]);
        }
    }

    public function processPrecalculationJobs(Round $round): void
    {
        $dominions = $round->activeDominions()
        ->where('protection_ticks', 0)
        ->inRandomOrder()
        ->get();

        // Queue up all dominions for precalculation (simultaneous processing)
        foreach ($dominions as $dominion)
        {
            xtLog("** [{$dominion->id}] Queuing up precalculation of dominion: {$dominion->name}");
            ProcessPrecalculationJob::dispatch($dominion)->onQueue('tick');
        }

        // Wait for queue to clear
        $attempts = (int)config('ticking.queue_retry_attempts');
        $delay = (int)config('ticking.queue_check_delay');
        
        retry($attempts, function () use ($delay) {
            $i = isset($i) ? $i + 1 : 1;
        
            $infoString = sprintf(
                '** Waiting for queued ProcessPrecalculationJob (queue:tick) to finish. Current queue: %s. Next check in: %s ms.',
                Redis::llen('queues:tick'),
                number_format($delay)
            );
        
            if (Redis::llen('queues:tick') !== 0) {
                xtLog($infoString);
                throw new Exception('Tick queue not finish');
            }
        }, $delay);
    }

    public function processDominionJobs(Round $round): void
    {
        $dominions = $round->activeDominions()
            ->where('protection_ticks', 0)
            ->inRandomOrder()
            ->get();
    
        // Queue up all dominions for ticking (simultaneous processing)
        foreach ($dominions as $dominion) {
            xtLog("** [{$dominion->id}] Queueing up dominion for processing job: {$dominion->name}");
            ProcessDominionJob::dispatch($dominion)->onQueue('tick');
        }
    
        // Wait for queue to clear
        $attempts = (int)config('ticking.queue_retry_attempts');
        $delay = (int)config('ticking.queue_check_delay');

        $initialDelay = roundInt(($delay * 1) / 1000);
        $closingDelay = roundInt(($delay * 1) / 1000);
        
        sleep($initialDelay); // Add initial delay before retrying
    
        retry($attempts, function () use ($delay) {
            $i = isset($i) ? $i + 1 : 1;
    
            $infoString = sprintf(
                '** Waiting for queued ProcessDominionJob (queue:tick) to finish. Current queue: %s. Next check in: %s ms.',
                Redis::llen('queues:tick'),
                number_format($delay)
            );
    
            if (Redis::llen('queues:tick') !== 0) {
                xtLog($infoString);
                throw new Exception('Tick queue not finish');
            }
        }, $delay);

        sleep($closingDelay); // Add initial delay before retrying
    }

    public function processTradeRouteJobs(Round $round): void
    {
        $activeTradeRoutes = $round->tradeRoutes->whereIn('status', [0,1])->sortBy('id');

        // Queue up all dominions for precalculation (simultaneous processing)
        foreach ($activeTradeRoutes as $tradeRoute)
        {
            xtLog("** [TR{$tradeRoute->id}] Queueing up trade route for processing job: {$tradeRoute->dominion->name} (ID {$tradeRoute->dominion->id}) and {$tradeRoute->hold->name}");
            ProcessTradeRouteJob::dispatch($tradeRoute)->onQueue('tick');
        }

        // Wait for queue to clear
        $attempts = (int)config('ticking.queue_retry_attempts');
        $delay = (int)config('ticking.queue_check_delay');
        
        retry($attempts, function () use ($delay) {
            $i = isset($i) ? $i + 1 : 1;
        
            $infoString = sprintf(
                '** Waiting for queued ProcessTradeRouteJob (queue:tick) to finish. Current queue: %s. Next check in: %s ms.',
                Redis::llen('queues:tick'),
                number_format($delay)
            );
        
            if (Redis::llen('queues:tick') !== 0) {
                xtLog($infoString);
                throw new Exception('Tick queue not finish');
            }
        }, $delay);
    }
    
    public function processHoldJobs(Round $round): void
    {

        // Queue up all dominions for precalculation (simultaneous processing)
        foreach ($round->holds as $hold)
        {
            xtLog("** [HL{$hold->id}] Queueing up hold for processing job: {$hold->name}");
            ProcessHoldJob::dispatch($hold)->onQueue('tick');
        }

        // Wait for queue to clear
        $attempts = (int)config('ticking.queue_retry_attempts');
        $delay = (int)config('ticking.queue_check_delay');
        
        retry($attempts, function () use ($delay) {
            $i = isset($i) ? $i + 1 : 1;
        
            $infoString = sprintf(
                '** Waiting for queued ProcessHoldJob (queue:tick) to finish. Current queue: %s. Next check in: %s ms.',
                Redis::llen('queues:tick'),
                number_format($delay)
            );
        
            if (Redis::llen('queues:tick') !== 0) {
                xtLog($infoString);
                throw new Exception('Tick queue not finish');
            }
        }, $delay);
    }

    public function handleHoldsAndTradeRoutes(Round $round): void
    {
        if(!$round->getSetting('trade_routes'))
        {
            return;
        }

        // Holds first to update hold resources from production
        $this->processHoldJobs($round);
        $this->processTradeRouteJobs($round);

        xtLog('** Handle holds ticking (sentiment and price updates)');
        $holdService = app(HoldService::class);

        xtLog('** Update all hold sentiments');
        $holdService->updateAllHoldSentiments($round);

        $discoverHoldChance = rand(1, (int)config('holds.tick_discover_hold_chance'));

        xtLog("** Hold discovery chance value: $discoverHoldChance (discover if this value is 1).");

        if($discoverHoldChance == 1)
        {
            xtLog('*** Hold discovered');
            $holdService->discoverHold($round);
        }
    }

}
