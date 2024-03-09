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

use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\LandHelper;

use OpenDominion\Factories\DominionFactory;

use OpenDominion\Calculators\Dominion\BarbarianCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Calculators\Dominion\TerrainCalculator;

use OpenDominion\Http\Requests\Dominion\Actions\InvadeActionRequest;
use OpenDominion\Services\Dominion\Actions\InvadeActionService;

use OpenDominion\Services\Dominion\ResourceService;
use OpenDominion\Services\Dominion\StatsService;
use OpenDominion\Services\Dominion\Action\ImproveActionService;

class BarbarianService
{

    /** @var BuildingHelper */
    protected $buildingHelper;

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

    /** @var TerrainCalculator */
    protected $terrainCalculator;

    public function __construct()
    {
        #$this->now = now();
        $this->buildingHelper = app(BuildingHelper::class);
        $this->landCalculator = app(LandCalculator::class);
        $this->queueService = app(QueueService::class);
        $this->militaryCalculator = app(MilitaryCalculator::class);
        $this->landCalculator = app(LandCalculator::class);
        $this->landHelper = app(LandHelper::class);
        $this->spellCalculator = app(SpellCalculator::class);
        $this->rangeCalculator = app(RangeCalculator::class);
        $this->dominionFactory = app(DominionFactory::class);
        $this->barbarianCalculator = app(BarbarianCalculator::class);
        $this->terrainCalculator = app(TerrainCalculator::class);
        $this->resourceService = app(ResourceService::class);
        $this->statsService = app(StatsService::class);
        $this->improvementCalculator = app(ImprovementCalculator::class);
    }

    public function handleBarbarianTraining(Dominion $dominion): void
    {
        if($dominion->race->name !== 'Barbarian')
        {
            return;
        }
    
        foreach($this->landHelper->getLandTypes() as $landType)
        {
            $dominion->land += $this->queueService->getInvasionQueueTotalByResource($dominion, $landType);
        }
    
        $units = [
            'military_unit1' => 0,
            'military_unit2' => 0,
            'military_unit3' => 0,
            'military_unit4' => 0,
        ];
    
        $dpaDeltaPaid = $this->barbarianCalculator->getDpaDeltaPaid($dominion);
        $opaDeltaPaid = $this->barbarianCalculator->getOpaDeltaPaid($dominion);
    
        $specsRatio = rand($this->barbarianCalculator->getSetting('SPECS_RATIO_MIN'), $this->barbarianCalculator->getSetting('SPECS_RATIO_MIN'))/1000;
        $elitesRatio = 1-$specsRatio;
    
        if($dpaDeltaPaid > 0)
        {
            $dpToTrain = $dpaDeltaPaid * $dominion->land * $this->barbarianCalculator->getSetting('DPA_OVERSHOT');
    
            $units['military_unit2'] = ceil(($dpToTrain * $specsRatio) / $this->barbarianCalculator->getSetting('UNIT2_DP'));
            $units['military_unit3'] = ceil(($dpToTrain * $elitesRatio) / $this->barbarianCalculator->getSetting('UNIT3_DP'));
        }
    
        if($opaDeltaPaid > 0)
        {
            $opToTrain = $opaDeltaPaid * $dominion->land;
    
            $units['military_unit1'] = ceil(($opToTrain * $specsRatio) / $this->barbarianCalculator->getSetting('UNIT1_OP'));
            $units['military_unit4'] = ceil(($opToTrain * $elitesRatio) / $this->barbarianCalculator->getSetting('UNIT4_OP'));
        }
    
        foreach($units as $unit => $amountToTrain)
        {
            if($amountToTrain > 0)
            {
                $data = [$unit => $amountToTrain];
                $ticks = intval($this->barbarianCalculator->getSetting('UNITS_TRAINING_TICKS'));
                $this->queueService->queueResources('training', $dominion, $data, $ticks);
            }
        }
    }

    public function handleBarbarianInvasion(Dominion $dominion): void
    {
        $invade = false;

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

        $oneLineLogString = '';

        $logString = "\n[BARBARIAN]\n\t[invasion]\n";
        $logString .= "\t\tName: $dominion->name\n";
        $logString .= "\t\tSize: ".number_format($dominion->land)."\n";

        $oneLineLogString = '[BARBARIAN]{T' . $dominion->round->ticks . '} #' . $dominion->id;
        $oneLineLogString .= ' | Current DPA: ' . number_format($this->barbarianCalculator->getDpaCurrent($dominion),2) . ' | Current DP: ' . number_format($this->barbarianCalculator->getDpCurrent($dominion),2);
        $oneLineLogString .= ' | Current OPA: ' . number_format($this->barbarianCalculator->getOpaCurrent($dominion),2) . ' | Current OP: ' . number_format($this->barbarianCalculator->getOpCurrent($dominion),2);
        $oneLineLogString .= ' | Target DPA: ' . number_format($this->barbarianCalculator->getDpaTarget($dominion), 2) . ' | Target OPA: ' . number_format($this->barbarianCalculator->getOpaTarget($dominion), 2);

        # Make sure we have the expected OPA to hit, and enough DPA at home.
        if($this->barbarianCalculator->getDpaDeltaCurrent($dominion) <= 0 and $this->barbarianCalculator->getOpaDeltaAtHome($dominion) <= 0)
        {

            $currentDay = $dominion->round->start_date->subDays(1)->diffInDays(now());
            $chanceOneIn = $this->barbarianCalculator->getSetting('CHANCE_TO_HIT_CONSTANT') - (14 - $currentDay);

            $chanceOneIn += floor($this->statsService->getStat($dominion, 'defense_failures') * 0.125);

            $chanceToHit = rand(1,$chanceOneIn);

            $logString .= "\t\t* OP/DP\n";
            $logString .= "\t\t** DPA current: " . $this->barbarianCalculator->getDpaCurrent($dominion) ."\n";
            $logString .= "\t\t** DP current: " . $this->barbarianCalculator->getDpCurrent($dominion) ."\n";

            $logString .= "\t\t** OPA at home: " . $this->barbarianCalculator->getOpaAtHome($dominion) ."\n";
            $logString .= "\t\t** OP current: " . $this->barbarianCalculator->getOpCurrent($dominion) ."\n";

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

            $oneLineLogString .= 'Invasion decision: ' . ($chanceToHit == 1 ? '✅ ' : '❌');

        }
        else
        {
            if($this->barbarianCalculator->getDpaDeltaCurrent($dominion) > 0)
            {
                $logString .= "\t\t🚫 Insufficient DP:\n";
                $logString .= "\t\t* DPA\n";
                $logString .= "\t\t** DPA delta current: " . $this->barbarianCalculator->getDpaDeltaCurrent($dominion) ."\n";
                $logString .= "\t\t** DPA delta paid: " . $this->barbarianCalculator->getDpaDeltaPaid($dominion) ."\n";
                $logString .= "\t\t** DPA target: " . $this->barbarianCalculator->getDpaTarget($dominion) ."\n";
                $logString .= "\t\t** DPA paid: " . $this->barbarianCalculator->getDpaPaid($dominion) ."\n";
                $logString .= "\t\t** DPA current: " . $this->barbarianCalculator->getDpaCurrent($dominion) ."\n";
            }

            if($this->barbarianCalculator->getOpaDeltaAtHome($dominion) > 0)
            {
                $logString .= "\t\t🚫 Insufficient OP:\n";
                $logString .= "\t\t* OPA\n";
                $logString .= "\t\t** OPA delta at home: " . $this->barbarianCalculator->getOpaDeltaAtHome($dominion) ."\n";
                $logString .= "\t\t** OPA delta paid: " . $this->barbarianCalculator->getOpaDeltaPaid($dominion) ."\n";
                $logString .= "\t\t** OPA target: " . $this->barbarianCalculator->getOpaTarget($dominion) ."\n";
                $logString .= "\t\t** OPA paid: " . $this->barbarianCalculator->getOpaPaid($dominion) ."\n";
                $logString .= "\t\t** OPA at home: " . $this->barbarianCalculator->getOpaAtHome($dominion) ."\n";
            }

            $oneLineLogString .= 'Need to train ' . ($this->barbarianCalculator->getDpaDeltaCurrent($dominion) > 0 ? 'more DP' : 'no DP') . ' and ' . ($this->barbarianCalculator->getOpaDeltaAtHome($dominion) > 0 ? 'more OP' : 'no OP');
        }

        if($invade)
        {
            $invadePlayer = false;
            # First, look for human players
            $targetsInRange = $this->rangeCalculator->getDominionsInRange($dominion);

            $logString .= "\t\t* Find Target:\n";
            $logString .= "\t\t** Looking for human targets in range:\n";

            foreach($targetsInRange as $target)
            {
                if(!$target->getSpellPerkValue('fog_of_war') and $target->realm->id !== $dominion->realm->id)
                {
                    $landRatio = $this->rangeCalculator->getDominionRange($dominion, $target) / 100;
                    $units = [1 => $dominion->military_unit1, 4 => $dominion->military_unit4];
                    $targetDp = $this->militaryCalculator->getDefensivePower($target, $dominion, $landRatio);

                    $logString .= "\t\t** " . $dominion->name . ' is checking ' . $target->name . ': ';

                    if($this->barbarianCalculator->getOpCurrent($dominion) >= $targetDp * 0.85)
                    {
                        $logString .= '✅ DP is within tolerance! DP: ' . number_format($targetDp) . ' vs. available OP: ' . number_format($this->barbarianCalculator->getOpCurrent($dominion)) . "\n";
                        $invadePlayer = $target;
                        break;
                    }
                    else
                    {
                        $logString .= '🚫 DP is too high. DP: ' . number_format($targetDp) . ' vs. available OP: ' . number_format($this->barbarianCalculator->getOpCurrent($dominion)) . "\n";
                        $invadePlayer = false;
                    }
                }
                else
                {
                    $logString .= "🚫 Target has fog.\n";
                    $invadePlayer = false;
                }

            }

            # Chicken out: 7/8 chance that the Barbarians won't hit.
            if($invadePlayer and rand(1, 8) !== 1)
            {
                $logString .= "\t\t** " . $dominion->name . ' chickens out from invading ' . $target->name . "! 🐤\n";
                $invadePlayer = false;
            }

            if($this->barbarianCalculator->getOpaDeltaPaid($dominion) < -1)
            {
                $invadePlayer = false;
            }

            if($invadePlayer)
            {
                $logString .= "\t\t** " . $dominion->name . ' is invading ' . $target->name . "! ⚔️\n";
                app(InvadeActionService::class)->invade($dominion, $target, $units);
            }
            else
            {
                DB::transaction(function () use ($dominion, $logString)
                {
                    $landGainRatio = rand($this->barbarianCalculator->getSetting('LAND_GAIN_MIN'), $this->barbarianCalculator->getSetting('LAND_GAIN_MAX'))/1000;

                    $logString .= "\t\t* Invasion:\n";
                    $logString .= "\t\t**Land gain ratio: " . number_format($landGainRatio*100,2) . "% \n";

                    # Calculate the amount of acres to grow.
                    $landGained = (int)($dominion->land * $landGainRatio);
                    $logString .= "\t\t**Land to gain: " . number_format($landGained). "\n";

                    # After 384 ticks into the round, Barbarian will abort invasion if the land gained would put the Barbarian within 60% of the largest dominion of the round
                    if($dominion->round->ticks > 384 and !(env('APP_ENV') == 'local'))
                    {
                        $largestDominion = $dominion->round->getNthLargestDominion(1);

                        if(($dominion->land + $landGained) >= ($largestDominion->land * 0.6))
                        {
                            $logString .= "\t\t**Land to gain would put Barbarian within 60% of largest dominion. Aborting invasion.\n";
                            Log::info($logString);
                            return;
                        }
                    }

                    # Add the land gained to the $dominion.
                    $this->statsService->updateStat($dominion, 'land_conquered', $landGained);
                    $this->statsService->updateStat($dominion, 'invasion_victories', 1);

                    $sentRatio = rand($this->barbarianCalculator->getSetting('SENT_RATIO_MIN'), $this->barbarianCalculator->getSetting('SENT_RATIO_MAX'))/1000;

                    $casualtiesRatio = rand($this->barbarianCalculator->getSetting('CASUALTIES_MIN'), $this->barbarianCalculator->getSetting('CASUALTIES_MAX'))/1000;

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

                    $terrainGained = $this->terrainCalculator->getTerrainDiscovered($dominion, $landGained);

                    # Queue the incoming land.
                    $this->queueService->queueResources(
                        'invasion',
                        $dominion,
                        ['land' => $landGained]
                    );

                    foreach($terrainGained as $terrainKey => $amount)
                    {
                        # Queue the incoming terrain.
                        $this->queueService->queueResources(
                            'invasion',
                            $dominion,
                            [('terrain_'.$terrainKey) => $amount]
                        );
                    }


                    # Queue the returning units.
                    $this->queueService->queueResources(
                        'invasion',
                        $dominion,
                        $unitsReturning
                    );

                    $invasionTypes = config('barbarians.invasionTypes');
                    
                    $invasionTargets = config('barbarians.invasionTargets');
                    

                    $bodies = array_sum($unitsLost) / 10 + $landGained;
                    $bodies = (int)floor($bodies);
                
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

                    # Update RoundResources
                    if($bodies > 0)
                    {
                        $this->resourceService->updateRoundResources($dominion->round, ['body' => $bodies]);
                    }

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

                });
            }

        }

        $logString .= "\t[/invasion]\n[/BARBARIAN]";

        #Log::Debug($logString);
        #Log::Debug($oneLineLogString);
    }

    public function handleBarbarianConstruction(Dominion $dominion): void
    {
        $buildings = [];

        # Determine buildings
        if(($barrenLand = $this->landCalculator->getTotalBarrenLand($dominion)) > 0)
        {
            foreach($this->buildingHelper->getBuildingsByRace($dominion->race) as $building)
            {
                $buildings[('building_' . $building->key)] = intval($barrenLand / $this->buildingHelper->getBuildingsByRace($dominion->race)->count());
            }

            $buildings = array_map('intval', $buildings);
        }

        if(array_sum($buildings) > 0)
        {
            $this->queueService->queueResources('construction', $dominion, $buildings, $this->barbarianCalculator->getSetting('CONSTRUCTION_TIME'));
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
    
            $race = Race::firstWhere('name', '=', 'Barbarian');
    
            $title = Title::firstWhere('name', '=', 'Commander');
   
            # Barbarian tribe names
            $tribeTypes = config('barbarians.tribeTypes');

            # Get ruler name.
            $rulerName = $barbarianUser->display_name;

            # Get the corresponding dominion name.
            $dominionName = $rulerName . "'s " . $tribeTypes[array_rand($tribeTypes, 1)];

            $barbarian = $this->dominionFactory->create($barbarianUser, $realm, $race, $title, $rulerName, $dominionName, NULL);

            GameEvent::create([
                'round_id' => $barbarian->round_id,
                'source_type' => Dominion::class,
                'source_id' => $barbarian->id,
                'target_type' => Realm::class,
                'target_id' => $barbarian->realm_id,
                'type' => 'new_dominion',
                'data' => NULL,
                'tick' => $barbarian->round->ticks
            ]);

            return $barbarian;
        }
    }

}
