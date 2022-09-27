<?php

namespace OpenDominion\Services\Dominion\API;

use LogicException;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\ProtectionService;

class InvadeCalculationService
{
    /**
     * @var int How many units can fit in a single boat
     */

    protected const UNITS_PER_BOAT = 30;

    /** @var MilitaryCalculator */
    protected $militaryCalculator;

    /** @var RangeCalculator */
    protected $rangeCalculator;

    /** @var array Calculation result array. */
    protected $calculationResult = [
        'result' => 'success',
        #'boats_needed' => 0,
        #'boats_remaining' => 0,
        'dp_multiplier' => 0,
        'op_multiplier' => 0,
        'away_defense' => 0,
        'away_offense' => 0,
        'home_defense' => 0,
        'home_defense_raw' => 0,
        'home_offense' => 0,
        'home_dpa' => 0,
        'max_op' => 0,
        'min_dp' => 0,
        'land_conquered' => 0,
        'land_ratio' => 0.5,
        'spell_bonus' => null,
        'units_sent' => 0,
        'units' => [ // home, away, raw OP, raw DP
            '1' => ['dp' => 0, 'op' => 0],
            '2' => ['dp' => 0, 'op' => 0],
            '3' => ['dp' => 0, 'op' => 0],
            '4' => ['dp' => 0, 'op' => 0],
            ],
        'target_dp' => 0,
        'is_ambush' => 0,
        'target_fog' => 0,
    ];

    /**
     * InvadeActionService constructor.
     *
     * @param MilitaryCalculator $militaryCalculator
     * @param RangeCalculator $rangeCalculator
     */
    public function __construct()
    {
        $this->landCalculator = app(LandCalculator::class);
        $this->militaryCalculator = app(MilitaryCalculator::class);
        $this->rangeCalculator = app(RangeCalculator::class);

        $this->protectionService = app(ProtectionService::class);
    }

    /**
     * Calculates an invasion against dominion $target from $dominion.
     *
     * @param Dominion $dominion
     * @param Dominion|null $target
     * @param array $units
     * @return array
     */
    public function calculate(Dominion $dominion, Dominion $target = null, ?array $units, ?array $calc): array
    {
        if ($dominion->isLocked() || $dominion->round->hasEnded())
        {
            return ['result' => 'error', 'message' => 'invalid dominion(s) selected'];
        }

        if (empty($units)) {
            return ['result' => 'error', 'message' => 'invalid input'];
        }

        // Sanitize input
        $units = array_map('intval', array_filter($units));
        if ($calc === null) {
            $calc = ['api' => true];
        }

        if ($target !== null) {
            $landRatio = $this->rangeCalculator->getDominionRange($dominion, $target) / 100;
            $this->calculationResult['land_ratio'] = $landRatio;
        } else {
            $landRatio = 0.5;
        }

        // Calculate unit stats
        #$unitsThatNeedBoats = 0;
        foreach ($dominion->race->units as $unit) {
            $this->calculationResult['units'][$unit->slot]['dp'] = $this->militaryCalculator->getUnitPowerWithPerks(
                $dominion,
                $target,
                $landRatio,
                $unit,
                'defense'
            );
            $this->calculationResult['units'][$unit->slot]['op'] = $this->militaryCalculator->getUnitPowerWithPerks(
                $dominion,
                $target,
                $landRatio,
                $unit,
                'offense',
                $calc
            );
            // Calculate boats needed
            #if (isset($units[$unit->slot]) && $unit->need_boat) {
            #    $unitsThatNeedBoats += (int)$units[$unit->slot];
            #}
        }
        $this->calculationResult['units_sent'] = array_sum($units);

        #$this->calculationResult['boats_needed'] = ceil($unitsThatNeedBoats / $dominion->race->getBoatCapacity());
        #$this->calculationResult['boats_remaining'] = floor($dominion->resource_boats - $this->calculationResult['boats_needed']);

        // Calculate total offense and defense
        $this->calculationResult['dp_multiplier'] = $this->militaryCalculator->getDefensivePowerMultiplier($dominion);
        $this->calculationResult['op_multiplier'] = $this->militaryCalculator->getOffensivePowerMultiplier($dominion);

        $this->calculationResult['away_defense'] = $this->militaryCalculator->getDefensivePower($dominion, null, null, $units);

        if($target)
        {
            $this->calculationResult['away_offense'] = $this->militaryCalculator->getOffensivePower($dominion, $target, $landRatio, $units, [], true); #
        }
        else
        {
            $this->calculationResult['away_offense'] = $this->militaryCalculator->getOffensivePower($dominion, $target, $landRatio, $units, $calc);
        }

        $unitsHome = [
            0 => $dominion->military_draftees,
        ];

        foreach($dominion->race->units as $unit)
        {
            $unitsHome[] = $dominion->{'military_unit'.$unit->slot} - (isset($units[$unit->slot]) ? $units[$unit->slot] : 0);
        }

        $this->calculationResult['home_defense'] = $this->militaryCalculator->getDefensivePower($dominion, null, null, $unitsHome);
        $this->calculationResult['home_defense_raw'] = $this->militaryCalculator->getDefensivePowerRaw($dominion, null, null, $unitsHome);

        $this->calculationResult['home_offense'] = $this->militaryCalculator->getOffensivePower($dominion, $target, $landRatio, $unitsHome, $calc);
        $this->calculationResult['home_dpa'] = $this->calculationResult['home_defense'] / $this->landCalculator->getTotalLand($dominion);

        $this->calculationResult['max_op'] = $this->calculationResult['home_defense'] * (4/3);
        $this->calculationResult['min_dp'] = $this->calculationResult['away_offense'] / 3;


        if($dominion->hasProtector())
        {
            $this->calculationResult['home_defense'] = $this->militaryCalculator->getDefensivePower($dominion->protector);
            $this->calculationResult['home_defense_raw'] = $this->militaryCalculator->getDefensivePowerRaw($dominion->protector);
            $this->calculationResult['max_op'] = $this->calculationResult['away_offense'];    
            $this->calculationResult['min_dp'] = $this->calculationResult['home_defense'];         
        }

        if(isset($target) and $dominion->round->hasStarted() and !$this->protectionService->isUnderProtection($target))
        {
            $this->calculationResult['land_conquered'] = $this->militaryCalculator->getLandConquered($dominion, $target, $landRatio*100);

            $dpMultiplierReduction = $this->militaryCalculator->getDefensiveMultiplierReduction($dominion);

            // Void: immunity to DP mod reductions
            if ($target->getSpellPerkValue('immune_to_temples'))
            {
                $dpMultiplierReduction = 0;
            }
            
            $this->calculationResult['is_ambush'] = ($this->militaryCalculator->getRawDefenseAmbushReductionRatio($dominion) > 0);
    
            if($target->getSpellPerkValue('fog_of_war') and !$target->hasProtector())
            {
                $this->calculationResult['target_dp'] = 'Unknown due to Sazal\'s Fog';
                $this->calculationResult['target_fog'] = 1;
                $this->calculationResult['away_offense'] = number_format($this->militaryCalculator->getOffensivePower($dominion, $target, $landRatio, $units, $calc));
                $this->calculationResult['away_offense'] .= ' (may be inaccurate due to Sazal\'s Fog)';
            }
            elseif($target->protector->getSpellPerkValue('fog_of_war'))
            {
                $this->calculationResult['target_dp'] = 'Unknown due to Sazal\'s Fog';
                $this->calculationResult['target_fog'] = 1;
                $this->calculationResult['away_offense'] = number_format($this->militaryCalculator->getOffensivePower($dominion, $target, $landRatio, $units, $calc));
                $this->calculationResult['away_offense'] .= ' (may be inaccurate due to Sazal\'s Fog)';
            }
            else
            {
                $this->calculationResult['target_dp'] = $this->militaryCalculator->getDefensivePower(
                    $target,
                    $dominion,
                    $landRatio,
                    null,
                    $dpMultiplierReduction,
                    $this->calculationResult['is_ambush'],
                    false,
                    $units, # Becomes $invadingUnits
                    false
                  );
    
                # Round up.
                $this->calculationResult['target_dp'] = ceil($this->calculationResult['target_dp']);
            }
        }

        return $this->calculationResult;
    }
}
