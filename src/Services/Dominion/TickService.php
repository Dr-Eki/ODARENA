<?php

namespace OpenDominion\Services\Dominion;

use DB;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Log;
use OpenDominion\Calculators\Dominion\CasualtiesCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\ProductionCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Dominion\Tick;
use OpenDominion\Models\Round;
use OpenDominion\Services\NotificationService;
use Throwable;

# ODA
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Models\GameEvent;
use OpenDominion\Calculators\Dominion\RangeCalculator;

class TickService
{
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

    /** @var SpellCalculator */
    protected $spellCalculator;

    /** @var MilitaryCalculator */
    protected $militaryCalculator;

    /** @var RangeCalculator */
    protected $rangeCalculator;

    /**
     * TickService constructor.
     */
    public function __construct()
    {
        $this->now = now();
        $this->casualtiesCalculator = app(CasualtiesCalculator::class);
        $this->landCalculator = app(LandCalculator::class);
        $this->networthCalculator = app(NetworthCalculator::class);
        $this->notificationService = app(NotificationService::class);
        $this->populationCalculator = app(PopulationCalculator::class);
        $this->productionCalculator = app(ProductionCalculator::class);
        $this->queueService = app(QueueService::class);
        $this->spellCalculator = app(SpellCalculator::class);

        $this->militaryCalculator = app(MilitaryCalculator::class);
        $this->rangeCalculator = app(RangeCalculator::class);

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
        Log::debug('Hourly tick started');

        // Hourly tick

        //DB::connection()->enableQueryLog();

        $activeRounds = Round::active()->get();

        foreach ($activeRounds as $round) {
            // Precalculate all dominion ticks on hour 0
            if ($this->now->diffInHours($round->start_date) === 0)
            {
                $dominions = $round->activeDominions()
                    ->with([
                        'race',
                        'race.perks',
                        'race.units',
                        'race.units.perks',
                    ])
                    ->get();

                  foreach ($dominions as $dominion)
                  {
                    $this->precalculateTick($dominion, true);
                  }

                continue;
            }

            DB::transaction(function () use ($round)
            {

                // Update dominions
                DB::table('dominions')
                    ->join('dominion_tick', 'dominions.id', '=', 'dominion_tick.dominion_id')
                    ->where('dominions.round_id', $round->id)
                    ->where('dominions.is_locked', false)
                    ->where('dominions.protection_ticks', '=', 0)
                    ->update([
                        'dominions.prestige' => DB::raw('dominions.prestige + dominion_tick.prestige'),
                        'dominions.peasants' => DB::raw('dominions.peasants + dominion_tick.peasants'),
                        'dominions.peasants_last_hour' => DB::raw('dominion_tick.peasants'),
                        'dominions.morale' => DB::raw('dominions.morale + dominion_tick.morale'),
                        'dominions.spy_strength' => DB::raw('dominions.spy_strength + dominion_tick.spy_strength'),
                        'dominions.wizard_strength' => DB::raw('dominions.wizard_strength + dominion_tick.wizard_strength'),

                        'dominions.resource_platinum' => DB::raw('dominions.resource_platinum + dominion_tick.resource_platinum'),
                        'dominions.resource_food' => DB::raw('dominions.resource_food + dominion_tick.resource_food'),
                        'dominions.resource_lumber' => DB::raw('dominions.resource_lumber + dominion_tick.resource_lumber'),
                        'dominions.resource_mana' => DB::raw('dominions.resource_mana + dominion_tick.resource_mana'),
                        'dominions.resource_ore' => DB::raw('dominions.resource_ore + dominion_tick.resource_ore'),
                        'dominions.resource_gems' => DB::raw('dominions.resource_gems + dominion_tick.resource_gems'),
                        'dominions.resource_tech' => DB::raw('dominions.resource_tech + dominion_tick.resource_tech'),
                        'dominions.resource_boats' => DB::raw('dominions.resource_boats + dominion_tick.resource_boats'),

                        # ODA resources
                        'dominions.resource_wild_yeti' => DB::raw('dominions.resource_wild_yeti + dominion_tick.resource_wild_yeti'),
                        'dominions.resource_champion' => DB::raw('dominions.resource_champion + dominion_tick.resource_champion'),
                        'dominions.resource_soul' => DB::raw('dominions.resource_soul + dominion_tick.resource_soul'),

                        'dominions.military_draftees' => DB::raw('dominions.military_draftees + dominion_tick.military_draftees'),
                        'dominions.military_unit1' => DB::raw('dominions.military_unit1 + dominion_tick.military_unit1 + dominion_tick.generated_unit1'),
                        'dominions.military_unit2' => DB::raw('dominions.military_unit2 + dominion_tick.military_unit2 + dominion_tick.generated_unit2'),
                        'dominions.military_unit3' => DB::raw('dominions.military_unit3 + dominion_tick.military_unit3 + dominion_tick.generated_unit3'),
                        'dominions.military_unit4' => DB::raw('dominions.military_unit4 + dominion_tick.military_unit4 + dominion_tick.generated_unit4'),
                        'dominions.military_spies' => DB::raw('dominions.military_spies + dominion_tick.military_spies'),
                        'dominions.military_wizards' => DB::raw('dominions.military_wizards + dominion_tick.military_wizards'),
                        'dominions.military_archmages' => DB::raw('dominions.military_archmages + dominion_tick.military_archmages'),

                        'dominions.land_plain' => DB::raw('dominions.land_plain + dominion_tick.land_plain'),
                        'dominions.land_mountain' => DB::raw('dominions.land_mountain + dominion_tick.land_mountain'),
                        'dominions.land_swamp' => DB::raw('dominions.land_swamp + dominion_tick.land_swamp'),
                        'dominions.land_cavern' => DB::raw('dominions.land_cavern + dominion_tick.land_cavern'),
                        'dominions.land_forest' => DB::raw('dominions.land_forest + dominion_tick.land_forest + dominion_tick.generated_land'),
                        'dominions.land_hill' => DB::raw('dominions.land_hill + dominion_tick.land_hill'),
                        'dominions.land_water' => DB::raw('dominions.land_water + dominion_tick.land_water'),

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
                        'dominions.building_diamond_mine' => DB::raw('dominions.building_diamond_mine + dominion_tick.building_diamond_mine'),
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

                        'dominions.stat_total_platinum_production' => DB::raw('dominions.stat_total_platinum_production + dominion_tick.resource_platinum'),
                        #'dominions.stat_total_platinum_production' => 0,
                        'dominions.stat_total_food_production' => DB::raw('dominions.stat_total_food_production + dominion_tick.resource_food_production'),
                        #'dominions.stat_total_food_production' => 0,
                        'dominions.stat_total_lumber_production' => DB::raw('dominions.stat_total_lumber_production + dominion_tick.resource_lumber_production'),
                        'dominions.stat_total_mana_production' => DB::raw('dominions.stat_total_mana_production + dominion_tick.resource_mana_production'),
                        'dominions.stat_total_wild_yeti_production' => DB::raw('dominions.stat_total_wild_yeti_production + dominion_tick.resource_wild_yeti_production'),
                        'dominions.stat_total_ore_production' => DB::raw('dominions.stat_total_ore_production + dominion_tick.resource_ore'),
                        'dominions.stat_total_gem_production' => DB::raw('dominions.stat_total_gem_production + dominion_tick.resource_gems'),
                        'dominions.stat_total_tech_production' => DB::raw('dominions.stat_total_tech_production + dominion_tick.resource_tech'),
                        'dominions.stat_total_boat_production' => DB::raw('dominions.stat_total_boat_production + dominion_tick.resource_boats'),

                        'dominions.protection_ticks' => DB::raw('dominions.protection_ticks + dominion_tick.protection_ticks'),

                        'dominions.last_tick_at' => DB::raw('now()')
                    ]);

                // Update spells
                  DB::table('active_spells')
                      ->join('dominions', 'active_spells.dominion_id', '=', 'dominions.id')
                      ->where('dominions.round_id', $round->id)
                      ->update([
                          'duration' => DB::raw('`duration` - 1'),
                          'active_spells.updated_at' => $this->now,
                      ]);

                // Update queues
                  DB::table('dominion_queue')
                      ->join('dominions', 'dominion_queue.dominion_id', '=', 'dominions.id')
                      ->where('dominions.round_id', $round->id)
                      ->update([
                          'hours' => DB::raw('`hours` - 1'),
                          'dominion_queue.updated_at' => $this->now,
                      ]);



                // Update queues

            }, 10);

            Log::info(sprintf(
                'Ticked %s dominions in %s ms in %s',
                number_format($round->activeDominions->count()),
                number_format($this->now->diffInMilliseconds(now())),
                $round->name
            ));

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

            foreach ($dominions as $dominion)
            {
                if(!empty($dominion->tick->pestilence_units))
                {
                  $caster = Dominion::findorfail($dominion->tick->pestilence_units['caster_dominion_id']);
                  if ($caster)
                  {
                      echo print_r($dominion->tick->pestilence_units);
                      $this->queueService->queueResources('training', $caster, ['military_unit1' => $dominion->tick->pestilence_units['units']['military_unit1']], 12);
                  }
                }

                  DB::transaction(function () use ($dominion) {
                      if (!empty($dominion->tick->starvation_casualties)) {
                          $this->notificationService->queueNotification(
                              'starvation_occurred',
                              $dominion->tick->starvation_casualties
                          );
                      }

                      $this->cleanupActiveSpells($dominion);
                      $this->cleanupQueues($dominion);

                      $this->notificationService->sendNotifications($dominion, 'hourly_dominion');



                        $this->precalculateTick($dominion, true);

                  }, 5);

            }

            Log::info(sprintf(
                'Cleaned up queues, sent notifications, and precalculated %s dominions in %s ms in %s',
                number_format($round->activeDominions->count()),
                number_format($this->now->diffInMilliseconds(now())),
                $round->name
            ));

            $this->now = now();
        }

        // Update rankings
        if (($this->now->hour % 6) === 0) {
            foreach ($activeRounds as $round) {
                $this->updateDailyRankings($round->dominions);

                Log::info(sprintf(
                    'Updated rankings in %s ms in %s',
                    number_format($this->now->diffInMilliseconds(now())),
                    $round->name
                ));

                $this->now = now();
            }
        }
    }

    /**
     * Does a daily tick on all active dominions and rounds.
     *
     * @throws Exception|Throwable
     */
    public function tickDaily()
    {
        Log::debug('Daily tick started');

        DB::transaction(function () {
            foreach (Round::with('dominions')->active()->get() as $round) {
                // Ignore the first hour 0 of the round
                if ($this->now->diffInHours($round->start_date) === 0) {
                    continue;
                }

                // toBase required to prevent ambiguous updated_at column in query
                $round->dominions()->toBase()->update([
                    'daily_platinum' => false,
                    'daily_land' => false,
                ], [
                    'event' => 'tick',
                ]);
            }
        });

        Log::info('Daily tick finished');
    }

    protected function cleanupActiveSpells(Dominion $dominion)
    {
        $finished = DB::table('active_spells')
            ->where('dominion_id', $dominion->id)
            ->where('duration', '<=', 0)
            ->get();

        $beneficialSpells = [];
        $harmfulSpells = [];

        foreach ($finished as $row) {
            if ($row->cast_by_dominion_id == $dominion->id) {
                $beneficialSpells[] = $row->spell;
            } else {
                $harmfulSpells[] = $row->spell;
            }
        }

        if (!empty($beneficialSpells)) {
            $this->notificationService->queueNotification('beneficial_magic_dissipated', $beneficialSpells);
        }

        if (!empty($harmfulSpells)) {
            $this->notificationService->queueNotification('harmful_magic_dissipated', $harmfulSpells);
        }

        DB::table('active_spells')
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

        foreach ($finished->groupBy('source') as $source => $group) {
            $resources = [];
            foreach ($group as $row) {
                $resources[$row->resource] = $row->amount;
            }

            if ($source === 'invasion') {
                $notificationType = 'returning_completed';
            } else {
                $notificationType = "{$source}_completed";
            }

            $this->notificationService->queueNotification($notificationType, $resources);
        }

        // Cleanup
        DB::table('dominion_queue')
            ->where('dominion_id', $dominion->id)
            ->where('hours', '<=', 0)
            ->delete();
    }

    public function precalculateTick(Dominion $dominion, ?bool $saveHistory = false): void
    {

        /** @var Tick $tick */
        $tick = Tick::firstOrCreate(
            ['dominion_id' => $dominion->id]
        );

        if ($saveHistory) {
            // Save a dominion history record
            $dominionHistoryService = app(HistoryService::class);

            $changes = array_filter($tick->getAttributes(), static function ($value, $key) {
                return (
                    !in_array($key, [
                        'id',
                        'dominion_id',
                        'created_at',
                    ], true) &&
                    ($value != 0) // todo: strict type checking?
                );
            }, ARRAY_FILTER_USE_BOTH);

            $dominionHistoryService->record($dominion, $changes, HistoryService::EVENT_TICK);
        }

        // Reset tick values
        foreach ($tick->getAttributes() as $attr => $value)
        {
            if (!in_array($attr, ['id', 'dominion_id', 'updated_at', 'starvation_casualties', 'pestilence_units'], true))
            {
                  $tick->{$attr} = 0;
            }
            elseif (in_array($attr, ['starvation_casualties', 'pestilence_units'], true))
            {
                $tick->{$attr} = [];
            }/*
            elseif ($attr === 'starvation_casualties' or $attr === 'pestilence_units')
            {
                $tick->{$attr} = [];
            }*/
          }

        // Hacky refresh for dominion
        $dominion->refresh();
        $this->spellCalculator->getActiveSpells($dominion, true);

        // Queues
        $incomingQueue = DB::table('dominion_queue')
            ->where('dominion_id', $dominion->id)
            ->where('hours', '=', 1)
            ->get();

        # NPC Barbarian: invasion
        if($dominion->race->alignment === 'npc')
        {
          $invade = false;
          // Are we invading?

          // Make sure all units1 and unit4 are at home.
          if($dominion->military_unit1 > 0 and
             $dominion->military_unit4 > 0 and
             $this->queueService->getInvasionQueueTotalByResource($dominion, 'military_unit1') == 0 and
             $this->queueService->getInvasionQueueTotalByResource($dominion, 'military_unit4') == 0
             )
          {
            $currentDay = $dominion->round->start_date->subDays(1)->diffInDays(now());
            $chanceOneIn = 32 - (14 - min($currentDay, 14));
            if(rand(1,$chanceOneIn) == 1)
            {
              $invade = true;
            }
          }

          if($invade)
          {
            # Grow by 4-12% (random).
            $landGainRatio = max(400,rand(300,1200))/10000;

            # Calculate the amount of acres to grow.
            $totalLandToGain = $this->landCalculator->getTotalLand($dominion) * $landGainRatio;

            # Split the land gained evenly across all 6 land types.
            $landGained['land_plain'] = intval($totalLandToGain/6);
            $landGained['land_mountain'] = intval($totalLandToGain/6);
            $landGained['land_forest'] = intval($totalLandToGain/6);
            $landGained['land_swamp'] = intval($totalLandToGain/6);
            $landGained['land_hill'] = intval($totalLandToGain/6);
            $landGained['land_water'] = intval($totalLandToGain/6);

            # Send out 80-100% of all units. Rand over 100 but capped at 100
            # to make it more likely 100% are sent.
            $sentRatio = 1 - $landGainRatio;

            # Casualties between 8.5% and 12% (random).
            $casualtiesRatio = rand(85,120)/1000;

            # Calculate how many Unit1 and Unit4 are sent.
            $unitsSent['military_unit1'] = $dominion->military_unit1 * $sentRatio;
            $unitsSent['military_unit4'] = $dominion->military_unit4 * $sentRatio;

            # Remove the sent units from the dominion.
            $dominion->military_unit1 -= $unitsSent['military_unit1'];
            $dominion->military_unit4 -= $unitsSent['military_unit4'];

            # Calculate losses by applying casualties ratio to units sent.
            $unitsLost['military_unit1'] = $unitsSent['military_unit1'] * $casualtiesRatio;
            $unitsLost['military_unit4'] = $unitsSent['military_unit4'] * $casualtiesRatio;

            # Calculate amount of returning units.
            $unitsReturning['military_unit1'] = max($unitsSent['military_unit1'] - $unitsLost['military_unit1'],0);
            $unitsReturning['military_unit4'] = max($unitsSent['military_unit4'] - $unitsLost['military_unit4'],0);

            # Queue the returning units.
            foreach($unitsReturning as $unit => $amountReturning)
            {
               $dominion->{$unit} - $unitsSent[$unit];
               $this->queueService->queueResources(
                   'invasion',
                   $dominion,
                   [$unit => $amountReturning],
                   12
               );
            }

           # Queue the incoming land.
           foreach($landGained as $type => $amount)
           {
             $data = [$type => $amount];
             $this->queueService->queueResources(
                 'invasion',
                 $dominion,
                 $data
             );

             $dominion->save(['event' => HistoryService::EVENT_ACTION_INVADE]);

           }
         }
        }

        foreach ($incomingQueue as $row)
        {
            $tick->{$row->resource} += $row->amount;
            // Temporarily add next hour's resources for accurate calculations
            $dominion->{$row->resource} += $row->amount;
        }

        # NPC Barbarian: training
        if($dominion->race->alignment === 'npc')
        {
          /*
           Every tick, NPCs:
           1) Train until they reach the DPA requirement
           2) Train until they reach the OPA requirement
           3) Have a chance to quasi-invade.
              Invade = send out between 80% and 100% of the OP and queue land.
           */

           // Calculate DPA required
           $constant = 20;
           #$day = $this->now->diffInDays($dominion->round->start_date);
           $hours = now()->startOfHour()->diffInHours(Carbon::parse($dominion->round->start_date)->startOfHour()); # Borrowed from Void OP from MilitaryCalculator

           # Linear hourly
           $dpa = $constant + ($hours * 0.35 * 1.02); # Down from 0.50.
           $dpa *= ($dominion->npc_modifier / 1000);
           $dpa = intval($dpa);
           $opa = intval($dpa * 0.75);

           $dpRequired = $this->landCalculator->getTotalLand($dominion) * $dpa;
           $opRequired = $this->landCalculator->getTotalLand($dominion) * $opa;

           // Determine current DP and OP
           # Unit 1: 3 OP, 0 DP
           # Unit 2: 3 DP, 0 OP
           # Unit 3: 5 DP, 0 OP
           # Unit 4: 5 OP, 2 DP (turtle)

           $dpUnit1 = 0;
           $dpUnit2 = 3;
           $dpUnit3 = 5;
           $dpUnit4 = 0; # Has turtle but ignored here

           $opUnit1 = 3;
           $opUnit2 = 0;
           $opUnit3 = 0;
           $opUnit4 = 5;

           $dpTrained = $this->militaryCalculator->getTotalUnitsForSlot($dominion, 2) * $dpUnit2;
           $dpTrained += $this->militaryCalculator->getTotalUnitsForSlot($dominion, 3) * $dpUnit3;

           $dpInTraining = $this->queueService->getTrainingQueueTotalByResource($dominion, 'military_unit2') * $dpUnit2;
           $dpInTraining += $this->queueService->getTrainingQueueTotalByResource($dominion, 'military_unit3') * $dpUnit3;

           $dpPaid = $dpTrained + $dpInTraining;

           $opTrained = $this->militaryCalculator->getTotalUnitsForSlot($dominion, 1) * $opUnit1;
           $opTrained += $this->militaryCalculator->getTotalUnitsForSlot($dominion, 4) * $opUnit4;

           $opInTraining = $this->queueService->getTrainingQueueTotalByResource($dominion, 'military_unit1') * $opUnit1;
           $opInTraining += $this->queueService->getTrainingQueueTotalByResource($dominion, 'military_unit4') * $opUnit4;

           $opReturning = $this->queueService->getInvasionQueueTotalByResource($dominion, 'military_unit1') * $opUnit1;
           $opReturning += $this->queueService->getInvasionQueueTotalByResource($dominion, 'military_unit4') * $opUnit4;

           $opPaid = $opTrained + $opInTraining + $opReturning;

           // Determine what (if any) training is required
           $dpToTrain = max(0, $dpRequired - $dpPaid);
           $opToTrain = max(0, $opRequired - $opPaid);

           # Randomly train between 10% and 30% of units as specs.
           $specsRatio = rand(10,30)/100;
           $elitesRatio = 1 - $specsRatio;

           $units = [
             'military_unit1' => intval(($opToTrain * $specsRatio) / $opUnit1),
             'military_unit2' => intval(($dpToTrain * $specsRatio) / $dpUnit2),
             'military_unit3' => intval(($dpToTrain * $elitesRatio) / $dpUnit3),
             'military_unit4' => intval(($opToTrain * $elitesRatio) / $opUnit4),
           ];

           // Train the units
           if(rand(1,2) == 1)
           {
             foreach($units as $unit => $amountToTrain)
             {
                $data = [$unit => $amountToTrain];

                $hours = 12;

                $this->queueService->queueResources('training', $dominion, $data, $hours);
             }
           }

        }

        $tick->protection_ticks = 0;
        // Tick
        if($dominion->protection_ticks > 0)
        {
          $tick->protection_ticks += -1;
        }

        // Population
        $drafteesGrowthRate = $this->populationCalculator->getPopulationDrafteeGrowth($dominion);
        $populationPeasantGrowth = $this->populationCalculator->getPopulationPeasantGrowth($dominion) - $this->populationCalculator->getPeasantsSacrified($dominion);

        if ($this->spellCalculator->isSpellActive($dominion, 'pestilence'))
        {
            $caster = $this->spellCalculator->getCaster($dominion, 'pestilence');

            $amountToDie = intval($dominion->peasants * 0.01);
            $amountToDie *= $this->rangeCalculator->getDominionRange($caster, $dominion) / 100;
            $amountToDie *= (1 - $dominion->race->getPerkMultiplier('reduced_conversions'));

            $tick->pestilence_units = ['caster_dominion_id' => $caster->id, 'units' => ['military_unit1' => $amountToDie]];
            $populationPeasantGrowth -= $amountToDie;
        }

        $tick->peasants = $populationPeasantGrowth;
        $tick->military_draftees = $drafteesGrowthRate;

        // Resources

        # Max storage

        $maxStorageTicks = 24 * 4; # Store at most 24 hours (96 ticks) per building.
        $acres = $this->landCalculator->getTotalLand($dominion);
        $maxPlatinumPerAcre = 5000;

        $maxStorage = [];
        $maxStorage['platinum'] = $acres * $maxPlatinumPerAcre;
        #$maxStorage['food'] = $maxStorageTicks * (($dominion->building_farm * 80) + ($dominion->building_dock * 35) + $dominion->getUnitPerkProductionBonus('food_production'));
        $maxStorage['lumber'] = max($acres * 100, $maxStorageTicks * ($dominion->building_lumberyard * 50 + $dominion->getUnitPerkProductionBonus('lumber_production')));
        $maxStorage['ore'] = max($acres * 100, $maxStorageTicks * ($dominion->building_ore_mine * 60 + $dominion->getUnitPerkProductionBonus('ore_production')));
        $maxStorage['gems'] = max($acres * 50, $maxStorageTicks * ($dominion->building_diamond_mine * 15 + $dominion->getUnitPerkProductionBonus('gem_production')));
        if($dominion->race->name == 'Myconid')
        {
          $maxStorage['gems'] += $dominion->getUnitPerkProductionBonus('tech_production') * 10;
        }

        $tick->resource_platinum += min($this->productionCalculator->getPlatinumProduction($dominion), max(0, ($maxStorage['platinum'] - $dominion->resource_platinum)));

        $tick->resource_lumber_production += $this->productionCalculator->getLumberProduction($dominion);
        #$tick->resource_lumber += $this->productionCalculator->getLumberNetChange($dominion);
        $tick->resource_lumber += min($this->productionCalculator->getLumberNetChange($dominion), max(0, ($maxStorage['lumber'] - $dominion->resource_lumber)));

        $tick->resource_mana_production += $this->productionCalculator->getManaProduction($dominion);
        $tick->resource_mana += $this->productionCalculator->getManaNetChange($dominion);

        #$tick->resource_ore += $this->productionCalculator->getOreProduction($dominion);
        $tick->resource_ore += min($this->productionCalculator->getOreProduction($dominion), max(0, ($maxStorage['ore'] - $dominion->resource_ore)));

        #$tick->resource_gems += $this->productionCalculator->getGemProduction($dominion);
        $tick->resource_gems += min($this->productionCalculator->getGemProduction($dominion), max(0, ($maxStorage['gems'] - $dominion->resource_gems)));

        $tick->resource_tech += $this->productionCalculator->getTechProduction($dominion);
        $tick->resource_boats += $this->productionCalculator->getBoatProduction($dominion);

        # ODA: wild yeti production
        $tick->resource_wild_yeti_production += $this->productionCalculator->getWildYetiProduction($dominion);
        $tick->resource_wild_yeti += $this->productionCalculator->getWildYetiNetChange($dominion);

        $tick->resource_food_production += $this->productionCalculator->getFoodProduction($dominion);

        // Check for starvation before adjusting food
        $foodNetChange = $this->productionCalculator->getFoodNetChange($dominion);

        // Starvation casualties
        if (($dominion->resource_food + $foodNetChange) < 0) {
            $isStarving = true;
            $casualties = $this->casualtiesCalculator->getStarvationCasualtiesByUnitType(
                $dominion,
                ($dominion->resource_food + $foodNetChange)
            );

            $tick->starvation_casualties = $casualties;

            foreach ($casualties as $unitType => $unitCasualties) {
                $tick->{$unitType} -= $unitCasualties;
            }

            // Decrement to zero
            $tick->resource_food = -$dominion->resource_food;
        } else {
            // Food production
            $isStarving = false;
            $tick->resource_food += $foodNetChange;
        }

        // Morale
        if ($isStarving) {
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
        } else {
            if ($dominion->morale < 35)
            {
              $tick->morale = 7;
            }
            elseif ($dominion->morale < 70)
            {
                $tick->morale = 6;
            }
            elseif ($dominion->morale < 100)
            {
                $tick->morale = min(3, 100 - $dominion->morale);
            }
            elseif($dominion->morale > 100)
            {
              $tick->morale -= min(2, $dominion->morale - 100);
            }
        }

        // Spy Strength
        if ($dominion->spy_strength < 100) {
            $spyStrengthAdded = 4;
            $spyStrengthAdded += $dominion->getTechPerkValue('spy_strength_recovery');

            $tick->spy_strength = min($spyStrengthAdded, 100 - $dominion->spy_strength);
        }

        // Wizard Strength
        if ($dominion->wizard_strength < 100) {
            $wizardStrengthAdded = 4;

            $wizardStrengthPerWizardGuild = 0.1;
            $wizardStrengthPerWizardGuildMax = 2;

            $wizardStrengthAdded += min(
                (($dominion->building_wizard_guild / $this->landCalculator->getTotalLand($dominion)) * (100 * $wizardStrengthPerWizardGuild)),
                $wizardStrengthPerWizardGuildMax
            );

            $wizardStrengthAdded += $dominion->getTechPerkValue('wizard_strength_recovery');

            $tick->wizard_strength = min($wizardStrengthAdded, 100 - $dominion->wizard_strength);
        }

        // Mycelia: Spore training and Land generation

        $slot = 1;
        $acresToExplore = 0;
        #$tick->generated_unit1 = 0;
        #$tick->generated_unit2 = 0;
        #$tick->generated_unit3 = 0;
        #$tick->generated_unit4 = 0;

        $tick->generated_land = 0;

        while($slot <= 4)
        {

          if($dominion->race->getUnitPerkValueForUnitSlot($slot, 'land_per_tick'))
          {
            $acresToExplore += intval($dominion->{"military_unit".$slot} * $dominion->race->getUnitPerkValueForUnitSlot($slot, 'land_per_tick'));
            #$landPerTick = $dominion->{"military_unit".$slot} * $dominion->race->getUnitPerkValueForUnitSlot($slot, 'land_per_tick');
            #$acresToExplore += intval($landPerTick) + (rand(0,1) < fmod($landPerTick, 1) ? 1 : 0);
          }

          if($dominion->race->getUnitPerkValueForUnitSlot($slot, 'unit_production'))
          {
            $unitGeneration = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'unit_production');
            $unitsToGenerate[$unitGeneration[0]] = intval($dominion->{"military_unit".$slot} * $unitGeneration[1]);
          }

          $slot++;
        }

        if($acresToExplore > 0)
        {
          $hours = 12;
          $homeLandType = 'land_' . $dominion->race->home_land_type;
          $data = array($homeLandType => $acresToExplore);

          #$this->queueService->queueResources('exploration', $dominion, $data, $hours);
          $tick->generated_land = $acresToExplore;

          unset($data);
        }

        if(isset($unitsToGenerate))
        {
          if(isset($unitsToGenerate[1]))
          {
            $tick->generated_unit1 = $unitsToGenerate[1];
          }
          if(isset($unitsToGenerate[2]))
          {
            $tick->generated_unit2 = $unitsToGenerate[2];
          }
          if(isset($unitsToGenerate[3]))
          {
            $tick->generated_unit3 = $unitsToGenerate[3];
          }
          if(isset($unitsToGenerate[4]))
          {
            $tick->generated_unit4 = $unitsToGenerate[4];
          }

          unset($unitsToGenerate);
        }

        foreach ($incomingQueue as $row) {
            // Reset current resources in case object is saved later
            $dominion->{$row->resource} -= $row->amount;
        }

        $tick->save();
    }

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

    # SINGLE DOMINION TICKS, MANUAL TICK
    /**
     * Does an hourly tick on all active dominions.
     *
     * @throws Exception|Throwable
     */
    public function tickManually(Dominion $dominion)
    {

        Log::debug(sprintf(
            'Manual tick started for %s.',
            $dominion->name
        ));

        $this->precalculateTick($dominion, true);

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
                        'dominions.peasants' => DB::raw('dominions.peasants + dominion_tick.peasants'),
                        'dominions.peasants_last_hour' => DB::raw('dominion_tick.peasants'),
                        'dominions.morale' => DB::raw('dominions.morale + dominion_tick.morale'),
                        'dominions.spy_strength' => DB::raw('dominions.spy_strength + dominion_tick.spy_strength'),
                        'dominions.wizard_strength' => DB::raw('dominions.wizard_strength + dominion_tick.wizard_strength'),

                        'dominions.resource_platinum' => DB::raw('dominions.resource_platinum + dominion_tick.resource_platinum'),
                        'dominions.resource_food' => DB::raw('dominions.resource_food + dominion_tick.resource_food'),
                        'dominions.resource_lumber' => DB::raw('dominions.resource_lumber + dominion_tick.resource_lumber'),
                        'dominions.resource_mana' => DB::raw('dominions.resource_mana + dominion_tick.resource_mana'),
                        'dominions.resource_ore' => DB::raw('dominions.resource_ore + dominion_tick.resource_ore'),
                        'dominions.resource_gems' => DB::raw('dominions.resource_gems + dominion_tick.resource_gems'),
                        'dominions.resource_tech' => DB::raw('dominions.resource_tech + dominion_tick.resource_tech'),
                        'dominions.resource_boats' => DB::raw('dominions.resource_boats + dominion_tick.resource_boats'),

                        # ODA resources
                        'dominions.resource_wild_yeti' => DB::raw('dominions.resource_wild_yeti + dominion_tick.resource_wild_yeti'),
                        'dominions.resource_champion' => DB::raw('dominions.resource_champion + dominion_tick.resource_champion'),
                        'dominions.resource_soul' => DB::raw('dominions.resource_soul + dominion_tick.resource_soul'),

                        'dominions.military_draftees' => DB::raw('dominions.military_draftees + dominion_tick.military_draftees'),
                        'dominions.military_unit1' => DB::raw('dominions.military_unit1 + dominion_tick.military_unit1 + dominion_tick.generated_unit1'),
                        'dominions.military_unit2' => DB::raw('dominions.military_unit2 + dominion_tick.military_unit2 + dominion_tick.generated_unit2'),
                        'dominions.military_unit3' => DB::raw('dominions.military_unit3 + dominion_tick.military_unit3 + dominion_tick.generated_unit3'),
                        'dominions.military_unit4' => DB::raw('dominions.military_unit4 + dominion_tick.military_unit4 + dominion_tick.generated_unit4'),
                        'dominions.military_spies' => DB::raw('dominions.military_spies + dominion_tick.military_spies'),
                        'dominions.military_wizards' => DB::raw('dominions.military_wizards + dominion_tick.military_wizards'),
                        'dominions.military_archmages' => DB::raw('dominions.military_archmages + dominion_tick.military_archmages'),

                        'dominions.land_plain' => DB::raw('dominions.land_plain + dominion_tick.land_plain'),
                        'dominions.land_mountain' => DB::raw('dominions.land_mountain + dominion_tick.land_mountain'),
                        'dominions.land_swamp' => DB::raw('dominions.land_swamp + dominion_tick.land_swamp'),
                        'dominions.land_cavern' => DB::raw('dominions.land_cavern + dominion_tick.land_cavern'),
                        'dominions.land_forest' => DB::raw('dominions.land_forest + dominion_tick.land_forest + dominion_tick.generated_land'),
                        'dominions.land_hill' => DB::raw('dominions.land_hill + dominion_tick.land_hill'),
                        'dominions.land_water' => DB::raw('dominions.land_water + dominion_tick.land_water'),

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
                        'dominions.building_diamond_mine' => DB::raw('dominions.building_diamond_mine + dominion_tick.building_diamond_mine'),
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

                        'dominions.stat_total_platinum_production' => DB::raw('dominions.stat_total_platinum_production + dominion_tick.resource_platinum'),
                        #'dominions.stat_total_platinum_production' => 0,
                        'dominions.stat_total_food_production' => DB::raw('dominions.stat_total_food_production + dominion_tick.resource_food_production'),
                        #'dominions.stat_total_food_production' => 0,
                        'dominions.stat_total_lumber_production' => DB::raw('dominions.stat_total_lumber_production + dominion_tick.resource_lumber_production'),
                        'dominions.stat_total_mana_production' => DB::raw('dominions.stat_total_mana_production + dominion_tick.resource_mana_production'),
                        'dominions.stat_total_wild_yeti_production' => DB::raw('dominions.stat_total_wild_yeti_production + dominion_tick.resource_wild_yeti_production'),
                        'dominions.stat_total_ore_production' => DB::raw('dominions.stat_total_ore_production + dominion_tick.resource_ore'),
                        'dominions.stat_total_gem_production' => DB::raw('dominions.stat_total_gem_production + dominion_tick.resource_gems'),
                        'dominions.stat_total_tech_production' => DB::raw('dominions.stat_total_tech_production + dominion_tick.resource_tech'),
                        'dominions.stat_total_boat_production' => DB::raw('dominions.stat_total_boat_production + dominion_tick.resource_boats'),

                        'dominions.protection_ticks' => DB::raw('dominions.protection_ticks + dominion_tick.protection_ticks'),

                        'dominions.last_tick_at' => DB::raw('now()')
                    ]);

                // Update spells
                  DB::table('active_spells')
                      ->join('dominions', 'active_spells.dominion_id', '=', 'dominions.id')
                      ->where('dominions.id', $dominion->id)
                      ->update([
                          'duration' => DB::raw('`duration` - 1'),
                          'active_spells.updated_at' => $this->now,
                      ]);

                // Update queues
                  DB::table('dominion_queue')
                      ->join('dominions', 'dominion_queue.dominion_id', '=', 'dominions.id')
                      ->where('dominions.id', $dominion->id)
                      ->update([
                          'hours' => DB::raw('`hours` - 1'),
                          'dominion_queue.updated_at' => $this->now,
                      ]);



                // Update queues

            }, 10);

            Log::info(sprintf(
                'Ticked dominion %s in %s ms.',
                $dominion->name,
                number_format($this->now->diffInMilliseconds(now()))
            ));

            $this->now = now();

            # Starvation
            DB::transaction(function () use ($dominion) {
                if (!empty($dominion->tick->starvation_casualties)) {
                    $this->notificationService->queueNotification(
                        'starvation_occurred',
                        $dominion->tick->starvation_casualties
                    );
                }

            # Clean up
            $this->cleanupActiveSpells($dominion);
            $this->cleanupQueues($dominion);

            $this->notificationService->sendNotifications($dominion, 'hourly_dominion');

            $this->precalculateTick($dominion, true);

            }, 5);


            Log::info(sprintf(
                'Cleaned up queues, sent notifications, and precalculated dominion %s in %s ms.',
                $dominion->name,
                number_format($this->now->diffInMilliseconds(now()))
            ));

            $this->now = now();
        }

}
