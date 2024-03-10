<?php

namespace OpenDominion\Services\Dominion\API;

use LogicException;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Calculators\Dominion\TheftCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Resource;

class TheftCalculationService
{

    protected $calculationResult = [
        'spy_strength_cost' => 0,
        'amount_stolen' => 0,
        'units_sent' => 0
    ];

    public function __construct(
        LandCalculator $landCalculator,
        MilitaryCalculator $militaryCalculator,
        TheftCalculator $theftCalculator,
        RangeCalculator $rangeCalculator
    )
    {
        $this->landCalculator = $landCalculator;
        $this->militaryCalculator = $militaryCalculator;
        $this->theftCalculator = $theftCalculator;
        $this->rangeCalculator = $rangeCalculator;
    }

    public function calculate(Dominion $thief, string $resourceKey, array $units, ?Dominion $target, ?array $calc): array
    {
        if ($thief->isLocked() || !$thief->round->isActive()) {
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

        $this->calculationResult['spy_strength_cost'] = $this->theftCalculator->getSpyStrengthCost($thief, $units);

        if(isset($target))
        {
            $this->calculationResult['amount_stolen'] = $this->theftCalculator->getTheftAmount($thief, $target, $resource, $units, true);
        }

        return $this->calculationResult;
    }
}
