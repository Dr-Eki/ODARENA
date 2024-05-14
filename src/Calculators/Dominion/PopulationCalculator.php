<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Helpers\RaceHelper;
use OpenDominion\Models\Building;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\RaceTerrain;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Services\Dominion\StatsService;

class PopulationCalculator
{

    /** @var bool */
    protected $forTick = false;

    /** @var LandCalculator */
    protected $landCalculator;

    /** @var MoraleCalculator */
    protected $moraleCalculator;

    /** @var PrestigeCalculator */
    protected $prestigeCalculator;

    /** @var QueueService */
    protected $queueService;

    /** @var RaceHelper */
    protected $raceHelper;

    /** @var SpellCalculator */
    protected $spellCalculator;

    /** @var StatsService */
    protected $statsService;

    /** @var UnitCalculator */
    protected $unitCalculator;


    /*
     * PopulationCalculator constructor.
     */
    public function __construct() {
        $this->landCalculator = app(LandCalculator::class);
        $this->prestigeCalculator = app(PrestigeCalculator::class);
        $this->queueService = app(QueueService::class);
        $this->raceHelper = app(RaceHelper::class);
        $this->spellCalculator = app(SpellCalculator::class);
        $this->statsService = app(StatsService::class);
        $this->unitCalculator = app(UnitCalculator::class);
    }

    /**
     * Toggle if this calculator should include the following hour's resources.
     */
    public function setForTick(bool $value)
    {
        $this->forTick = $value;
        $this->queueService->setForTick($value);
    }

    /**
     * Returns the Dominion's total population, both peasants and military.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getPopulation(Dominion $dominion): int
    {
        if($dominion->race->getPerkValue('no_population'))
        {
            return 0;
        }
        return ($dominion->peasants + $this->getPopulationMilitary($dominion));
    }

    /**
     * Returns the Dominion's military population.
     *
     * The military consists of draftees, combat units, spies, wizards, archmages and
     * units currently in training.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getPopulationMilitary(Dominion $dominion): int
    {

        $military = 0;

        foreach(['draftees','spies','wizards','archmages'] as $unitKey)
        {
            $military += $this->unitCalculator->getUnitTypeTotal($dominion, $unitKey);
        }

        # Check each Unit for does_not_count_as_population perk.
        foreach($dominion->race->units as $unit)
        {
            $unitAmount = 0;
            if (!$dominion->race->getUnitPerkValueForUnitSlot($unit->slot, 'does_not_count_as_population'))
            {
                $unitAmount += $this->unitCalculator->getUnitTypeTotal($dominion, $unit->slot);

                # Check for housing_count
                if($nonStandardHousing = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, 'housing_count'))
                {
                    $unitAmount = ceil($unitAmount * $nonStandardHousing);
                }

                $military += $unitAmount;
            }
        }

        return $military;
    }

    /**
     * Returns the Dominion's max population.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getMaxPopulation(Dominion $dominion): int
    {
        $maxPopulation = 0;

        if($dominion->race->getPerkValue('no_population'))
        {
            return $maxPopulation;
        }

        $maxPopulation += $this->getMaxPopulationRaw($dominion) * $this->getMaxPopulationMultiplier($dominion);
        $maxPopulation += $this->getUnitsHousedInUnitAttributeSpecificBuildings($dominion);
        $maxPopulation += $this->getUnitsHousedInUnitSpecificBuildings($dominion);
        $maxPopulation += $this->getUnitsHousedInSpyHousing($dominion);
        $maxPopulation += $this->getUnitsHousedInWizardHousing($dominion);
        $maxPopulation += $this->getDrafteesHousedInDrafteeSpecificBuildings($dominion);
        $maxPopulation += $this->getUnitsHousedInMilitaryHousing($dominion);

        # For barbs, lower pop by NPC modifier.
        if($dominion->race->name == 'Barbarian')
        {
            $maxPopulation *= ($dominion->npc_modifier / 1000);
        }

        return (int)$maxPopulation;
    }

    /**
     * Returns the Dominion's raw max population.
     *
     * Maximum population is determined by housing in homes, other buildings (sans barracks, FH, and WG), and barren land.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getMaxPopulationRaw(Dominion $dominion): int
    {
        $population = 0;

        $population += $dominion->getBuildingPerkValue('housing');
        $population += $dominion->getBuildingPerkValue('housing_increasing');

        // Constructing buildings
        $population += $this->getConstructionHousing($dominion);


        # Multiply $housingPerBarrenAcre by total barren land from LandCalculator
        $population += $this->getBarrenHousing($dominion);
        
        return $population;
    }

    public function getBarrenHousing(Dominion $dominion): int
    {
        $barrenHousing = 0;
        $barrenLand = $this->landCalculator->getTotalBarrenLand($dominion);

        if(!$barrenLand)
        {
            return $barrenHousing;
        }

        $barrenLandRatio = $barrenLand / $dominion->land;

        foreach($dominion->race->raceTerrains as $raceTerrain)
        {
            $barrenHousing += $this->getBarrenHousingOnTerrain($dominion, $raceTerrain) * $barrenLandRatio * $dominion->{'terrain_' . $raceTerrain->terrain->key};
        }

        $multiplier = 1;
        $multiplier += $dominion->getImprovementPerkMultiplier('barren_housing');
        $multiplier += $dominion->getDeityPerkMultiplier('barren_housing');
        $multiplier += $dominion->getAdvancementPerkMultiplier('barren_housing');

        $barrenHousing *= $multiplier;
        $barrenHousing = (int)round($barrenHousing);

        return $barrenHousing;
    }

    # Gets the amount of barren housing on a specific terrain type PER LAND
    public function getBarrenHousingOnTerrain(Dominion $dominion, RaceTerrain $raceTerrain): int
    {
        $barrenHousing = 0;
        $default = 5;

        $default += $dominion->race->getPerkValue('extra_barren_housing');
        $default += $dominion->race->getPerkValue('extra_barren_housing_per_net_victory') * max(($this->statsService->getStat($dominion, 'invasion_victories') - $this->statsService->getStat($dominion, 'defense_failures')), 0);

        $terrainKey = $raceTerrain->terrain->key;

        if($dominion->{'terrain_' . $terrainKey} > 0)
        {
            # Check if $raceTerrain->perks contains fixed_barren_housing_raw
            if($raceTerrain->perks->contains('key', 'fixed_barren_housing_raw'))
            {
                $barrenHousing = $raceTerrain->perks->where('key', 'fixed_barren_housing_raw')->first()->pivot->value;
            }
            else
            {
                $barrenHousing += $default;
            }

            # Check if $raceTerrain->perks contains extra_barren_housing_raw
            if($raceTerrain->perks->contains('key', 'extra_barren_housing_raw'))
            {
                $barrenHousing += $raceTerrain->perks->where('key', 'extra_barren_housing_raw')->first()->pivot->value;
            }
        }
        
        return $barrenHousing;
    }

    public function getConstructionHousing(Dominion $dominion): int
    {

        return $this->queueService->getConstructionQueueTotal($dominion) * 15;

        $constructionHousing = 0;

        if(!$this->queueService->getConstructionQueueTotal($dominion))
        {
            return $constructionHousing;
        }

        $default = 15;

        foreach($this->queueService->getConstructionQueue($dominion) as $queueItem)
        {
            $buildingKey = str_replace('building_', '', $queueItem['resource']);
            $building = Building::where('key', $buildingKey)->first();

            # Check if 'housing' is in $building->perks

            #dd($building->perks->where('key', 'housing')->first());

            $constructionHousingFromThisBuilding = 0;

            if(($buildingHousingPerk = $building->perks->where('key', 'housing')->first()))
            {
                $buildingHousingPerk = (float)$buildingHousingPerk->pivot->value;

                if($buildingHousingPerk === 0.0)
                {
                    $constructionHousingFromThisBuilding = 0;
                }
                else
                {
                    $constructionHousingFromThisBuilding = min($default, $buildingHousingPerk);
                }
            }

            $constructionHousing += $constructionHousingFromThisBuilding * $queueItem['amount'];
        }
        
        $constructionHousing = (int)round($constructionHousing);

        return $constructionHousing;
    }

    /**
     * Returns the Dominion's max population multiplier.
     *
     * Max population multiplier is affected by:
     * - Racial Bonus
     * - Improvement: Keep
     * - Tech: Urban Mastery and Construction (todo)
     * - Prestige bonus (multiplicative)
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getMaxPopulationMultiplier(Dominion $dominion): float
    {
        $multiplier = 0;

        // Racial Bonus
        $multiplier += $dominion->race->getPerkMultiplier('max_population');

        // Improvements
        $multiplier += $dominion->getImprovementPerkMultiplier('population');

        // Buildings
        $multiplier += $dominion->getBuildingPerkMultiplier('population');

        // Deity
        $multiplier += $dominion->getDeityPerkMultiplier('max_population');

        // Decree
        $multiplier += $dominion->getDecreePerkMultiplier('max_population');

        // Tech
        $multiplier += $dominion->getTechPerkMultiplier('max_population');

        // Terrain
        $multiplier += $dominion->getTerrainPerkMultiplier('population_mod');

        // Prestige Bonus
        $prestigeMultiplier = $this->prestigeCalculator->getPrestigeMultiplier($dominion);

        return (1 + $multiplier) * (1 + $dominion->getAdvancementPerkMultiplier('max_population')) * (1 + $prestigeMultiplier);
    }

    /*
    *   Calculate how many units can be fit in this Dominion's Barracks and other buildings that provide military_housing
    */
    public function getAvailableHousingFromMilitaryHousing(Dominion $dominion): int
    {
        $militaryHousingMultiplier = 1;
        $militaryHousingMultiplier += $dominion->race->getPerkMultiplier('military_housing');
        $militaryHousingMultiplier += $dominion->getAdvancementPerkMultiplier('military_housing');
        $militaryHousingMultiplier += $dominion->getDecreePerkMultiplier('military_housing');
        $militaryHousingMultiplier += $dominion->getImprovementPerkMultiplier('military_housing');

        $militaryHousing = 0;
        $militaryHousing += $dominion->getBuildingPerkValue('military_housing');
        $militaryHousing += $dominion->getBuildingPerkValue('military_housing_increasing');
        $militaryHousing *= $militaryHousingMultiplier;
        $militaryHousing += $this->getAvailableHousingFromUnits($dominion);

        return round($militaryHousing);
        
    }

    /*
    *   Calculate how many units can be fit in this Dominion's Barracks.
    *   If $slot is empty, return total available unit housing.
    *   If $slot is set, return unit housing for that slot.
    */
    public function getAvailableHousingFromUnitSpecificBuildings(Dominion $dominion/*, int $slot = null, bool $returnDataArrayOnly = false*/): array
    {
        $unitSpecificBuildingHousingPerkData = $dominion->getBuildingPerkValue($dominion->race->key . '_unit_housing');

        if(!$unitSpecificBuildingHousingPerkData)
        {
            return [];
        }

        $multiplier = 1;
        $multiplier += $dominion->getImprovementPerkMultiplier('unit_specific_housing');
        $multiplier += $dominion->getDeityPerkMultiplier('unit_specific_housing');

        foreach ($unitSpecificBuildingHousingPerkData as $item) {
            foreach ($item as $key => $value) {
                if (!isset($merged[$key])) {
                    $merged[$key] = 0;
                }
                $merged[$key] += $value * $multiplier;
            }
        }

        return $merged;
        /*
        $unitSpecificBuildingHousingPerkData = $merged;
        unset($merged);

        #dd($unitSpecificBuildingHousingPerkData, $merged);

        # For each building
        foreach($dominion->buildings as $building)
        {
            if($buildingPerValueString = $building->getPerkValue($dominion->race->key . '_unit_housing'))
            {
                $perkValues = $dominion->extractBuildingPerkValues($buildingPerValueString);

                if(!is_array($perkValues[0]))
                {
                    $perkValues[0] = [$perkValues[0], $perkValues[1]];
                    unset($perkValues[1]);
                }

                foreach($perkValues as $key => $perkValue)
                {
                    $unitSlot = (int)$perkValue[0];
                    $amountHoused = (float)$perkValue[1];
                        
                    $amountHousable = $amountHoused * $building->pivot->amount * (1 + $dominion->realm->getArtefactPerkMultiplier($building->land_type . '_buildings_effect'));
                    $amountHousable = intval($amountHousable);

                    $availableHousingFromUnitSpecificBuildings[$unitSlot] = (isset($availableHousingFromUnitSpecificBuildings[$unitSlot]) ? $availableHousingFromUnitSpecificBuildings[$unitSlot] + $amountHousable : $amountHousable);
                }
            }
        }

        if($returnDataArrayOnly)
        {
            return (array)$availableHousingFromUnitSpecificBuildings;
        }

        if(!isset($availableHousingFromUnitSpecificBuildings[$slot]))
        {
            return (int)array_sum($availableHousingFromUnitSpecificBuildings);
        }

        if($slot)
        {
            $unitSpecificBuildingHousing += $availableHousingFromUnitSpecificBuildings[$slot];
        }
        else
        {
            foreach($availableHousingFromUnitSpecificBuildings as $slot => $amountHoused)
            {
                $unitSpecificBuildingHousing += $amountHoused;
            }
        }
        


        $multiplier = 1;
        $multiplier += $dominion->getImprovementPerkMultiplier('unit_specific_housing');
        $multiplier += $dominion->getDeityPerkMultiplier('unit_specific_housing');

        $unitSpecificBuildingHousing *= $multiplier;

        return (int)$unitSpecificBuildingHousing;
        */
    }

    /*
    *   Calculate how many units can be fit in this Dominion's Barracks.
    */
    public function getAvailableHousingFromUnitAttributeSpecificBuildings(Dominion $dominion): int
    {
        $unitAttributeSpecificBuildingHousing = 0;

        foreach($this->raceHelper->getAllUnitsAttributes($dominion->race) as $attribute)
        {
            $unitAttributeSpecificBuildingHousing += $dominion->getBuildingPerkValue($attribute . '_units_housing');
        }


        return $unitAttributeSpecificBuildingHousing;
    }

    /*
    *   Calculate how many units can be fit in this Dominion's Barracks.
    */
    public function getAvailableHousingFromDrafteeSpecificBuildings(Dominion $dominion): int
    {
        return $dominion->getBuildingPerkValue('draftee_housing');
    }

    /*
    *   Calculate how many units can be fit in this Dominion's Forest Havens.
    */
    public function getAvailableHousingFromSpyHousing(Dominion $dominion): int
    {
        $housing = 0;
        $housing += $dominion->getBuildingPerkValue('spy_housing');
        $housing += $dominion->getBuildingPerkValue('spy_housing_increasing');
        $housing += $dominion->getBuildingPerkValue('spy_housing_decreasing');

        $multiplier = 1;
        $multiplier += $dominion->getImprovementPerkMultiplier('spy_housing');
        $multiplier += $dominion->getAdvancementPerkMultiplier('spy_housing');

        return round($housing * $multiplier);
    }

    /*
    *   Calculate how many units can be fit in this Dominion's Wizard Guilds.
    */
    public function getAvailableHousingFromWizardHousing(Dominion $dominion): int
    {
        $housing = 0;
        $housing += $dominion->getBuildingPerkValue('wizard_housing');
        $housing += $dominion->getBuildingPerkValue('wizard_housing_increasing');
        $housing += $dominion->getBuildingPerkValue('wizard_housing_decreasing');

        $multiplier = 1;
        $multiplier += $dominion->getImprovementPerkMultiplier('wizard_housing');
        $multiplier += $dominion->getAdvancementPerkMultiplier('wizard_housing');

        return round($housing * $multiplier);
    }

    /*
    *   Calculate how many units can be fit in this Dominion's Units that can house military units.
    *   This is added to getAvailableHousingFromMilitaryHousing().
    */
    public function getAvailableHousingFromUnits(Dominion $dominion): int
    {
        $housingFromUnits = 0;
        $raceKey = str_replace(' ', '_', strtolower($dominion->race->name));

        for ($slot = 1; $slot <= $dominion->race->units->count(); $slot++)
        {
            if($housesMilitaryUnitsPerk = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'houses_military_units'))
            {
                $housingFromUnits += $this->unitCalculator->getUnitTypeTotalTrained($dominion, $slot) * $housesMilitaryUnitsPerk;
            }

            for ($housingPerkSlot = 1; $housingPerkSlot <= $dominion->race->units->count(); $housingPerkSlot++)
            {
                # Unit cannot house itself (e.g. norse_unit1_housing)
                if($housingPerkSlot !== $slot)
                {
                    if($housesSpecificMilitaryUnitPerk = $dominion->race->getUnitPerkValueForUnitSlot($slot, ($raceKey . '_unit' . $housingPerkSlot . '_housing')))
                    {
                        $housingFromUnits += $this->unitCalculator->getUnitTypeTotalTrained($dominion, $slot) * $housesSpecificMilitaryUnitPerk;
                    }
                }
            }
        }

        return $housingFromUnits;
    }

    /*
    *   Calculate how many units live in Barracks.
    *   Units start to live in barracks as soon as their military training begins.
    *   Spy and wiz units prefer to live in FHs or WGs, and will only live in Barracks if FH/WG are full or unavailable.
    */
    public function getUnitsHousedInMilitaryHousing(Dominion $dominion): int
    {
        $units = $this->getPopulationMilitary($dominion);
        #$units -= $this->getUnitsHousedInUnits($dominion);
        $units -= $this->getUnitsHousedInUnitSpecificBuildings($dominion);
        $units -= $this->getUnitsHousedInUnitAttributeSpecificBuildings($dominion);
        $units -= $this->getDrafteesHousedInDrafteeSpecificBuildings($dominion);
        $units -= $this->getUnitsHousedInSpyHousing($dominion);
        $units -= $this->getUnitsHousedInWizardHousing($dominion);

        
        $units = max(0, $units);

        return min($units, $this->getAvailableHousingFromMilitaryHousing($dominion));
    }


    /*
    *   Calculate how many units live in Barracks.
    *   Units start to live in barracks as soon as their military training begins.
    *   Spy and wiz units prefer to live in FHs or WGs, and will only live in Barracks if FH/WG are full or unavailable.
    */
    public function getUnitsHousedInUnitSpecificBuildings(Dominion $dominion, ?int $slot = null): int
    {

        $units = 0;

        $availableHousingFromUnitSpecificBuildings = $this->getAvailableHousingFromUnitSpecificBuildings($dominion);

        # Return nothing if $availableHousingFromUnitSpecificBuildings is an empty array
        if(!$availableHousingFromUnitSpecificBuildings)
        {
            return 0;
        }

        if($slot)
        {
            $capacity = $availableHousingFromUnitSpecificBuildings[$slot];
            return min($this->unitCalculator->getUnitTypeTotalPaid($dominion, $slot), $capacity);
        }

        foreach($availableHousingFromUnitSpecificBuildings as $slot => $capacity)
        {
            $usedCapacity = min($this->unitCalculator->getUnitTypeTotalPaid($dominion, $slot), $capacity);

            $units += $usedCapacity;
        }

        return min($units, array_sum($availableHousingFromUnitSpecificBuildings));
    }

    /*
    *   Calculate how many units live in attribute specific housing.
    */
    public function getUnitsHousedInUnitAttributeSpecificBuildings(Dominion $dominion): int
    {
        $units = 0;

        foreach($dominion->race->units as $unit)
        {
            $slotUnits = 0;
            foreach($unit->type as $attribute)
            {
                if($unitAttributeSpecificBuildingHousing = $dominion->getBuildingPerkValue($attribute . '_units_housing'))
                {
                    if(!$dominion->race->getUnitPerkValueForUnitSlot($unit->slot, 'does_not_count_as_population'))
                    {
                        $slotUnits += $this->unitCalculator->getUnitTypeTotalPaid($dominion, "military_unit{$unit->slot}");
                    }

                    $units += min($slotUnits, $this->getAvailableHousingFromUnitAttributeSpecificBuildings($dominion));
                }
            }
        }

        return min($units, $this->getAvailableHousingFromUnitAttributeSpecificBuildings($dominion));
    }



    /*
    *   Calculate how many units live in Barracks.
    *   Units start to live in barracks as soon as their military training begins.
    *   Spy and wiz units prefer to live in FHs or WGs, and will only live in Barracks if FH/WG are full or unavailable.
    */
    public function getDrafteesHousedInDrafteeSpecificBuildings(Dominion $dominion): int
    {
        $draftees = $this->unitCalculator->getUnitTypeTotalPaid($dominion, 'draftees');
        return min($draftees, $this->getAvailableHousingFromDrafteeSpecificBuildings($dominion));
    }

    /*
    *   Calculate how many units live in Forest Havens.
    *   Spy units start to live in FHs as soon as their military training begins.
    */
    public function getUnitsHousedInSpyHousing(Dominion $dominion): int
    {
        $spyUnits = $this->unitCalculator->getUnitTypeTotalPaid($dominion, 'spies');

        for ($slot = 1; $slot <= $dominion->race->units->count(); $slot++)
        {
            if(
                (
                    $dominion->race->getUnitPerkValueForUnitSlot($slot, 'counts_as_spy_offense') or
                    $dominion->race->getUnitPerkValueForUnitSlot($slot, 'counts_as_spy_defense') or
                    $dominion->race->getUnitPerkValueForUnitSlot($slot, 'counts_as_spy')
                )
                and
                (
                    !$dominion->race->getUnitPerkValueForUnitSlot($slot, 'does_not_count_as_population')
                )
            )
            {
                if(!$dominion->race->getUnitPerkValueForUnitSlot($slot, 'counts_as_wizard_offense') and !$dominion->race->getUnitPerkValueForUnitSlot($slot, 'counts_as_wizard_defense'))
                {
                    $spyUnits += $this->unitCalculator->getUnitTypeTotalPaid($dominion, ('unit'.$slot));
                }
            }
        }

        return min($spyUnits, $this->getAvailableHousingFromSpyHousing($dominion));
    }

    /*
    *   Calculate how many units live in Wizard Guilds.
    *   Wiz units start to live in WGs as soon as their military training begins.
    */
    public function getUnitsHousedInWizardHousing(Dominion $dominion): int
    {
        $wizUnits = $this->unitCalculator->getUnitTypeTotalPaid($dominion, 'wizards');
        $wizUnits += $this->unitCalculator->getUnitTypeTotalPaid($dominion, 'archmages');

        for ($slot = 1; $slot <= $dominion->race->units->count(); $slot++)
        {
            if(
                (
                    $dominion->race->getUnitPerkValueForUnitSlot($slot, 'counts_as_wizard_offense') or
                    $dominion->race->getUnitPerkValueForUnitSlot($slot, 'counts_as_wizard_defense') or
                    $dominion->race->getUnitPerkValueForUnitSlot($slot, 'counts_as_wizard')
                )
                and
                (
                    !$dominion->race->getUnitPerkValueForUnitSlot($slot, 'does_not_count_as_population')
                )
            )
            {
                if(!$dominion->race->getUnitPerkValueForUnitSlot($slot, 'counts_as_spy_offense') and !$dominion->race->getUnitPerkValueForUnitSlot($slot, 'counts_as_spy_defense'))
                {
                    $wizUnits += $this->unitCalculator->getUnitTypeTotalPaid($dominion, ('unit'.$slot));
                }
            }
        }

        return min($wizUnits, $this->getAvailableHousingFromWizardHousing($dominion));
    }

    /**
     * Returns the Dominion's population birth.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getPopulationBirth(Dominion $dominion): int
    {
        return round($this->getPopulationBirthRaw($dominion) * $this->getPopulationBirthMultiplier($dominion));
    }
    /**
     * Returns the Dominions raw population birth.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getPopulationBirthRaw(Dominion $dominion): float
    {

        $growthFactor = 0.03 * $dominion->getMoraleMultiplier();

        // Population births
        $birth = ($dominion->peasants - $this->getUnhousedDraftees($dominion)) * $growthFactor;

        // In case of 0 peasants:
        if($dominion->peasants === 0)
        {
            $birth = ($this->getMaxPopulation($dominion) - $this->getPopulation($dominion) - $this->getUnhousedDraftees($dominion)) * $growthFactor;
        }

        return $birth;
    }

    /**
     * Returns the Dominion's population birth multiplier.
     *
     * @param Dominion $dominion
     * @return float
     */

    public function getPopulationBirthMultiplier(Dominion $dominion): float
    {
        $multiplier = 0;

        // Racial Bonus
        $multiplier += $dominion->race->getPerkMultiplier('population_growth');

        // Buildings
        $multiplier += $dominion->getBuildingPerkMultiplier('population_growth');
        $multiplier += $dominion->getBuildingPerkMultiplier('population_growth_capped');

        // Spells
        $multiplier += $dominion->getSpellPerkMultiplier('population_growth');

        // Advancement
        $multiplier += $dominion->getAdvancementPerkMultiplier('population_growth');

        // Deity
        $multiplier += $dominion->getDeityPerkMultiplier('population_growth');

        // Improvement
        $multiplier += $dominion->getImprovementPerkMultiplier('population_growth');

        // Deity
        $multiplier += $dominion->getDecreePerkMultiplier('population_growth');

        // Terrain
        $multiplier += $dominion->getTerrainPerkMultiplier('population_growth_mod');

        # Look for population_growth in units
        foreach($dominion->race->units as $unit)
        {
            if($unitPopulationGrowthPerk = $dominion->race->getUnitPerkValueForUnitSlot($unit->slot, 'population_growth') and $dominion->{"military_unit".$unit->slot} > 0)
            {
                $unitPopulationGrowthPerk = (float)$unitPopulationGrowthPerk;
                $multiplier += ($dominion->{"military_unit".$unit->slot} / $this->getMaxPopulation($dominion)) * $unitPopulationGrowthPerk;
            }
        }

        $unfilledJobs = $this->getUnfilledJobs($dominion);
        $totalJobs = $this->getEmploymentJobs($dominion);
        
        if($unfilledJobs)
        {
            $unfilledJobsMultiplier = ($unfilledJobs / $totalJobs);

            #dump($unfilledJobsMultiplier, $unfilledJobs, $totalJobs);

            $multiplier += $unfilledJobsMultiplier;
        }
        
        return (1 + $multiplier);
    }


    /**
     * Returns the Dominion's population peasant growth.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getPopulationPeasantGrowth(Dominion $dominion): int
    {

        $maximumPeasantDeath = ((-0.05 * $dominion->peasants) - $this->getPopulationDrafteeGrowth($dominion));

        $roomForPeasants = ($this->getMaxPopulation($dominion) - $this->getPopulation($dominion));# - $this->getUnhousedDraftees($dominion));

        $currentPopulationChange = ($this->getPopulationBirth($dominion) - $this->getUnhousedDraftees($dominion));

        $maximumPopulationChange = min($roomForPeasants, $currentPopulationChange);

        $peasantGrowth = max($maximumPeasantDeath, $maximumPopulationChange);

        return $peasantGrowth;
    }

    /**
     * Returns the Dominion's population draftee growth.
     *
     * Draftee growth is influenced by draft rate.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getPopulationDrafteeGrowth(Dominion $dominion): int
    {
        $draftees = 0;

        if($dominion->getSpellPerkValue('no_drafting') or $dominion->getDecreePerkValue('no_drafting') or $dominion->race->getPerkValue('no_drafting') or $dominion->draft_rate == 0)
        {
            return $draftees;
        }

        // Values (percentages)
        $growthFactor = 0.01;

        $growthFactor += $dominion->race->getPerkValue('drafting_growth_factor');

        $multiplier = 1;

        // Advancement
        $multiplier += $dominion->getAdvancementPerkMultiplier('drafting');

        // Spell
        $multiplier += $dominion->getSpellPerkMultiplier('drafting');

        // Buildings
        $multiplier += $dominion->getBuildingPerkMultiplier('drafting');

        // Improvements
        $multiplier += $dominion->getImprovementPerkMultiplier('drafting');

        // Faction Perk
        $multiplier += $dominion->race->getPerkMultiplier('drafting');
        $multiplier += $dominion->race->getPerkMultiplier('drafting_per_defensive_failure') * $this->statsService->getStat($dominion, 'defense_failures');

        // Decree
        $multiplier += $dominion->getDecreePerkMultiplier('drafting');

        $growthFactor *= $multiplier;

        if ($this->getPopulationMilitaryPercentage($dominion) < $dominion->draft_rate)
        {
            $draftees += round($dominion->peasants * $growthFactor);
        }

        return $draftees;
    }

    /**
     * Returns the Dominion's population peasant percentage.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getPopulationPeasantPercentage(Dominion $dominion): float
    {
        if (($dominionPopulation = $this->getPopulation($dominion)) === 0) {
            return (float)0;
        }

        return (($dominion->peasants / $dominionPopulation) * 100);
    }

    /**
     * Returns the Dominion's population military percentage.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getPopulationMilitaryPercentage(Dominion $dominion): float
    {
        if (($dominionPopulation = $this->getPopulation($dominion)) === 0) {
            return 0;
        }

        return (($this->getPopulationMilitary($dominion) / $dominionPopulation) * 100);
    }

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

    public function getPopulationUnemployed(Dominion $dominion): int
    {
        return max(0, $dominion->peasants - $this->getEmploymentJobs($dominion));
    }

    /**
     * Returns the Dominion's employment percentage.
     *
     * If employment is at or above 100%, then one should strive to build more homes to get more peasants to the working
     * force. If employment is below 100%, then one should construct more buildings to employ idle peasants.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getEmploymentPercentage(Dominion $dominion): float
    {
        if ($dominion->peasants === 0) {
            return 0;
        }

        return (min(1, ($this->getPopulationEmployed($dominion) / $dominion->peasants)) * 100);
    }

    public function getFilledJobs(Dominion $dominion): int
    {
        return min($this->getEmploymentJobs($dominion), $dominion->peasants);
    }

    public function getUnfilledJobs(Dominion $dominion): int
    {
        return max(0, $this->getEmploymentJobs($dominion) - $this->getPopulationEmployed($dominion));
    }


    public function getAnnexedPeasants($dominion): int
    {
        $annexedPeasants = 0;

        if($this->spellCalculator->hasAnnexedDominions($dominion))
        {
            foreach($this->spellCalculator->getAnnexedDominions($dominion) as $annexedDominion)
            {
                $annexedPeasants += $annexedDominion->peasants;
            }
        }

        return $annexedPeasants;
    }

    public function getUnhousedDraftees(Dominion $dominion): int
    {
        $availableDrafteeHousing = $this->getAvailableHousingFromDrafteeSpecificBuildings($dominion) - $this->getDrafteesHousedInDrafteeSpecificBuildings($dominion);
        $newDraftees = $this->getPopulationDrafteeGrowth($dominion);

        $unhousedDraftees = $availableDrafteeHousing - $newDraftees;

        if($unhousedDraftees > 0)
        {
            return 0;
        }
        else
        {
            return abs($unhousedDraftees);
        }

        #return $unhousedDraftees;
    }

}
