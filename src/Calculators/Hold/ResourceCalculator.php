<?php

namespace OpenDominion\Calculators\Hold;

use OpenDominion\Helpers\UnitHelper;

use OpenDominion\Models\Hold;
use OpenDominion\Models\Building;
use OpenDominion\Models\Resource;
use OpenDominion\Models\TradeRoute;

use OpenDominion\Calculators\Dominion\UnitCalculator;

use OpenDominion\Services\Hold\QueueService;

class ResourceCalculator
{

    protected $unitHelper;

    protected $spellCalculator;
    protected $unitCalculator;
    protected $queueService;
    protected $statsService;

    public function __construct()
    {
        $this->unitHelper = app(UnitHelper::class);
        $this->unitCalculator = app(UnitCalculator::class);
        $this->queueService = app(QueueService::class);
    }

    public function getProduction(Hold $hold, string $resourceKey): int
    {
        // Get raw production
        $production = $this->getProductionRaw($hold, $resourceKey);

        // Apply multiplier
        $production *= $this->getProductionMultiplier($hold, $resourceKey);

        // Add interest
        $production += $this->getInterest($hold, $resourceKey);

        // Add trade
        $production += $this->getResourceDueFromTradeNextTick($hold, $resourceKey);

        // Return
        return (int)max($production, 0);
    }

    public function getProductionRaw(Hold $hold, string $resourceKey): float
    {
        if(!in_array($resourceKey, $hold->sold_resources))
        {
            return 0;
        }

        $production = 0;
        $production += $hold->getBuildingPerkValue($resourceKey . '_production_raw');
        #$production += $hold->getBuildingPerkValue($resourceKey . '_production_depleting_raw');
        #$production += $hold->getBuildingPerkValue($resourceKey . '_production_increasing_raw');
        #$production += $hold->getBuildingPerkValue($resourceKey . '_production_raw_from_time');
        #$production += $hold->getSpellPerkValue($resourceKey . '_production_raw');
        #$production += $hold->getImprovementPerkValue($resourceKey . '_production_raw');
        #$production += $hold->getAdvancementPerkValue($resourceKey . '_production_raw');
        #$production += $hold->getUnitPerkProductionBonus($resourceKey . '_production_raw');
        #$production += $hold->race->getPerkValue($resourceKey . '_production_raw');
        #$production += $hold->getUnitPerkProductionBonusFromTitle($resourceKey);
        #$production += $hold->getTerrainPerkValue($resourceKey . '_production_raw');

        # Production from wizard ratio
        #$production += $hold->getBuildingPerkValue($resourceKey . '_production_raw_from_wizard_ratio');

        if(isset($hold->title))
        {
            $production += $hold->title->getPerkValue($resourceKey . '_production_raw') * $hold->getTitlePerkMultiplier();
        }

        /*
        if(isset($hold->race->peasants_production[$resourceKey]))
        {
            $productionPerPeasant = (float)$hold->race->peasants_production[$resourceKey];

            $productionPerPeasantMultiplier = 1;
            $productionPerPeasantMultiplier += $hold->getTechPerkMultiplier('production_from_peasants_mod');
            $productionPerPeasantMultiplier += $hold->getSpellPerkMultiplier('production_from_peasants_mod');
            $productionPerPeasantMultiplier += $hold->realm->getArtefactPerkMultiplier('production_from_peasants_mod');

            $production += $hold->peasants * $productionPerPeasant;

            # Legion: annexed peasants
            #if($this->spellCalculator->hasAnnexedHolds($hold))
            #{
                foreach($this->spellCalculator->getAnnexedHolds($hold) as $annexedHold)
                {
                    $production += $annexedHold->peasants * $productionPerPeasant;
                }
            #}
        }
        */

        # Check for resource_conversion
        if($resourceConversionData = $hold->getBuildingPerkValue('resource_conversion'))
        {
            $resourceConversionMultiplier = 1;
            $resourceConversionMultiplier += $hold->getImprovementPerkMultiplier('resource_conversion');
            foreach($hold->race->resources as $factionResourceKey)
            {
                if(
                      isset($resourceConversionData['from'][$factionResourceKey]) and
                      isset($resourceConversionData['to'][$resourceKey])
                  )
                {
                    $production += floor($resourceConversionData['to'][$resourceKey] * $resourceConversionMultiplier);
                }
            }
        }

        /*
        # Check for peasants_conversion (single resource)
        if($peasantConversionData = $hold->getBuildingPerkValue('peasants_conversion'))
        {
            $resourceConversionMultiplier = 1;
            $resourceConversionMultiplier += $hold->getImprovementPerkMultiplier('resource_conversion');

            if(isset($peasantConversionData['to'][$resourceKey]))
            {
                $production += floor($peasantConversionData['to'][$resourceKey] * $resourceConversionMultiplier);
            }
        }

        # Check for peasants_conversion (multiple resources)
        if($peasantConversionsData = $hold->getBuildingPerkValue('peasants_conversions'))
        {
            $resourceConversionMultiplier = 1;
            $resourceConversionMultiplier += $hold->getImprovementPerkMultiplier('resource_conversion');

            if(isset($peasantConversionsData['to'][$resourceKey]))
            {
                $production += floor($peasantConversionsData['to'][$resourceKey] * $resourceConversionMultiplier);
            }
        }

        # Check for RESOURCE_production_raw_from_ANOTHER_RESOURCE
        foreach($hold->race->resources as $sourceResourceKey)
        {
            $production += $hold->getBuildingPerkValue($resourceKey . '_production_raw_from_' . $sourceResourceKey);

        }

        # Check for RESOURCE_production_raw_from_land tech perk <-- This is land (not terrain)
        $production += $hold->getTechPerkValue($resourceKey . '_production_raw_from_land') * $hold->land;   

        # Unit specific perks
        for ($slot = 1; $slot <= $hold->race->units->count(); $slot++)
        {
            # Get the $unit
            $unit = $hold->race->units->filter(function ($unit) use ($slot) {
                return ($unit->slot == $slot);
            })->first();

            # Check for RESOURCE_production_raw_from_pairing
            if($productionFromPairingPerk = $hold->race->getUnitPerkValueForUnitSlot($slot, ($resourceKey . '_production_raw_from_pairing')))
            {
                $slotPairedWith = (int)$productionFromPairingPerk[0];
                $productionPerPair = (float)$productionFromPairingPerk[1];

                $availablePairingUnits = $hold->{'military_unit' . $slotPairedWith};

                $availableProducingUnit = $hold->{'military_unit' . $slot};

                $extraProducingUnits = $availableProducingUnit; #min($availableProducingUnit, $availablePairingUnits); -- Archdemon breaks the min()

                if($availablePairingUnits)
                {
                    $production += $extraProducingUnits * $productionPerPair;
                }
            }

            # Check for RESOURCE_production_raw_from_time
            if ($timePerkData = $hold->race->getUnitPerkValueForUnitSlot($slot, ($resourceKey . '_production_raw_from_time')))
            {
                $amountProduced = (float)$timePerkData[2];
                $hourFrom = $timePerkData[0];
                $hourTo = $timePerkData[1];

                if (
                    (($hourFrom < $hourTo) and (now()->hour >= $hourFrom and now()->hour < $hourTo)) or
                    (($hourFrom > $hourTo) and (now()->hour >= $hourFrom or now()->hour < $hourTo))
                )
                {
                    $production += $hold->{'military_unit' . $slot} * $amountProduced;
                }
            }

            # Check for RESOURCE_production_raw_per_victory
            if ($victoryPerkData = $hold->race->getUnitPerkValueForUnitSlot($slot, ($resourceKey . '_production_raw_per_victory')))
            {
                $amountProduced = (float)$victoryPerkData;
                $victories = $this->statsService->getStat($hold, 'invasion_victories');

                $production += $hold->{'military_unit' . $slot} * $amountProduced * $victories;
            }

            # Check for RESOURCE_production_raw_from_building_pairing
            if ($buildingPairingProductionPerkData = $hold->race->getUnitPerkValueForUnitSlot($slot, ($resourceKey . '_production_raw_from_building_pairing')))
            {
                $unitsPerBuilding = (float)$buildingPairingProductionPerkData[0];
                $buildingKey = (string)$buildingPairingProductionPerkData[1];
                $amountProduced = (float)$buildingPairingProductionPerkData[2];

                $buildingAmountOwned = $hold->{'building_' . $buildingKey};

                $maxProducingUnits = $buildingAmountOwned / $unitsPerBuilding;

                $producingUnits = min($maxProducingUnits, $hold->{'military_unit' . $slot});

                $production += $producingUnits * $amountProduced;
            }

            # Check for peasants_conversions
            if($unitPeasantsConversionPerkData = $hold->race->getUnitPerkValueForUnitSlot($unit->slot, 'peasants_conversions'))
            {
                $resourcePairs = $unitPeasantsConversionPerkData;
                array_shift($resourcePairs); // Remove the first element
                $peasantsConvertedPerUnit = (float)$unitPeasantsConversionPerkData[0];
                $amountPerPeasant = 0;

                foreach ($resourcePairs as $resourcePair)
                {
                    if ($resourcePair[1] == $resourceKey)
                    {
                        $amountPerPeasant += (float)$resourcePair[0];
                        break;
                    }
                }

                $multiplier = 1;
                $multiplier += $hold->getSpellPerkMultiplier('peasants_converted');
                $multiplier += $hold->getBuildingPerkMultiplier('peasants_converted');
                $multiplier += $hold->getImprovementPerkMultiplier('peasants_converted');

                $production += min($hold->{'military_unit' . $slot} * $multiplier * $peasantsConvertedPerUnit, $hold->peasants) * $amountPerPeasant;
            }
        }

        # Check for RESOURCE_production_raw_random
        if($randomProductionPerkValue = $hold->getBuildingPerkValue($resourceKey . '_production_raw_random'))
        {
            $production += $randomProductionPerkValue;
            #dump($randomProductionPerkValue);
        }

        # Check for RESOURCE_production_raw_from_terrain (spell)
        if($productionFromLand = $hold->getSpellPerkValue($resourceKey . '_production_raw_from_terrain'))
        {
            $production += $productionFromLand;
        }

        # Check for RESOURCE_production_raw_from_population
        if($productionFromPopulation = $hold->race->getPerkValue($resourceKey . '_production_raw_from_population'))
        {
            $population = $hold->peasants;
            $population += $hold->military_draftees;
            $population += $hold->military_spies;
            $population += $hold->military_wizards;
            $population += $hold->military_archmages;

            foreach($hold->race->units as $unit)
            {
                $population += 0;#$this->unitCalculator->getUnitTypeTotalTrained($hold, $unit->slot);
            }

            $production += $population * $productionFromPopulation;
        }

        # Check for RESOURCE_production_raw_from_draftees
        $production += $hold->military_draftees * $hold->race->getPerkValue($resourceKey . '_production_raw_from_draftees');

        // _production_raw_mod perks
        $rawModPerks = 1;
        $rawModPerks += $hold->getBuildingPerkMultiplier($resourceKey . '_production_raw_mod');
        $rawModPerks += $hold->getSpellPerkMultiplier($resourceKey . '_production_raw_mod');
        $rawModPerks += $hold->getImprovementPerkMultiplier($resourceKey . '_production_raw_mod');
        $rawModPerks += $hold->getAdvancementPerkMultiplier($resourceKey . '_production_raw_mod');
        $rawModPerks += $hold->getTechPerkMultiplier($resourceKey . '_production_raw_mod');

        $production *= $rawModPerks;
        */

        return max(0, $production);
    }

    public function getProductionMultiplier(Hold $hold, string $resourceKey): float
    {
        $multiplier = 1;

        return $multiplier;
        /*
        $multiplier += $hold->getBuildingPerkMultiplier($resourceKey . '_production_mod');
        $multiplier += $hold->getSpellPerkMultiplier($resourceKey . '_production_mod');
        $multiplier += $hold->getImprovementPerkMultiplier($resourceKey . '_production_mod');
        $multiplier += $hold->getAdvancementPerkMultiplier($resourceKey . '_production_mod');
        $multiplier += $hold->getTechPerkMultiplier($resourceKey . '_production_mod');
        $multiplier += $hold->getDeityPerkMultiplier($resourceKey . '_production_mod');
        $multiplier += $hold->realm->getArtefactPerkMultiplier($resourceKey . '_production_mod');
        $multiplier += $hold->getDecreePerkMultiplier($resourceKey . '_production_mod');
        $multiplier += $hold->getTerrainPerkMultiplier($resourceKey . '_production_mod');

        if(isset($hold->title))
        {
            $multiplier += $hold->title->getPerkMultiplier($resourceKey . '_production_mod') * $hold->getTitlePerkMultiplier();
        }

        $multiplier += $hold->race->getPerkMultiplier($resourceKey . '_production_mod');

        # Add prestige
        if($resourceKey == 'food')
        {
            $multiplier *= 1 + $this->prestigeCalculator->getPrestigeMultiplier($hold);
        }

        $multiplier *= $hold->getMoraleMultiplier();
        
        return $multiplier;
        */
    }

    public function getConsumption(Hold $hold, string $consumedResourceKey): int
    {
        return 0;

        /*

        if(!in_array($consumedResourceKey, $hold->race->resources) or $hold->race->getPerkValue('no_' . $consumedResourceKey . '_consumption') or $hold->isAbandoned())
        {
            return 0;
        }

        $consumedResource = Resource::where('key', $consumedResourceKey)->firstOrFail();

        $consumption = 0;
        $consumption += $hold->getBuildingPerkValue($consumedResourceKey . '_upkeep_raw');
        $consumption += $hold->getSpellPerkValue($consumedResourceKey . '_upkeep_raw');
        $consumption += $hold->getImprovementPerkValue($consumedResourceKey . '_upkeep_raw');
        $consumption += $hold->getAdvancementPerkValue($consumedResourceKey . '_upkeep_raw');
        $consumption += $hold->getUnitPerkProductionBonus($consumedResourceKey . '_upkeep_raw');

        $consumption += $hold->getBuildingPerkValue($consumedResourceKey . '_upkeep_raw_per_artefact') * $hold->realm->artefacts->count();

        # Add upkeep mod
        $upkeepMultiplier = 1;
        $upkeepMultiplier += $hold->getBuildingPerkMultiplier($consumedResourceKey . '_upkeep_mod');
        $upkeepMultiplier += $hold->getTerrainPerkMultiplier($consumedResourceKey . '_upkeep_mod');

        $consumption *= $upkeepMultiplier;

        # Check for resource_conversion
        if($resourceConversionData = $hold->getBuildingPerkValue('resource_conversion'))
        {
            foreach($hold->resourceKeys() as $resourceKey)
            {
                if(
                      isset($resourceConversionData['from'][$consumedResourceKey]) and
                      isset($resourceConversionData['to'][$resourceKey])
                  )
                {
                    $consumption += $resourceConversionData['from'][$consumedResourceKey];
                }
            }
        }

        if(($lightManaDrain = $hold->race->getPerkValue('light_drains_' . $consumedResourceKey)) > 0)
        {
            $consumption += $lightManaDrain * $this->getAmount($hold, 'light');
        }

        # Food consumption
        if($consumedResourceKey === 'food')
        {
            $nonConsumingUnitAttributes = [
                'ammunition',
                'equipment',
                'magical',
                'machine',
                'ship',
                'ethereal'
              ];

            $consumers = $hold->peasants;

            # Check each Unit for does_not_count_as_population perk.
            for ($slot = 1; $slot <= $hold->race->units->count(); $slot++)
            {
                # Get the $unit
                $unit = $hold->race->units->filter(function ($unit) use ($slot) {
                        return ($unit->slot == $slot);
                    })->first();

                $amount = $hold->{'military_unit'.$slot};

                # Check for housing_count
                if($nonStandardHousing = $hold->race->getUnitPerkValueForUnitSlot($slot, 'housing_count'))
                {
                    $amount *= $nonStandardHousing;
                }

                # Get the unit attributes
                $unitAttributes = $this->unitHelper->getUnitAttributes($unit);

                if (!$hold->race->getUnitPerkValueForUnitSlot($slot, 'does_not_count_as_population') and !$hold->race->getUnitPerkValueForUnitSlot($slot, 'does_not_consume_food') and count(array_intersect($nonConsumingUnitAttributes, $unitAttributes)) === 0)
                {
                    $consumers += $amount;
                    $consumers += $this->queueService->getTrainingQueueTotalByResource($hold, "military_unit{$slot}");
                    #$consumers += $this->queueService->getStunQueueTotalByResource($hold, "military_unit{$slot}"); # Specifically and intentionally excluded
                    #$consumers += $this->queueService->getSummoningQueueTotalByResource($hold, "military_unit{$slot}"); # Specifically and intentionally excluded
                    $consumers += $this->queueService->getEvolutionQueueTotalByResource($hold, "military_unit{$slot}");
                }
            }

            $consumers += $hold->military_draftees;
            $consumers += $hold->military_spies;
            $consumers += $hold->military_wizards;
            $consumers += $hold->military_archmages;

            $consumption += $consumers * 0.25;

            // Unit Perk: food_consumption
            $extraFoodEaten = 0;
            for ($unitSlot = 1; $unitSlot <= $hold->race->units->count(); $unitSlot++)
            {
                if ($extraFoodEatenPerUnit = $hold->race->getUnitPerkValueForUnitSlot($unitSlot, 'food_consumption_raw'))
                {
                    $extraFoodUnits = $hold->{'military_unit'.$unitSlot};
                    $extraFoodEaten += intval($extraFoodUnits * $extraFoodEatenPerUnit);
                }
            }

            $consumption += $extraFoodEaten;
        }

        # Multipliers
        $multiplier = 1;
        $multiplier += $hold->getBuildingPerkMultiplier($consumedResourceKey . '_consumption_mod');
        $multiplier += $hold->getSpellPerkMultiplier($consumedResourceKey . '_consumption_mod');
        $multiplier += $hold->getImprovementPerkMultiplier($consumedResourceKey . '_consumption_mod');
        $multiplier += $hold->getAdvancementPerkMultiplier($consumedResourceKey . '_consumption_mod');
        $multiplier += $hold->getTechPerkMultiplier($consumedResourceKey . '_consumption_mod');
        $multiplier += $hold->getDeityPerkMultiplier($consumedResourceKey . '_consumption_mod');
        $multiplier += $hold->race->getPerkMultiplier($consumedResourceKey . '_consumption_mod');
        $multiplier += $hold->getDecreePerkMultiplier($consumedResourceKey . '_consumption_mod');

        if(isset($hold->title))
        {
            $multiplier += $hold->title->getPerkMultiplier($consumedResourceKey . '_consumption_mod') * $hold->getTitlePerkMultiplier();
        }

        $consumption *= $multiplier;

        if($decayRate = $this->getDecay($hold, $consumedResourceKey))
        {
            $consumption += $this->getAmount($hold, $consumedResourceKey) * $decayRate;
        }

        $consumption += $this->getResourceTotalSoldPerTick($hold, $consumedResource);

        return (int)max(0, $consumption);
        */

    }

    public function getResourceTotalSoldPerTick(Hold $hold, Resource $resource): float
    {
        return TradeRoute::where('hold_id', $hold->id)->where('source_resource_id', $resource->id)->sum('source_amount');
    }

    public function getResourceDueFromTradeNextTick(Hold $hold, string $resourceKey): float
    {
        $resource = Resource::fromKey($resourceKey);

        if($resource->id !== 10)
        {
            return 0;
        }

        $tradeRouteIds = $hold->tradeRoutes()->where('status',1)->where('target_resource_id', $resource->id)->pluck('id');
        $queues = TradeRoute\Queue::whereIn('trade_route_id', $tradeRouteIds)->get();

        return 0;#$queues->where('tick',1)->where('type', 'import')->sum('amount');

    }

    public function getDecay(Hold $hold, string $consumedResourceKey): float
    {
        if(!in_array($consumedResourceKey, $hold->race->resources) or $hold->race->getPerkValue('no_' . $consumedResourceKey . '_consumption') or $hold->isAbandoned())
        {
            return 0;
        }

        $decayRate = 0;
        $decayRate += $hold->race->getPerkMultiplier($consumedResourceKey . '_decay');
        $decayRate += $hold->getBuildingPerkValue($consumedResourceKey . '_decay');
        $decayRate += $hold->getSpellPerkValue($consumedResourceKey . '_decay');
        $decayRate += $hold->getImprovementPerkValue($consumedResourceKey . '_decay');
        $decayRate += $hold->getAdvancementPerkValue($consumedResourceKey . '_decay');
        $decayRate += $hold->getUnitPerkProductionBonus($consumedResourceKey . '_decay');

        return $decayRate;
    }

    public function getInterest(Hold $hold, string $interestBearingResourceKey): int
    {
        $interest = 0;
        /*
        $interestRate = $this->getInterestRate($hold, $interestBearingResourceKey);

        if($interestRate > 0)
        {
            $interest += $this->getAmount($hold, $interestBearingResourceKey) * $interestRate;
        }

        $rawProductionCap = $this->getProductionRaw($hold, $interestBearingResourceKey) / 5;

        $interest = min($interest, $rawProductionCap);
        */
        return $interest;
    }


    public function getResourceNetProduction(Hold $hold, string $resourceKey): int
    {
        return $this->getProduction($hold, $resourceKey) - $this->getConsumption($hold, $resourceKey);
    }
}
