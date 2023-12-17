<?php

namespace OpenDominion\Calculators\Dominion;


use OpenDominion\Models\Dominion;
use OpenDominion\Calculators\Dominion\ResourceCalculator;


class DesecrationCalculator
{

    /** @var ResourceCalculator */
    protected $resourceCalculator;

    public function __construct(ResourceCalculator $resourceCalculator)
    {
        $this->resourceCalculator = $resourceCalculator;
    }

    public function getBodiesDesecrated(Dominion $desecrator, array $desecratingUnits): int
    {
        $maxDesecrated = 0;
        foreach($desecratingUnits as $slot => $amount)
        {
            if(($desecrationPerk = $desecrator->race->getUnitPerkValueForUnitSlot($slot, 'desecration')))
            {
                $perUnit = (float)$desecrationPerk[0];
                $maxDesecrated += $perUnit * $amount;
            }
        }

        return min($maxDesecrated, $desecrator->round->resource_body);

    }

    public function getDesecrationResult(Dominion $desecrator, array $desecratingUnits): array
    {
        $result = []; # [resourceKey => amount] pairs

        $bodiesDesecrated = $this->getBodiesDesecrated($desecrator, $desecratingUnits);
        $bodiesRemaining = $desecrator->round->resource_body;

        if($bodiesDesecrated <= 0 or $bodiesRemaining <= 0)
        {
            return $result;
        }

        foreach($desecratingUnits as $slot => $amount)
        {
            if($bodiesRemaining <= 0)
            {
                break;
            }

            $desecrationPerk = $desecrator->race->getUnitPerkValueForUnitSlot($slot, 'desecration');

            $perUnit = (float)$desecrationPerk[0];
            $perBody = (float)$desecrationPerk[1];
            $resourceKey = $desecrationPerk[2];

            $bodiesUsed = min($bodiesRemaining, $amount * $perUnit);

            $resourceCreated = $bodiesUsed * $perBody;

            $result[$resourceKey] = (int)floor(($result[$resourceKey] ?? 0) + $resourceCreated);

            $bodiesRemaining = $bodiesRemaining - $bodiesUsed;
        }

        return $result;
    }

}