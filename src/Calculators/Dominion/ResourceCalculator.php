<?php

namespace OpenDominion\Calculators\Dominion;


use DB;
use Carbon\Carbon;
use Illuminate\Support\Collection;

use OpenDominion\Helpers\LandHelper;
use OpenDominion\Helpers\UnitHelper;

use OpenDominion\Models\Dominion;
use OpenDominion\Models\Building;
use OpenDominion\Models\DominionResource;
use OpenDominion\Models\Race;
use OpenDominion\Models\Realm;
use OpenDominion\Models\RealmResource;
use OpenDominion\Models\Resource;
use OpenDominion\Models\Round;
use OpenDominion\Models\RoundResource;
use OpenDominion\Models\TradeRoute;

use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MagicCalculator;
use OpenDominion\Calculators\Dominion\PrestigeCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Calculators\Dominion\UnitCalculator;

use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Services\Dominion\StatsService;

class ResourceCalculator
{

    protected $landHelper;
    protected $unitHelper;

    protected $buildingCalculator;
    protected $landCalculator;
    protected $magicCalculator;
    protected $prestigeCalculator;
    protected $spellCalculator;
    protected $unitCalculator;
    protected $queueService;
    protected $statsService;

    public function __construct(
        BuildingCalculator $buildingCalculator,
        LandCalculator $landCalculator,
        MagicCalculator $magicCalculator,
        PrestigeCalculator $prestigeCalculator,
        SpellCalculator $spellCalculator,
        UnitCalculator $unitCalculator,
        QueueService $queueService,
        StatsService $statsService,
        )
    {
        $this->landHelper = app(LandHelper::class);
        $this->unitHelper = app(UnitHelper::class);

        $this->buildingCalculator = $buildingCalculator;
        $this->landCalculator = $landCalculator;
        $this->magicCalculator = $magicCalculator;
        $this->prestigeCalculator = $prestigeCalculator;
        $this->spellCalculator = $spellCalculator;
        $this->unitCalculator = $unitCalculator;

        $this->queueService = $queueService;
        $this->statsService = $statsService;
    }

    public function dominionHasResource(Dominion $dominion, string $resourceKey): bool
    {
        $resource = Resource::where('key', $resourceKey)->firstOrFail();

        return DominionResource::where('resource_id',$resource->id)->where('dominion_id',$dominion->id)->first() ? true : false;
    }

    public function getDominionResources(Dominion $dominion): Collection
    {
        return DominionResource::where('dominion_id',$dominion->id)->get();
    }

    public function realmHasResource(Realm $realm, string $resourceKey): bool
    {
        $resource = Resource::where('key', $resourceKey)->first();
        return RealmResource::where('resource_id',$resource->id)->where('realm_id',$realm->id)->first() ? true : false;
    }

    public function roundHasResource(Round $round, string $resourceKey): bool
    {
        return $round->resources->contains('key', $resourceKey);
    }

    public function getRealmResources(Realm $realm): Collection
    {
        return RealmResource::where('realm_id',$realm->id)->get();
    }

    public function getRoundResources(Round $round): Collection
    {
        return RoundResource::where('round_id',$round->id)->get();
    }

    public function getAmount(Dominion $dominion, string $resourceKey): int
    {
        $resource = Resource::where('key', $resourceKey)->first();

        $dominionResourceAmount = DominionResource::where('dominion_id', $dominion->id)->where('resource_id', $resource->id)->first();

        if($dominionResourceAmount)
        {
            return $dominionResourceAmount->amount;
        }

        return 0;
    }

    public function getRealmAmount(Realm $realm, string $resourceKey): int
    {
        $resource = Resource::where('key', $resourceKey)->first();

        $realmResourceAmount = RealmResource::where('realm_id', $realm->id)->where('resource_id', $resource->id)->first();

        if($realmResourceAmount)
        {
            return $realmResourceAmount->amount;
        }

        return 0;
    }

    public function getProduction(Dominion $dominion, string $resourceKey): int
    {
        // Get raw production
        $production = $this->getProductionRaw($dominion, $resourceKey);

        // Apply multiplier
        $production *= $this->getProductionMultiplier($dominion, $resourceKey);

        // Add interest
        $production += $this->getInterest($dominion, $resourceKey);

        // Add trade
        $production += $this->getResourceDueFromTradeNextTick($dominion, $resourceKey);

        // Return
        return (int)max($production, 0);
    }

    public function getProductionRaw(Dominion $dominion, string $resourceKey): float
    {
        if(
              !in_array($resourceKey, $dominion->race->resources) or
              $dominion->race->getPerkValue('no_' . $resourceKey . '_production') or
              $dominion->getSpellPerkValue('no_' . $resourceKey . '_production') or
              $dominion->getSpellPerkValue('no_production') or
              $dominion->getSpellPerkValue('stasis') or
              $dominion->isAbandoned() or
              $dominion->isLocked()
          )
        {
            return 0;
        }

        $production = 0;
        $production += $dominion->getBuildingPerkValue($resourceKey . '_production_raw');
        $production += $dominion->getBuildingPerkValue($resourceKey . '_production_depleting_raw');
        $production += $dominion->getBuildingPerkValue($resourceKey . '_production_increasing_raw');
        $production += $dominion->getBuildingPerkValue($resourceKey . '_production_raw_from_time');
        $production += $dominion->getSpellPerkValue($resourceKey . '_production_raw');
        $production += $dominion->getImprovementPerkValue($resourceKey . '_production_raw');
        $production += $dominion->getAdvancementPerkValue($resourceKey . '_production_raw');
        $production += $dominion->getUnitPerkProductionBonus($resourceKey . '_production_raw');
        $production += $dominion->race->getPerkValue($resourceKey . '_production_raw');
        $production += $dominion->getUnitPerkProductionBonusFromTitle($resourceKey);
        $production += $dominion->getTerrainPerkValue($resourceKey . '_production_raw');

        # Production from wizard ratio
        $production += $dominion->getBuildingPerkValue($resourceKey . '_production_raw_from_wizard_ratio');

        if(isset($dominion->title))
        {
            $production += $dominion->title->getPerkValue($resourceKey . '_production_raw') * $dominion->getTitlePerkMultiplier();
        }

        if(isset($dominion->race->peasants_production[$resourceKey]))
        {
            $productionPerPeasant = (float)$dominion->race->peasants_production[$resourceKey];

            $productionPerPeasantMultiplier = 1;
            $productionPerPeasantMultiplier += $dominion->getTechPerkMultiplier('production_from_peasants_mod');
            $productionPerPeasantMultiplier += $dominion->getSpellPerkMultiplier('production_from_peasants_mod');
            $productionPerPeasantMultiplier += $dominion->realm->getArtefactPerkMultiplier('production_from_peasants_mod');

            if($dominion->race->getPerkValue('unemployed_peasants_produce'))
            {
                $production += $dominion->peasants * $productionPerPeasant;
            }
            else
            {
                $production += $this->getPopulationEmployed($dominion) * $productionPerPeasant;
            }

            # Legion: annexed peasants
            #if($this->spellCalculator->hasAnnexedDominions($dominion))
            #{
                foreach($this->spellCalculator->getAnnexedDominions($dominion) as $annexedDominion)
                {
                    $production += $annexedDominion->peasants * $productionPerPeasant;
                }
            #}
        }

        # Check for resource_conversion
        if($resourceConversionData = $dominion->getBuildingPerkValue('resource_conversion'))
        {
            $resourceConversionMultiplier = 1;
            $resourceConversionMultiplier += $dominion->getImprovementPerkMultiplier('resource_conversion');
            foreach($dominion->race->resources as $factionResourceKey)
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

        # Check for peasants_conversion (single resource)
        if($peasantConversionData = $dominion->getBuildingPerkValue('peasants_conversion'))
        {
            $resourceConversionMultiplier = 1;
            $resourceConversionMultiplier += $dominion->getImprovementPerkMultiplier('resource_conversion');

            if(isset($peasantConversionData['to'][$resourceKey]))
            {
                $production += floor($peasantConversionData['to'][$resourceKey] * $resourceConversionMultiplier);
            }
        }

        # Check for peasants_conversion (multiple resources)
        if($peasantConversionsData = $dominion->getBuildingPerkValue('peasants_conversions'))
        {
            $resourceConversionMultiplier = 1;
            $resourceConversionMultiplier += $dominion->getImprovementPerkMultiplier('resource_conversion');

            if(isset($peasantConversionsData['to'][$resourceKey]))
            {
                $production += floor($peasantConversionsData['to'][$resourceKey] * $resourceConversionMultiplier);
            }
        }

        # Check for RESOURCE_production_raw_from_ANOTHER_RESOURCE
        foreach($dominion->race->resources as $sourceResourceKey)
        {
            $production += $dominion->getBuildingPerkValue($resourceKey . '_production_raw_from_' . $sourceResourceKey);

        }

        # Check for RESOURCE_production_raw_from_land tech perk <-- This is land (not terrain)
        $production += $dominion->getTechPerkValue($resourceKey . '_production_raw_from_land') * $dominion->land;   

        # Unit specific perks
        for ($slot = 1; $slot <= $dominion->race->units->count(); $slot++)
        {
            # Get the $unit
            $unit = $dominion->race->units->filter(function ($unit) use ($slot) {
                return ($unit->slot == $slot);
            })->first();

            # Check for RESOURCE_production_raw_from_pairing
            if($productionFromPairingPerk = $dominion->race->getUnitPerkValueForUnitSlot($slot, ($resourceKey . '_production_raw_from_pairing')))
            {
                $slotPairedWith = (int)$productionFromPairingPerk[0];
                $productionPerPair = (float)$productionFromPairingPerk[1];

                $availablePairingUnits = $dominion->{'military_unit' . $slotPairedWith};

                $availableProducingUnit = $dominion->{'military_unit' . $slot};

                $extraProducingUnits = $availableProducingUnit; #min($availableProducingUnit, $availablePairingUnits); -- Archdemon breaks the min()

                if($availablePairingUnits)
                {
                    $production += $extraProducingUnits * $productionPerPair;
                }
            }

            # Check for RESOURCE_production_raw_from_time
            if ($timePerkData = $dominion->race->getUnitPerkValueForUnitSlot($slot, ($resourceKey . '_production_raw_from_time')))
            {
                $amountProduced = (float)$timePerkData[2];
                $hourFrom = $timePerkData[0];
                $hourTo = $timePerkData[1];

                if (
                    (($hourFrom < $hourTo) and (now()->hour >= $hourFrom and now()->hour < $hourTo)) or
                    (($hourFrom > $hourTo) and (now()->hour >= $hourFrom or now()->hour < $hourTo))
                )
                {
                    $production += $dominion->{'military_unit' . $slot} * $amountProduced;
                }
            }

            # Check for RESOURCE_production_raw_per_victory
            if ($victoryPerkData = $dominion->race->getUnitPerkValueForUnitSlot($slot, ($resourceKey . '_production_raw_per_victory')))
            {
                $amountProduced = (float)$victoryPerkData;
                $victories = $this->statsService->getStat($dominion, 'invasion_victories');

                $production += $dominion->{'military_unit' . $slot} * $amountProduced * $victories;
            }

            # Check for RESOURCE_production_raw_from_building_pairing
            if ($buildingPairingProductionPerkData = $dominion->race->getUnitPerkValueForUnitSlot($slot, ($resourceKey . '_production_raw_from_building_pairing')))
            {
                $unitsPerBuilding = (float)$buildingPairingProductionPerkData[0];
                $buildingKey = (string)$buildingPairingProductionPerkData[1];
                $amountProduced = (float)$buildingPairingProductionPerkData[2];

                $building = Building::where('key', $buildingKey)->firstOrFail();

                $buildingAmountOwned = $this->buildingCalculator->getBuildingAmountOwned($dominion, $building);

                $maxProducingUnits = $buildingAmountOwned / $unitsPerBuilding;

                $producingUnits = min($maxProducingUnits, $dominion->{'military_unit' . $slot});

                $production += $producingUnits * $amountProduced;
            }

            # Check for peasants_conversions
            if($unitPeasantsConversionPerkData = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, 'peasants_conversions'))
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
                $multiplier += $dominion->getSpellPerkMultiplier('peasants_converted');
                $multiplier += $dominion->getBuildingPerkMultiplier('peasants_converted');
                $multiplier += $dominion->getImprovementPerkMultiplier('peasants_converted');

                $production += min($dominion->{'military_unit' . $slot} * $multiplier * $peasantsConvertedPerUnit, $dominion->peasants) * $amountPerPeasant;
            }
        }

        # Check for RESOURCE_production_raw_random
        if($randomProductionPerkValue = $dominion->getBuildingPerkValue($resourceKey . '_production_raw_random'))
        {
            $production += $randomProductionPerkValue;
            #dump($randomProductionPerkValue);
        }

        # Check for RESOURCE_production_raw_from_terrain (spell)
        if($productionFromLand = $dominion->getSpellPerkValue($resourceKey . '_production_raw_from_terrain'))
        {
            $production += $productionFromLand;
        }

        # Check for RESOURCE_production_raw_from_population
        if($productionFromPopulation = $dominion->race->getPerkValue($resourceKey . '_production_raw_from_population'))
        {
            $population = $dominion->peasants;
            $population += $dominion->military_draftees;
            $population += $dominion->military_spies;
            $population += $dominion->military_wizards;
            $population += $dominion->military_archmages;

            foreach($dominion->race->units as $unit)
            {
                $population += $this->unitCalculator->getUnitTypeTotalTrained($dominion, $unit->slot);
            }

            $production += $population * $productionFromPopulation;
        }

        # Check for RESOURCE_production_raw_from_draftees
        $production += $dominion->military_draftees * $dominion->race->getPerkValue($resourceKey . '_production_raw_from_draftees');

        // _production_raw_mod perks
        $rawModPerks = 1;
        $rawModPerks += $dominion->getBuildingPerkMultiplier($resourceKey . '_production_raw_mod');
        $rawModPerks += $dominion->getSpellPerkMultiplier($resourceKey . '_production_raw_mod');
        $rawModPerks += $dominion->getImprovementPerkMultiplier($resourceKey . '_production_raw_mod');
        $rawModPerks += $dominion->getAdvancementPerkMultiplier($resourceKey . '_production_raw_mod');
        $rawModPerks += $dominion->getTechPerkMultiplier($resourceKey . '_production_raw_mod');

        $production *= $rawModPerks;

        // Check for max storage
        if($this->hasMaxStorage($dominion, $resourceKey))
        {
            $maxStorage = $this->getMaxStorage($dominion, $resourceKey);
            $availableStorage = max(0, $maxStorage - $this->getAmount($dominion, $resourceKey));
            $production = min($production, $availableStorage);
        }

        return max(0, $production);
    }

    public function getProductionMultiplier(Dominion $dominion, string $resourceKey): float
    {
        $multiplier = 1;
        $multiplier += $dominion->getBuildingPerkMultiplier($resourceKey . '_production_mod');
        $multiplier += $dominion->getSpellPerkMultiplier($resourceKey . '_production_mod');
        $multiplier += $dominion->getImprovementPerkMultiplier($resourceKey . '_production_mod');
        $multiplier += $dominion->getAdvancementPerkMultiplier($resourceKey . '_production_mod');
        $multiplier += $dominion->getTechPerkMultiplier($resourceKey . '_production_mod');
        $multiplier += $dominion->getDeityPerkMultiplier($resourceKey . '_production_mod');
        $multiplier += $dominion->realm->getArtefactPerkMultiplier($resourceKey . '_production_mod');
        $multiplier += $dominion->getDecreePerkMultiplier($resourceKey . '_production_mod');
        $multiplier += $dominion->getTerrainPerkMultiplier($resourceKey . '_production_mod');

        if(isset($dominion->title))
        {
            $multiplier += $dominion->title->getPerkMultiplier($resourceKey . '_production_mod') * $dominion->getTitlePerkMultiplier();
        }

        $multiplier += $dominion->race->getPerkMultiplier($resourceKey . '_production_mod');

        # Add prestige
        if($resourceKey == 'food')
        {
            $multiplier *= 1 + $this->prestigeCalculator->getPrestigeMultiplier($dominion);
        }

        $multiplier *= $dominion->getMoraleMultiplier();
        
        return $multiplier;
    }

    public function getConsumption(Dominion $dominion, string $consumedResourceKey): int
    {
        if(!in_array($consumedResourceKey, $dominion->race->resources) or $dominion->race->getPerkValue('no_' . $consumedResourceKey . '_consumption') or $dominion->isAbandoned())
        {
            return 0;
        }

        $consumedResource = Resource::where('key', $consumedResourceKey)->firstOrFail();

        $consumption = 0;
        $consumption += $dominion->getBuildingPerkValue($consumedResourceKey . '_upkeep_raw');
        $consumption += $dominion->getSpellPerkValue($consumedResourceKey . '_upkeep_raw');
        $consumption += $dominion->getImprovementPerkValue($consumedResourceKey . '_upkeep_raw');
        $consumption += $dominion->getAdvancementPerkValue($consumedResourceKey . '_upkeep_raw');
        $consumption += $dominion->getUnitPerkProductionBonus($consumedResourceKey . '_upkeep_raw');

        $consumption += $dominion->getBuildingPerkValue($consumedResourceKey . '_upkeep_raw_per_artefact') * $dominion->realm->artefacts->count();

        # Add upkeep mod
        $upkeepMultiplier = 1;
        $upkeepMultiplier += $dominion->getBuildingPerkMultiplier($consumedResourceKey . '_upkeep_mod');
        $upkeepMultiplier += $dominion->getTerrainPerkMultiplier($consumedResourceKey . '_upkeep_mod');

        $consumption *= $upkeepMultiplier;

        # Check for resource_conversion
        if($resourceConversionData = $dominion->getBuildingPerkValue('resource_conversion'))
        {
            foreach($dominion->resourceKeys() as $resourceKey)
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

        if(($lightManaDrain = $dominion->race->getPerkValue('light_drains_' . $consumedResourceKey)) > 0)
        {
            $consumption += $lightManaDrain * $this->getAmount($dominion, 'light');
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

            $consumers = $dominion->peasants;

            # Check each Unit for does_not_count_as_population perk.
            for ($slot = 1; $slot <= $dominion->race->units->count(); $slot++)
            {
                # Get the $unit
                $unit = $dominion->race->units->filter(function ($unit) use ($slot) {
                        return ($unit->slot == $slot);
                    })->first();

                $amount = $dominion->{'military_unit'.$slot};

                # Check for housing_count
                if($nonStandardHousing = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'housing_count'))
                {
                    $amount *= $nonStandardHousing;
                }

                # Get the unit attributes
                $unitAttributes = $this->unitHelper->getUnitAttributes($unit);

                if (!$dominion->race->getUnitPerkValueForUnitSlot($slot, 'does_not_count_as_population') and !$dominion->race->getUnitPerkValueForUnitSlot($slot, 'does_not_consume_food') and count(array_intersect($nonConsumingUnitAttributes, $unitAttributes)) === 0)
                {
                    $consumers += $amount;
                    $consumers += $this->queueService->getTrainingQueueTotalByResource($dominion, "military_unit{$slot}");
                    #$consumers += $this->queueService->getStunQueueTotalByResource($dominion, "military_unit{$slot}"); # Specifically and intentionally excluded
                    #$consumers += $this->queueService->getSummoningQueueTotalByResource($dominion, "military_unit{$slot}"); # Specifically and intentionally excluded
                    $consumers += $this->queueService->getEvolutionQueueTotalByResource($dominion, "military_unit{$slot}");
                }
            }

            $consumers += $dominion->military_draftees;
            $consumers += $dominion->military_spies;
            $consumers += $dominion->military_wizards;
            $consumers += $dominion->military_archmages;

            $consumption += $consumers * 0.25;

            // Unit Perk: food_consumption
            $extraFoodEaten = 0;
            for ($unitSlot = 1; $unitSlot <= $dominion->race->units->count(); $unitSlot++)
            {
                if ($extraFoodEatenPerUnit = $dominion->race->getUnitPerkValueForUnitSlot($unitSlot, 'food_consumption_raw'))
                {
                    $extraFoodUnits = $dominion->{'military_unit'.$unitSlot};
                    $extraFoodEaten += intval($extraFoodUnits * $extraFoodEatenPerUnit);
                }
            }

            $consumption += $extraFoodEaten;
        }

        # Multipliers
        $multiplier = 1;
        $multiplier += $dominion->getBuildingPerkMultiplier($consumedResourceKey . '_consumption_mod');
        $multiplier += $dominion->getSpellPerkMultiplier($consumedResourceKey . '_consumption_mod');
        $multiplier += $dominion->getImprovementPerkMultiplier($consumedResourceKey . '_consumption_mod');
        $multiplier += $dominion->getAdvancementPerkMultiplier($consumedResourceKey . '_consumption_mod');
        $multiplier += $dominion->getTechPerkMultiplier($consumedResourceKey . '_consumption_mod');
        $multiplier += $dominion->getDeityPerkMultiplier($consumedResourceKey . '_consumption_mod');
        $multiplier += $dominion->race->getPerkMultiplier($consumedResourceKey . '_consumption_mod');
        $multiplier += $dominion->getDecreePerkMultiplier($consumedResourceKey . '_consumption_mod');

        if(isset($dominion->title))
        {
            $multiplier += $dominion->title->getPerkMultiplier($consumedResourceKey . '_consumption_mod') * $dominion->getTitlePerkMultiplier();
        }

        $consumption *= $multiplier;

        if($decayRate = $this->getDecay($dominion, $consumedResourceKey))
        {
            $consumption += $this->getAmount($dominion, $consumedResourceKey) * $decayRate;
        }

        $consumption += $this->getResourceTotalSoldPerTick($dominion, $consumedResource);

        return (int)max(0, $consumption);


    }

    public function getResourceTotalSoldPerTick(Dominion $dominion, Resource $resource): float
    {
        return TradeRoute::where('dominion_id', $dominion->id)->where('source_resource_id', $resource->id)->sum('source_amount');
    }

    public function getResourceDueFromTradeNextTick(Dominion $dominion, string $resourceKey): float
    {
        $resource = Resource::fromKey($resourceKey);

        if($resource->id !== 10)
        {
            return 0;
        }

        $tradeRouteIds = $dominion->tradeRoutes()->where('target_resource_id', $resource->id)->pluck('id');
        $queues = TradeRoute\Queue::whereIn('trade_route_id', $tradeRouteIds)->get();

        return $queues->where('tick',1)->where('type', 'import')->sum('amount');

    }

    public function getDecay(Dominion $dominion, string $consumedResourceKey): float
    {
        if(!in_array($consumedResourceKey, $dominion->race->resources) or $dominion->race->getPerkValue('no_' . $consumedResourceKey . '_consumption') or $dominion->isAbandoned())
        {
            return 0;
        }

        $decayRate = 0;
        $decayRate += $dominion->race->getPerkMultiplier($consumedResourceKey . '_decay');
        $decayRate += $dominion->getBuildingPerkValue($consumedResourceKey . '_decay');
        $decayRate += $dominion->getSpellPerkValue($consumedResourceKey . '_decay');
        $decayRate += $dominion->getImprovementPerkValue($consumedResourceKey . '_decay');
        $decayRate += $dominion->getAdvancementPerkValue($consumedResourceKey . '_decay');
        $decayRate += $dominion->getUnitPerkProductionBonus($consumedResourceKey . '_decay');

        return $decayRate;
    }

    public function getInterest(Dominion $dominion, string $interestBearingResourceKey): int
    {
        $interest = 0;

        $interestRate = $this->getInterestRate($dominion, $interestBearingResourceKey);

        if($interestRate > 0)
        {
            $interest += $this->getAmount($dominion, $interestBearingResourceKey) * $interestRate;
        }

        $rawProductionCap = $this->getProductionRaw($dominion, $interestBearingResourceKey) / 5;

        $interest = min($interest, $rawProductionCap);

        return $interest;
    }

    public function getStockpileRequiredToMaxOutInterest(Dominion $dominion, string $interestBearingResourceKey): int
    {
        $interestRate = $this->getInterestRate($dominion, $interestBearingResourceKey);

        if($interestRate > 0)
        {
            $rawProductionCap = $this->getProductionRaw($dominion, $interestBearingResourceKey) / 5;

            $stockpileRequired = $rawProductionCap / $interestRate;

            return $stockpileRequired;
        }

        return 0;
    }

    public function getInterestRate(Dominion $dominion, string $interestBearingResourceKey): float
    {
        $interestRate = 0;
        $interestRate += $dominion->getTechPerkMultiplier($interestBearingResourceKey . '_interest');

        return $interestRate;
    }

    public function canStarve(Race $race): bool
    {
        if($race->getPerkValue('no_food_consumption') or $race->getPerkValue('no_morale_changes'))
        {
            return false;
        }

        return true;
    }

    public function isOnBrinkOfStarvation(Dominion $dominion): bool
    {
        if(!$dominion->race->getPerkValue('no_food_consumption'))
        {
            return ($this->getAmount($dominion, 'food') + ($this->getProduction($dominion, 'food') - $this->getConsumption($dominion, 'food')) < 0);
        }

        return false;
    }

    #   #   #   #   #   #   #   #   #   #   #   #   #   #   #   #   #   #   #   #   #   #   #   #   #   #   #   #
    /*
    *   Copied in from PopulationCalculator because calling PopulationCalculator in this class breaks the app.
    */

    /**
     * Returns the Dominion's employment jobs.
     *
     * Each building (sans home and barracks) employs 20 peasants.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getEmploymentJobs(Dominion $dominion): int
    {

        $jobs = 0;

        $jobs += $dominion->getBuildingPerkValue('jobs');

        for ($slot = 1; $slot <= $dominion->race->units->count(); $slot++)
        {
            if($slotProvidesJobs = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'provides_jobs'))
            {
                $jobs += $dominion->{'military_unit' . $slot} * $slotProvidesJobs;
            }
        }

        $jobs += $this->queueService->getConstructionQueueTotal($dominion) * 5;

        $multiplier = 1;
        $multiplier += $dominion->getAdvancementPerkMultiplier('jobs_per_building');
        $multiplier += $dominion->getImprovementPerkMultiplier('jobs_per_building');

        $jobs *= $multiplier;

        return $jobs;
    }

    /**
     * Returns the Dominion's employed population.
     *
     * The employed population consists of the Dominion's peasant count, up to the number of max available jobs.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getPopulationEmployed(Dominion $dominion): int
    {
        return min($this->getEmploymentJobs($dominion), $dominion->peasants);
    }

    public function getExchangeRatePerkMultiplier(Dominion $dominion): float
    {
        $perk = 1;

        // Faction perk
        $perk += $dominion->race->getPerkMultiplier('exchange_rate');

        // Advancement
        $perk += $dominion->getAdvancementPerkMultiplier('exchange_rate');

        // Techs
        $perk += $dominion->getTechPerkMultiplier('exchange_rate');

        // Spells
        $perk += $dominion->getSpellPerkMultiplier('exchange_rate');

        // Buildings
        $perk += $dominion->getBuildingPerkMultiplier('exchange_rate');

        // Deity
        $perk += $dominion->getDeityPerkMultiplier('exchange_rate');

        // Improvements
        $perk += $dominion->getImprovementPerkMultiplier('exchange_rate');

        // Artefacts
        $perk += $dominion->realm->getArtefactPerkMultiplier('exchange_rate');

        // Decree
        $perk += $dominion->getDecreePerkMultiplier('exchange_rate');

        // Terrain
        $perk += $dominion->getTerrainPerkMultiplier('exchange_rate_mod');

        // Ruler Title: Merchant
        $perk += $dominion->title->getPerkMultiplier('exchange_rate') * $dominion->getTitlePerkMultiplier();

        $perk = min($perk, 2);

        return $perk;
    }

    public function getMaxStorage(Dominion $dominion, string $resourceKey): int
    {
        if(!$this->hasMaxStorage($dominion, $resourceKey))
        {
            return 0;
        }

        $maxStorage = 0;

        if($resourceKey == 'gunpowder')
        {
            if($dominion->race->name == 'Artillery')
            {
                $maxStorage += $dominion->military_unit2 * $dominion->race->getPerkValue('max_gunpowder_per_cannon');
            }

            $maxStorage += $dominion->getBuildingPerkValue('gunpowder_storage_raw');
        }

        if($resourceKey == 'sapling')
        {
            $maxStorage += $dominion->terrain_forest * $dominion->race->getPerkValue('saplings_per_forest');
            $maxStorage += $dominion->getBuildingPerkValue('sapling_storage_raw');
        }

        $multiplier = 1;

        // Improvements
        $multiplier += $dominion->getImprovementPerkMultiplier('max_storage');
        $multiplier += $dominion->getImprovementPerkMultiplier($resourceKey . '_max_storage');

        // Advancements
        $multiplier += $dominion->getAdvancementPerkMultiplier('max_storage');
        $multiplier += $dominion->getAdvancementPerkMultiplier($resourceKey . '_max_storage');

        // Techs
        $multiplier += $dominion->getTechPerkMultiplier('max_storage');
        $multiplier += $dominion->getTechPerkMultiplier($resourceKey . '_max_storage');

        $maxStorage *= $multiplier;

        return round($maxStorage);
    }

    # To be fixed later
    public function hasMaxStorage(Dominion $dominion, string $resourceKey): bool
    {

        $resourcesWithMaxStorage = ['gunpowder', 'sapling'];

        return in_array($resourceKey, $resourcesWithMaxStorage);
    }

    public function getRoundResourceDecay(Round $round, string $resourceKey): int
    {
        // Get the current time
        $currentTime = Carbon::now();
    
        // Set decay rates
        $dayDecayRate = 2 / 100; // 2% for 06:00 to 18:00
        $nightDecayRate = 0.5 / 100; // 0.5% for 18:00 to 06:00
    
        // Determine the current decay rate based on the time of day
        $decayRate = ($currentTime->hour >= 6 && $currentTime->hour < 18) ? $dayDecayRate : $nightDecayRate;
        
        // Calculate decay
        $decay = (int)round($round->{'resource_' . $resourceKey} * $decayRate);
    
        return $decay;
    }
    
    public function getReturningResources(Dominion $dominion, bool $prestige = true, bool $xp = true): array
    {
        $returningResources = [];

        foreach($dominion->race->resources as $resourceKey)
        {
            $returningResources[$resourceKey] = $this->queueService->getInvasionQueueTotalByResource($dominion, 'resource_' . $resourceKey);
            $returningResources[$resourceKey] += $this->queueService->getExpeditionQueueTotalByResource($dominion, 'resource_' . $resourceKey);
            $returningResources[$resourceKey] += $this->queueService->getTheftQueueTotalByResource($dominion, 'resource_' . $resourceKey);
            $returningResources[$resourceKey] += $this->queueService->getSabotageQueueTotalByResource($dominion, 'resource_' . $resourceKey);
            $returningResources[$resourceKey] += $this->queueService->getDesecrationQueueTotalByResource($dominion, 'resource_' . $resourceKey);
            $returningResources[$resourceKey] += $this->queueService->getStunQueueTotalByResource($dominion, 'resource_' . $resourceKey);
        }

        if($prestige)
        {
            $returningResources['prestige'] = $this->queueService->getInvasionQueueTotalByResource($dominion, 'prestige');
            $returningResources['prestige'] += $this->queueService->getExpeditionQueueTotalByResource($dominion, 'prestige');
            $returningResources['prestige'] += $this->queueService->getTheftQueueTotalByResource($dominion, 'prestige');
            $returningResources['prestige'] += $this->queueService->getSabotageQueueTotalByResource($dominion, 'prestige');
            $returningResources['prestige'] += $this->queueService->getDesecrationQueueTotalByResource($dominion, 'prestige');
            $returningResources['prestige'] += $this->queueService->getStunQueueTotalByResource($dominion, 'prestige');    
            $returningResources['prestige'] += $this->queueService->getArtefactattackQueueTotalByResource($dominion, 'prestige');    
        }

        if($xp)
        {
            $returningResources['xp'] = $this->queueService->getInvasionQueueTotalByResource($dominion, 'xp');
            $returningResources['xp'] += $this->queueService->getExpeditionQueueTotalByResource($dominion, 'xp');
            $returningResources['xp'] += $this->queueService->getTheftQueueTotalByResource($dominion, 'xp');
            $returningResources['xp'] += $this->queueService->getSabotageQueueTotalByResource($dominion, 'xp');
            $returningResources['xp'] += $this->queueService->getDesecrationQueueTotalByResource($dominion, 'xp');
            $returningResources['xp'] += $this->queueService->getStunQueueTotalByResource($dominion, 'xp');    
            $returningResources['xp'] += $this->queueService->getArtefactattackQueueTotalByResource($dominion, 'xp');    
        }

        if($this->queueService->getArtefactQueueTotal($dominion))
        {
            foreach($this->queueService->getArtefactQueue($dominion) as $artefactQueue)
            {
                $returningResources['artefacts'][$artefactQueue->resource] = $artefactQueue->hours;
            }

        }

        return $returningResources;
    }

    public function getProductionPerResource(Dominion $dominion): array
    {
        $production = [];
        foreach($dominion->resourceKeys() as $resourceKey)
        {
            $production[$resourceKey] = $this->getProduction($dominion, $resourceKey);
        }

        return $production;
    }

    public function getConsumptionPerResource(Dominion $dominion): array
    {
        $consumption = [];
        foreach($dominion->resourceKeys() as $resourceKey)
        {
            $consumption[$resourceKey] = $this->getConsumption($dominion, $resourceKey);
        }

        return $consumption;
    }

    public function getNetProductionPerResource(Dominion $dominion): array
    {
        $netProduction = [];
        foreach($dominion->resourceKeys() as $resourceKey)
        {
            $netProduction[$resourceKey] = $this->getProduction($dominion, $resourceKey) - $this->getConsumption($dominion, $resourceKey);
        }

        return $netProduction;
    }
    
    public function getResourceNetProduction(Dominion $dominion, string $resourceKey): int
    {
        return $this->getProduction($dominion, $resourceKey) - $this->getConsumption($dominion, $resourceKey);
    }
}
