<?php

namespace OpenDominion\Services\Dominion\API;

use OpenDominion\Traits\DominionGuardsTrait;

use OpenDominion\Models\Dominion;
use OpenDominion\Models\Artefact;

use OpenDominion\Calculators\Dominion\ArtefactCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;

class ArtefactAttackCalculationService
{
    use DominionGuardsTrait;
    /**
     * @var int How many units can fit in a single boat
     */

    protected const UNITS_PER_BOAT = 30;

    /** @var ArtefactCalculator */
    protected $artefactCalculator;

    /** @var MilitaryCalculator */
    protected $militaryCalculator;

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
     * @param ArtefactCalculator $artefactCalculator
     */
    public function __construct()
    {
        $this->artefactCalculator = app(ArtefactCalculator::class);
        $this->militaryCalculator = app(MilitaryCalculator::class);
    }

    /**
     * Calculates an invasion against dominion $target from $dominion.
     *
     * @param Dominion $dominion
     * @param Dominion|null $target
     * @param array $units
     * @return array
     */
    public function calculate(Dominion $dominion, Artefact $artefact = null, ?array $units, ?array $calc): array
    {
        #$this->guardActionsDuringTick($dominion);

        #isset($target) ?? $this->guardActionsDuringTick($target);

        $landRatio = 1;
        $target = null;

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

        // Calculate unit stats
        foreach ($dominion->race->units as $unit) {
            $this->calculationResult['units'][$unit->slot]['dp'] = $this->militaryCalculator->getUnitPowerWithPerks(
                $dominion,
                null,
                1,
                $unit,
                'defense'
            );
            $this->calculationResult['units'][$unit->slot]['op'] = $this->militaryCalculator->getUnitPowerWithPerks(
                $dominion,
                null,
                1,
                $unit,
                'offense',
                $calc
            );
        }
        $this->calculationResult['units_sent'] = array_sum($units);

        // Calculate total offense and defense
        $this->calculationResult['dp_multiplier'] = $this->militaryCalculator->getDefensivePowerMultiplier($dominion);
        $this->calculationResult['op_multiplier'] = $this->militaryCalculator->getOffensivePowerMultiplier($dominion);

        $this->calculationResult['away_defense'] = $this->militaryCalculator->getDefensivePower($dominion, null, null, $units);

        $this->calculationResult['away_offense'] = $this->artefactCalculator->getDamageDealt($dominion, $units, $artefact); #

        if($artefact)
        {
            if($dominion->hasDeity() and $artefact->deity->id == $dominion->deity->id)
            {
                $this->calculationResult['away_offense'] *= 1.2;
            }
        }

        $this->calculationResult['away_opa'] = $this->calculationResult['away_offense'] / $dominion->land;

        #######

        $unitsHome = [
            0 => $dominion->military_draftees,
        ];

        foreach($dominion->race->units as $unit)
        {
            $unitsHome[] = $dominion->{'military_unit'.$unit->slot} - (isset($units[$unit->slot]) ? $units[$unit->slot] : 0);
        }

        $this->calculationResult['home_defense'] = $this->militaryCalculator->getDefensivePower($dominion, null, null, $unitsHome);
        $this->calculationResult['home_defense_raw'] = $this->militaryCalculator->getDefensivePowerRaw($dominion, null, null, $unitsHome);

        $this->calculationResult['home_offense'] = $this->militaryCalculator->getOffensivePower($dominion, null, $landRatio, $unitsHome, $calc);
        $this->calculationResult['home_dpa'] = $this->calculationResult['home_defense'] / $dominion->land;

        $this->calculationResult['max_op'] = $this->calculationResult['home_defense'] * (4/3);
        $this->calculationResult['min_dp'] = $this->calculationResult['away_offense'] / 3;


        if($dominion->hasProtector())
        {
            $this->calculationResult['home_defense'] = $this->militaryCalculator->getDefensivePower($dominion->protector);
            $this->calculationResult['home_defense_raw'] = $this->militaryCalculator->getDefensivePowerRaw($dominion->protector);
            $this->calculationResult['max_op'] = $this->calculationResult['away_offense'];    
            $this->calculationResult['min_dp'] = $this->calculationResult['home_defense'];         
        }

        return $this->calculationResult;
    }
}
