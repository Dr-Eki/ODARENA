<?php

namespace OpenDominion\Services\Dominion\API;

use LogicException;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Calculators\Dominion\SabotageCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Resource;

class SabotageCalculationService
{

    protected $calculationResult = [
        'spy_strength_cost' => 0,
        'amount_stolen' => 0,
        'units_sent' => 0
    ];

    public function __construct(
        LandCalculator $landCalculator,
        MilitaryCalculator $militaryCalculator,
        SabotageCalculator $sabotageCalculator,
        RangeCalculator $rangeCalculator
    )
    {
        $this->landCalculator = $landCalculator;
        $this->militaryCalculator = $militaryCalculator;
        $this->sabotageCalculator = $sabotageCalculator;
        $this->rangeCalculator = $rangeCalculator;
    }

    public function calculate(Dominion $saboteur, string $resourceKey, array $units, ?Dominion $target, ?array $calc): array
    {
        if ($saboteur->isLocked() || !$saboteur->round->isActive()) {
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

        $resource = Resource::where('key', $resourceKey)->first();

        $this->calculationResult['spy_strength_cost'] = $this->sabotageCalculator->getSpyStrengthCost($saboteur, $units);

        if(isset($target))
        {
            $this->calculationResult['amount_stolen'] = $this->sabotageCalculator->getSabotageAmount($saboteur, $target, $resource, $units, true);
        }

        return $this->calculationResult;
    }
}
