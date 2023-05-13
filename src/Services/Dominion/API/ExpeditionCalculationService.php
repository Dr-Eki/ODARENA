<?php

namespace OpenDominion\Services\Dominion\API;

use LogicException;
use OpenDominion\Calculators\Dominion\ArtefactCalculator;
use OpenDominion\Calculators\Dominion\ExpeditionCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Models\Dominion;

class ExpeditionCalculationService
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
        'land_discovered' => 0,
        'land_ratio' => 0.5,
        'spell_bonus' => null,
        'units_sent' => 0,
        'artefact_discovery_chance' => 0,
        'units' => [ // home, away, raw OP, raw DP
            '1' => ['dp' => 0, 'op' => 0],
            '2' => ['dp' => 0, 'op' => 0],
            '3' => ['dp' => 0, 'op' => 0],
            '4' => ['dp' => 0, 'op' => 0],
        ],
    ];

    /**
     * InvadeActionService constructor.
     *
     * @param MilitaryCalculator $militaryCalculator
     * @param RangeCalculator $rangeCalculator
     */
    public function __construct(
        ArtefactCalculator $artefactCalculator,
        ExpeditionCalculator $expeditionCalculator,
        LandCalculator $landCalculator,
        MilitaryCalculator $militaryCalculator,
        RangeCalculator $rangeCalculator
    )
    {
        $this->artefactCalculator = $artefactCalculator;
        $this->expeditionCalculator = $expeditionCalculator;
        $this->landCalculator = $landCalculator;
        $this->militaryCalculator = $militaryCalculator;
        $this->rangeCalculator = $rangeCalculator;
    }

    /**
     * Calculates an expedition
     *
     * @param Dominion $dominion
     * @param array $units
     * @return array
     */
    public function calculate(Dominion $dominion, ?array $units, ?array $calc): array
    {
        if ($dominion->isLocked() || !$dominion->round->isActive()) {
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
        foreach ($dominion->race->units as $unit)
        {
            $this->calculationResult['units'][$unit->slot]['dp'] = $this->militaryCalculator->getUnitPowerWithPerks(
                $dominion,
                null,
                null,
                $unit,
                'defense'
            );
            $this->calculationResult['units'][$unit->slot]['op'] = $this->militaryCalculator->getUnitPowerWithPerks(
                $dominion,
                null,
                null,
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
        $this->calculationResult['away_offense'] = $this->militaryCalculator->getOffensivePower($dominion, null, null, $units, $calc);

        $unitsHome = [
            0 => $dominion->military_draftees,
        ];

        foreach($dominion->race->units as $unit)
        {
            $unitsHome[] = $dominion->{'military_unit'.$unit->slot} - (isset($units[$unit->slot]) ? $units[$unit->slot] : 0);
        }

        $this->calculationResult['home_defense'] = $this->militaryCalculator->getDefensivePower($dominion, null, null, $unitsHome);
        $this->calculationResult['home_defense_raw'] = $this->militaryCalculator->getDefensivePowerRaw($dominion, null, null, $unitsHome);
        $this->calculationResult['home_offense'] = $this->militaryCalculator->getOffensivePower($dominion, null, null, $unitsHome, $calc);
        $this->calculationResult['home_dpa'] = $this->calculationResult['home_defense'] / $this->landCalculator->getTotalLand($dominion);

        $this->calculationResult['max_op'] = $this->calculationResult['home_defense'] * (4/3);
        $this->calculationResult['min_dp'] = $this->calculationResult['away_offense'] / 3;

        $this->calculationResult['land_discovered'] = $this->expeditionCalculator->getLandDiscoveredAmount($dominion, $this->calculationResult['away_offense']);

        $this->calculationResult['artefact_discovery_chance'] = $this->artefactCalculator->getChanceToDiscoverArtefactOnExpedition($dominion, $this->calculationResult) * 100;

        #if(isset($target))
        #{
        #    $this->calculationResult['land_conquered'] = $this->militaryCalculator->getLandConquered($dominion, $target, $landRatio*100);
        #}

        return $this->calculationResult;
    }
}
