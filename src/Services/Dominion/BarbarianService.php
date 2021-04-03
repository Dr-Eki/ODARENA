<?php

namespace OpenDominion\Services\Dominion;

Use DB;
use Log;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Models\User;
use OpenDominion\Models\Race;
use OpenDominion\Models\Title;

use OpenDominion\Factories\DominionFactory;

use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Http\Requests\Dominion\Actions\InvadeActionRequest;
use OpenDominion\Services\Dominion\Actions\InvadeActionService;

class BarbarianService
{

    protected const DPA_CONSTANT = 25;
    protected const DPA_PER_HOUR = 0.40;
    protected const DPA_PER_TIMES_INVADED = 0.005;

    protected const OPA_MULTIPLIER = 1;

    # Train % of new units as specs. /1000
    protected const SPECS_RATIO_MIN = 50;
    protected const SPECS_RATIO_MAX = 500;

    # Chance to hit
    protected const CHANCE_TO_HIT_CONSTANT = 14;

    # Gain % of land between these two values when hitting. /1000
    # Current formula returns:
    #  40% - 1.5% raw 3.0% total
    #  60% - 1.7% raw 3.4% total
    #  65% - 2.3% raw 4.6% total
    #  75% - 3.6% raw 7.2% total
    #  85% - 4.6% raw 9.2% total
    #  95% - 5.5% raw 11.0% total
    # 100% - 6.0% raw 12.0% total
    # 120% - 8.0% raw 16.0% total
    # 133% - 9.20% raw 18.4% total

    protected const LAND_GAIN_MIN = 46; # 65% hit
    protected const LAND_GAIN_MAX = 110; # 95% hit

    # Send between these two values when hitting. /1000
    protected const SENT_RATIO_MIN = 800;
    protected const SENT_RATIO_MAX = 1000;

    # Lose % of units between these two values when hitting. /1000
    protected const CASUALTIES_MIN = 50;
    protected const CASUALTIES_MAX = 100;

    # Train between these two values % of required units per tick. /1000
    // Disabled, always training 100%.
    protected const UNITS_TRAINED_MIN = 800;
    protected const UNITS_TRAINED_MAX = 1200;

    # Training time in ticks
    protected const UNITS_TRAINING_TICKS = 6;
    protected const CONSTRUCTION_TIME = 12;

    # Unit powers
    protected const UNIT1_OP = 3;
    protected const UNIT2_DP = 3;
    protected const UNIT3_DP = 5;
    protected const UNIT4_OP = 5;

    # Chance each tick for new Barbarian to spawn
    protected const ONE_IN_CHANCE_TO_SPAWN = 84;

    /** @var MilitaryCalculator */
    protected $militaryCalculator;

    /** @var LandCalculator */
    protected $landCalculator;

    /** @var QueueService */
    protected $queueService;

    /**
     * BarbarianService constructor.
     */
    public function __construct()
    {
        #$this->now = now();
        $this->landCalculator = app(LandCalculator::class);
        $this->queueService = app(QueueService::class);
        $this->militaryCalculator = app(MilitaryCalculator::class);
        $this->landCalculator = app(LandCalculator::class);
        $this->rangeCalculator = app(RangeCalculator::class);
        $this->dominionFactory = app(DominionFactory::class);
    }

    public function getSettings(): array
    {
        $settings = [
            'DPA_CONSTANT' => static::DPA_CONSTANT,
            'DPA_PER_HOUR' => static::DPA_PER_HOUR,
            'DPA_PER_TIMES_INVADED' => static::DPA_PER_TIMES_INVADED,
            'OPA_MULTIPLIER' => static::OPA_MULTIPLIER,
            'SPECS_RATIO_MIN' => static::SPECS_RATIO_MIN,
            'SPECS_RATIO_MAX' => static::SPECS_RATIO_MAX,
            'CHANCE_TO_HIT_CONSTANT' => static::CHANCE_TO_HIT_CONSTANT,
            'LAND_GAIN_MIN' => static::LAND_GAIN_MIN,
            'LAND_GAIN_MAX' => static::LAND_GAIN_MAX,
            'SENT_RATIO_MIN' => static::SENT_RATIO_MIN,
            'SENT_RATIO_MAX' => static::SENT_RATIO_MAX,
            'CASUALTIES_MIN' => static::CASUALTIES_MIN,
            'CASUALTIES_MAX' => static::CASUALTIES_MAX,
            'UNITS_TRAINED_MIN' => static::UNITS_TRAINED_MIN,
            'UNITS_TRAINED_MAX' => static::UNITS_TRAINED_MAX,
            'UNITS_TRAINING_TICKS' => static::UNITS_TRAINING_TICKS,
            'CONSTRUCTION_TIME' => static::CONSTRUCTION_TIME,
            'UNIT1_OP' => static::UNIT1_OP,
            'UNIT2_DP' => static::UNIT2_DP,
            'UNIT3_DP' => static::UNIT3_DP,
            'UNIT4_OP' => static::UNIT4_OP,
            'ONE_IN_CHANCE_TO_SPAWN' => static::ONE_IN_CHANCE_TO_SPAWN,
        ];

        return $settings;
    }

    public function getSetting(string $setting): string
    {
        $value = null;
        $settings = $this->getSettings();
        if(isset($settings[$setting]))
        {
            $value = $settings[$setting];
        }

        return $value;
    }

    private function getDpaTarget(Dominion $dominion): int
    {
        $calculateDate = max($dominion->round->start_date, $dominion->created_at);

        $hoursIntoTheRound = now()->startOfHour()->diffInHours(Carbon::parse($calculateDate)->startOfHour());
        $dpa = static::DPA_CONSTANT + ($hoursIntoTheRound * (static::DPA_PER_HOUR + ($dominion->stat_defending_failures * static::DPA_PER_TIMES_INVADED)));
        return $dpa *= ($dominion->npc_modifier / 1000);
    }

    private function getOpaTarget(Dominion $dominion): int
    {
        return $this->getDpaTarget($dominion) * static::OPA_MULTIPLIER;
    }

    # Includes units out on attack.
    private function getDpCurrent(Dominion $dominion): int
    {
        $dp = $this->militaryCalculator->getTotalUnitsForSlot($dominion, 2) * static::UNIT2_DP;
        $dp += $this->militaryCalculator->getTotalUnitsForSlot($dominion, 3) * static::UNIT3_DP;

        return $dp;
    }

    # Includes units at home and out on attack.
    private function getOpCurrent(Dominion $dominion): int
    {
        $op = $this->militaryCalculator->getTotalUnitsForSlot($dominion, 1) * static::UNIT1_OP;
        $op += $this->militaryCalculator->getTotalUnitsForSlot($dominion, 4) * static::UNIT4_OP;

        return $op;
    }

    # Includes units at home and out on attack.
    private function getOpAtHome(Dominion $dominion): int
    {
        $op = $dominion->military_unit1 * static::UNIT1_OP;
        $op += $dominion->military_unit4 * static::UNIT4_OP;

        return $op;
    }

    private function getDpPaid(Dominion $dominion): int
    {
        $dp = $this->getDpCurrent($dominion);
        $dp += $this->queueService->getTrainingQueueTotalByResource($dominion, 'military_unit2') * static::UNIT2_DP;
        $dp += $this->queueService->getTrainingQueueTotalByResource($dominion, 'military_unit3') * static::UNIT3_DP;

        return $dp;
    }

    private function getOpPaid(Dominion $dominion): int
    {
        $op = $this->getOpCurrent($dominion);
        $op += $this->queueService->getTrainingQueueTotalByResource($dominion, 'military_unit1') * static::UNIT1_OP;
        $op += $this->queueService->getTrainingQueueTotalByResource($dominion, 'military_unit4') * static::UNIT4_OP;

        return $op;
    }

    private function getDpaCurrent(Dominion $dominion): int
    {
        return $this->getDpCurrent($dominion) / $this->landCalculator->getTotalLand($dominion);
    }

    private function getOpaCurrent(Dominion $dominion): int
    {
        return $this->getOpCurrent($dominion) / $this->landCalculator->getTotalLand($dominion);
    }


    private function getDpaPaid(Dominion $dominion): int
    {
        return $this->getDpPaid($dominion) / $this->landCalculator->getTotalLand($dominion);
    }

    private function getOpaPaid(Dominion $dominion): int
    {
        return $this->getOpPaid($dominion) / $this->landCalculator->getTotalLand($dominion);
    }

    private function getOpaAtHome(Dominion $dominion): int
    {
        return $this->getOpAtHome($dominion) / $this->landCalculator->getTotalLand($dominion);
    }

    private function getOpaDeltaPaid(Dominion $dominion): int
    {
        return $this->getOpaTarget($dominion) - $this->getOpaPaid($dominion);
    }

    private function getDpaDeltaPaid(Dominion $dominion): int
    {
        return $this->getDpaTarget($dominion) - $this->getDpaPaid($dominion);
    }

    private function getOpaDeltaAtHome(Dominion $dominion): int
    {
        return $this->getOpaTarget($dominion) - $this->getOpaAtHome($dominion);
    }

    private function getDpaDeltaCurrent(Dominion $dominion): int
    {
        return $this->getDpaTarget($dominion) - $this->getDpaCurrent($dominion);
    }

    public function handleBarbarianTraining(Dominion $dominion): void
    {
        if($dominion->race->name === 'Barbarian')
        {
            $logString = "\n[BARBARIAN]\n\t[training]\n";
            $land = $this->landCalculator->getTotalLand($dominion);

            $land += $this->queueService->getInvasionQueueTotalByResource($dominion, 'land_plain');
            $land += $this->queueService->getInvasionQueueTotalByResource($dominion, 'land_mountain');
            $land += $this->queueService->getInvasionQueueTotalByResource($dominion, 'land_swamp');
            $land += $this->queueService->getInvasionQueueTotalByResource($dominion, 'land_forest');
            $land += $this->queueService->getInvasionQueueTotalByResource($dominion, 'land_hill');
            $land += $this->queueService->getInvasionQueueTotalByResource($dominion, 'land_water');

            $units = [
              'military_unit1' => 0,
              'military_unit2' => 0,
              'military_unit3' => 0,
              'military_unit4' => 0,
            ];

            $dpaDeltaPaid = $this->getDpaDeltaPaid($dominion);
            $opaDeltaPaid = $this->getOpaDeltaPaid($dominion);

            $logString .= "\t\tName: $dominion->name\n";
            $logString .= "\t\tSize: ".number_format($land)."\n";
            $logString .= "\t\t* DPA\n";
            $logString .= "\t\t** DPA target: " . $this->getDpaTarget($dominion) ."\n";
            $logString .= "\t\t** DPA paid: " . $this->getDpaPaid($dominion) ."\n";
            $logString .= "\t\t** DPA current: " . $this->getDpaCurrent($dominion) ."\n";
            $logString .= "\t\t** DPA delta current: " . $this->getDpaDeltaCurrent($dominion) ."\n";
            $logString .= "\t\t** DPA delta paid: " . $dpaDeltaPaid ."\n";

            $logString .= "\t\t* OPA\n";
            $logString .= "\t\t** OPA target: " . $this->getOpaTarget($dominion) ."\n";
            $logString .= "\t\t** OPA paid: " . $this->getOpaPaid($dominion) ."\n";
            $logString .= "\t\t** OPA at home: " . $this->getOpaAtHome($dominion) ."\n";
            $logString .= "\t\t** OPA delta at home: " . $this->getOpaDeltaAtHome($dominion) ."\n";
            $logString .= "\t\t** OPA delta paid: " . $opaDeltaPaid ."\n";

            if($dpaDeltaPaid > 0)
            {
                $dpToTrain = $dpaDeltaPaid * $land;

                $specsRatio = rand(static::SPECS_RATIO_MIN, static::SPECS_RATIO_MAX)/1000;
                $elitesRatio = 1-$specsRatio;

                $units['military_unit2'] = ceil(($dpToTrain*$specsRatio)/static::UNIT2_DP);
                $units['military_unit3'] = ceil(($dpToTrain*$elitesRatio)/static::UNIT3_DP);
            }

            if($opaDeltaPaid > 0)
            {
                $opToTrain = $opaDeltaPaid * $land;

                $specsRatio = rand(static::SPECS_RATIO_MIN, static::SPECS_RATIO_MAX)/1000;
                $elitesRatio = 1-$specsRatio;

                $units['military_unit1'] = ceil(($opToTrain*$specsRatio)/static::UNIT1_OP);
                $units['military_unit4'] = ceil(($opToTrain*$elitesRatio)/static::UNIT4_OP);
            }

            foreach($units as $unit => $amountToTrain)
            {
                if($amountToTrain > 0)
                {
                    $data = [$unit => $amountToTrain];
                    $ticks = intval(static::UNITS_TRAINING_TICKS);
                    $this->queueService->queueResources('training', $dominion, $data, $ticks);
                }
            }

            if(isset($dpToTrain))
            {
                $logString .= "\t\t* DP to train: " . number_format($dpToTrain) . "\n";
            }
            else
            {
                $logString .= "\t\t* No need to train additional DP\n";
            }

            if(isset($opToTrain))
            {
                $logString .= "\t\t* OP to train: " . number_format($opToTrain) . "\n";
            }
            else
            {
                #$logString .= 'No need train additional OP. ';
                $logString .= "\t\t* No need to train additional OP\n";
            }

            $logString .= "\t\t* Training:\n";
            $logString .= "\t\t** Unit1: " . number_format($units['military_unit1']) ."\n";
            $logString .= "\t\t** Unit2: " . number_format($units['military_unit2']) ."\n";
            $logString .= "\t\t** Unit3: " . number_format($units['military_unit3']) ."\n";
            $logString .= "\t\t** Unit4: " . number_format($units['military_unit4']) ."\n";

            $logString .= "\t[/training]\n[/BARBARIAN]";

            #Log::Debug($logString);
        }

    }

    public function handleBarbarianInvasion(Dominion $dominion): void
    {
        $invade = false;

        if($dominion->race->name === 'Barbarian')
        {
            $logString = "\n[BARBARIAN]\n\t[invasion]\n";
            $logString .= "\t\tName: $dominion->name\n";
            $logString .= "\t\tSize: ".number_format($this->landCalculator->getTotalLand($dominion))."\n";

            # Make sure we have the expected OPA to hit, and enough DPA at home.
            if($this->getDpaDeltaCurrent($dominion) <= 0 and $this->getOpaDeltaAtHome($dominion) <= 0)
            {
                $currentDay = $dominion->round->start_date->subDays(1)->diffInDays(now());
                $chanceOneIn = static::CHANCE_TO_HIT_CONSTANT - (14 - $currentDay);
                $chanceToHit = rand(1,$chanceOneIn);


                $logString .= "\t\t* OP/DP\n";
                $logString .= "\t\t** DPA current: " . $this->getDpaCurrent($dominion) ."\n";
                $logString .= "\t\t** DP current: " . $this->getDpaCurrent($dominion) ."\n";

                $logString .= "\t\t** OPA at home: " . $this->getOpaAtHome($dominion) ."\n";
                $logString .= "\t\t** OP current: " . $this->getOpCurrent($dominion) ."\n";

                $logString .= "\t\t* Chance to hit: 1 in $chanceOneIn\n";
                $logString .= "\t\t** Outcome: $chanceToHit: ";

                if($chanceToHit === 1)
                {
                    $invade = true;
                    $logString .= "✅ Invade!\n";
                }
                else
                {
                    $logString .= "❌ No invasion\n";
                }
            }
            else
            {
                if($this->getDpaDeltaCurrent($dominion) > 0)
                {
                    $logString .= "\t\t🚫 Insufficient DP:\n";
                    $logString .= "\t\t* DPA\n";
                    $logString .= "\t\t** DPA delta current: " . $this->getDpaDeltaCurrent($dominion) ."\n";
                    $logString .= "\t\t** DPA delta paid: " . $this->getDpaDeltaPaid($dominion) ."\n";
                    $logString .= "\t\t** DPA target: " . $this->getDpaTarget($dominion) ."\n";
                    $logString .= "\t\t** DPA paid: " . $this->getDpaPaid($dominion) ."\n";
                    $logString .= "\t\t** DPA current: " . $this->getDpaCurrent($dominion) ."\n";
                }

                if($this->getOpaDeltaAtHome($dominion) > 0)
                {
                    $logString .= "\t\t🚫 Insufficient OP:\n";
                    $logString .= "\t\t* OPA\n";
                    $logString .= "\t\t** OPA delta at home: " . $this->getOpaDeltaAtHome($dominion) ."\n";
                    $logString .= "\t\t** OPA delta paid: " . $this->getOpaDeltaPaid($dominion) ."\n";
                    $logString .= "\t\t** OPA target: " . $this->getOpaTarget($dominion) ."\n";
                    $logString .= "\t\t** OPA paid: " . $this->getOpaPaid($dominion) ."\n";
                    $logString .= "\t\t** OPA at home: " . $this->getOpaAtHome($dominion) ."\n";
                }
            }

            if($invade)
            {
                $invadePlayer = false;
                # First, look for human players
                $targetsInRange = $this->rangeCalculator->getDominionsInRange($dominion);

                foreach($targetsInRange as $target)
                {
                    $landRatio = $this->rangeCalculator->getDominionRange($dominion, $target) / 100;
                    $units = [1 => $dominion->military_unit1, 4 => $dominion->military_unit4];
                    $targetDp = $this->militaryCalculator->getDefensivePower($target, $dominion, $landRatio);


                    $logString .= $dominion->name . ' is checking ' . $target->name . ': ';
                    if($this->getOpCurrent($dominion) >= $targetDp * 0.85)
                    {
                        $logString .= ' ✅ DP is within tolerance! DP: ' . number_format($targetDp) . ' vs. available OP: ' . number_format($this->getOpCurrent($dominion));
                        $invadePlayer = $target;
                        break;
                    }
                    else
                    {
                        $logString .= ' 🚫 DP is too high. DP: ' . number_format($targetDp) . ' vs. available OP: ' . number_format($this->getOpCurrent($dominion));
                    }

                }

                # Chicken out: 11/12 chance that the Barbarians won't hit.
                if(rand(1, 12) !== 1)
                {
                    $invadePlayer = false;
                }

                if($invadePlayer)
                {
                    $invasionActionService = app(InvadeActionService::class);
                    $invasionActionService->invade($dominion, $target, $units);
                }
                else
                {
                    $landGainRatio = rand(static::LAND_GAIN_MIN, static::LAND_GAIN_MAX)/1000;

                    $logString .= "\t\t* Invasion:\n";
                    $logString .= "\t\t**Land gain ratio: " . number_format($landGainRatio*100,2) . "% \n";

                    # Calculate the amount of acres to grow.
                    $totalLandToGain = intval($this->landCalculator->getTotalLand($dominion) * $landGainRatio);
                    $logString .= "\t\t**Land to gain: " . number_format($totalLandToGain). "\n";

                    # Split the land gained evenly across all 6 land types.
                    $landGained['land_plain'] = intval($totalLandToGain/6);
                    $landGained['land_mountain'] = intval($totalLandToGain/6);
                    $landGained['land_forest'] = intval($totalLandToGain/6);
                    $landGained['land_swamp'] = intval($totalLandToGain/6);
                    $landGained['land_hill'] = intval($totalLandToGain/6);
                    $landGained['land_water'] = intval($totalLandToGain/6);

                    $logString .= "\t\t**Actual land gained: " . array_sum($landGained) . "\n";

                    # Add the land gained to the $dominion.
                    $dominion->stat_total_land_conquered += $totalLandToGain;
                    $dominion->stat_attacking_success += 1;

                    $sentRatio = rand(static::SENT_RATIO_MIN, static::SENT_RATIO_MAX)/1000;

                    $casualtiesRatio = rand(static::CASUALTIES_MIN, static::CASUALTIES_MAX)/1000;

                    $logString .= "\t\t**Sent ratio: " . number_format($sentRatio*100,2). "%\n";
                    $logString .= "\t\t**Casualties ratio: " . number_format($casualtiesRatio*100,2). "%\n";

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
                    $unitsReturning['military_unit1'] = intval(max($unitsSent['military_unit1'] - $unitsLost['military_unit1'],0));
                    $unitsReturning['military_unit4'] = intval(max($unitsSent['military_unit4'] - $unitsLost['military_unit4'],0));

                    # Queue the incoming land.
                    $this->queueService->queueResources(
                        'invasion',
                        $dominion,
                        $landGained
                    );

                    # Queue the returning units.
                    $this->queueService->queueResources(
                        'invasion',
                        $dominion,
                        $unitsReturning
                    );

                    $invasionTypes = ['attacked', 'raided', 'pillaged', 'ransacked', 'looted', 'devastated', 'plundered', 'sacked', 'invaded', 'laid waste to'];
                    $invasionTargets = ['settlement', 'village', 'town', 'hamlet', 'plot of unclaimed land', 'community', 'trading hub', 'merchant outpost', 'camp'];

                    $data = [
                        'type' => $invasionTypes[rand(0,count($invasionTypes)-1)],
                        'target' => $invasionTargets[rand(0,count($invasionTargets)-1)],
                        'land' => $totalLandToGain,
                      ];

                    $barbarianInvasionEvent = GameEvent::create([
                        'round_id' => $dominion->round_id,
                        'source_type' => Dominion::class,
                        'source_id' => $dominion->id,
                        'target_type' => Realm::class,
                        'target_id' => $dominion->realm_id,
                        'type' => 'barbarian_invasion',
                        'data' => $data,
                    ]);
                    $dominion->save(['event' => HistoryService::EVENT_ACTION_INVADE]);
                }


            }

            $logString .= "\t[/invasion]\n[/BARBARIAN]";
        }

        #Log::Debug($logString);

    }

    public function handleBarbarianConstruction(Dominion $dominion)
    {
        # Get barren land
        $barren = $this->landCalculator->getBarrenLandByLandType($dominion);

        # Determine buildings
        foreach ($barren as $landType => $acres)
        {
            if($landType === 'plain')
            {
                $buildings['building_smithy'] = (int)floor($acres * 0.80);
                $buildings['building_farm'] = (int)floor($acres * 0.20);
            }

            if($landType === 'mountain')
            {
                $buildings['building_ore_mine'] = (int)floor($acres * 0.50);
                $buildings['building_gem_mine'] = (int)floor($acres * 0.50);
            }

            if($landType === 'swamp')
            {
                $buildings['building_tower'] = (int)floor($acres * 0.50);
                $buildings['building_temple'] = (int)floor($acres * 0.50);
            }

            if($landType === 'forest')
            {
                $buildings['building_forest_haven'] = (int)floor($acres * 0.50);
                $buildings['building_lumberyard'] = (int)floor($acres * 0.50);
            }

            if($landType === 'hill')
            {
                $buildings['building_barracks'] = (int)$acres;
            }

            if($landType === 'water')
            {
                $buildings['building_dock'] = (int)$acres;
            }

        }

        # Queue buildings
        $this->queueService->queueResources('construction', $dominion, $buildings, static::CONSTRUCTION_TIME);
    }

    public function createBarbarian(Round $round): void
    {
        # Get Bandit/Barbarian users.
        $barbarianUsers = DB::table('users')
            ->where('users.email', 'like', 'bandit%@lykanthropos.com')
            ->pluck('users.id')
            ->toArray();

        $currentBarbarians = DB::table('users')
            ->join('dominions','dominions.user_id', 'users.id')
            ->whereIn('users.id', $barbarianUsers)
            ->where('dominions.round_id', '=' , $round->id)
            ->pluck('users.id')
            ->toArray();

        $availableUsers = array_diff($barbarianUsers, $currentBarbarians);

        if(!empty($availableUsers))
        {
            $barbarian = $availableUsers[array_rand($availableUsers, 1)];

            # Get Barbarian realm.
            $realm = Realm::query()
                ->where('alignment', '=' , 'npc')
                ->where('round_id', '=' , $round->id)
                ->first();

            # Get Barbarian race.
            $race = Race::query()
                ->where('name', '=', 'Barbarian')
                ->first();

            # Get title.
            $title = Title::query()
                ->where('name', '=', 'Commander')
                ->first();

            # Barbarian tribe names
            $tribeTypes = [
              'Crew',
              'Gang',
              'Tribe',
              'Band',
              'Rovers',
              'Raiders',
              'Ruffians',
              'Roughnecks',
              'Mongrels',
              'Clan',
              'Scofflaws',
              'Mob',
              'Scoundrels',
              'Rascals',
              'Outlaws',
              'Savages',
              'Vandals',
              'Coterie',
              'Muggers',
              'Brutes',
              'Pillagers',
              'Thieves',
              'Crooks',
              'Junta',
              'Bruisers',
              'Guerrillas',
              'Posse',
              'Herd',
              'Hooligans',
              'Hoodlums',
              'Rapscallions',
              'Scallywags',
              'Wretches',
              'Knaves',
              'Scamps',
              'Miscreants',
              'Misfits',
              'Good-For-Nothings',
              'Murderers',
            ];

            $user = User::findorfail($barbarian);

            # Get ruler name.
            $rulerName = $user->display_name;

            # Get the corresponding dominion name.
            $dominionName = $rulerName . "'s " . $tribeTypes[array_rand($tribeTypes, 1)];

            #echo "[BARBARIAN] Creating $dominionName.";

            $barbarian = $this->dominionFactory->create($user, $realm, $race, $title, $rulerName, $dominionName, NULL);

            $this->newDominionEvent = GameEvent::create([
                'round_id' => $barbarian->round_id,
                'source_type' => Dominion::class,
                'source_id' => $barbarian->id,
                'target_type' => Realm::class,
                'target_id' => $barbarian->realm_id,
                'type' => 'new_dominion',
                'data' => NULL,
            ]);
        }
    }
}
