<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Models\Dominion;
use OpenDominion\Models\Resource;

use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Helpers\TheftHelper;

use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\ResourceCalculator;

class TheftCalculator
{
    protected $militaryCalculator;
    protected $resourceCalculator;

    protected $theftHelper;
    protected $unitHelper;

    public function __construct(
          TheftHelper $theftHelper,
          UnitHelper $unitHelper,

          MilitaryCalculator $militaryCalculator,
          ResourceCalculator $resourceCalculator
        )
    {
        $this->theftHelper = $theftHelper;
        $this->unitHelper = $unitHelper;

        $this->militaryCalculator = $militaryCalculator;
        $this->resourceCalculator = $resourceCalculator;
    }

    public function getTheftAmount(Dominion $thief, Dominion $target, Resource $resource, array $units, bool $forCalculator = false): int
    {
        if($forCalculator and $target->getSpellPerkValue('fog_of_war'))
        {
            return 0;
        }

        $resourceAvailableAmount = $this->resourceCalculator->getAmount($target, $resource->key);
        $resourceAvailableAmount = $resourceAvailableAmount - $this->getTheftProtection($target, $resource->key);
        $resourceAvailableAmount = max(0, $resourceAvailableAmount);

        foreach($units as $unitSlot => $amount)
        {
            $maxPerSpy = $this->getMaxCarryPerSpyForResource($thief, $resource, $unitSlot);

            $maxAmountStolen = $maxPerSpy * $amount;
        }


        $thiefSpa = max($this->militaryCalculator->getSpyRatio($thief, 'offense'), 0.0001);
        $targetSpa = $this->militaryCalculator->getSpyRatio($target, 'defense');
        $spaSpaRatio = max(min((1-(($targetSpa / $thiefSpa) * 0.5)),1),0);

        $theftAmount = min($resourceAvailableAmount, $maxAmountStolen * $spaSpaRatio);

        # But the target can decrease, which comes afterwards
        $targetModifier = 1;
        $targetModifier += $target->getSpellPerkMultiplier($resource->key . '_theft');
        $targetModifier += $target->getSpellPerkMultiplier('all_theft');
        $targetModifier += $target->getImprovementPerkMultiplier($resource->key . '_theft');
        $targetModifier += $target->getImprovementPerkMultiplier('all_theft');
        $targetModifier -= $target->getBuildingPerkMultiplier($resource->key . '_theft_reduction'); # Minus (-=) because it's a positive value and I don't feel like fixing the perk in the Dominion class right now

        $theftAmount *= $targetModifier;

        $theftAmount = max(0, $theftAmount);

        return $theftAmount;
    }

    public function getTheftProtection(Dominion $target, string $resourceKey)
    {
        $theftProtection = 0;
        $theftProtection += $target->getBuildingPerkValue($resourceKey . '_theft_protection');

        // Unit theft protection
        for ($slot = 1; $slot <= 4; $slot++)
        {
            if($theftProtectionPerk = $target->race->getUnitPerkValueForUnitSlot($slot, 'protects_resource_from_theft'))
            {
                if($theftProtectionPerk[0] == $resourceKey)
                {
                    $theftProtection += $target->{'military_unit'.$slot} * $theftProtectionPerk[1];
                }
            }
        }

        $theftProtectionMultiplier = 1;
        $theftProtectionMultiplier += $target->getImprovementPerkMultiplier('theft_protection');

        return $theftProtection *= $theftProtectionMultiplier;
    }

    public function getMaxCarryPerSpyForResource(Dominion $thief, Resource $resource, $unitSlot = null)
    {
        $max = $this->theftHelper->getMaxCarryPerSpyForResource($resource);

        if($unitSlot and $unitSlot !== 'spies')
        {
            $unitMultiplier = 1;
            $unitMultiplier += $thief->race->getUnitPerkValueForUnitSlot($unitSlot, ($resource->key . '_theft_carry_capacity')) / 100;
            $unitMultiplier += $thief->race->getUnitPerkValueForUnitSlot($unitSlot, 'theft_carry_capacity') / 100;

            $max *= $unitMultiplier;
        }

        # The stealer can increase
        $thiefModifier = 1;
        $thiefModifier += $thief->getTechPerkMultiplier('amount_stolen');
        $thiefModifier += $thief->getDeityPerkMultiplier('amount_stolen');
        $thiefModifier += $thief->getImprovementPerkMultiplier('amount_stolen');
        $thiefModifier += $thief->race->getPerkMultiplier('amount_stolen');

        $thiefModifier += $thief->getTechPerkMultiplier($resource->key . '_amount_stolen');
        $thiefModifier += $thief->getDeityPerkMultiplier($resource->key . '_amount_stolen');
        $thiefModifier += $thief->getImprovementPerkMultiplier($resource->key . '_amount_stolen');

        return $max * $thiefModifier;
    }

    public function getUnitsKilled(Dominion $thief, Dominion $target, array $units): array
    {
        if($thief->getSpellPerkValue('immortal_spies') or $thief->race->getPerkValue('immortal_spies') or $target->race->getPerkValue('does_not_kill'))
        {
            foreach($units as $slot => $amount)
            {
                $killedUnits[$slot] = 0;
            }

            return $killedUnits;
        }

        $baseCasualties = 0.01; # 1%

        $thiefSpa = $this->militaryCalculator->getSpyRatio($thief, 'offense');
        $targetSpa = $this->militaryCalculator->getSpyRatio($target, 'defense');
        $spaRatio = max($targetSpa / $thiefSpa, 0.001);

        # If SPA/SPA is 0.25 or less, there is a random chance spies are immortal.
        if($spaRatio <= 0.25 and random_chance(1 / $spaRatio))
        {
            $baseCasualties = 0;
        }

        $baseCasualties *= (1 + $spaRatio);

        $casualties = $baseCasualties * $this->getSpyLossesReductionMultiplier($thief);

        #dd($thiefSpa, $targetSpa, $spaRatio, $baseCasualties, $casualties);

        foreach($units as $slot => $amount)
        {
            $killedUnits[$slot] = (int)min(ceil($amount * $casualties), $units[$slot]);
        }

        return $killedUnits;
    }

    public function getSpyStrengthCost(Dominion $dominion, array $units): int
    {
        $cost = 0;

        $spyUnits = $this->militaryCalculator->getTotalUnitsForSlot($dominion, 'spies');
        foreach ($dominion->race->units as $unit)
        {
            if($this->unitHelper->isUnitOffensiveSpy($unit))
            {
                $spyUnits += $this->militaryCalculator->getTotalUnitsForSlot($dominion, $unit->slot);
            }
        }

        $cost = (int)ceil(array_sum($units) / $spyUnits * 100);

        return $cost;
    }

    protected function getSpyLossesReductionMultiplier(Dominion $dominion): float
    {
        $spiesKilledMultiplier = 1;

        // Buildings
        $spiesKilledMultiplier -= $dominion->getBuildingPerkMultiplier('spy_losses');

        # Techs
        $spiesKilledMultiplier += $dominion->getTechPerkMultiplier('spy_losses');

        // Improvements
        $spiesKilledMultiplier += $dominion->getImprovementPerkMultiplier('spy_losses');

        # Cap at 10% losses (-90%)
        $spiesKilledMultiplier = max(0.10, $spiesKilledMultiplier);

        return $spiesKilledMultiplier;
    }

}
