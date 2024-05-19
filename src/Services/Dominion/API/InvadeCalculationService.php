<?php

namespace OpenDominion\Services\Dominion\API;

use OpenDominion\Traits\DominionGuardsTrait;

use OpenDominion\Models\Dominion;

use OpenDominion\Calculators\Dominion\MagicCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;

class InvadeCalculationService
{
    use DominionGuardsTrait;

    /** @var MagicCalculator */
    protected $magicCalculator;

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
        'wizard_points' => 0,
        'wizard_points_required' => 0,
    ];

    /**
     * InvadeActionService constructor.
     *
     * @param MilitaryCalculator $militaryCalculator
     * @param RangeCalculator $rangeCalculator
     */
    public function __construct()
    {
        $this->magicCalculator = app(MagicCalculator::class);
        $this->militaryCalculator = app(MilitaryCalculator::class);
        $this->rangeCalculator = app(RangeCalculator::class);
    }

    /**
     * Calculates an invasion against dominion $target from $attacker.
     *
     * @param Dominion $attacker
     * @param Dominion|null $target
     * @param array $units
     * @return array
     */
    public function calculate(Dominion $attacker, Dominion $target = null, ?array $units, ?array $calc): array
    {
        #$this->guardActionsDuringTick($attacker);

        #isset($target) ?? $this->guardActionsDuringTick($target);

        if ($attacker->isLocked() || $attacker->round->hasEnded())
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
            $landRatio = $this->rangeCalculator->getDominionRange($attacker, $target) / 100;
            $this->calculationResult['land_ratio'] = $landRatio;
        } else {
            $landRatio = 0.5;
        }

        // Calculate unit stats
        foreach ($attacker->race->units as $unit) {
            $this->calculationResult['units'][$unit->slot]['dp'] = $this->militaryCalculator->getUnitPowerWithPerks(
                $attacker,
                $target,
                $landRatio,
                $unit,
                'defense'
            );
            $this->calculationResult['units'][$unit->slot]['op'] = $this->militaryCalculator->getUnitPowerWithPerks(
                $attacker,
                $target,
                $landRatio,
                $unit,
                'offense',
                $calc
            );
        }
        $this->calculationResult['units_sent'] = array_sum($units);

        // Calculate total offense and defense
        $this->calculationResult['dp_multiplier'] = $this->militaryCalculator->getDefensivePowerMultiplier($attacker);
        $this->calculationResult['op_multiplier'] = $this->militaryCalculator->getOffensivePowerMultiplier($attacker);

        $this->calculationResult['away_defense'] = $this->militaryCalculator->getDefensivePower($attacker, null, null, $units);

        if($target)
        {
            $this->calculationResult['away_offense'] = $this->militaryCalculator->getOffensivePower($attacker, $target, $landRatio, $units, [], true); #
        }
        else
        {
            $this->calculationResult['away_offense'] = $this->militaryCalculator->getOffensivePower($attacker, $target, $landRatio, $units, $calc);
        }

        $unitsHome = [
            0 => $attacker->military_draftees,
        ];

        foreach($attacker->race->units as $unit)
        {
            $unitsHome[] = $attacker->{'military_unit'.$unit->slot} - (isset($units[$unit->slot]) ? $units[$unit->slot] : 0);
        }

        $this->calculationResult['home_defense'] = $this->militaryCalculator->getDefensivePower($attacker, null, null, $unitsHome);
        $this->calculationResult['home_defense_raw'] = $this->militaryCalculator->getDefensivePowerRaw($attacker, null, null, $unitsHome);

        $this->calculationResult['home_offense'] = $this->militaryCalculator->getOffensivePower($attacker, $target, $landRatio, $unitsHome, $calc);
        $this->calculationResult['home_dpa'] = $this->calculationResult['home_defense'] / $attacker->land;

        $this->calculationResult['max_op'] = $this->calculationResult['home_defense'] * (4/3);
        $this->calculationResult['min_dp'] = $this->calculationResult['away_offense'] / 3;


        if($attacker->hasProtector())
        {
            $this->calculationResult['home_defense'] = $this->militaryCalculator->getDefensivePower($attacker->protector);
            $this->calculationResult['home_defense_raw'] = $this->militaryCalculator->getDefensivePowerRaw($attacker->protector);
            $this->calculationResult['max_op'] = $this->calculationResult['away_offense'];    
            $this->calculationResult['min_dp'] = $this->calculationResult['home_defense'];         
        }

        if(isset($target) and $attacker->round->hasStarted() and $target->protection_ticks == 0)
        {
            $this->calculationResult['land_conquered'] = $this->militaryCalculator->getLandConquered($attacker, $target, $landRatio*100);

            $dpMultiplierReduction = $this->militaryCalculator->getDefensiveMultiplierReduction($attacker);

            // Void: immunity to DP mod reductions
            if ($target->getSpellPerkValue('immune_to_temples'))
            {
                $dpMultiplierReduction = 0;
            }
            
            $this->calculationResult['is_ambush'] = ($this->militaryCalculator->getRawDefenseAmbushReductionRatio($attacker) > 0);
    
            if($target->getSpellPerkValue('fog_of_war') and !$target->hasProtector())
            {
                $this->calculationResult['target_dp'] = 'Unknown due to Sazal\'s Fog';
                $this->calculationResult['target_fog'] = 1;
                $this->calculationResult['away_offense'] = number_format($this->militaryCalculator->getOffensivePower($attacker, $target, $landRatio, $units, $calc));
                $this->calculationResult['away_offense'] .= ' (may be inaccurate due to Sazal\'s Fog)';
            }
            elseif($target->hasProtector() and $target->protector->getSpellPerkValue('fog_of_war'))
            {
                $this->calculationResult['target_dp'] = 'Unknown due to Sazal\'s Fog';
                $this->calculationResult['target_fog'] = 1;
                $this->calculationResult['away_offense'] = number_format($this->militaryCalculator->getOffensivePower($attacker, $target, $landRatio, $units, $calc));
                $this->calculationResult['away_offense'] .= ' (may be inaccurate due to Sazal\'s Fog)';
            }
            else
            {
                $this->calculationResult['target_dp'] = $this->militaryCalculator->getDefensivePower(
                    $target,
                    $attacker,
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

        $this->calculationResult['wizard_points'] = $this->magicCalculator->getWizardPoints($attacker, 'offense');
        $this->calculationResult['wizard_points_required'] = $this->magicCalculator->getWizardPointsRequiredToSendUnits($attacker, $units);

        return $this->calculationResult;
    }
}
