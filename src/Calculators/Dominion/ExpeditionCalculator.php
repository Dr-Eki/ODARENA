<?php

namespace OpenDominion\Calculators\Dominion;

use DB;
use Illuminate\Support\Collection;

use OpenDominion\Helpers\LandHelper;
use OpenDominion\Helpers\UnitHelper;

use OpenDominion\Models\Dominion;

use OpenDominion\Calculators\Dominion\LandCalculator;

use OpenDominion\Services\Dominion\QueueService;

class ExpeditionCalculator
{

    protected $landHelper;
    protected $unitHelper;
    protected $landCalculator;
    protected $prestigeCalculator;
    protected $queueService;

    public function __construct(

          LandHelper $landHelper,

          LandCalculator $landCalculator,
          PrestigeCalculator $prestigeCalculator,

          QueueService $queueService
        )
    {
        $this->landHelper = $landHelper;
        $this->unitHelper = app(UnitHelper::class);

        $this->landCalculator = $landCalculator;
        $this->prestigeCalculator = $prestigeCalculator;

        $this->queueService = $queueService;
    }

    public function getOpPerLand(Dominion $dominion): float
    {
        return ($dominion->land ** 1.25) / 5;
    }

    public function getLandDiscoveredAmount(Dominion $dominion, float $op): int
    {
        $baseGain = floor($op / $this->getOpPerLand($dominion) * $this->getLandDiscoveredMultiplier($dominion));

        # Reduced by deity multiplier
        $deityMultiplier = 1;
        if ($dominion->hasDeity())
        {
            $deityMultiplier -= $dominion->deity->range_multiplier;
        }
        elseif($dominion->hasPendingDeitySubmission())
        {
            $deityMultiplier -= $dominion->getPendingDeitySubmission()->range_multiplier;
        }

        return floor($baseGain * $deityMultiplier);
    }

    public function getLandDiscoveredMultiplier(Dominion $dominion): float
    {
        $multiplier = 1;
        $multiplier += $dominion->getSpellPerkMultiplier('expedition_land_gains');
        $multiplier += $dominion->getAdvancementPerkMultiplier('expedition_land_gains');
        $multiplier += $dominion->getImprovementPerkMultiplier('expedition_land_gains');
        $multiplier += $dominion->getTechPerkMultiplier('expedition_land_gains');
        $multiplier += $dominion->race->getPerkMultiplier('expedition_land_gains');
        $multiplier += $dominion->title->getPerkMultiplier('expedition_land_gains') * $dominion->getTitlePerkMultiplier();

        return $multiplier;
    }

    public function getLandDiscovered(Dominion $dominion, int $landDiscoveredAmount): array
    {
        $landDiscovered = [];
        $landSize = $dominion->land;
        foreach($this->landHelper->getLandTypes() as $landType)
        {
            $ratio = $dominion->{'land_' . $landType} / $landSize;
            $landDiscovered['land_' . $landType] = (int)floor($landDiscoveredAmount * $ratio);
        }

        if(array_sum($landDiscovered) < $landDiscoveredAmount)
        {
            $landDiscovered['land_' . $dominion->race->home_land_type] += ($landDiscoveredAmount - array_sum($landDiscovered));
        }

        return $landDiscovered;
    }

    public function getResourcesFound(Dominion $dominion, array $units): array
    {
        $resourcesFound = [];

        $resourceFindingPerks = [
            'finds_resource_on_expedition',
            'finds_resources_on_expedition',
            'finds_resource_on_expedition_random',
            'finds_resources_on_expedition_random',
        ];

        foreach($units as $slot => $amount)
        {
            $unit = $dominion->race->units->filter(function ($unit) use ($slot) {
                return ($unit->slot === $slot);
            })->first();

            if(!$this->unitHelper->checkUnitHasPerks($dominion, $unit, $resourceFindingPerks))
            {
                continue;
            }

            if(($findsResourceOnExpeditionPerk = $unit->getPerkValue('finds_resource_on_expedition')))
            {
                $amountFound = $findsResourceOnExpeditionPerk[0];
                $resourceKey = $findsResourceOnExpeditionPerk[1];

                $resourcesFound[$resourceKey] = ($resourcesFound[$resourceKey] ?? 0) + ($amount * $amountFound);
            }

            if(($findsResourcesOnExpeditionPerk = $unit->getPerkValue('finds_resources_on_expedition')))
            {
                foreach($findsResourcesOnExpeditionPerk as $findsResourceOnExpeditionPerk)
                {
                    $amountFound = $findsResourceOnExpeditionPerk[0];
                    $resourceKey = $findsResourceOnExpeditionPerk[1];
    
                    $resourcesFound[$resourceKey] = ($resourcesFound[$resourceKey] ?? 0) + ($amount * $amountFound);    
                }
            }

            if(($findsResourceOnExpeditionPerkRandom = $unit->getPerkValue('finds_resource_on_expedition_random')))
            {
                $amountFound = $findsResourceOnExpeditionPerkRandom[0];
                $resourceKey = $findsResourceOnExpeditionPerkRandom[1];
                $probability = $findsResourceOnExpeditionPerkRandom[2];

                $randomChance = (float)$probability / 100;

                $found = 0;

                for ($trials = 1; $trials <= $amount; $trials++)
                {
                    if(random_chance($randomChance))
                    {
                        $found += 1;
                    }
                }

                $resourcesFound[$resourceKey] = ($resourcesFound[$resourceKey] ?? 0) + $found;
            }

            if(($findsResourcesOnExpeditionPerkRandom = $unit->getPerkValue('finds_resources_on_expedition_random')))
            {
                foreach($findsResourcesOnExpeditionPerkRandom as $findsResourceOnExpeditionPerk)
                {
                    $amountFound = $findsResourceOnExpeditionPerk[0];
                    $resourceKey = $findsResourceOnExpeditionPerk[1];
                    $probability = $findsResourceOnExpeditionPerk[2];
    
                    $randomChance = (float)$probability / 100;
    
                    $found = 0;
    
                    for ($trials = 1; $trials <= $amount; $trials++)
                    {
                        if(random_chance($randomChance))
                        {
                            $found += 1;
                        }
                    }
    
                    $resourcesFound[$resourceKey] = ($resourcesFound[$resourceKey] ?? 0) + $found;
                }
            }

        }

        return $resourcesFound;
    }

}
