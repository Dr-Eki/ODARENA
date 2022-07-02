<?php

namespace OpenDominion\Services\Dominion;

use DB;
use File;
use Exception;
use Log;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

use OpenDominion\Calculators\RealmCalculator;
use OpenDominion\Calculators\Dominion\BarbarianCalculator;
use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\ConversionCalculator;
use OpenDominion\Calculators\Dominion\DeityCalculator;
use OpenDominion\Calculators\Dominion\EspionageCalculator;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\MoraleCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\PrestigeCalculator;
use OpenDominion\Calculators\Dominion\ProductionCalculator;
use OpenDominion\Calculators\Dominion\ResourceCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Calculators\Dominion\SpellDamageCalculator;
use OpenDominion\Helpers\ImprovementHelper;
use OpenDominion\Helpers\RoundHelper;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Models\Building;
use OpenDominion\Models\Deity;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\DominionBuilding;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\Improvement;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Resource;
use OpenDominion\Models\Round;
use OpenDominion\Models\Spell;
use OpenDominion\Models\Dominion\Tick;

use OpenDominion\Services\NotificationService;
use OpenDominion\Services\Dominion\InsightService;
use OpenDominion\Services\Dominion\ProtectionService;
use Throwable;

class TickService
{
    protected const EXTENDED_LOGGING = false;

    /** @var Carbon */
    protected $now;

    /** @var LandCalculator */
    protected $landCalculator;

    /** @var NotificationService */
    protected $notificationService;

    /** @var PopulationCalculator */
    protected $populationCalculator;

    /** @var ProductionCalculator */
    protected $productionCalculator;

    /** @var QueueService */
    protected $queueService;

    /** @var BarbarianService */
    protected $barbarianService;

    /** @var BarbarianCalculator */
    protected $barbarianCalculator;

    /** @var SpellCalculator */
    protected $spellCalculator;

    /** @var MilitaryCalculator */
    protected $militaryCalculator;

    /** @var ImprovementHelper */
    protected $improvementHelper;

    /** @var RealmCalculator */
    protected $realmCalculator;

    /**
     * TickService constructor.
     */
    public function __construct()
    {
        $this->now = now();
        $this->barbarianCalculator = app(BarbarianCalculator::class);
        $this->conversionCalculator = app(ConversionCalculator::class);
        $this->improvementCalculator = app(ImprovementCalculator::class);
        $this->landCalculator = app(LandCalculator::class);
        $this->notificationService = app(NotificationService::class);
        $this->populationCalculator = app(PopulationCalculator::class);
        $this->prestigeCalculator = app(PrestigeCalculator::class);
        $this->productionCalculator = app(ProductionCalculator::class);
        $this->resourceCalculator = app(ResourceCalculator::class);
        $this->queueService = app(QueueService::class);
        $this->spellCalculator = app(SpellCalculator::class);

        $this->buildingCalculator = app(BuildingCalculator::class);
        $this->espionageCalculator = app(EspionageCalculator::class);
        $this->militaryCalculator = app(MilitaryCalculator::class);
        $this->moraleCalculator = app(MoraleCalculator::class);
        $this->improvementHelper = app(ImprovementHelper::class);
        $this->unitHelper = app(UnitHelper::class);
        $this->roundHelper = app(RoundHelper::class);
        $this->realmCalculator = app(RealmCalculator::class);
        $this->spellDamageCalculator = app(SpellDamageCalculator::class);
        $this->deityService = app(DeityService::class);
        $this->insightService = app(InsightService::class);
        $this->protectionService = app(ProtectionService::class);
        $this->resourceService = app(ResourceService::class);

        $this->barbarianService = app(BarbarianService::class);

        /* These calculators need to ignore queued resources for the following tick */
        $this->populationCalculator->setForTick(true);
        $this->queueService->setForTick(true);
        /* OK, get it? */
    }

    /**
     * Does an hourly tick on all active dominions.
     *
     * @throws Exception|Throwable
     */
    public function tickHourly()
    {
        if(File::exists('storage/framework/down'))
        {
            $logString = 'Tick at ' . $this->now . ' skipped.';
            Log::debug($logString);
        }

        $tickTime = now();

        Log::debug('Scheduled tick started');

        $activeRounds = Round::active()->get();

        foreach ($activeRounds as $round)
        {

            Log::debug('Tick number ' . number_format($round->ticks + 1) . ' for round ' . $round->number . ' started at ' . $tickTime . '.');

            # Get dominions IDs with Stasis active
            $stasisDominions = [];
            $dominions = $round->activeDominions()->get();
            $largestDominionSize = 0;

            if(static::EXTENDED_LOGGING) { Log::debug('* Going through all dominions'); }
            foreach ($dominions as $dominion)
            {
                if($dominion->protection_ticks === 0)
                {
                    if($dominion->getSpellPerkValue('stasis'))
                    {
                        $stasisDominions[] = $dominion->id;
                    }

                    if(($dominion->round->ticks % 4 == 0) and !$this->protectionService->isUnderProtection($dominion) and $dominion->round->hasStarted() and !$dominion->getSpellPerkValue('fog_of_war'))
                    {
                        $this->queueService->setForTick(false); # Necessary as otherwise this-tick units are missing
                        if(static::EXTENDED_LOGGING) { Log::debug('** Capturing insight for ' . $dominion->name); }
                        $this->insightService->captureDominionInsight($dominion);
                        $this->queueService->setForTick(true); # Reset
                    }

                    if(static::EXTENDED_LOGGING) { Log::debug('** Updating resources for ' . $dominion->name); }
                    $this->handleResources($dominion);

                    if(static::EXTENDED_LOGGING) { Log::debug('** Updating buildings for ' . $dominion->name); }
                    $this->handleBuildings($dominion);

                    if(static::EXTENDED_LOGGING){ Log::debug('** Updating improvments for ' . $dominion->name); }
                    $this->handleImprovements($dominion);

                    if(static::EXTENDED_LOGGING){ Log::debug('** Updating deities for ' . $dominion->name); }
                    $this->handleDeities($dominion);

                    if(static::EXTENDED_LOGGING) { Log::debug('** Handle Barbarians:'); }
                    # NPC Barbarian: invasion, training, construction
                    if($dominion->race->name === 'Barbarian')
                    {
                        if(static::EXTENDED_LOGGING) { Log::debug('*** Handle Barbarian invasions for ' . $dominion->name); }
                        $this->barbarianService->handleBarbarianInvasion($dominion, $largestDominionSize);

                        if(static::EXTENDED_LOGGING) { Log::debug('*** Handle Barbarian construction for ' . $dominion->name); }
                        $this->barbarianService->handleBarbarianConstruction($dominion);

                        if(static::EXTENDED_LOGGING) { Log::debug('*** Handle Barbarian improvements for ' . $dominion->name); }
                        $this->barbarianService->handleBarbarianImprovements($dominion);
                    }

                    if(static::EXTENDED_LOGGING) { Log::debug('** Calculate $largestDominion'); }
                    $largestDominionSize = max($this->landCalculator->getTotalLand($dominion), $largestDominionSize);
                    if(static::EXTENDED_LOGGING) { Log::debug('*** $largestDominion =' . number_format($largestDominionSize)); }

                    if(static::EXTENDED_LOGGING) { Log::debug('** Checking for countdown'); }
                    # If we don't already have a countdown, see if any dominion triggers it.
                    if(!$round->hasCountdown())
                    {
                        # For fixed length rounds, show a countdown when there are COUNTDOWN_DURATION_TICKS ticks left.
                        if(in_array($round->mode, ['standard-duration', 'deathmatch-duration']))
                        {
                            if($round->ticks >= ($round->end_tick - $this->roundHelper->getRoundCountdownTickLength()))
                            {
                                $countdownEvent = GameEvent::create([
                                    'round_id' => $dominion->round_id,
                                    'source_type' => Dominion::class,
                                    'source_id' => $dominion->id,
                                    'target_type' => Realm::class,
                                    'target_id' => $dominion->realm_id,
                                    'type' => 'round_countdown_duration',
                                    'data' => ['end_tick' => $round->end_tick],
                                    'tick' => $dominion->round->ticks
                                ]);
                                $dominion->save(['event' => HistoryService::EVENT_ROUND_COUNTDOWN]);

                                if(static::EXTENDED_LOGGING) { Log::debug('*** Countdown triggered by ticks'); }
                            }
                        }
                        # For indefinite rounds, create a countdown.
                        if(in_array($round->mode, ['standard', 'deathmatch']))
                        {
                            if($this->landCalculator->getTotalLand($dominion) >= $round->goal)
                            {
                                $endTick = $round->ticks + $this->roundHelper->getRoundCountdownTickLength();

                                $countdownEvent = GameEvent::create([
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

                                if(static::EXTENDED_LOGGING) { Log::debug('*** Countdown triggered by ' . $dominion->name . ' in realm #' . $dominion->realm->number); }
                            }
                        }

                        # For indefinite rounds, create a countdown.
                        if($round->mode == 'artefacts')
                        {
                        #    dd('uhhh...');
                        }
                    }
                }
            }

            unset($dominions);

            if(static::EXTENDED_LOGGING) { Log::debug('* Update stasis dominions'); }
            // Scoot hour 1 Qur Stasis units back to hour 2
            foreach($stasisDominions as $stasisDominion)
            {
                $stasisDominion = Dominion::findorfail($stasisDominion);

                ## Determine how many of each unit type is returning in $tick ticks
                $tick = 1;

                foreach (range(1, 4) as $slot)
                {
                    $unitType = 'unit' . $slot;
                    for ($i = 1; $i <= 12; $i++)
                    {
                        $invasionQueueUnits[$slot][$i] = $this->queueService->getInvasionQueueAmount($stasisDominion, "military_{$unitType}", $i);
                    }
                }

                $this->queueService->setForTick(false);
                $units['unit1'] = $this->queueService->getInvasionQueueAmount($stasisDominion, "military_unit1", $tick);
                $units['unit1'] = $this->queueService->getInvasionQueueAmount($stasisDominion, "military_unit2", $tick);
                $units['unit1'] = $this->queueService->getInvasionQueueAmount($stasisDominion, "military_unit3", $tick);
                $units['unit1'] = $this->queueService->getInvasionQueueAmount($stasisDominion, "military_unit4", $tick);
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

                $units['unit1'] = $this->queueService->getExpeditionQueueAmount($stasisDominion, "military_unit1", $tick);
                $units['unit2'] = $this->queueService->getExpeditionQueueAmount($stasisDominion, "military_unit2", $tick);
                $units['unit3'] = $this->queueService->getExpeditionQueueAmount($stasisDominion, "military_unit3", $tick);
                $units['unit4'] = $this->queueService->getExpeditionQueueAmount($stasisDominion, "military_unit4", $tick);

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

                $units['unit1'] = $this->queueService->getTheftQueueAmount($stasisDominion, "military_unit1", $tick);
                $units['unit2'] = $this->queueService->getTheftQueueAmount($stasisDominion, "military_unit2", $tick);
                $units['unit3'] = $this->queueService->getTheftQueueAmount($stasisDominion, "military_unit3", $tick);
                $units['unit4'] = $this->queueService->getTheftQueueAmount($stasisDominion, "military_unit4", $tick);
                $units['spies'] = $this->queueService->getTheftQueueAmount($stasisDominion, "military_spies", $tick);

                foreach($units as $slot => $amount)
                {
                      $unitType = 'military_'.$slot;
                      # Dequeue the units from hour 1
                      $this->queueService->dequeueResourceForHour('theft', $stasisDominion, $unitType, $amount, $tick);
                      #echo "\nUnits dequeued";

                      # (Re-)Queue the units to hour 2
                      $this->queueService->queueResources('theft', $stasisDominion, [$unitType => $amount], ($tick+1));
                      #echo "\nUnits requeued";
                }

                $units['unit1'] = $this->queueService->getSabotageQueueAmount($stasisDominion, "military_unit1", $tick);
                $units['unit2'] = $this->queueService->getSabotageQueueAmount($stasisDominion, "military_unit2", $tick);
                $units['unit3'] = $this->queueService->getSabotageQueueAmount($stasisDominion, "military_unit3", $tick);
                $units['unit4'] = $this->queueService->getSabotageQueueAmount($stasisDominion, "military_unit4", $tick);
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

            if(static::EXTENDED_LOGGING) { Log::debug('* Update all dominions'); }
            $this->updateDominions($round, $stasisDominions);

            if(static::EXTENDED_LOGGING) { Log::debug('* Update all spells'); }
            $this->updateAllSpells($round);

            if(static::EXTENDED_LOGGING) { Log::debug('* Update all deities duration'); }
            $this->updateAllDeities($round);

            if(static::EXTENDED_LOGGING) { Log::debug('* Update invasion queues'); }
            $this->updateAllInvasionQueues($round);

            if(static::EXTENDED_LOGGING) { Log::debug('* Update all other queues'); }
            $this->updateAllOtherQueues($round, $stasisDominions);

            Log::info(sprintf(
                '[TICK] Ticked %s dominions in %s ms in %s',
                number_format($round->activeDominions->count()),
                number_format($this->now->diffInMilliseconds(now())),
                $round->name
            ));

            $this->now = now();

            $dominions = $round->activeDominions()
                ->with([
                    'race',
                    'race.perks',
                    'race.units',
                    'race.units.perks',
                ])
                ->get();

            $realms = $round->realms()->get();

            $spawnBarbarian = rand(1, (int)$this->barbarianCalculator->getSetting('ONE_IN_CHANCE_TO_SPAWN'));

            Log::Debug('[BARBARIAN] spawn chance value: '. $spawnBarbarian . ' (spawn if this value is 1).');

            if($spawnBarbarian === 1)
            {
                $this->barbarianService->createBarbarian($round);
            }

            if(static::EXTENDED_LOGGING){ Log::debug('* Going through all dominions again'); }
            foreach ($dominions as $dominion)
            {

                if(static::EXTENDED_LOGGING) { Log::debug('** Handle Pestilence'); }
                // Afflicted: Abomination generation
                if(!empty($dominion->tick->pestilence_units))
                {
                    $caster = Dominion::findorfail($dominion->tick->pestilence_units['caster_dominion_id']);

                    if(static::EXTENDED_LOGGING) { Log::debug('*** ' . $dominion->name . ' has pestilence from ' . $caster->name); }

                    if ($caster)
                    {
                        $this->queueService->queueResources('training', $caster, ['military_unit1' => $dominion->tick->pestilence_units['units']['military_unit1']], 12);
                    }
                }

                if(static::EXTENDED_LOGGING) { Log::debug('** Handle land generation'); }
                // Myconid: Land generation
                if(!empty($dominion->tick->generated_land) and $dominion->protection_ticks == 0)
                {
                    $homeLandType = 'land_' . $dominion->race->home_land_type;
                    $this->queueService->queueResources('exploration', $dominion, [$homeLandType => $dominion->tick->generated_land], 12);
                }

                if(static::EXTENDED_LOGGING) { Log::debug('** Handle unit generation'); }
                // Unit generation
                if(!empty($dominion->tick->generated_unit1) and $dominion->protection_ticks == 0)
                {
                    $this->queueService->queueResources('training', $dominion, ['military_unit1' => $dominion->tick->generated_unit1], 12);
                }
                if(!empty($dominion->tick->generated_unit2) and $dominion->protection_ticks == 0)
                {
                    $this->queueService->queueResources('training', $dominion, ['military_unit2' => $dominion->tick->generated_unit2], 12);
                }
                if(!empty($dominion->tick->generated_unit3) and $dominion->protection_ticks == 0)
                {
                    $this->queueService->queueResources('training', $dominion, ['military_unit3' => $dominion->tick->generated_unit3], 12);
                }
                if(!empty($dominion->tick->generated_unit4) and $dominion->protection_ticks == 0)
                {
                    $this->queueService->queueResources('training', $dominion, ['military_unit4' => $dominion->tick->generated_unit4], 12);
                }


                DB::transaction(function () use ($dominion)
                {
                    if(static::EXTENDED_LOGGING) { Log::debug('** Handle starvation for ' . $dominion->name); }
                    if(/*$dominion->tick->starvation_casualties*/ $this->resourceCalculator->isOnBrinkOfStarvation($dominion) and !$dominion->isAbandoned())
                    {
                        $this->notificationService->queueNotification('starvation_occurred');
                        Log::info('[STARVATION] ' . $dominion->name . ' (# ' . $dominion->realm->number . ') is starving.');
                        #echo "Queue starvation notification for " . $dominion->name . "\t\n";
                    }

                    if(static::EXTENDED_LOGGING) { Log::debug('** Handle unit attrition for ' . $dominion->name); }
                    if(
                        (
                          isset($dominion->tick->attrition_unit1) or
                          isset($dominion->tick->attrition_unit2) or
                          isset($dominion->tick->attrition_unit3) or
                          isset($dominion->tick->attrition_unit4)
                        )
                        and array_sum([$dominion->tick->attrition_unit1, $dominion->tick->attrition_unit2, $dominion->tick->attrition_unit3, $dominion->tick->attrition_unit4]) > 0
                        and !$dominion->isAbandoned()
                      )
                    #if(array_sum([$dominion->tick->attrition_unit1, $dominion->tick->attrition_unit2, $dominion->tick->attrition_unit3, $dominion->tick->attrition_unit4]) > 0 and !$dominion->isAbandoned())
                    {
                        $this->notificationService->queueNotification('attrition_occurred',[$dominion->tick->attrition_unit1, $dominion->tick->attrition_unit2, $dominion->tick->attrition_unit3, $dominion->tick->attrition_unit4]);
                    }

                    if(static::EXTENDED_LOGGING) { Log::debug('** Cleaning up active spells'); }
                    $this->cleanupActiveSpells($dominion);

                    if(static::EXTENDED_LOGGING) { Log::debug('** Cleaning up queues'); }
                    $this->cleanupQueues($dominion);

                    if(static::EXTENDED_LOGGING) { Log::debug('** Sending notifications (hourly_dominion)'); }
                    $this->notificationService->sendNotifications($dominion, 'hourly_dominion');

                    if(static::EXTENDED_LOGGING) { Log::debug('** Precalculate tick'); }
                    $this->precalculateTick($dominion, true);

                });
            }

            foreach($realms as $realm)
            {
                $bodiesAmount = $this->resourceCalculator->getRealmAmount($realm, 'body');
                if($bodiesAmount > 0)
                {
                    # Imperial Crypt: handle decay (handleDecay)
                    $bodiesDecayed = $this->realmCalculator->getCryptBodiesDecayed($realm);

                    $bodiesSpent = DB::table('dominion_tick')
                        ->join('dominions', 'dominion_tick.dominion_Id', '=', 'dominions.id')
                        ->join('races', 'dominions.race_id', '=', 'races.id')
                        ->select(DB::raw("SUM(crypt_bodies_spent) as cryptBodiesSpent"))
                        ->where('dominions.round_id', '=', $realm->round->id)
                        ->where('races.name', '=', 'Undead')
                        ->where('dominions.protection_ticks', '=', 0)
                        ->where('dominions.is_locked', '=', 0)
                        ->first();

                    $bodiesToRemove = intval($bodiesDecayed + $bodiesSpent->cryptBodiesSpent);
                    $bodiesToRemove = max(0, min($bodiesToRemove, $bodiesAmount));

                    $cryptLogString = '[CRYPT] ';
                    $cryptLogString .= "Current: " . number_format($this->resourceCalculator->getRealmAmount($realm, 'body')) . ". ";
                    $cryptLogString .= "Decayed: " . number_format($bodiesDecayed) . ". ";
                    $cryptLogString .= "Spent: " . number_format($bodiesSpent->cryptBodiesSpent) . ". ";
                    $cryptLogString .= "Removed: " . number_format($bodiesToRemove) . ". ";

                    $this->resourceService->updateRealmResources($realm, ['body' => (-$bodiesToRemove)]);

                    Log::info($cryptLogString);
                }
            }

            Log::info(sprintf(
                '[QUEUES] Cleaned up queues, sent notifications, and precalculated %s dominions in %s ms in %s',
                number_format($round->activeDominions->count()),
                number_format($this->now->diffInMilliseconds(now())),
                $round->name
            ));

            $this->now = now();

             $round->fill([
                 'ticks' => ($round->ticks + 1),
             ])->save();
        }
    }

    /**
     * Does a daily tick on all active dominions and rounds.
     *
     * @throws Exception|Throwable
     */
    public function tickDaily()
    {
        Log::debug('[DAILY] Daily tick started');

        DB::transaction(function () {
            foreach (Round::with('dominions')->active()->get() as $round) {
                // toBase required to prevent ambiguous updated_at column in query
                $round->dominions()->toBase()->update([
                    'daily_gold' => false,
                    'daily_land' => false,
                ], [
                    'event' => 'tick',
                ]);
            }
        });

        Log::info('[DAILY] Daily tick finished');
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

        // Reset tick values
        foreach ($tick->getAttributes() as $attr => $value)
        {
            if (!in_array($attr, ['id', 'dominion_id', 'updated_at', 'pestilence_units', 'generated_land', 'generated_unit1', 'generated_unit2', 'generated_unit3', 'generated_unit4'], true))
            {
                  $tick->{$attr} = 0;
            }
            elseif (in_array($attr, ['starvation_casualties', 'pestilence_units', 'generated_land', 'generated_unit1', 'generated_unit2', 'generated_unit3', 'generated_unit4'/*, 'attrition_unit1', 'attrition_unit2', 'attrition_unit3', 'attrition_unit4'*/], true))
            {
                  $tick->{$attr} = [];
            }
        }

        // Hacky refresh for dominion
        $dominion->refresh();

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
            if($row->source !== 'deity' and substr($row->resource, 0, strlen('resource_')) !== 'resource_')
            {
                $tick->{$row->resource} += $row->amount;
                // Temporarily add next hour's resources for accurate calculations
                $dominion->{$row->resource} += $row->amount;
            }
        }

        if($dominion->race->name == 'Barbarian')
        {
            if(static::EXTENDED_LOGGING) { Log::debug('*** Handle Barbarian training for ' . $dominion->name); }
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

            $amountToDie = $dominion->peasants * $ratio * $this->spellDamageCalculator->getDominionHarmfulSpellDamageModifier($dominion, null, Spell::where('key', 'pestilence')->first(), null);
            $amountToDie *= $this->conversionCalculator->getConversionReductionMultiplier($dominion);
            $amountToDie = (int)round($amountToDie);

            $tick->pestilence_units = ['caster_dominion_id' => $caster->id, 'units' => ['military_unit1' => $amountToDie]];

            $populationPeasantGrowth -= $amountToDie;
        }

        # Check for resource_conversion
        if($peasantConversionData = $dominion->getBuildingPerkValue('peasants_conversion'))
        {
            $multiplier = 1;
            $multiplier += $dominion->getSpellPerkMultiplier('peasants_converted');
            $multiplier += $dominion->getBuildingPerkMultiplier('peasants_converted');
            $multiplier += $dominion->getImprovementPerkMultiplier('peasants_converted');

            $populationPeasantGrowth -= $peasantConversionData['from']['peasants'];
        }
        # Check for resource_conversion
        if($peasantConversionsData = $dominion->getBuildingPerkValue('peasants_conversions'))
        {
            $multiplier = 1;
            $multiplier += $dominion->getSpellPerkMultiplier('peasants_converted');
            $multiplier += $dominion->getBuildingPerkMultiplier('peasants_converted');
            $multiplier += $dominion->getImprovementPerkMultiplier('peasants_converted');

            $populationPeasantGrowth -= $peasantConversionsData['from']['peasants'];
        }

        if(($dominion->peasants + $tick->peasants) <= 0)
        {
            $tick->peasants = ($dominion->peasants)*-1;
        }

        $tick->peasants = $populationPeasantGrowth;

        #dd($populationPeasantGrowth, $peasantConversionsData['from']['peasants']);

        $tick->peasants_sacrificed = 0;#min($this->populationCalculator->getPeasantsSacrificed($dominion), ($dominion->peasants + $tick->peasants)) * -1;
        #$tick->peasants_sacrificed = max($tick->peasants_sacrificed, ($dominion->peasants + $tick->peasants)*-1);

        $tick->military_draftees = $drafteesGrowthRate;

        // Production/generation
        $tick->xp += $this->productionCalculator->getXpGeneration($dominion);
        $tick->prestige += $this->productionCalculator->getPrestigeInterest($dominion);

        // Starvation
        $tick->starvation_casualties = false;

        if($this->resourceCalculator->canStarve($dominion->race))
        {
            $foodProduction = $this->resourceCalculator->getProduction($dominion, 'food');
            $foodConsumed = $this->resourceCalculator->getConsumption($dominion, 'food');
            $foodNetChange = $foodProduction - $foodConsumed;
            $foodOwned = $this->resourceCalculator->getAmount($dominion, 'food');

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
        $generatedLand = 0;

        $generatedUnit1 = 0;
        $generatedUnit2 = 0;
        $generatedUnit3 = 0;
        $generatedUnit4 = 0;

        $attritionUnit1 = 0;
        $attritionUnit2 = 0;
        $attritionUnit3 = 0;
        $attritionUnit4 = 0;

        # Cult unit attrition reduction
        $attritionMultiplier = 0;
        if($dominion->race->name == 'Cult')
        {
            $attritionMultiplier -= $dominion->military_unit3 / max($this->populationCalculator->getPopulationMilitary($dominion),1);
            $attritionMultiplier -= $dominion->getBuildingPerkMultiplier('reduces_attrition');

            #dd($dominion->getBuildingPerkMultiplier('reduces_attrition'));
        }

        # Cap at -100%
        $attritionMultiplier = max(-1, $attritionMultiplier);

        // Check for no-attrition perks.
        if($dominion->getSpellPerkValue('no_attrition'))
        {
            $attritionMultiplier = -1;
        }

        for ($slot = 1; $slot <= 4; $slot++)
        {
            // Myconid: Land generation
            if($dominion->race->getUnitPerkValueForUnitSlot($slot, 'land_per_tick'))
            {
                $generatedLand += $dominion->{"military_unit".$slot} * $dominion->race->getUnitPerkValueForUnitSlot($slot, 'land_per_tick');
                $generatedLand = max($generatedLand, 0);

                # Defensive Warts turn off land generation
                if($dominion->getSpellPerkValue('stop_land_generation'))
                {
                    $generatedLand = 0;
                }
            }

            $availablePopulation = $this->populationCalculator->getMaxPopulation($dominion) - $this->populationCalculator->getPopulationMilitary($dominion);

            // Myconid and Cult: Unit generation
            if($unitGenerationPerk = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'unit_production'))
            {
                $unitToGenerateSlot = $unitGenerationPerk[0];
                $unitAmountToGeneratePerGeneratingUnit = $unitGenerationPerk[1];
                $unitAmountToGenerate = $dominion->{'military_unit'.$slot} * $unitAmountToGeneratePerGeneratingUnit;

                #echo $dominion->name . " has " . number_format($dominion->{'military_unit'.$slot}) . " unit" . $slot .", which generate " . $unitAmountToGeneratePerGeneratingUnit . " per tick. Total generation is " . $unitAmountToGenerate . " unit" . $unitToGenerateSlot . ". Available population: " . number_format($availablePopulation) . "\n";

                $unitAmountToGenerate = max(0, min($unitAmountToGenerate, $availablePopulation));

                #echo "\tAmount generated: " . $unitAmountToGenerate . "\n\n";

                if($unitToGenerateSlot == 1)
                {
                    $generatedUnit1 += $unitAmountToGenerate;
                }
                elseif($unitToGenerateSlot == 2)
                {
                    $generatedUnit2 += $unitAmountToGenerate;
                }
                elseif($unitToGenerateSlot == 3)
                {
                    $generatedUnit3 += $unitAmountToGenerate;
                }
                elseif($unitToGenerateSlot == 4)
                {
                    $generatedUnit4 += $unitAmountToGenerate;
                }

                $availablePopulation -= $unitAmountToGenerate;
            }

            // Unit attrition
            if($unitAttritionPerk = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'attrition'))
            {
                $unitAttritionAmount = intval($dominion->{'military_unit'.$slot} * $unitAttritionPerk/100 * (1 + $attritionMultiplier));
                #echo $dominion->name . " has " . number_format($dominion->{'military_unit'.$slot}) . " unit" . $slot .", which has an attrition rate of " . $unitAttritionPerk . "%. " . number_format($unitAttritionAmount) . " will abandon.\n";
                $unitAttritionAmount = max(0, min($unitAttritionAmount, $dominion->{'military_unit'.$slot})); # Sanity caps.

                if($slot == 1)
                {
                    $attritionUnit1 += $unitAttritionAmount;
                }
                elseif($slot == 2)
                {
                    $attritionUnit2 += $unitAttritionAmount;
                }
                elseif($slot == 3)
                {
                    $attritionUnit3 += $unitAttritionAmount;
                }
                elseif($slot == 4)
                {
                    $attritionUnit4 += $unitAttritionAmount;
                }
            }
        }

        # Imperial Crypt: Rites of Zidur, Rites of Kinthys
        $tick->crypt_bodies_spent = 0;

        # Version 1.4 (Round 50, no Necromancer pairing limit)
        # Version 1.3 (Round 42, Spells 2.0 compatible-r)
        if ($this->spellCalculator->isSpellActive($dominion, 'rites_of_zidur'))
        {
            $spell = Spell::where('key', 'rites_of_zidur')->first();

            $spellPerkValues = $spell->getActiveSpellPerkValues($spell->key, 'converts_crypt_bodies');

            # Check bodies available in the crypt
            $bodiesAvailable = max(0, floor($this->resoureCalculator->getRealmAmount($dominion->realm, 'body') - $tick->crypt_bodies_spent));

            # Break down the spell perk
            $raisersPerRaisedUnit = (int)$spellPerkValues[0];
            $raisingUnitSlot = (int)$spellPerkValues[1];
            $unitRaisedSlot = (int)$spellPerkValues[2];

            $unitsRaised = $dominion->{'military_unit' . $raisingUnitSlot} / $raisersPerRaisedUnit;

            $unitsRaised = max(0, min($unitsRaised, $bodiesAvailable));

            $tick->{'generated_unit' . $unitRaisedSlot} += $unitsRaised;
            $tick->crypt_bodies_spent += $unitsRaised;
        }
        if ($this->spellCalculator->isSpellActive($dominion, 'rites_of_kinthys'))
        {
            $spell = Spell::where('key', 'rites_of_kinthys')->first();

            $spellPerkValues = $spell->getActiveSpellPerkValues($spell->key, 'converts_crypt_bodies');

            # Check bodies available in the crypt
            $bodiesAvailable = max(0, floor($this->resoureCalculator->getRealmAmount($dominion->realm, 'body') - $tick->crypt_bodies_spent));

            # Break down the spell perk
            $raisersPerRaisedUnit = (int)$spellPerkValues[0];
            $raisingUnitSlot = (int)$spellPerkValues[1];
            $unitRaisedSlot = (int)$spellPerkValues[2];

            $unitsRaised = $dominion->{'military_unit' . $raisingUnitSlot} / $raisersPerRaisedUnit;

            $unitsRaised = max(0, min($unitsRaised, $bodiesAvailable));

            $tick->{'generated_unit' . $unitRaisedSlot} += $unitsRaised;
            $tick->crypt_bodies_spent += $unitsRaised;
        }

        # Passive unit generation from buildings
        for ($slot = 1; $slot <= 4; $slot++)
        {
            $raceKey = str_replace(' ', '_', strtolower($dominion->race->name));
            $unitSummoningRaw = $dominion->getBuildingPerkValue($raceKey . '_unit' . $slot . '_production_raw');
            $unitSummoningRaw += $dominion->getBuildingPerkValue($raceKey . '_unit' . $slot . '_production_raw_capped');

            $unitSummoningMultiplier = 1;
            $unitSummoningMultiplier += $dominion->getBuildingPerkMultiplier($raceKey . '_unit' . $slot . '_production_mod');
            $unitSummoningMultiplier += $dominion->getSpellPerkMultiplier($raceKey . '_unit' . $slot . '_production_mod');

            if($unitProductionFromWizardRatioPerk = $dominion->getBuildingPerkValue('unit_production_from_wizard_ratio'))
            {
                $unitSummoningMultiplier += $this->militaryCalculator->getWizardRatio($dominion) / $unitProductionFromWizardRatioPerk;
            }

            $unitSummoning = $unitSummoningRaw * $unitSummoningMultiplier;

            # Check for capacity limit
            if($this->unitHelper->unitHasCapacityLimit($dominion, $slot))
            {
                $maxCapacity = $this->unitHelper->getUnitMaxCapacity($dominion, $slot);

                $usedCapacity = $dominion->{'military_unit' . $slot};
                $usedCapacity += $this->queueService->getTrainingQueueTotalByResource($dominion, 'military_unit' . $slot);
                $usedCapacity += $this->queueService->getInvasionQueueTotalByResource($dominion, 'military_unit' . $slot);
                $usedCapacity += $this->queueService->getExpeditionQueueTotalByResource($dominion, 'military_unit' . $slot);
                $usedCapacity += $this->queueService->getTheftQueueTotalByResource($dominion, 'military_unit' . $slot);
                $usedCapacity += $this->queueService->getSabotageQueueTotalByResource($dominion, 'military_unit' . $slot);

                $availableCapacity = max(0, $maxCapacity - $usedCapacity);

                $unitsToSummon = floor(min($unitSummoning, $availableCapacity));
            }
            # If no capacity limit
            else
            {
                $unitsToSummon = $unitSummoning;
            }

            # Because you never know...
            $unitsToSummon = intval(max($unitsToSummon, 0));

            $tick->{'generated_unit'.$slot} += $unitsToSummon;
        }

        # Use decimals as probability to round up
        $tick->generated_land += intval($generatedLand) + (rand()/getrandmax() < fmod($generatedLand, 1) ? 1 : 0);

        $tick->generated_unit1 += intval($generatedUnit1) + (rand()/getrandmax() < fmod($generatedUnit1, 1) ? 1 : 0);
        $tick->generated_unit2 += intval($generatedUnit2) + (rand()/getrandmax() < fmod($generatedUnit2, 1) ? 1 : 0);
        $tick->generated_unit3 += intval($generatedUnit3) + (rand()/getrandmax() < fmod($generatedUnit3, 1) ? 1 : 0);
        $tick->generated_unit4 += intval($generatedUnit4) + (rand()/getrandmax() < fmod($generatedUnit4, 1) ? 1 : 0);

        $tick->attrition_unit1 += intval($attritionUnit1);
        $tick->attrition_unit2 += intval($attritionUnit2);
        $tick->attrition_unit3 += intval($attritionUnit3);
        $tick->attrition_unit4 += intval($attritionUnit4);



        foreach ($incomingQueue as $row)
        {
            if($row->source !== 'deity' and substr($row->resource, 0, strlen('resource_')) !== 'resource_')
            {
                // Reset current resources in case object is saved later
                $dominion->{$row->resource} -= $row->amount;
            }
        }

        $tick->save();
    }

    # SINGLE DOMINION TICKS, MANUAL TICK
    /**
     * Does an hourly tick on all active dominions.
     *
     * @throws Exception|Throwable
     */
    public function tickManually(Dominion $dominion)
    {

        Log::debug(sprintf(
            '[TICK] Manual tick started for %s.',
            $dominion->name
        ));

        $this->precalculateTick($dominion, true);

        $this->handleResources($dominion);
        $this->handleBuildings($dominion);
        $this->handleImprovements($dominion);
        $this->handleDeities($dominion);

        $this->updateDominion($dominion);
        $this->updateDominionSpells($dominion);
        $this->updateDominionDeity($dominion);
        $this->updateDominionQueues($dominion);

        Log::info(sprintf(
            '[TICK] Ticked dominion %s in %s ms.',
            $dominion->name,
            number_format($this->now->diffInMilliseconds(now()))
        ));

        $this->now = now();

        DB::transaction(function () use ($dominion)
        {
            # Queue starvation notification.
            if($dominion->tick->starvation_casualties and !$dominion->isAbandoned())
            {
                $this->notificationService->queueNotification('starvation_occurred');
            }

            if(array_sum([$dominion->tick->attrition_unit1, $dominion->tick->attrition_unit2, $dominion->tick->attrition_unit3, $dominion->tick->attrition_unit4]) > 0 and !$dominion->isAbandoned())
            {
                $this->notificationService->queueNotification('attrition_occurred',[$dominion->tick->attrition_unit1, $dominion->tick->attrition_unit2, $dominion->tick->attrition_unit3, $dominion->tick->attrition_unit4]);
            }

            # Clean up
            $this->cleanupActiveSpells($dominion);
            $this->cleanupQueues($dominion);

            $this->notificationService->sendNotifications($dominion, 'hourly_dominion');

            $this->precalculateTick($dominion, true);

        });

        // Myconid: Land generation
        if(!empty($dominion->tick->generated_land) and $dominion->protection_ticks > 0)
        {
            $homeLandType = 'land_' . $dominion->race->home_land_type;
            $this->queueService->queueResources('exploration', $dominion, [$homeLandType => $dominion->tick->generated_land], 12);
        }

        // Myconid and Cult: Unit generation
        if(!empty($dominion->tick->generated_unit1) and $dominion->protection_ticks > 0)
        {
            $this->queueService->queueResources('training', $dominion, ['military_unit1' => $dominion->tick->generated_unit1], 12);
        }
        if(!empty($dominion->tick->generated_unit2) and $dominion->protection_ticks > 0)
        {
            $this->queueService->queueResources('training', $dominion, ['military_unit2' => $dominion->tick->generated_unit2], 12);
        }
        if(!empty($dominion->tick->generated_unit3) and $dominion->protection_ticks > 0)
        {
            $this->queueService->queueResources('training', $dominion, ['military_unit3' => $dominion->tick->generated_unit3], 12);
        }
        if(!empty($dominion->tick->generated_unit4) and $dominion->protection_ticks > 0)
        {
            $this->queueService->queueResources('training', $dominion, ['military_unit4' => $dominion->tick->generated_unit4], 12);
        }

        Log::info(sprintf(
            '[TICK] Cleaned up queues, sent notifications, and precalculated dominion %s in %s ms.',
            $dominion->name,
            number_format($this->now->diffInMilliseconds(now()))
        ));

        $this->now = now();
    }

    # TICK SERVICE 1.1 functions

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
                'dominions.peasants' => DB::raw('dominions.peasants + dominion_tick.peasants + dominion_tick.peasants_sacrificed'),
                'dominions.peasants_last_hour' => DB::raw('dominion_tick.peasants'),
                'dominions.morale' => DB::raw('dominions.morale + dominion_tick.morale'),
                'dominions.spy_strength' => DB::raw('dominions.spy_strength + dominion_tick.spy_strength'),
                'dominions.wizard_strength' => DB::raw('dominions.wizard_strength + dominion_tick.wizard_strength'),

                'dominions.military_draftees' => DB::raw('dominions.military_draftees + dominion_tick.military_draftees'),
                'dominions.military_unit1' => DB::raw('dominions.military_unit1 + dominion_tick.military_unit1 - dominion_tick.attrition_unit1'),
                'dominions.military_unit2' => DB::raw('dominions.military_unit2 + dominion_tick.military_unit2 - dominion_tick.attrition_unit2'),
                'dominions.military_unit3' => DB::raw('dominions.military_unit3 + dominion_tick.military_unit3 - dominion_tick.attrition_unit3'),
                'dominions.military_unit4' => DB::raw('dominions.military_unit4 + dominion_tick.military_unit4 - dominion_tick.attrition_unit4'),
                'dominions.military_spies' => DB::raw('dominions.military_spies + dominion_tick.military_spies'),
                'dominions.military_wizards' => DB::raw('dominions.military_wizards + dominion_tick.military_wizards'),
                'dominions.military_archmages' => DB::raw('dominions.military_archmages + dominion_tick.military_archmages'),

                'dominions.land_plain' => DB::raw('dominions.land_plain + dominion_tick.land_plain'),
                'dominions.land_mountain' => DB::raw('dominions.land_mountain + dominion_tick.land_mountain'),
                'dominions.land_swamp' => DB::raw('dominions.land_swamp + dominion_tick.land_swamp'),
                'dominions.land_cavern' => DB::raw('dominions.land_cavern + dominion_tick.land_cavern'),
                'dominions.land_forest' => DB::raw('dominions.land_forest + dominion_tick.land_forest'),
                'dominions.land_hill' => DB::raw('dominions.land_hill + dominion_tick.land_hill'),
                'dominions.land_water' => DB::raw('dominions.land_water + dominion_tick.land_water'),

                'dominions.protection_ticks' => DB::raw('dominions.protection_ticks + dominion_tick.protection_ticks'),
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
                'dominions.peasants' => DB::raw('dominions.peasants + dominion_tick.peasants + dominion_tick.peasants_sacrificed'),
                'dominions.peasants_last_hour' => DB::raw('dominion_tick.peasants'),
                'dominions.morale' => DB::raw('dominions.morale + dominion_tick.morale'),
                'dominions.spy_strength' => DB::raw('dominions.spy_strength + dominion_tick.spy_strength'),
                'dominions.wizard_strength' => DB::raw('dominions.wizard_strength + dominion_tick.wizard_strength'),

                'dominions.military_draftees' => DB::raw('dominions.military_draftees + dominion_tick.military_draftees'),
                'dominions.military_unit1' => DB::raw('dominions.military_unit1 + dominion_tick.military_unit1 - dominion_tick.attrition_unit1'),
                'dominions.military_unit2' => DB::raw('dominions.military_unit2 + dominion_tick.military_unit2 - dominion_tick.attrition_unit2'),
                'dominions.military_unit3' => DB::raw('dominions.military_unit3 + dominion_tick.military_unit3 - dominion_tick.attrition_unit3'),
                'dominions.military_unit4' => DB::raw('dominions.military_unit4 + dominion_tick.military_unit4 - dominion_tick.attrition_unit4'),
                'dominions.military_spies' => DB::raw('dominions.military_spies + dominion_tick.military_spies'),
                'dominions.military_wizards' => DB::raw('dominions.military_wizards + dominion_tick.military_wizards'),
                'dominions.military_archmages' => DB::raw('dominions.military_archmages + dominion_tick.military_archmages'),

                'dominions.land_plain' => DB::raw('dominions.land_plain + dominion_tick.land_plain'),
                'dominions.land_mountain' => DB::raw('dominions.land_mountain + dominion_tick.land_mountain'),
                'dominions.land_swamp' => DB::raw('dominions.land_swamp + dominion_tick.land_swamp'),
                'dominions.land_cavern' => DB::raw('dominions.land_cavern + dominion_tick.land_cavern'),
                'dominions.land_forest' => DB::raw('dominions.land_forest + dominion_tick.land_forest'),
                'dominions.land_hill' => DB::raw('dominions.land_hill + dominion_tick.land_hill'),
                'dominions.land_water' => DB::raw('dominions.land_water + dominion_tick.land_water'),

                'dominions.protection_ticks' => DB::raw('dominions.protection_ticks + dominion_tick.protection_ticks'),
                'dominions.ticks' => DB::raw('dominions.ticks + 1'),

                'dominions.last_tick_at' => DB::raw('now()')
            ]);
    }

    // Update spells for a specific dominion
    private function updateDominionSpells(Dominion $dominion): void
    {
        DB::table('dominion_spells')
            ->where('dominion_id', $dominion->id)
            ->update([
                'duration' => DB::raw('GREATEST(0, `duration` - 1)'),
                'cooldown' => DB::raw('GREATEST(0, `cooldown` - 1)'),
                'dominion_spells.updated_at' => $this->now,
            ]);
    }

    // Update deity duration for a specific dominion
    private function updateDominionDeity(Dominion $dominion): void
    {
        DB::table('dominion_deity')
            ->join('dominions', 'dominion_deity.dominion_id', '=', 'dominions.id')
            ->where('dominions.id', $dominion->id)
            ->update([
                'duration' => DB::raw('`duration` + 1'),
                'dominion_deity.updated_at' => $this->now,
            ]);
    }

    // Update queues for a specific dominion
    private function updateDominionQueues(Dominion $dominion): void
    {
        DB::table('dominion_queue')
            ->join('dominions', 'dominion_queue.dominion_id', '=', 'dominions.id')
            ->where('dominions.id', $dominion->id)
            ->update([
                'hours' => DB::raw('`hours` - 1'),
                'dominion_queue.updated_at' => $this->now,
            ]);
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
            $building = Building::where('key', $buildingKey)->first();
            $this->buildingCalculator->createOrIncrementBuildings($dominion, [$buildingKey => $amount]);
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
            $improvementInterest = [];

            $multiplier = 1;
            $multiplier += $dominion->getBuildingPerkMultiplier('improvements_interest');
            $multiplier += $dominion->getSpellPerkMultiplier('improvements_interest');
            $multiplier += $dominion->getImprovementPerkMultiplier('improvements_interest');
            $multiplier += $dominion->getTechPerkMultiplier('improvements_interest');
            $multiplier += $dominion->getDeityPerkMultiplier('improvements_interest');

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
            $amount = 1;
            $deity = Deity::where('key', $deityKey)->first();
            $this->deityService->completeSubmissionToDeity($dominion, $deity);

            $deityEvent = GameEvent::create([
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

    # Take resources that are one tick away from finished and create or increment DominionImprovements.
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
                                        ->whereIn('source', ['exploration','invasion','expedition','theft'])
                                        ->where('hours',1)
                                        ->get();

        foreach($finishedResourcesInQueue as $finishedResourceInQueue)
        {
            $resourceKey = str_replace('resource_', '', $finishedResourceInQueue->resource);
            $amount = intval($finishedResourceInQueue->amount);
            $resource = Resource::where('key', $resourceKey)->first();

            # Silently discard resources this faction doesn't use, if we somehow have any incoming from queue.
            if(in_array($resourceKey, $dominion->race->resources))
            {
                if(isset($resourcesToAdd[$resourceKey]))
                {
                    $resourcesProduced[$resourceKey] += $amount;
                }
                else
                {
                    $resourcesProduced[$resourceKey] = $amount;
                }
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
}
