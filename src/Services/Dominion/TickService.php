<?php

namespace OpenDominion\Services\Dominion;

use DB;
use File;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Log;
use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\CasualtiesCalculator;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\PrestigeCalculator;
use OpenDominion\Calculators\Dominion\ProductionCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Dominion\Tick;
use OpenDominion\Models\Round;
use OpenDominion\Models\Spell;
use OpenDominion\Models\Building;
use OpenDominion\Models\DominionBuilding;
use OpenDominion\Services\NotificationService;
use Throwable;

# ODA
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Models\GameEvent;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Helpers\ImprovementHelper;
use OpenDominion\Calculators\RealmCalculator;
use OpenDominion\Calculators\Dominion\SpellDamageCalculator;

class TickService
{

    protected const LAND_TO_TRIGGER_COUNTDOWN = 10000;
    protected const COUNTDOWN_DURATION_HOURS = 12;

    /** @var Carbon */
    protected $now;

    /** @var CasualtiesCalculator */
    protected $casualtiesCalculator;

    /** @var LandCalculator */
    protected $landCalculator;

    /** @var NetworthCalculator */
    protected $networthCalculator;

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

    /** @var SpellCalculator */
    protected $spellCalculator;

    /** @var MilitaryCalculator */
    protected $militaryCalculator;

    /** @var RangeCalculator */
    protected $rangeCalculator;

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
        $this->casualtiesCalculator = app(CasualtiesCalculator::class);
        $this->improvementCalculator = app(ImprovementCalculator::class);
        $this->landCalculator = app(LandCalculator::class);
        $this->networthCalculator = app(NetworthCalculator::class);
        $this->notificationService = app(NotificationService::class);
        $this->populationCalculator = app(PopulationCalculator::class);
        $this->prestigeCalculator = app(PrestigeCalculator::class);
        $this->productionCalculator = app(ProductionCalculator::class);
        $this->queueService = app(QueueService::class);
        $this->spellCalculator = app(SpellCalculator::class);

        $this->buildingCalculator = app(BuildingCalculator::class);
        $this->militaryCalculator = app(MilitaryCalculator::class);
        $this->rangeCalculator = app(RangeCalculator::class);
        $this->improvementHelper = app(ImprovementHelper::class);
        $this->realmCalculator = app(RealmCalculator::class);
        $this->spellDamageCalculator = app(SpellDamageCalculator::class);

        $this->barbarianService = app(BarbarianService::class);

        /* These calculators need to ignore queued resources for the following tick */
        $this->populationCalculator->setForTick(true);
        $this->queueService->setForTick(true);
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
            Log::debug('Tick for round ' . $round->number . ' started at ' . $tickTime . '.');

            # Get dominions IDs with Stasis active
            $stasisDominions = [];
            $dominions = $round->activeDominions()->get();

            foreach($dominions as $dominion)
            {
                if($dominion->protection_ticks === 0)
                {
                    $this->logDominionTickState($dominion, $tickTime);
                }

                if($dominion->getSpellPerkValue('stasis'))
                {
                    $stasisDominions[] = $dominion->id;
                }

                # Take buildings that are one tick away from finished and create or increment DominionBuildings.
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

                # If we don't already have a countdown, see if any dominion triggers it.
                if(!$round->hasCountdown())
                {
                    if($this->landCalculator->getTotalLand($dominion) >= static::LAND_TO_TRIGGER_COUNTDOWN)
                    {
                        $hoursEndingIn = static::COUNTDOWN_DURATION_HOURS + 1;
                        $roundEnd = Carbon::now()->addHours($hoursEndingIn)->startOfHour();

                        $countdownEvent = GameEvent::create([
                            'round_id' => $dominion->round_id,
                            'source_type' => Dominion::class,
                            'source_id' => $dominion->id,
                            'target_type' => Realm::class,
                            'target_id' => $dominion->realm_id,
                            'type' => 'round_countdown',
                            'data' => ['end_date' => $roundEnd],
                        ]);
                        $dominion->save(['event' => HistoryService::EVENT_ROUND_COUNTDOWN]);
                        $round->end_date = $roundEnd;
                        $round->save();
                    }
                }
            }

            unset($dominions);

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
                $units[1] = $this->queueService->getInvasionQueueAmount($stasisDominion, "military_unit1", $tick);
                $units[2] = $this->queueService->getInvasionQueueAmount($stasisDominion, "military_unit2", $tick);
                $units[3] = $this->queueService->getInvasionQueueAmount($stasisDominion, "military_unit3", $tick);
                $units[4] = $this->queueService->getInvasionQueueAmount($stasisDominion, "military_unit4", $tick);

                foreach($units as $slot => $amount)
                {
                      $unitType = 'military_unit'.$slot;
                      # Dequeue the units from hour 1
                      $this->queueService->dequeueResourceForHour('invasion', $stasisDominion, $unitType, $amount, $tick);
                      #echo "\nUnits dequeued";

                      # (Re-)Queue the units to hour 2
                      $this->queueService->queueResources('invasion', $stasisDominion, [$unitType => $amount], ($tick+1));
                      #echo "\nUnits requeued";
                }

                $this->queueService->setForTick(true);

            }

            DB::transaction(function () use ($round, $stasisDominions)
            {
                // Update dominions
                DB::table('dominions')
                    ->join('dominion_tick', 'dominions.id', '=', 'dominion_tick.dominion_id')
                    ->where('dominions.round_id', $round->id)
                    ->where('dominions.is_locked', false)
                    ->whereNotIn('dominion_tick.dominion_id', $stasisDominions)
                    ->where('dominions.protection_ticks', '=', 0)
                    ->update([
                        'dominions.prestige' => DB::raw('dominions.prestige + dominion_tick.prestige'),
                        'dominions.peasants' => DB::raw('dominions.peasants + dominion_tick.peasants + dominion_tick.peasants_sacrificed'),
                        'dominions.peasants_last_hour' => DB::raw('dominion_tick.peasants'),
                        'dominions.morale' => DB::raw('dominions.morale + dominion_tick.morale'),
                        'dominions.spy_strength' => DB::raw('dominions.spy_strength + dominion_tick.spy_strength'),
                        'dominions.wizard_strength' => DB::raw('dominions.wizard_strength + dominion_tick.wizard_strength'),

                        'dominions.resource_gold' => DB::raw('dominions.resource_gold + dominion_tick.resource_gold'),
                        'dominions.resource_food' => DB::raw('dominions.resource_food + dominion_tick.resource_food + dominion_tick.resource_food_contributed'),
                        'dominions.resource_lumber' => DB::raw('dominions.resource_lumber + dominion_tick.resource_lumber'),
                        'dominions.resource_mana' => DB::raw('dominions.resource_mana + dominion_tick.resource_mana + dominion_tick.resource_mana_contributed'),
                        'dominions.resource_ore' => DB::raw('dominions.resource_ore + dominion_tick.resource_ore'),
                        'dominions.resource_gems' => DB::raw('dominions.resource_gems + dominion_tick.resource_gems'),
                        'dominions.resource_tech' => DB::raw('dominions.resource_tech + dominion_tick.resource_tech'),
                        'dominions.resource_boats' => 0,#DB::raw('dominions.resource_boats + dominion_tick.resource_boats'),

                        # Improvements
                        'dominions.improvement_markets' => DB::raw('dominions.improvement_markets + dominion_tick.improvement_markets'),
                        'dominions.improvement_keep' => DB::raw('dominions.improvement_keep + dominion_tick.improvement_keep'),
                        'dominions.improvement_forges' => DB::raw('dominions.improvement_forges + dominion_tick.improvement_forges'),
                        'dominions.improvement_walls' => DB::raw('dominions.improvement_walls + dominion_tick.improvement_walls'),
                        'dominions.improvement_armory' => DB::raw('dominions.improvement_armory + dominion_tick.improvement_armory'),
                        'dominions.improvement_infirmary' => DB::raw('dominions.improvement_infirmary + dominion_tick.improvement_infirmary'),
                        'dominions.improvement_workshops' => DB::raw('dominions.improvement_workshops + dominion_tick.improvement_workshops'),
                        'dominions.improvement_observatory' => DB::raw('dominions.improvement_observatory + dominion_tick.improvement_observatory'),
                        'dominions.improvement_cartography' => DB::raw('dominions.improvement_cartography + dominion_tick.improvement_cartography'),
                        'dominions.improvement_towers' => DB::raw('dominions.improvement_towers + dominion_tick.improvement_towers'),
                        'dominions.improvement_spires' => DB::raw('dominions.improvement_spires + dominion_tick.improvement_spires'),
                        'dominions.improvement_hideouts' => DB::raw('dominions.improvement_hideouts + dominion_tick.improvement_hideouts'),
                        'dominions.improvement_granaries' => DB::raw('dominions.improvement_granaries + dominion_tick.improvement_granaries'),
                        'dominions.improvement_harbor' => DB::raw('dominions.improvement_harbor + dominion_tick.improvement_harbor'),
                        'dominions.improvement_forestry' => DB::raw('dominions.improvement_forestry + dominion_tick.improvement_forestry'),
                        'dominions.improvement_refinery' => DB::raw('dominions.improvement_refinery + dominion_tick.improvement_refinery'),
                        'dominions.improvement_tissue' => DB::raw('dominions.improvement_tissue + dominion_tick.improvement_tissue'),

                        # ODA resources
                        'dominions.resource_wild_yeti' => DB::raw('dominions.resource_wild_yeti + dominion_tick.resource_wild_yeti'),
                        'dominions.resource_champion' => DB::raw('dominions.resource_champion + dominion_tick.resource_champion'),
                        'dominions.resource_soul' => DB::raw('dominions.resource_soul + dominion_tick.resource_soul'),
                        'dominions.resource_blood' => DB::raw('dominions.resource_blood + dominion_tick.resource_blood'),

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

                        /*
                        'dominions.discounted_land' => DB::raw('dominions.discounted_land + dominion_tick.discounted_land'),
                        'dominions.building_home' => DB::raw('dominions.building_home + dominion_tick.building_home'),
                        'dominions.building_alchemy' => DB::raw('dominions.building_alchemy + dominion_tick.building_alchemy'),
                        'dominions.building_farm' => DB::raw('dominions.building_farm + dominion_tick.building_farm'),
                        'dominions.building_smithy' => DB::raw('dominions.building_smithy + dominion_tick.building_smithy'),
                        'dominions.building_masonry' => DB::raw('dominions.building_masonry + dominion_tick.building_masonry'),
                        'dominions.building_ore_mine' => DB::raw('dominions.building_ore_mine + dominion_tick.building_ore_mine'),
                        'dominions.building_gryphon_nest' => DB::raw('dominions.building_gryphon_nest + dominion_tick.building_gryphon_nest'),
                        'dominions.building_tower' => DB::raw('dominions.building_tower + dominion_tick.building_tower'),
                        'dominions.building_wizard_guild' => DB::raw('dominions.building_wizard_guild + dominion_tick.building_wizard_guild'),
                        'dominions.building_temple' => DB::raw('dominions.building_temple + dominion_tick.building_temple'),
                        'dominions.building_gem_mine' => DB::raw('dominions.building_gem_mine + dominion_tick.building_gem_mine'),
                        'dominions.building_school' => DB::raw('dominions.building_school + dominion_tick.building_school'),
                        'dominions.building_lumberyard' => DB::raw('dominions.building_lumberyard + dominion_tick.building_lumberyard'),
                        'dominions.building_forest_haven' => DB::raw('dominions.building_forest_haven + dominion_tick.building_forest_haven'),
                        'dominions.building_factory' => DB::raw('dominions.building_factory + dominion_tick.building_factory'),
                        'dominions.building_guard_tower' => DB::raw('dominions.building_guard_tower + dominion_tick.building_guard_tower'),
                        'dominions.building_shrine' => DB::raw('dominions.building_shrine + dominion_tick.building_shrine'),
                        'dominions.building_barracks' => DB::raw('dominions.building_barracks + dominion_tick.building_barracks'),
                        'dominions.building_dock' => DB::raw('dominions.building_dock + dominion_tick.building_dock'),

                        'dominions.building_ziggurat' => DB::raw('dominions.building_ziggurat + dominion_tick.building_ziggurat'),
                        'dominions.building_tissue' => DB::raw('dominions.building_tissue + dominion_tick.building_tissue'),
                        'dominions.building_mycelia' => DB::raw('dominions.building_mycelia + dominion_tick.building_mycelia'),
                        */

                        'dominions.stat_total_gold_production' => DB::raw('dominions.stat_total_gold_production + dominion_tick.resource_gold'),
                        'dominions.stat_total_food_production' => DB::raw('dominions.stat_total_food_production + dominion_tick.resource_food_production'),
                        'dominions.stat_total_lumber_production' => DB::raw('dominions.stat_total_lumber_production + dominion_tick.resource_lumber_production'),
                        'dominions.stat_total_mana_production' => DB::raw('dominions.stat_total_mana_production + dominion_tick.resource_mana_production'),
                        'dominions.stat_total_wild_yeti_production' => DB::raw('dominions.stat_total_wild_yeti_production + dominion_tick.resource_wild_yeti_production'),
                        'dominions.stat_total_ore_production' => DB::raw('dominions.stat_total_ore_production + dominion_tick.resource_ore'),
                        'dominions.stat_total_gem_production' => DB::raw('dominions.stat_total_gem_production + dominion_tick.resource_gems'),
                        'dominions.stat_total_tech_production' => DB::raw('dominions.stat_total_tech_production + dominion_tick.resource_tech'),
                        'dominions.stat_total_boat_production' => 0,#DB::raw('dominions.stat_total_boat_production + dominion_tick.resource_boats'),

                        'dominions.stat_total_food_decayed' => DB::raw('dominions.stat_total_food_decayed + dominion_tick.resource_food_decay'),
                        'dominions.stat_total_food_consumed' => DB::raw('dominions.stat_total_food_consumed + dominion_tick.resource_food_consumption'),
                        'dominions.stat_total_lumber_rotted' => DB::raw('dominions.stat_total_lumber_rotted + dominion_tick.resource_lumber_rot'),
                        'dominions.stat_total_mana_drained' => DB::raw('dominions.stat_total_mana_drained + dominion_tick.resource_mana_drain'),

                        'dominions.protection_ticks' => DB::raw('dominions.protection_ticks + dominion_tick.protection_ticks'),

                        'dominions.last_tick_at' => DB::raw('now()')
                    ]);

                // Update spells
                DB::table('dominion_spells')
                    ->join('dominions', 'dominion_spells.dominion_id', '=', 'dominions.id')
                    ->where('dominions.round_id', $round->id)
                    ->where('dominions.protection_ticks', '=', 0)
                    ->update([
                        'duration' => DB::raw('`duration` - 1'),
                        'dominion_spells.updated_at' => $this->now,
                    ]);

                // Update invasion queues
                DB::table('dominion_queue')
                    ->join('dominions', 'dominion_queue.dominion_id', '=', 'dominions.id')
                    ->where('dominions.round_id', $round->id)
                    ->where('dominions.protection_ticks', '=', 0)
                    ->where('source', '=', 'invasion')
                    ->update([
                        'hours' => DB::raw('`hours` - 1'),
                        'dominion_queue.updated_at' => $this->now,
                    ]);

                // Update other queues
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
            }, 10);

            Log::info(sprintf(
                '[TICK] Ticked %s dominions in %s ms in %s',
                number_format($round->activeDominions->count()),
                number_format($this->now->diffInMilliseconds(now())),
                $round->name
            ));

            /*
            */



            /*
            */

            $this->now = now();
        }

        foreach ($activeRounds as $round) {
            $dominions = $round->activeDominions()
                ->with([
                    'race',
                    'race.perks',
                    'race.units',
                    'race.units.perks',
                ])
                ->get();

            $realms = $round->realms()->get();

            $spawnBarbarian = rand(1, (int)$this->barbarianService->getSetting('ONE_IN_CHANCE_TO_SPAWN'));

            Log::Debug('[BARBARIAN] spawn chance value: '. $spawnBarbarian . ' (spawn if this value is 1).');

            if($spawnBarbarian === 1)
            {
                $this->barbarianService->createBarbarian($round);
            }

            foreach ($dominions as $dominion)
            {
                # NPC Barbarian: invasion
                if($dominion->race->name === 'Barbarian')
                {
                    $this->barbarianService->handleBarbarianInvasion($dominion);
                }

                // Afflicted: Abomination generation
                if(!empty($dominion->tick->pestilence_units))
                {
                    $caster = Dominion::findorfail($dominion->tick->pestilence_units['caster_dominion_id']);
                    if ($caster)
                    {
                        $this->queueService->queueResources('training', $caster, ['military_unit1' => $dominion->tick->pestilence_units['units']['military_unit1']], 12);
                    }
                }

                // Myconid: Land generation
                if(!empty($dominion->tick->generated_land) and $dominion->protection_ticks == 0)
                {
                    $homeLandType = 'land_' . $dominion->race->home_land_type;
                    $this->queueService->queueResources('exploration', $dominion, [$homeLandType => $dominion->tick->generated_land], 12);
                }

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
                    if($dominion->tick->starvation_casualties > 0 and !$dominion->isAbandoned())
                    {
                        $this->notificationService->queueNotification('starvation_occurred');
                    }

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
                    {
                        $this->notificationService->queueNotification('attrition_occurred',[$dominion->tick->attrition_unit1, $dominion->tick->attrition_unit2, $dominion->tick->attrition_unit3, $dominion->tick->attrition_unit4]);
                    }

                    $this->cleanupActiveSpells($dominion);
                    $this->cleanupQueues($dominion);

                    $this->notificationService->sendNotifications($dominion, 'hourly_dominion');

                    $this->precalculateTick($dominion, true);

                }, 5);

            }

            foreach($realms as $realm)
            {
                if($realm->crypt > 0)
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
                     $bodiesToRemove = min($bodiesToRemove, $realm->crypt);

                     $cryptLogString = '[CRYPT] ';
                     $cryptLogString .= "Bodies current: " . $realm->crypt . ". ";
                     $cryptLogString .= "Bodies decayed: " . $bodiesDecayed . ". ";
                     $cryptLogString .= "Bodies spent: " . $bodiesSpent->cryptBodiesSpent . ". ";
                     $cryptLogString .= "Bodies to remove: " . $bodiesToRemove . ". ";


                    $realm->fill([
                        'crypt' => ($realm->crypt - $bodiesToRemove),
                    ])->save();

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
        }

        // Update rankings
        /*
        if (($this->now->hour === 0 or $this->now->hour === 12) and $this->now->minute < 15)
        {
            foreach ($activeRounds as $round)
            {
                $this->updateDailyRankings($round->dominions);

                Log::info(sprintf(
                    '[RANKINGS] Updated rankings in %s ms in %s',
                    number_format($this->now->diffInMilliseconds(now())),
                    $round->name
                ));

                $this->now = now();
            }
        }
        */
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
                // Ignore the first hour 0 of the round
                #if ($this->now->diffInHours($round->start_date) === 0) {
                #    continue;
                #}

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
        $tick = Tick::firstOrCreate(
            ['dominion_id' => $dominion->id]
        );

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
              ->where('hours', '=', 1)
              ->get();

          foreach ($incomingQueue as $row)
          {
              $tick->{$row->resource} += $row->amount;
              // Temporarily add next hour's resources for accurate calculations
              $dominion->{$row->resource} += $row->amount;
          }

          # NPC Barbarian: training
          if($dominion->race->name === 'Barbarian')
          {
              $this->barbarianService->handleBarbarianTraining($dominion);
              $this->barbarianService->handleBarbarianConstruction($dominion);
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
              $caster = $this->spellCalculator->getCaster($dominion, 'pestilence');

              $amountToDie = $dominion->peasants * 0.01 * $this->spellDamageCalculator->getDominionHarmfulSpellDamageModifier($dominion, null, Spell::where('key', 'pestilence')->first(), null);
              $amountToDie *= $this->rangeCalculator->getDominionRange($caster, $dominion) / 100;
              $amountToDie *= (1 - $dominion->race->getPerkMultiplier('reduced_conversions'));
              $amountToDie = (int)round($amountToDie);

              $tick->pestilence_units = ['caster_dominion_id' => $caster->id, 'units' => ['military_unit1' => $amountToDie]];
              $populationPeasantGrowth -= $amountToDie;
          }

          $tick->peasants_sacrificed = $this->populationCalculator->getPeasantsSacrificed($dominion) * -1;

          $tick->peasants = $populationPeasantGrowth;
          $tick->military_draftees = $drafteesGrowthRate;

          // Void: Improvements Decay - Lower all improvements by improvements_decay%.
          if($dominion->race->getPerkValue('improvements_decay'))
          {
              foreach($this->improvementHelper->getImprovementTypes($dominion) as $improvementType)
              {
                  $percentageDecayed = $dominion->race->getPerkValue('improvements_decay') / 100;
                  $tick->{'improvement_' . $improvementType} -= $dominion->{'improvement_' . $improvementType} * $percentageDecayed;
              }
          }

          // Resources

          # Max storage
          /*
          $maxStorageTicks = 24 * 4; # Store at most 24 hours (96 ticks) per building.
          $acres = $this->landCalculator->getTotalLand($dominion);
          $maxGoldPerAcre = 10000;

          $maxStorage = [];
          $maxStorage['gold'] = $this->productionCalculator->getMaxStorage($dominion, 'gold');
          $maxStorage['lumber'] = $this->productionCalculator->getMaxStorage($dominion, 'lumber');
          $maxStorage['ore'] = $this->productionCalculator->getMaxStorage($dominion, 'ore');
          $maxStorage['gems'] = $this->productionCalculator->getMaxStorage($dominion, 'gems');
          */

          #$tick->resource_gold += min($this->productionCalculator->getGoldProduction($dominion), max(0, ($maxStorage['gold'] - $dominion->resource_gold)));
          $tick->resource_gold += $this->productionCalculator->getGoldProduction($dominion);

          $tick->resource_lumber_production += $this->productionCalculator->getLumberProduction($dominion);
          #$tick->resource_lumber += min($this->productionCalculator->getLumberProduction($dominion), max(0, ($maxStorage['lumber'] - $dominion->resource_lumber)));
          $tick->resource_lumber += $this->productionCalculator->getLumberProduction($dominion);

          $tick->resource_mana_production += $this->productionCalculator->getManaProduction($dominion);
          $tick->resource_mana += $this->productionCalculator->getManaNetChange($dominion);

          $tick->resource_food_production += $this->productionCalculator->getFoodProduction($dominion);
          $tick->resource_food += $this->productionCalculator->getFoodNetChange($dominion);

          #$tick->resource_ore += min($this->productionCalculator->getOreProduction($dominion), max(0, ($maxStorage['ore'] - $dominion->resource_ore)));
          $tick->resource_ore += $this->productionCalculator->getOreProduction($dominion);

          #$tick->resource_gems += min($this->productionCalculator->getGemProduction($dominion), max(0, ($maxStorage['gems'] - $dominion->resource_gems)));
          $tick->resource_gems += $this->productionCalculator->getGemProduction($dominion);

          $tick->resource_tech += $this->productionCalculator->getTechProduction($dominion);
          $tick->resource_boats += 0; #$this->productionCalculator->getBoatProduction($dominion);

          $tick->resource_soul += $this->productionCalculator->getSoulProduction($dominion);
          $tick->resource_blood += $this->productionCalculator->getBloodProduction($dominion);

          # Decay, rot, drain
          $tick->resource_food_consumption += $this->productionCalculator->getFoodConsumption($dominion);
          $tick->resource_food_decay += 0;#$this->productionCalculator->getFoodDecay($dominion);
          $tick->resource_lumber_rot += 0;#$this->productionCalculator->getLumberDecay($dominion);
          $tick->resource_mana_drain += 0;#$this->productionCalculator->getManaDecay($dominion);

          # Contribution: how much is LOST (GIVEN AWAY)
          $tick->resource_food_contribution = $this->productionCalculator->getContribution($dominion, 'food');
          $tick->resource_mana_contribution = $this->productionCalculator->getContribution($dominion, 'mana');

          # Contributed: how much is RECEIVED (GIVEN TO)
          $tick->resource_food_contributed = 0;
          $tick->resource_mana_contributed = 0;
          if($dominion->race->name == 'Monster')
          {
              $totalContributions = $this->realmCalculator->getTotalContributions($dominion->realm);
              $tick->resource_food_contributed = $totalContributions['food'];
              $tick->resource_mana_contributed = $totalContributions['mana'];
          }

          // Starvation

          $tick->starvation_casualties = 0;
          if($dominion->resource_food + $this->productionCalculator->getFoodNetChange($dominion) < 0)
          {
              $tick->resource_food = ($dominion->resource_food*-1);
              $tick->starvation_casualties = 10;
          }

          // Morale
          $baseMorale = 100;
          $baseMoraleModifier = $this->militaryCalculator->getBaseMoraleModifier($dominion, $this->populationCalculator->getPopulation($dominion));
          $baseMorale *= (1 + $baseMoraleModifier);
          $baseMorale = intval($baseMorale);

          $moraleChangeModifier = (1 + $dominion->race->getPerkValue('morale_change'));

          if($tick->starvation_casualties > 0)
          {
              # Lower morale by 10.
              $starvationMoraleChange = -10;
              if(($dominion->morale + $starvationMoraleChange) < 0)
              {
                  $tick->morale = -$dominion->morale;
              }
              else
              {
                  $tick->morale = $starvationMoraleChange;
              }
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

          // Spy Strength
          if ($dominion->spy_strength < 100)
          {
              $spyStrengthAdded = 4;
              $spyStrengthAdded += $dominion->getTechPerkValue('spy_strength_recovery');

              $tick->spy_strength = min($spyStrengthAdded, 100 - $dominion->spy_strength);
          }

          // Wizard Strength
          if ($dominion->wizard_strength < 100)
          {
              $wizardStrengthAdded = 4;

              $wizardStrengthPerWizardGuild = 0.1;
              $wizardStrengthPerWizardGuildMax = 2;

              $wizardStrengthAdded += $dominion->getBuildingPerkMultiplier('wizard_strength_recovery');

              $wizardStrengthAdded += $dominion->getTechPerkValue('wizard_strength_recovery');

              $tick->wizard_strength = min($wizardStrengthAdded, 100 - $dominion->wizard_strength);
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
          $attritionReduction = 1;
          if($dominion->race->name == 'Cult')
          {
              $attritionReduction = $dominion->military_unit3 / max($this->populationCalculator->getPopulationMilitary($dominion),1);
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
                  $unitAttritionAmount = intval($dominion->{'military_unit'.$slot} * $unitAttritionPerk/100 * $attritionReduction);
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

          # Version 1.3 (Round 42, Spells 2.0 compatible-r)
          if ($this->spellCalculator->isSpellActive($dominion, 'rites_of_zidur'))
          {
              $spell = Spell::where('key', 'rites_of_zidur')->first();

              $spellPerkValues = $spell->getActiveSpellPerkValues($spell->key, 'converts_crypt_bodies');

              # Check bodies available in the crypt
              $bodiesAvailable = max(0, floor($dominion->realm->crypt - $tick->crypt_bodies_spent));

              # Break down the spell perk
              $raisersPerRaisedUnit = (int)$spellPerkValues[0];
              $raisingUnitSlot = (int)$spellPerkValues[1];
              $unitRaisedSlot = (int)$spellPerkValues[2];

              $unitsRaised = $dominion->{'military_unit' . $raisingUnitSlot} / $raisersPerRaisedUnit;

              # Check the pairing limit
              $pairingLimitedIncreasable = $dominion->race->getUnitPerkValueForUnitSlot($unitRaisedSlot, 'pairing_limit_increasable');

              $unitLimitedTo = (float)$pairingLimitedIncreasable[0]; # Units paired-limited to
              $unitsPerLimitingUnit = (float)$pairingLimitedIncreasable[1]; # Number of this unit per unit paired-limited to
              $extendingImprovement = (string)$pairingLimitedIncreasable[2]; # Improvement which can increase this limit
              $improvementMultiplier = (float)$pairingLimitedIncreasable[3]; # Multiplier used to extend the increase from improvement
              $unitsPerLimitingUnit *= 1 + ($this->improvementCalculator->getImprovementMultiplierBonus($dominion, $extendingImprovement) * $improvementMultiplier);

              $maxAdditionalPermittedOfThisUnit = round($dominion->{'military_unit'.$unitLimitedTo} * $unitsPerLimitingUnit);
              $maxAdditionalPermittedOfThisUnit -= $this->militaryCalculator->getTotalUnitsForSlot($dominion, $unitRaisedSlot);
              $maxAdditionalPermittedOfThisUnit -= $this->queueService->getTrainingQueueTotalByResource($dominion, 'military_unit'.$unitRaisedSlot);

              $unitsRaised = min($unitsRaised, $maxAdditionalPermittedOfThisUnit, $bodiesAvailable);

              $tick->{'generated_unit' . $unitRaisedSlot} += $unitsRaised;
              $tick->crypt_bodies_spent += $unitsRaised;
          }
          if ($this->spellCalculator->isSpellActive($dominion, 'rites_of_kinthys'))
          {
              $spell = Spell::where('key', 'rites_of_kinthys')->first();

              $spellPerkValues = $spell->getActiveSpellPerkValues($spell->key, 'converts_crypt_bodies');

              # Check bodies available in the crypt
              $bodiesAvailable = max(0, floor($dominion->realm->crypt - $tick->crypt_bodies_spent));

              # Break down the spell perk
              $raisersPerRaisedUnit = (int)$spellPerkValues[0];
              $raisingUnitSlot = (int)$spellPerkValues[1];
              $unitRaisedSlot = (int)$spellPerkValues[2];

              $unitsRaised = $dominion->{'military_unit' . $raisingUnitSlot} / $raisersPerRaisedUnit;

              # Check the pairing limit
              $pairingLimitedIncreasable = $dominion->race->getUnitPerkValueForUnitSlot($unitRaisedSlot, 'pairing_limit_increasable');

              $unitLimitedTo = (float)$pairingLimitedIncreasable[0]; # Units paired-limited to
              $unitsPerLimitingUnit = (float)$pairingLimitedIncreasable[1]; # Number of this unit per unit paired-limited to
              $extendingImprovement = (string)$pairingLimitedIncreasable[2]; # Improvement which can increase this limit
              $improvementMultiplier = (float)$pairingLimitedIncreasable[3]; # Multiplier used to extend the increase from improvement
              $unitsPerLimitingUnit *= 1 + ($this->improvementCalculator->getImprovementMultiplierBonus($dominion, $extendingImprovement) * $improvementMultiplier);

              $maxAdditionalPermittedOfThisUnit = round($dominion->{'military_unit'.$unitLimitedTo} * $unitsPerLimitingUnit);
              $maxAdditionalPermittedOfThisUnit -= $this->militaryCalculator->getTotalUnitsForSlot($dominion, $unitRaisedSlot);
              $maxAdditionalPermittedOfThisUnit -= $this->queueService->getTrainingQueueTotalByResource($dominion, 'military_unit'.$unitRaisedSlot);

              $unitsRaised = min($unitsRaised, $maxAdditionalPermittedOfThisUnit, $bodiesAvailable);

              $tick->{'generated_unit' . $unitRaisedSlot} += $unitsRaised;
              $tick->crypt_bodies_spent += $unitsRaised;
          }

          # Snow Elf: Gryphon Nests generate Gryphons
          if($dominion->race->getPerkValue('gryphon_nests_generate_gryphons'))
          {
              $gryphonSlot = 4;
              $newGryphons = 0;
              $maxGryphonNestsPercentage = 0.20;
              $gryphonNestsOwned = $this->buildingCalculator->getBuildingAmountOwned($dominion, null, 'gryphon_nest', null);
              $gryphonNestsPercentage = min($maxGryphonNestsPercentage, ($gryphonNestsOwned / $this->landCalculator->getTotalLand($dominion)));

              $gryphonNests = floor($gryphonNestsPercentage * $this->landCalculator->getTotalLand($dominion));
              $gryphonsMax = $gryphonNests * 1 * (1 + $this->prestigeCalculator->getPrestigeMultiplier($dominion));

              $gryphonsCurrent = $this->militaryCalculator->getTotalUnitsForSlot($dominion, $gryphonSlot);
              $gryphonsCurrent += $this->queueService->getTrainingQueueTotalByResource($dominion, 'military_unit'.$gryphonSlot);

              $availableNestHousing = $gryphonsMax - $gryphonsCurrent;

              if($availableNestHousing > 0)
              {
                  $newGryphons = min($gryphonNests * 0.05, $availableNestHousing);
              }

              $tick->generated_unit4 = $newGryphons;
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
              // Reset current resources in case object is saved later
              $dominion->{$row->resource} -= $row->amount;
          }

          $tick->save();
    }

    /*
    protected function updateDailyRankings(Collection $activeDominions): void
    {
        $dominionIds = $activeDominions->pluck('id')->toArray();

        // First pass: Saving land and networth
        Dominion::with(['race', 'realm'])
            ->whereIn('id', $dominionIds)
            ->chunk(50, function ($dominions) {
                foreach ($dominions as $dominion) {
                    $where = [
                        'round_id' => (int)$dominion->round_id,
                        'dominion_id' => $dominion->id,
                    ];

                    $data = [
                        'dominion_name' => $dominion->name,
                        'race_name' => $dominion->race->name,
                        'realm_number' => $dominion->realm->number,
                        'realm_name' => $dominion->realm->name,
                        'land' => $this->landCalculator->getTotalLand($dominion),
                        'networth' => $this->networthCalculator->getDominionNetworth($dominion),
                    ];

                    $result = DB::table('daily_rankings')->where($where)->get();

                    if ($result->isEmpty()) {
                        $row = $where + $data + [
                                'created_at' => $dominion->created_at,
                                'updated_at' => $this->now,
                            ];

                        DB::table('daily_rankings')->insert($row);
                    } else {
                        $row = $data + [
                                'updated_at' => $this->now,
                            ];

                        DB::table('daily_rankings')->where($where)->update($row);
                    }
                }
            });

        // Second pass: Calculating ranks
        $result = DB::table('daily_rankings')
            ->orderBy('land', 'desc')
            ->orderBy(DB::raw('COALESCE(land_rank, created_at)'))
            ->get();

        //Getting all rounds
        $rounds = DB::table('rounds')
            ->where('start_date', '<=', $this->now)
            ->where('end_date', '>', $this->now)
            ->get();

        foreach ($rounds as $round) {
            $rank = 1;

            foreach ($result as $row) {
                if ($row->round_id == (int)$round->id) {
                    DB::table('daily_rankings')
                        ->where('id', $row->id)
                        ->where('round_id', $round->id)
                        ->update([
                            'land_rank' => $rank,
                            'land_rank_change' => (($row->land_rank !== null) ? ($row->land_rank - $rank) : 0),
                        ]);

                    $rank++;
                }
            }

            $result = DB::table('daily_rankings')
                ->orderBy('networth', 'desc')
                ->orderBy(DB::raw('COALESCE(networth_rank, created_at)'))
                ->get();

            $rank = 1;

            foreach ($result as $row) {
                if ($row->round_id == (int)$round->id) {
                    DB::table('daily_rankings')
                        ->where('id', $row->id)
                        ->update([
                            'networth_rank' => $rank,
                            'networth_rank_change' => (($row->networth_rank !== null) ? ($row->networth_rank - $rank) : 0),
                        ]);

                    $rank++;
                }
            }
        }
    }
    */

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
        $this->logDominionTickState($dominion, now());

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

        DB::transaction(function () use ($dominion)
        {
            // Update dominions
            DB::table('dominions')
                ->join('dominion_tick', 'dominions.id', '=', 'dominion_tick.dominion_id')
                ->where('dominions.id', $dominion->id)
                ->where('dominions.protection_ticks', '>', 0)
                ->where('dominions.is_locked', false)
                ->update([
                    'dominions.prestige' => DB::raw('dominions.prestige + dominion_tick.prestige'),
                    'dominions.peasants' => DB::raw('dominions.peasants + dominion_tick.peasants + dominion_tick.peasants_sacrificed'),
                    'dominions.peasants_last_hour' => DB::raw('dominion_tick.peasants'),
                    'dominions.morale' => DB::raw('dominions.morale + dominion_tick.morale'),
                    'dominions.spy_strength' => DB::raw('dominions.spy_strength + dominion_tick.spy_strength'),
                    'dominions.wizard_strength' => DB::raw('dominions.wizard_strength + dominion_tick.wizard_strength'),

                    'dominions.resource_gold' => DB::raw('dominions.resource_gold + dominion_tick.resource_gold'),
                    'dominions.resource_food' => DB::raw('dominions.resource_food + dominion_tick.resource_food'),
                    'dominions.resource_lumber' => DB::raw('dominions.resource_lumber + dominion_tick.resource_lumber'),
                    'dominions.resource_mana' => DB::raw('dominions.resource_mana + dominion_tick.resource_mana'),
                    'dominions.resource_ore' => DB::raw('dominions.resource_ore + dominion_tick.resource_ore'),
                    'dominions.resource_gems' => DB::raw('dominions.resource_gems + dominion_tick.resource_gems'),
                    'dominions.resource_tech' => DB::raw('dominions.resource_tech + dominion_tick.resource_tech'),
                    'dominions.resource_boats' => 0, #DB::raw('dominions.resource_boats + dominion_tick.resource_boats'),

                    # Improvements
                    'dominions.improvement_markets' => DB::raw('dominions.improvement_markets + dominion_tick.improvement_markets'),
                    'dominions.improvement_keep' => DB::raw('dominions.improvement_keep + dominion_tick.improvement_keep'),
                    'dominions.improvement_forges' => DB::raw('dominions.improvement_forges + dominion_tick.improvement_forges'),
                    'dominions.improvement_walls' => DB::raw('dominions.improvement_walls + dominion_tick.improvement_walls'),
                    'dominions.improvement_armory' => DB::raw('dominions.improvement_armory + dominion_tick.improvement_armory'),
                    'dominions.improvement_infirmary' => DB::raw('dominions.improvement_infirmary + dominion_tick.improvement_infirmary'),
                    'dominions.improvement_workshops' => DB::raw('dominions.improvement_workshops + dominion_tick.improvement_workshops'),
                    'dominions.improvement_observatory' => DB::raw('dominions.improvement_observatory + dominion_tick.improvement_observatory'),
                    'dominions.improvement_cartography' => DB::raw('dominions.improvement_cartography + dominion_tick.improvement_cartography'),
                    'dominions.improvement_towers' => DB::raw('dominions.improvement_towers + dominion_tick.improvement_towers'),
                    'dominions.improvement_spires' => DB::raw('dominions.improvement_spires + dominion_tick.improvement_spires'),
                    'dominions.improvement_hideouts' => DB::raw('dominions.improvement_hideouts + dominion_tick.improvement_hideouts'),
                    'dominions.improvement_granaries' => DB::raw('dominions.improvement_granaries + dominion_tick.improvement_granaries'),
                    'dominions.improvement_harbor' => DB::raw('dominions.improvement_harbor + dominion_tick.improvement_harbor'),
                    'dominions.improvement_forestry' => DB::raw('dominions.improvement_forestry + dominion_tick.improvement_forestry'),
                    'dominions.improvement_refinery' => DB::raw('dominions.improvement_refinery + dominion_tick.improvement_refinery'),
                    'dominions.improvement_tissue' => DB::raw('dominions.improvement_tissue + dominion_tick.improvement_tissue'),

                    # ODA resources
                    'dominions.resource_wild_yeti' => DB::raw('dominions.resource_wild_yeti + dominion_tick.resource_wild_yeti'),
                    'dominions.resource_champion' => DB::raw('dominions.resource_champion + dominion_tick.resource_champion'),
                    'dominions.resource_soul' => DB::raw('dominions.resource_soul + dominion_tick.resource_soul'),
                    'dominions.resource_blood' => DB::raw('dominions.resource_blood + dominion_tick.resource_blood'),

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

                    /*
                    'dominions.discounted_land' => DB::raw('dominions.discounted_land + dominion_tick.discounted_land'),
                    'dominions.building_home' => DB::raw('dominions.building_home + dominion_tick.building_home'),
                    'dominions.building_alchemy' => DB::raw('dominions.building_alchemy + dominion_tick.building_alchemy'),
                    'dominions.building_farm' => DB::raw('dominions.building_farm + dominion_tick.building_farm'),
                    'dominions.building_smithy' => DB::raw('dominions.building_smithy + dominion_tick.building_smithy'),
                    'dominions.building_masonry' => DB::raw('dominions.building_masonry + dominion_tick.building_masonry'),
                    'dominions.building_ore_mine' => DB::raw('dominions.building_ore_mine + dominion_tick.building_ore_mine'),
                    'dominions.building_gryphon_nest' => DB::raw('dominions.building_gryphon_nest + dominion_tick.building_gryphon_nest'),
                    'dominions.building_tower' => DB::raw('dominions.building_tower + dominion_tick.building_tower'),
                    'dominions.building_wizard_guild' => DB::raw('dominions.building_wizard_guild + dominion_tick.building_wizard_guild'),
                    'dominions.building_temple' => DB::raw('dominions.building_temple + dominion_tick.building_temple'),
                    'dominions.building_gem_mine' => DB::raw('dominions.building_gem_mine + dominion_tick.building_gem_mine'),
                    'dominions.building_school' => DB::raw('dominions.building_school + dominion_tick.building_school'),
                    'dominions.building_lumberyard' => DB::raw('dominions.building_lumberyard + dominion_tick.building_lumberyard'),
                    'dominions.building_forest_haven' => DB::raw('dominions.building_forest_haven + dominion_tick.building_forest_haven'),
                    'dominions.building_factory' => DB::raw('dominions.building_factory + dominion_tick.building_factory'),
                    'dominions.building_guard_tower' => DB::raw('dominions.building_guard_tower + dominion_tick.building_guard_tower'),
                    'dominions.building_shrine' => DB::raw('dominions.building_shrine + dominion_tick.building_shrine'),
                    'dominions.building_barracks' => DB::raw('dominions.building_barracks + dominion_tick.building_barracks'),
                    'dominions.building_dock' => DB::raw('dominions.building_dock + dominion_tick.building_dock'),

                    'dominions.building_ziggurat' => DB::raw('dominions.building_ziggurat + dominion_tick.building_ziggurat'),
                    'dominions.building_tissue' => DB::raw('dominions.building_tissue + dominion_tick.building_tissue'),
                    'dominions.building_mycelia' => DB::raw('dominions.building_mycelia + dominion_tick.building_mycelia'),
                    */

                    'dominions.stat_total_gold_production' => DB::raw('dominions.stat_total_gold_production + dominion_tick.resource_gold'),
                    'dominions.stat_total_food_production' => DB::raw('dominions.stat_total_food_production + dominion_tick.resource_food_production'),
                    'dominions.stat_total_lumber_production' => DB::raw('dominions.stat_total_lumber_production + dominion_tick.resource_lumber_production'),
                    'dominions.stat_total_mana_production' => DB::raw('dominions.stat_total_mana_production + dominion_tick.resource_mana_production'),
                    'dominions.stat_total_wild_yeti_production' => DB::raw('dominions.stat_total_wild_yeti_production + dominion_tick.resource_wild_yeti_production'),
                    'dominions.stat_total_ore_production' => DB::raw('dominions.stat_total_ore_production + dominion_tick.resource_ore'),
                    'dominions.stat_total_gem_production' => DB::raw('dominions.stat_total_gem_production + dominion_tick.resource_gems'),
                    'dominions.stat_total_tech_production' => DB::raw('dominions.stat_total_tech_production + dominion_tick.resource_tech'),
                    'dominions.stat_total_boat_production' => 0, #DB::raw('dominions.stat_total_boat_production + dominion_tick.resource_boats'),

                    'dominions.stat_total_food_decayed' => DB::raw('dominions.stat_total_food_decayed + dominion_tick.resource_food_decay'),
                    'dominions.stat_total_food_consumed' => DB::raw('dominions.stat_total_food_consumed + dominion_tick.resource_food_consumption'),
                    'dominions.stat_total_lumber_rotted' => DB::raw('dominions.stat_total_lumber_rotted + dominion_tick.resource_lumber_rot'),
                    'dominions.stat_total_mana_drained' => DB::raw('dominions.stat_total_mana_drained + dominion_tick.resource_mana_drain'),

                    'dominions.protection_ticks' => DB::raw('dominions.protection_ticks + dominion_tick.protection_ticks'),

                    'dominions.last_tick_at' => DB::raw('now()')
                ]);

            // Update spells
            DB::table('dominion_spells')
                ->join('dominions', 'dominion_spells.dominion_id', '=', 'dominions.id')
                ->where('dominions.id', $dominion->id)
                ->update([
                    'duration' => DB::raw('`duration` - 1'),
                    'dominion_spells.updated_at' => $this->now,
                ]);

            // Update queues
            DB::table('dominion_queue')
                ->join('dominions', 'dominion_queue.dominion_id', '=', 'dominions.id')
                ->where('dominions.id', $dominion->id)
                ->update([
                    'hours' => DB::raw('`hours` - 1'),
                    'dominion_queue.updated_at' => $this->now,
                ]);

        }, 10);

        Log::info(sprintf(
            '[TICK] Ticked dominion %s in %s ms.',
            $dominion->name,
            number_format($this->now->diffInMilliseconds(now()))
        ));

        $this->now = now();

        # Starvation
        DB::transaction(function () use ($dominion)
        {
            # Send starvation notification.
            if($dominion->tick->starvation_casualties > 0 and !$dominion->isAbandoned())
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

        }, 5);

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


    private function logDominionTickState(Dominion $dominion, Carbon $now): void
    {
        DB::table('dominion_tick_states')->insert([

          'dominion_id' => $dominion->id,
          'tick' => $now,

          'prestige' => $dominion->prestige,
          'peasants' => $dominion->peasants,
          'draft_rate' => $dominion->draft_rate,
          'morale' => $dominion->morale,
          'spy_strength' => $dominion->spy_strength,
          'wizard_strength' => $dominion->wizard_strength,

          'resource_gold' => $dominion->resource_gold,
          'resource_food' => $dominion->resource_food,
          'resource_lumber' => $dominion->resource_lumber,
          'resource_mana' => $dominion->resource_mana,
          'resource_ore' => $dominion->resource_ore,
          'resource_gems' => $dominion->resource_gems,
          'resource_tech' => $dominion->resource_tech,
          'resource_boats' => $dominion->resource_boats,
          'resource_champion' => $dominion->resource_champion,
          'resource_soul' => $dominion->resource_soul,
          'resource_blood' => $dominion->resource_blood,
          'resource_wild_yeti' => $dominion->resource_wild_yeti,

          'improvement_markets' => $dominion->improvement_markets,
          'improvement_keep' => $dominion->improvement_keep,
          'improvement_spires' => $dominion->improvement_spires,
          'improvement_forges' => $dominion->improvement_forges,
          'improvement_walls' => $dominion->improvement_walls,
          'improvement_harbor' => $dominion->improvement_harbor,
          'improvement_armory' => $dominion->improvement_armory,
          'improvement_infirmary' => $dominion->improvement_infirmary,
          'improvement_workshops' => $dominion->improvement_workshops,
          'improvement_observatory' => $dominion->improvement_observatory,
          'improvement_cartography' => $dominion->improvement_cartography,
          'improvement_hideouts' => $dominion->improvement_hideouts,
          'improvement_forestry' => $dominion->improvement_forestry,
          'improvement_refinery' => $dominion->improvement_refinery,
          'improvement_granaries' => $dominion->improvement_granaries,
          'improvement_tissue' => $dominion->improvement_tissue,
          'military_draftees' => $dominion->military_draftees,
          'military_unit1' => $dominion->military_unit1,
          'military_unit2' => $dominion->military_unit2,
          'military_unit3' => $dominion->military_unit3,
          'military_unit4' => $dominion->military_unit4,
          'military_spies' => $dominion->military_spies,
          'military_wizards' => $dominion->military_wizards,
          'military_archmages' => $dominion->military_archmages,

          'land_plain' => $dominion->land_plain,
          'land_mountain' => $dominion->land_mountain,
          'land_swamp' => $dominion->land_swamp,
          'land_forest' => $dominion->land_forest,
          'land_hill' => $dominion->land_hill,
          'land_water' => $dominion->land_water,

          'building_home' => 0,#$dominion->building_home,
          'building_alchemy' => 0,#$dominion->building_alchemy,
          'building_farm' => 0,#$dominion->building_farm,
          'building_smithy' => 0,#$dominion->building_smithy,
          'building_masonry' => 0,#$dominion->building_masonry,
          'building_ore_mine' => 0,#$dominion->building_ore_mine,
          'building_gryphon_nest' => 0,#$dominion->building_gryphon_nest,
          'building_tower' => 0,#$dominion->building_tower,
          'building_wizard_guild' => 0,#$dominion->building_wizard_guild,
          'building_temple' => 0,#$dominion->building_temple,
          'building_gem_mine' => 0,#$dominion->building_gem_mine,
          'building_school' => 0,#$dominion->building_school,
          'building_lumberyard' => 0,#$dominion->building_lumberyard,
          'building_forest_haven' => 0,#$dominion->building_forest_haven,
          'building_factory' => 0,#$dominion->building_factory,
          'building_guard_tower' => 0,#$dominion->building_guard_tower,
          'building_shrine' => 0,#$dominion->building_shrine,
          'building_barracks' => 0,#$dominion->building_barracks,
          'building_dock' => 0,#$dominion->building_dock,

          'protection_ticks' => $dominion->protection_ticks,
          'is_locked' => $dominion->is_locked,
        ]);
    }

}
