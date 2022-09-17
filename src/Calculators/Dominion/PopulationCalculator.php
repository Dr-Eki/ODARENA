<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Helpers\RaceHelper;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Services\Dominion\StatsService;

class PopulationCalculator
{

    /** @var bool */
    protected $forTick = false;

    /*
     * PopulationCalculator constructor.
     */
    public function __construct() {
          $this->buildingHelper = app(BuildingHelper::class);
          $this->improvementCalculator = app(ImprovementCalculator::class);
          $this->landCalculator = app(LandCalculator::class);
          $this->landHelper = app(LandHelper::class);
          $this->landImprovementCalculator = app(LandImprovementCalculator::class);
          $this->militaryCalculator = app(MilitaryCalculator::class);
          $this->prestigeCalculator = app(PrestigeCalculator::class);
          $this->queueService = app(QueueService::class);
          $this->raceHelper = app(RaceHelper::class);
          $this->spellCalculator = app(SpellCalculator::class);
          $this->spellDamageCalculator = app(SpellDamageCalculator::class);
          $this->statsService = app(StatsService::class);
          $this->unitHelper = app(UnitHelper::class);
    }

    /**
     * Toggle if this calculator should include the following hour's resources.
     */
    public function setForTick(bool $value)
    {
        $this->forTick = $value;
        $this->militaryCalculator->setForTick($value);
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

      # Draftees, Spies, Wizards, and Arch Mages
      $military += $this->militaryCalculator->getTotalUnitsForSlot($dominion, 'draftees');
      $military += $this->militaryCalculator->getTotalUnitsForSlot($dominion, 'spies');
      $military += $this->militaryCalculator->getTotalUnitsForSlot($dominion, 'wizards');
      $military += $this->militaryCalculator->getTotalUnitsForSlot($dominion, 'archmages');

      # Units in training
      $military += $this->queueService->getTrainingQueueTotalByResource($dominion, 'military_spies');
      $military += $this->queueService->getTrainingQueueTotalByResource($dominion, 'military_wizards');
      $military += $this->queueService->getTrainingQueueTotalByResource($dominion, 'military_archmages');

      # Check each Unit for does_not_count_as_population perk.
      for ($unitSlot = 1; $unitSlot <= $dominion->race->units->count(); $unitSlot++)
      {
          if (!$dominion->race->getUnitPerkValueForUnitSlot($unitSlot, 'does_not_count_as_population'))
          {
              $unitAmount = $this->militaryCalculator->getTotalUnitsForSlot($dominion, $unitSlot);
              $unitAmount += $this->queueService->getTrainingQueueTotalByResource($dominion, "military_unit{$unitSlot}");

              # Check for housing_count
              if($nonStandardHousing = $dominion->race->getUnitPerkValueForUnitSlot($unitSlot, 'housing_count'))
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

         return $maxPopulation;
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
        $population += ($this->queueService->getConstructionQueueTotal($dominion) * 15);

        // Barren land
        $housingPerBarrenAcre = 5;
        $housingPerBarrenAcre += $dominion->race->getPerkValue('extra_barren_max_population');
        $housingPerBarrenAcre += $dominion->race->getPerkValue('extra_barren_housing_per_victory') * $this->statsService->getStat($dominion, 'invasion_victories');
        $housingPerBarrenAcre += $dominion->race->getPerkValue('extra_barren_housing_per_net_victory') * max(($this->statsService->getStat($dominion, 'invasion_victories') - $this->statsService->getStat($dominion, 'defense_failures')), 0);

        foreach ($this->landHelper->getLandTypes($dominion) as $landType)
        {
            if($dominion->race->getPerkValue('barren_housing_only_on_water') and $landType !== 'water')
            {
                $population += 0;
            }
            else
            {
                $population += $this->landCalculator->getTotalBarrenLandByLandType($dominion, $landType) * ($housingPerBarrenAcre + $dominion->race->getPerkValue('extra_barren_' . $landType . '_max_population'));
            }
        }

        return $population;
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

        // Land improvements
        $multiplier += $dominion->getLandImprovementPerkMultiplier('max_population');

        // Deity
        $multiplier += $dominion->getDeityPerkMultiplier('max_population');

        // Decree
        $multiplier += $dominion->getDecreePerkMultiplier('max_population');

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
        $militaryHousingMultiplier += $dominion->race->getPerkMultiplier('extra_barracks_housing');
        $militaryHousingMultiplier += $dominion->getAdvancementPerkMultiplier('barracks_housing');

        $militaryHousingMultiplier += $dominion->race->getPerkMultiplier('military_housing');
        $militaryHousingMultiplier += $dominion->getAdvancementPerkMultiplier('military_housing');
        $militaryHousingMultiplier += $dominion->getDecreePerkMultiplier('military_housing');

        $militaryHousing = 0;
        $militaryHousing += $dominion->getBuildingPerkValue('military_housing');
        $militaryHousing += $dominion->getBuildingPerkValue('military_housing_increasing');
        $militaryHousing *= $militaryHousingMultiplier;
        $militaryHousing += $this->getAvailableHousingFromUnits($dominion);

        return round($militaryHousing);
        
    }

    /*
    *   Calculate how many units can be fit in this Dominion's Barracks.
    */
    public function getAvailableHousingFromUnitSpecificBuildings(Dominion $dominion, ?int $slot = null): int
    {
        $unitSpecificBuildingHousing = 0;

        if($slot)
        {
            $unitSpecificBuildingHousing += $dominion->getBuildingPerkValue($dominion->race->key . '_unit' . $slot . '_housing');

        }
        else
        {
            for ($slot = 1; $slot <= $dominion->race->units->count(); $slot++)
            {
                $unitSpecificBuildingHousing += $dominion->getBuildingPerkValue($dominion->race->key . '_unit' . $slot . '_housing');
            }
        }

        $multiplier = 1;
        $multiplier += $dominion->getImprovementPerkMultiplier('unit_specific_housing');
        $multiplier += $dominion->getDeityPerkMultiplier('unit_specific_housing');

        $unitSpecificBuildingHousing *= $multiplier;

        return $unitSpecificBuildingHousing;
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
                $housingFromUnits += $this->militaryCalculator->getTotalUnitsForSlot($dominion, $slot) * $housesMilitaryUnitsPerk;
            }

            for ($housingPerkSlot = 1; $housingPerkSlot <= $dominion->race->units->count(); $housingPerkSlot++)
            {
                # Unit cannot house itself (e.g. norse_unit1_housing)
                if($housingPerkSlot !== $slot)
                {
                    if($housesSpecificMilitaryUnitPerk = $dominion->race->getUnitPerkValueForUnitSlot($slot, ($raceKey . '_unit' . $housingPerkSlot . '_housing')))
                    {
                        $housingFromUnits += $this->militaryCalculator->getTotalUnitsForSlot($dominion, $slot) * $housesSpecificMilitaryUnitPerk;
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
        $units = 0;
        #$units -= $this->getUnitsHousedInUnits($dominion);
        $units -= $this->getUnitsHousedInUnitSpecificBuildings($dominion);
        $units -= $this->getUnitsHousedInUnitAttributeSpecificBuildings($dominion);
        $units -= $this->getDrafteesHousedInDrafteeSpecificBuildings($dominion);
        $units -= $this->getUnitsHousedInSpyHousing($dominion);
        $units -= $this->getUnitsHousedInWizardHousing($dominion);
        $units += $dominion->military_spies;
        $units += $dominion->military_wizards;
        $units += $dominion->military_archmages;
        $units += $this->queueService->getTrainingQueueTotalByResource($dominion, "military_spies");
        $units += $this->queueService->getTrainingQueueTotalByResource($dominion, "military_wizards");
        $units += $this->queueService->getTrainingQueueTotalByResource($dominion, "military_archmages");

        for ($slot = 1; $slot <= $dominion->race->units->count(); $slot++)
        {
            if(!$dominion->race->getUnitPerkValueForUnitSlot($slot, 'does_not_count_as_population'))
            {
                $units += $this->militaryCalculator->getTotalUnitsForSlot($dominion, $slot);
                $units += $this->queueService->getTrainingQueueTotalByResource($dominion, "military_unit{$slot}");
            }
        }

        $units += $this->militaryCalculator->getTotalUnitsForSlot($dominion, 'draftees');
        $units += $this->queueService->getTrainingQueueTotalByResource($dominion, "military_draftees");

        $units = max(0, $units);

        return min($units, $this->getAvailableHousingFromMilitaryHousing($dominion));
    }


        /*
        *   Calculate how many units live in Barracks.
        *   Units start to live in barracks as soon as their military training begins.
        *   Spy and wiz units prefer to live in FHs or WGs, and will only live in Barracks if FH/WG are full or unavailable.
        */
        public function getUnitsHousedInUnitSpecificBuildings(Dominion $dominion, ?int $housedSlot = null): int
        {

            $units = 0;

            if($housedSlot !== null)
            {
                $slotUnits = 0;
                if($unitSpecificBuildingHousing = $dominion->getBuildingPerkValue($dominion->race->key . '_unit' . $housedSlot . '_housing'))
                {
                    if(!$dominion->race->getUnitPerkValueForUnitSlot($housedSlot, 'does_not_count_as_population'))
                    {
                        $slotUnits += $this->militaryCalculator->getTotalUnitsForSlot($dominion, $housedSlot);
                        $slotUnits += $this->queueService->getTrainingQueueTotalByResource($dominion, "military_unit{$housedSlot}");
                    }

                    $units += min($slotUnits, $this->getAvailableHousingFromUnitSpecificBuildings($dominion));
                }
            }
            else
            {
                for ($slot = 1; $slot <= $dominion->race->units->count(); $slot++)
                {
                    $slotUnits = 0;
                    if($unitSpecificBuildingHousing = $dominion->getBuildingPerkValue($dominion->race->key . '_unit' . $slot . '_housing'))
                    {
                        if(!$dominion->race->getUnitPerkValueForUnitSlot($slot, 'does_not_count_as_population'))
                        {
                            $slotUnits += $this->militaryCalculator->getTotalUnitsForSlot($dominion, $slot);
                            $slotUnits += $this->queueService->getTrainingQueueTotalByResource($dominion, "military_unit{$slot}");
                        }

                        $units += min($slotUnits, $this->getAvailableHousingFromUnitSpecificBuildings($dominion));
                    }
                }
            }
            
            return min($units, $this->getAvailableHousingFromUnitSpecificBuildings($dominion, $housedSlot));
        }

        /*
        *   Calculate how many units live in Barracks.
        *   Units start to live in barracks as soon as their military training begins.
        *   Spy and wiz units prefer to live in FHs or WGs, and will only live in Barracks if FH/WG are full or unavailable.
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
                            $slotUnits += $this->militaryCalculator->getTotalUnitsForSlot($dominion, $unit->slot);
                            $slotUnits += $this->queueService->getTrainingQueueTotalByResource($dominion, "military_unit{$unit->slot}");
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
        $draftees = $dominion->military_draftees;
        $draftees += $this->militaryCalculator->getTotalUnitsForSlot($dominion, 'draftees');
        $draftees += $this->queueService->getTrainingQueueTotalByResource($dominion, "military_draftees"); # From stun?
        return min($dominion->military_draftees, $this->getAvailableHousingFromDrafteeSpecificBuildings($dominion));
    }

    /*
    *   Calculate how many units live in Forest Havens.
    *   Spy units start to live in FHs as soon as their military training begins.
    */
    public function getUnitsHousedInSpyHousing(Dominion $dominion): int
    {
        $spyUnits = $this->militaryCalculator->getTotalUnitsForSlot($dominion, 'spies');
        $spyUnits += $this->queueService->getTrainingQueueTotalByResource($dominion, "military_spies");

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
                    $spyUnits += $this->militaryCalculator->getTotalUnitsForSlot($dominion, $slot);
                    $spyUnits += $this->queueService->getTrainingQueueTotalByResource($dominion, "military_unit{$slot}");
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
        $wizUnits = $this->militaryCalculator->getTotalUnitsForSlot($dominion, 'wizards');
        $wizUnits += $this->militaryCalculator->getTotalUnitsForSlot($dominion, 'archmages');
        $wizUnits += $this->queueService->getTrainingQueueTotalByResource($dominion, "military_wizards");
        $wizUnits += $this->queueService->getTrainingQueueTotalByResource($dominion, "military_archmages");

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
                    $wizUnits += $this->militaryCalculator->getTotalUnitsForSlot($dominion, $slot);
                    $wizUnits += $this->queueService->getTrainingQueueTotalByResource($dominion, "military_unit{$slot}");
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
        $populationBirth = round($this->getPopulationBirthRaw($dominion) * $this->getPopulationBirthMultiplier($dominion));
        return $populationBirth;
    }
    /**
     * Returns the Dominions raw population birth.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getPopulationBirthRaw(Dominion $dominion): float
    {

        $growthFactor = 0.03 * $this->militaryCalculator->getMoraleMultiplier($dominion);

        // Population births
        $birth = ($dominion->peasants - $this->getUnhousedDraftees($dominion)) * $growthFactor;

        // In case of 0 peasants:
        if($dominion->peasants === 0)
        {
            #$birth = ($this->getMaxPopulation($dominion) - $this->getPopulation($dominion) - $this->getPopulationDrafteeGrowth($dominion)) * $growthFactor;
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

        // Temples
        $multiplier += $dominion->getBuildingPerkMultiplier('population_growth');

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

        # Look for population_growth in units
        for ($slot = 1; $slot <= $dominion->race->units->count(); $slot++)
        {
            if($unitPopulationGrowthPerk = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'population_growth'))
            {
                $multiplier += ($dominion->{"military_unit".$slot} / $this->getMaxPopulation($dominion)) * $unitPopulationGrowthPerk;
            }
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

        if($dominion->getSpellPerkValue('no_drafting') or $dominion->race->getPerkValue('no_drafting') or $dominion->draft_rate == 0)
        {
            return $draftees;
        }

        // Values (percentages)
        $growthFactor = 0.01;

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
        $multiplier += $dominion->race->getPerkValue('drafting');

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
            if($dominion->race->getUnitPerkValueForUnitSlot($slot, 'provides_jobs'))
            {
                $jobs += $this->militaryCalculator->getTotalUnitsForSlot($dominion, $slot) * $dominion->race->getUnitPerkValueForUnitSlot($slot, 'provides_jobs');
            }
        }

        foreach ($this->landHelper->getLandTypes($dominion) as $landType)
        {
            $jobs += $this->landCalculator->getTotalBarrenLandByLandType($dominion, $landType) * ($dominion->race->getPerkValue('extra_barren_' . $landType . '_jobs'));
        }

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
