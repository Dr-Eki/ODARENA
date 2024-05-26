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

use OpenDominion\Models\Dominion;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Models\RoundWinner;

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
use OpenDominion\Services\Dominion\TickChangeService;
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
    protected $tickChangeService;
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
        $this->tickChangeService = app(TickChangeService::class);
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
    public function tick()
    {
        if (File::exists('storage/framework/down')) {
            xtLog('Tick at ' . $this->now . ' skipped.');
            return;
        }
    
        xtLog('Scheduled tick started at ' . $this->now . '.');
    
        foreach (Round::active()->get() as $round)
        {
            xtLog('Round ' . $round->number . ' tick started at ' . $this->now . '.');
    
            $round->is_ticking = 1;
            $round->save();
    
            xtLog('* Queue, process, and wait for dominion precalculations.');
            $this->processPrecalculationJobs($round);
    
            // One transaction for all of these
            DB::transaction(function () use ($round) {
                $this->temporaryData[$round->id] = [];
        
                xtLog('* Checking for win conditions');
                $this->handleWinConditions($round);
        
                xtLog('* Update all artefact aegises');
                $this->updateArtefactsAegises($round);
    
                xtLog('* Handle barbarian spawn');
                $this->handleBarbarianSpawn($round);
    
                xtLog('* Handle body decay');
                $this->handleBodyDecay($round);
    
                xtLog('* Update all dominions');
                $this->updateDominions($round);

                xtLog('* Advance all dominion queues');
                $this->advanceAllDominionQueues($round);

                xtLog('* Advance all trade route queues');
                $this->advanceAllTradeRouteQueues($round);

            });
    
            xtLog('* Queue, process, and wait for dominion jobs.');
            $this->processDominionJobs($round);
    
            xtLog('* Update all trade routes');
            $this->handleHoldsAndTradeRoutes($round);
    
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

        xtLog('* Commit tick changes');
        $this->handleTickCommit();
    
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
            $this->temporaryData[$dominion->round->id][$dominion->id]['units_attrited'] = $this->unitCalculator->getUnitsAttrited($dominion);

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
        $closingDelay = roundInt(config('ticking.queue_closing_delay') / 2);
        
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

        usleep($closingDelay);

        $this->now = now();
        
        xtLog('* Commit tick changes');
        $this->handleTickCommit();
        
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
    private function updateDominions(Round $round)
    {
        DB::table('dominions')
            ->join('dominion_tick', 'dominions.id', '=', 'dominion_tick.dominion_id')
            ->where('dominions.round_id', $round->id)
            ->where('dominions.is_locked', false)
            #->whereNotIn('dominion_tick.dominion_id', $stasisDominions)
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

    private function advanceAllDominionQueues(Round $round): void
    {
        DB::table('dominion_queue')
            ->join('dominions', 'dominion_queue.dominion_id', '=', 'dominions.id')
            ->where('dominions.round_id', $round->id)
            ->where('dominions.is_locked', false)
            ->where('dominions.protection_ticks', 0)
            ->where('dominion_queue.hours', '>', 0)
            ->decrement('dominion_queue.hours', 1);
    }

    private function advanceAllTradeRouteQueues(Round $round): void
    {
        DB::table('trade_route_queue')
            ->join('trade_routes', 'trade_route_queue.trade_route_id', '=', 'trade_routes.id')
            ->where('trade_routes.round_id', $round->id)
            ->where('trade_route_queue.tick', '>', 0)
            ->decrement('trade_route_queue.tick', 1);
    }

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

    public function processPrecalculationJobs(Round $round): void
    {
        $dominions = $round->activeDominions()
        ->where('protection_ticks', 0)
        ->inRandomOrder()
        ->get();

        // Queue up all dominions for precalculation (simultaneous processing)
        foreach ($dominions as $dominion)
        {
            xtLog("[{$dominion->id}] ** Queuing up precalculation of dominion: {$dominion->name}");
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
            xtLog("[{$dominion->id}] ** Queueing up dominion for processing job: {$dominion->name}");
            ProcessDominionJob::dispatch($dominion)->onQueue('tick');
        }
    
        // Wait for queue to clear
        $attempts = (int)config('ticking.queue_retry_attempts');
        $delay = (int)config('ticking.queue_check_delay');

        $initialDelay = roundInt($delay * 1);
        $closingDelay = roundInt($delay * 1);
        
        usleep($initialDelay); // Add initial delay before retrying
    
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

        usleep($closingDelay); // Add initial delay before retrying
    }

    public function processTradeRouteJobs(Round $round): void
    {
        $tradeRoutes = $round->tradeRoutes->whereIn('status', [0,1])->sortBy('id');

        // Queue up all dominions for precalculation (simultaneous processing)
        foreach ($tradeRoutes as $tradeRoute)
        {
            if($tradeRoute->hasQueues() or $tradeRoute->isActive())
            {
                xtLog("[TR{$tradeRoute->id}] ** Queueing up trade route for processing job: {$tradeRoute->dominion->name} (ID {$tradeRoute->dominion->id}) and {$tradeRoute->hold->name}");
                ProcessTradeRouteJob::dispatch($tradeRoute)->onQueue('tick');    
            }
            else
            {
                xtLog("[TR{$tradeRoute->id}] ** Skipping trade route for processing job: {$tradeRoute->dominion->name} (ID {$tradeRoute->dominion->id}) and {$tradeRoute->hold->name} (no queues or not active)");
            }
        }

        // Wait for queue to clear
        $attempts = (int)config('ticking.queue_retry_attempts');
        $delay = (int)config('ticking.queue_check_delay');
        $closingDelay = (int)config('ticking.queue_closing_delay');
        
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

        usleep($closingDelay);
    }
    
    public function processHoldJobs(Round $round): void
    {

        // Queue up all dominions for precalculation (simultaneous processing)
        foreach ($round->holds as $hold)
        {
            xtLog("[HL{$hold->id}] ** Queueing up hold for processing job: {$hold->name}");
            ProcessHoldJob::dispatch($hold)->onQueue('tick');
        }

        // Wait for queue to clear
        $attempts = (int)config('ticking.queue_retry_attempts');
        $delay = (int)config('ticking.queue_check_delay');
        $closingDelay = (int)config('ticking.queue_closing_delay');
        
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

        usleep($closingDelay);
    }

    public function handleTickCommit(): void
    {

        # Make sure that all queued jobs are finished
        $attempts = (int)config('ticking.queue_retry_attempts');
        $delay = (int)config('ticking.queue_check_delay');
        $openingDelay = (int)config('ticking.queue_opening_delay');
        $closingDelay = (int)config('ticking.queue_closing_delay');

        usleep(roundInt($openingDelay / 2));

        retry($attempts, function () use ($delay) {
            $i = isset($i) ? $i + 1 : 1;
        
            $infoString = sprintf(
                '** Waiting for queued jobs to finish before committing tick. Current queue: %s. Next check in: %s ms.',
                Redis::llen('queues:tick'),
                number_format($delay)
            );
        
            if (Redis::llen('queues:tick') !== 0) {
                xtLog($infoString);
                throw new Exception('Tick queue not finish');
            }
        }, $delay);

        usleep(roundInt($openingDelay / 2));

        $this->tickChangeService->commit();
    }

}
