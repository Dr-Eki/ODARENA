<?php

// We want strict types here.
declare(strict_types=1);

namespace OpenDominion\Services;

Use DB;
use Log;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Models\Race;
use OpenDominion\Models\Title;
use OpenDominion\Models\User;

use OpenDominion\Helpers\LandHelper;

use OpenDominion\Factories\DominionFactory;

use OpenDominion\Calculators\Dominion\BarbarianCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;

use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Services\Dominion\QueueService;

use OpenDominion\Services\Dominion\Actions\InvadeActionService;
use OpenDominion\Services\Dominion\Actions\ReleaseActionService;

use OpenDominion\Services\Dominion\ResourceService;
use OpenDominion\Services\Dominion\StatsService;

class BarbarianService
{

    /** @var LandCalculator */
    protected $landCalculator;

    /** @var QueueService */
    protected $queueService;

    /** @var MilitaryCalculator */
    protected $militaryCalculator;

    /** @var LandHelper */
    protected $landHelper;

    /** @var SpellCalculator */
    protected $spellCalculator;

    /** @var RangeCalculator */
    protected $rangeCalculator;

    /** @var DominionFactory */
    protected $dominionFactory;

    /** @var BarbarianCalculator */
    protected $barbarianCalculator;

    /** @var ResourceService */
    protected $resourceService;

    /** @var StatsService */
    protected $statsService;

    /** @var ImprovementCalculator */
    protected $improvementCalculator;

    protected $settings;

    public function __construct()
    {
        $this->landCalculator = app(LandCalculator::class);
        $this->queueService = app(QueueService::class);
        $this->militaryCalculator = app(MilitaryCalculator::class);
        $this->landHelper = app(LandHelper::class);
        $this->spellCalculator = app(SpellCalculator::class);
        $this->rangeCalculator = app(RangeCalculator::class);
        $this->dominionFactory = app(DominionFactory::class);
        $this->barbarianCalculator = app(BarbarianCalculator::class);
        $this->resourceService = app(ResourceService::class);
        $this->statsService = app(StatsService::class);
        $this->improvementCalculator = app(ImprovementCalculator::class);

        $this->settings = config('barbarians.settings');
    }

    public function handleBarbarianTraining(Dominion $barbarian): void
    {
        if($barbarian->race->key !== 'barbarian')
        {
            return;
        }

        $logString = "[B{$barbarian->id}] ** Barbarian training: {$barbarian->name} (# {$barbarian->realm->number}). | ";

        $defensiveUnitsToTrain = $this->barbarianCalculator->getDefensiveUnitsToTrain($barbarian);

        if($defensiveUnitsToTrain > 0)
        {
            $data = ['military_unit1' => $defensiveUnitsToTrain];
            $ticks = intval($this->settings['UNITS_TRAINING_TICKS']);
            $this->queueService->queueResources('training', $barbarian, $data, $ticks);

            $logString .= "Defensive units to train: {$defensiveUnitsToTrain} | ";
        }

        $offensiveUnitsToTrain = $this->barbarianCalculator->getOffensiveUnitsToTrain($barbarian);

        if($offensiveUnitsToTrain > 0)
        {
            $data = ['military_unit2' => $offensiveUnitsToTrain];
            $ticks = intval($this->settings['UNITS_TRAINING_TICKS']);
            $this->queueService->queueResources('training', $barbarian, $data, $ticks);

            $logString .= "Offensive units to train: {$offensiveUnitsToTrain} | ";
        }

        if(!$defensiveUnitsToTrain and !$offensiveUnitsToTrain)
        {
            $logString .= "No units to train.";
        }

        # Release excessive DP
        $excessiveDefensivePower = $this->barbarianCalculator->getExcessiveDefensivePower($barbarian);

        if($excessiveDefensivePower > 0)
        {
            $logString .= "Excessive defensive power: {$excessiveDefensivePower} | ";

            $unitsToRelease = $this->barbarianCalculator->getDefensiveUnitsToRelease($barbarian);

            $units = ['military_unit1' => $unitsToRelease];

            $releaseResult = app(ReleaseActionService::class)->invade($barbarian, $units);

            $logString .= "Defensive units released: {$unitsToRelease} | ";

        }

        # Release excessive OP
        $excessiveOffensivePower = $this->barbarianCalculator->getExcessiveOffensivePower($barbarian);

        if($excessiveOffensivePower > 0)
        {
            $logString .= "Excessive offensive power: {$excessiveOffensivePower} | ";

            $unitsToRelease = $this->barbarianCalculator->getOffensiveUnitsToRelease($barbarian);

            $units = ['military_unit2' => $unitsToRelease];

            $releaseResult = app(ReleaseActionService::class)->invade($barbarian, $units);

            $logString .= "Offensive units released: {$unitsToRelease} | ";

        }

        if(!$defensiveUnitsToTrain and !$offensiveUnitsToTrain and !$excessiveDefensivePower and !$excessiveOffensivePower)
        {
            $logString .= "No units to release.";
        }

        xtLog($logString);
    }

    public function handleBarbarianInvasion(Dominion $dominion): void
    {

        if($dominion->race->name !== 'Barbarian')
        {
            return;
        }

        if($this->spellCalculator->isAnnexed($dominion))
        {
            # Annexed dominions only invade if legion has issued Limited Self-Governance decree.
            $legion = $this->spellCalculator->getCaster($dominion, 'annexation');
            if(!$legion->getDecreePerkValue('autonomous_barbarians'))
            {
                return;
            }
        }
        
        $invade = false;

        $logString = "[B{$dominion->id}] ** Barbarian invasion: {$dominion->name} (# {$dominion->realm->number}). | ";

        # Make sure we have the expected OPA to hit, and enough DPA at home.
        $currentOp = $this->barbarianCalculator->getCurrentOffensivePower($dominion);
        $targetedOp = $this->barbarianCalculator->getTargetedOffensivePower($dominion);
        $currentToTargetedOpRatio = $currentOp / $targetedOp;
        $currentToTargetedOpRatioToSend = $this->settings['CURRENT_TO_TARGETED_OP_RATIO_TO_SEND'];

        $logString .= "Current OP: {$currentOp} | Targeted OP: {$targetedOp} | Ratio: {$currentToTargetedOpRatio} | Required ratio: {$currentToTargetedOpRatioToSend} | ";
        $logString .= "Missing OP: {$this->barbarianCalculator->getMissingOffensivePower($dominion)} | ";
        $logString .= "Paid OP: {$this->barbarianCalculator->getPaidOffensivePower($dominion)} | ";

        if($currentToTargetedOpRatio >= $currentToTargetedOpRatioToSend)
        {
            $chanceToHit = rand(1, $this->barbarianCalculator->getChanceToHit($dominion));

            $invade = (bool)($chanceToHit == 1);

            $logString .= "Chance to hit: " . ($invade ? 'Yes' : 'No') . " | ";
        }

        if($invade)
        {
            $invadePlayer = false;
            
            $targetsInRange = $this->rangeCalculator->getDominionsInRange($dominion);

            foreach($targetsInRange as $target)
            {
                if(!$target->getSpellPerkValue('fog_of_war') and $target->realm->id !== $dominion->realm->id)
                {
                    $landRatio = $this->rangeCalculator->getDominionRange($dominion, $target) / 100;
                    $units = [2 => $dominion->military_unit2];
                    $targetDp = $this->militaryCalculator->getDefensivePower($target, $dominion, $landRatio, $units);

                    if($currentOp >= ($targetDp * $this->settings['PLAYER_INVASION_OP_DP_RATIO_TOLERANCE']))
                    {
                        $invadePlayer = $target;
                        break;
                    }
                }
            }

            # Chicken out: 7/8 chance that the Barbarians won't hit.
            if($invadePlayer and rand(1, 8) !== 1)
            {
                $invadePlayer = false;
                $logString .= "Chicken out. | ";
            }

            $logString .= "Invade player: " . ($invadePlayer ? 'Yes' : 'No') . " | ";

            if($invadePlayer)
            {
                $invasionResult = app(InvadeActionService::class)->invade($dominion, $target, $units);

            }
            else
            {
                DB::transaction(function () use ($dominion, $logString)
                {

                    $landGainRatio = rand($this->settings['LAND_GAIN_MIN'], $this->settings['LAND_GAIN_MAX']) / 1000;

                    # Calculate the amount of acres to grow.
                    if($dominion->round->ticks >= 384)
                    {
                        $largestDominion = $dominion->round->getNthLargestDominion(1);
                        $maxLandToGain = max(0, $largestDominion->land * 0.6 - $dominion->land - 1);
                        $landGained = min($maxLandToGain, $dominion->land * $landGainRatio);

                        $logString .= "Max land to gain: {$maxLandToGain} | ";
                    }
                    else
                    {
                        $landGained = $dominion->land * $landGainRatio;
                    }

                    $logString .= "Land gained: {$landGained} | ";

                    if($landGained > 0)
                    {
                        # Add the land gained to the $dominion.
                        $this->statsService->updateStat($dominion, 'land_conquered', $landGained);
                        $this->statsService->updateStat($dominion, 'invasion_victories', 1);
    
                        $sentRatio = rand($this->settings['SENT_RATIO_MIN'], $this->settings['SENT_RATIO_MAX']) / 1000;
                        $casualtiesRatio = rand($this->settings['CASUALTIES_MIN'], $this->settings['CASUALTIES_MAX']) / 1000;
    
                        $unitsSent['military_unit2'] = floorInt($dominion->military_unit2 * $sentRatio);
    
                        # Remove the sent units from the dominion.
                        $dominion->military_unit2 -= $unitsSent['military_unit2'];
    
                        # Calculate losses by applying casualties ratio to units sent.
                        $unitsLost['military_unit2'] = floorInt($unitsSent['military_unit2'] * $casualtiesRatio);
    
                        # Calculate amount of returning units.
                        $unitsReturning['military_unit2'] = max($unitsSent['military_unit2'] - $unitsLost['military_unit2'], 0);
    
                        # Queue the incoming land.
                        $this->queueService->queueResources(
                            'invasion',
                            $dominion,
                            ['land' => $landGained]
                        );
    
                        # Queue the returning units.
                        $this->queueService->queueResources(
                            'invasion',
                            $dominion,
                            $unitsReturning
                        );
    
                        $invasionTypes = config('barbarians.invasion_types');
                        
                        $invasionTargets = config('barbarians.invasion_targets');
                        
                        $bodies = floorInt(array_sum($unitsLost) / 10 + $landGained);
    
                        # Update RoundResources
                        if($bodies > 0)
                        {
                            $this->resourceService->updateRoundResources($dominion->round, ['body' => $bodies]);
                        }
                    
                        $data = [
                            'type' => $invasionTypes[rand(0,count($invasionTypes)-1)],
                            'target' => $invasionTargets[rand(0,count($invasionTargets)-1)],
                            'land' => $landGained,
                            'result' =>
                                [
                                'bodies' =>
                                    [
                                    'fallen' => $bodies,
                                    'available' => $bodies,
                                    'desecrated' => 0
                                    ],
                                ],
                        ];
    
                        GameEvent::create([
                            'round_id' => $dominion->round_id,
                            'source_type' => Dominion::class,
                            'source_id' => $dominion->id,
                            'target_type' => Realm::class,
                            'target_id' => $dominion->realm_id,
                            'type' => 'barbarian_invasion',
                            'data' => $data,
                            'tick' => $dominion->round->ticks
                        ]);
    
                        $dominion->save(['event' => HistoryService::EVENT_ACTION_INVADE]);

                    }

                });
            }

        }

        xtLog($logString);
    }

    public function handleBarbarianConstruction(Dominion $dominion): void
    {
        $buildings = [];

        # Determine buildings
        if(($barrenLand = $this->landCalculator->getTotalBarrenLand($dominion)) > 0)
        {
            foreach(config('barbarians.buildings') as $buildingKey => $ratio)
            {
                $buildings[('building_' . $buildingKey)] = roundInt($barrenLand * $ratio);
            }
        }

        if(array_sum($buildings) > 0)
        {
            $this->queueService->queueResources('construction', $dominion, $buildings, $this->settings['CONSTRUCTION_TIME']);
        }

    }

    public function handleBarbarianImprovements(Dominion $barbarian): void
    {
        $amount = $this->barbarianCalculator->getAmountToInvest($barbarian);
        $this->improvementCalculator->createOrIncrementImprovements($barbarian, ['tribalism' => $amount]);
    }

    public function createBarbarian(Round $round): ?Dominion
    {
        $barbarianUsers = User::where(function ($query) {
            $query->where('email', 'like', 'barbarian%@odarena.com')
                    ->orWhere('email', 'like', 'barbarian%@odarena.local');
            })->whereDoesntHave('dominions', function ($query) use ($round) {
                $query->where('round_id', $round->id);
            })->pluck('id')->toArray();

        if (!empty($barbarianUsers)) {
            $barbarianUserId = $barbarianUsers[array_rand($barbarianUsers, 1)];
    
            $barbarianUser = User::findOrFail($barbarianUserId);
    
            $realm = Realm::firstWhere([
                ['alignment', '=', 'npc'],
                ['round_id', '=', $round->id]
            ]);
    
            $race = Race::fromKey('barbarian'); 
            $title = Title::fromKey('commander');
   
            # Barbarian tribe names
            $tribeTypes = config('barbarians.tribe_types');

            # Get ruler name.
            $rulerName = $barbarianUser->display_name;

            # Get the corresponding dominion name.
            $dominionName = $rulerName . "'s " . $tribeTypes[array_rand($tribeTypes, 1)];

            $barbarian = $this->dominionFactory->create(
                $barbarianUser,
                $realm,
                $race,
                $title,
                $rulerName,
                $dominionName,
                null);

            GameEvent::create([
                'round_id' => $barbarian->round_id,
                'source_type' => Dominion::class,
                'source_id' => $barbarian->id,
                'target_type' => Realm::class,
                'target_id' => $barbarian->realm_id,
                'type' => 'new_dominion',
                'data' => null,
                'tick' => $round->ticks
            ]);

            return $barbarian;
        }
    }

}
