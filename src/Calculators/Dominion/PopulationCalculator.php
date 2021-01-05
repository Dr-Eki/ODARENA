<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\QueueService;

class PopulationCalculator
{

    /** @var bool */
    protected $forTick = false;

    /*
     * PopulationCalculator constructor.
     */
    public function __construct(
        BuildingHelper $buildingHelper,
        ImprovementCalculator $improvementCalculator,
        LandCalculator $landCalculator,
        MilitaryCalculator $militaryCalculator,
        PrestigeCalculator $prestigeCalculator,
        QueueService $queueService,
        SpellCalculator $spellCalculator,
        UnitHelper $unitHelper,
        LandImprovementCalculator $landImprovementCalculator
    ) {
          $this->buildingHelper = app(BuildingHelper::class);
          $this->improvementCalculator = app(ImprovementCalculator::class);
          $this->landCalculator = app(LandCalculator::class);
          $this->landHelper = app(LandHelper::class);
          $this->landImprovementCalculator = app(LandImprovementCalculator::class);
          $this->militaryCalculator = app(MilitaryCalculator::class);
          $this->prestigeCalculator = app(PrestigeCalculator::class);
          $this->queueService = app(QueueService::class);
          $this->spellCalculator = app(SpellCalculator::class);
          $this->spellDamageCalculator = app(SpellDamageCalculator::class);
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

      # Draftees, Spies, Wizards, and Arch Mages always count.
      $military += $dominion->military_draftees;
      $military += $dominion->military_spies;
      $military += $dominion->military_wizards;
      $military += $dominion->military_archmages;

      # Units in training
      $military += $this->queueService->getTrainingQueueTotalByResource($dominion, 'military_spies');
      $military += $this->queueService->getTrainingQueueTotalByResource($dominion, 'military_wizards');
      $military += $this->queueService->getTrainingQueueTotalByResource($dominion, 'military_archmages');

      # Check each Unit for does_not_count_as_population perk.
      for ($unitSlot = 1; $unitSlot <= 4; $unitSlot++)
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
        return round(
            ($this->getMaxPopulationRaw($dominion) * $this->getMaxPopulationMultiplier($dominion))
            + $this->getUnitsHousedInForestHavens($dominion)
            + $this->getUnitsHousedInWizardGuilds($dominion)
            + $this->getUnitsHousedInBarracks($dominion)
        );
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

        // Constructed buildings
        foreach ($this->buildingHelper->getBuildingTypes($dominion) as $buildingType)
        {
            switch ($buildingType)
            {
                case 'home':
                    $housing = 30;
                    break;

                case 'barracks':
                    $housing = 0;
                    break;

                case 'wizard_guild':
                    $housing = 0;
                    break;

                case 'forest_haven':
                    $housing = 0;
                    break;

                case 'tissue':
                    $housing = 160;
                    break;

                case 'mycelia':
                    $housing = 30;
                    break;

                case 'ziggurat':
                    $housing = 18;
                    break;

                default:
                    $housing = 15;
                    break;
            }

            if($dominion->race->getPerkValue('extra_' . $buildingType . '_housing'))
            {
                $housing += $dominion->race->getPerkValue('extra_' . $buildingType . '_housing');
            }

            $population += ($dominion->{'building_' . $buildingType} * $housing);

        }

        // Constructing buildings
        $population += ($this->queueService->getConstructionQueueTotal($dominion) * 15);

        // Barren land
        $housingPerBarrenAcre = 5;
        $housingPerBarrenAcre += $dominion->race->getPerkValue('extra_barren_max_population');

        foreach ($this->landHelper->getLandTypes($dominion) as $landType)
        {
            $population += $this->landCalculator->getTotalBarrenLandByLandType($dominion, $landType) * ($housingPerBarrenAcre + $dominion->race->getPerkValue('extra_barren_' . $landType . '_max_population'));
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

        // Techs
        #$multiplier += $dominion->getTechPerkMultiplier('max_population');

        // Improvement: Keep
        $multiplier += $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'keep');

        // Improvement: Tissue (Growth)
        $multiplier += $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'tissue');

        // Land improvements
        $multiplier += $this->landImprovementCalculator->getPopulationBonus($dominion);

        if($dominion->race->getPerkValue('population_from_alchemy'))
        {
            $multiplierFromAlchemies = ($dominion->building_alchemy / $this->landCalculator->getTotalLand($dominion)) * $dominion->race->getPerkValue('population_from_alchemy');
            $multiplier += min(0.30, $multiplierFromAlchemies);
        }

        // Prestige Bonus
        $prestigeMultiplier = $this->prestigeCalculator->getPrestigeMultiplier($dominion);

        return (1 + $multiplier) * (1 + $dominion->getTechPerkMultiplier('max_population')) * (1 + $prestigeMultiplier);
    }

    /**
     * Returns the Dominion's max population military bonus.
     *
     * @param Dominion $dominion
     * @return float
     */
/*
    public function getMaxPopulationMilitaryBonus(Dominion $dominion): float
    {
        $housingFromBarracks = 0;
        $housingFromUnits = 0;

        $troopsPerBarracks = 36;

        # BARRACKS
        // Race
        if($dominion->race->getPerkValue('extra_barracks_housing'))
        {
            $troopsPerBarracks += $dominion->race->getPerkValue('extra_barracks_housing');
        }

        // Tech
        if($dominion->getTechPerkMultiplier('barracks_housing'))
        {
            $troopsPerBarracks *= (1 + $dominion->getTechPerkMultiplier('barracks_housing'));
        }

        $housingFromBarracks = $dominion->building_barracks * $troopsPerBarracks;

        # UNITS: Look for houses_military_units
        for ($slot = 1; $slot <= 4; $slot++)
        {
            if($dominion->race->getUnitPerkValueForUnitSlot($slot, 'houses_military_units'))
            {
                $housingFromUnits += $this->militaryCalculator->getTotalUnitsForSlot($dominion, $slot) * $dominion->race->getUnitPerkValueForUnitSlot($slot, 'houses_military_units');
            }
        }

        $militaryHousing = $housingFromBarracks + $housingFromUnits;
        #$militaryHousing -= $this->getUnitsHousedInForestHavens($dominion);
        #$militaryHousing -= $this->getUnitsHousedInWizardGuilds($dominion);

        return min(
            ($this->getPopulationMilitary($dominion) - $dominion->military_draftees),
            $militaryHousing
          );
    }
*/

    /*
    *   Calculate how many units can be fit in this Dominion's Barracks.
    */
    public function getAvailableHousingFromBarracks(Dominion $dominion): int
    {
        $unitsPerBarracks = 36;

        if($dominion->race->getPerkValue('extra_barracks_housing'))
        {
            $unitsPerBarracks += $dominion->race->getPerkValue('extra_barracks_housing');
        }

        if($dominion->getTechPerkMultiplier('barracks_housing'))
        {
            $unitsPerBarracks *= (1 + $dominion->getTechPerkMultiplier('barracks_housing'));
        }

        return ($dominion->building_barracks * $unitsPerBarracks) + $this->getAvailableHousingFromUnits($dominion);
    }

    /*
    *   Calculate how many units can be fit in this Dominion's Forest Havens.
    */
    public function getAvailableHousingFromForestHavens(Dominion $dominion): int
    {
        $spyUnitsPerForestHaven = 40;

        $spyUnitsPerForestHaven *= (1 + $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'hideouts'));

        return ($dominion->building_forest_haven * $spyUnitsPerForestHaven);
    }

    /*
    *   Calculate how many units can be fit in this Dominion's Wizard Guilds.
    */
    public function getAvailableHousingFromWizardGuilds(Dominion $dominion): int
    {
        $wizUnitsPerWizardGuild = 40;

        #$wizUnitsPerWizardGuild *= (1 + $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'towers'));
        $wizUnitsPerWizardGuild *= (1 + $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'spires'));

        return ($dominion->building_wizard_guild * $wizUnitsPerWizardGuild);
    }

    /*
    *   Calculate how many units can be fit in this Dominion's Units that can house military units.
    *   This is added to getAvailableHousingFromBarracks().
    */
    public function getAvailableHousingFromUnits(Dominion $dominion): int
    {
        $housingFromUnits = 0;
        for ($slot = 1; $slot <= 4; $slot++)
        {
            if($dominion->race->getUnitPerkValueForUnitSlot($slot, 'houses_military_units'))
            {
                $housingFromUnits += $this->militaryCalculator->getTotalUnitsForSlot($dominion, $slot) * $dominion->race->getUnitPerkValueForUnitSlot($slot, 'houses_military_units');
            }
        }

        return $housingFromUnits;
    }

    /*
    *   Calculate how many units live in Barracks.
    *   Units start to live in barracks as soon as their military training begins.
    *   Spy and wiz units prefer to live in FHs or WGs, and will only live in Barracks if FH/WG are full or unavailable.
    */
    public function getUnitsHousedInBarracks(Dominion $dominion): int
    {
        $units = 0;
        $units -= $this->getUnitsHousedInForestHavens($dominion);
        $units -= $this->getUnitsHousedInWizardGuilds($dominion);
        $units += $dominion->military_spies;
        $units += $dominion->military_wizards;
        $units += $dominion->military_archmages;
        $units += $this->queueService->getTrainingQueueTotalByResource($dominion, "military_spies");
        $units += $this->queueService->getTrainingQueueTotalByResource($dominion, "military_wizards");
        $units += $this->queueService->getTrainingQueueTotalByResource($dominion, "military_archmages");

        for ($slot = 1; $slot <= 4; $slot++)
        {
            if($dominion->race->getUnitPerkValueForUnitSlot($slot, 'does_not_count_as_population') !== 1)
            {
                $units += $this->militaryCalculator->getTotalUnitsForSlot($dominion, $slot);
                $units += $this->queueService->getTrainingQueueTotalByResource($dominion, "military_unit{$slot}");
            }
        }

        $units = max(0, $units);

        return min($units, $this->getAvailableHousingFromBarracks($dominion));
    }

    /*
    *   Calculate how many units live in Forest Havens.
    *   Spy units start to live in FHs as soon as their military training begins.
    */
    public function getUnitsHousedInForestHavens(Dominion $dominion): int
    {
        $spyUnits = $dominion->military_spies;
        $spyUnits += $this->queueService->getTrainingQueueTotalByResource($dominion, "military_spies");

        for ($slot = 1; $slot <= 4; $slot++)
        {
            if(($dominion->race->getUnitPerkValueForUnitSlot($slot, 'counts_as_spy_offense') or $dominion->race->getUnitPerkValueForUnitSlot($slot, 'counts_as_spy_defense')) and $dominion->race->getUnitPerkValueForUnitSlot($slot, 'does_not_count_as_population') !== 1)
            {
                if(!$dominion->race->getUnitPerkValueForUnitSlot($slot, 'counts_as_wizard_offense') and !$dominion->race->getUnitPerkValueForUnitSlot($slot, 'counts_as_wizard_defense'))
                {
                    $spyUnits += $this->militaryCalculator->getTotalUnitsForSlot($dominion, $slot);
                    $spyUnits += $this->queueService->getTrainingQueueTotalByResource($dominion, "military_unit{$slot}");
                }
            }
        }

        return min($spyUnits, $this->getAvailableHousingFromForestHavens($dominion));
    }

    /*
    *   Calculate how many units live in Wizard Guilds.
    *   Wiz units start to live in WGs as soon as their military training begins.
    */
    public function getUnitsHousedInWizardGuilds(Dominion $dominion): int
    {
        $wizUnits = $dominion->military_wizards;
        $wizUnits += $dominion->military_archmages;
        $wizUnits += $this->queueService->getTrainingQueueTotalByResource($dominion, "military_wizards");
        $wizUnits += $this->queueService->getTrainingQueueTotalByResource($dominion, "military_archmages");

        for ($slot = 1; $slot <= 4; $slot++)
        {
            if(($dominion->race->getUnitPerkValueForUnitSlot($slot, 'counts_as_wizard_offense') or $dominion->race->getUnitPerkValueForUnitSlot($slot, 'counts_as_wizard_defense')) and $dominion->race->getUnitPerkValueForUnitSlot($slot, 'does_not_count_as_population') !== 1)
            {
                if(!$dominion->race->getUnitPerkValueForUnitSlot($slot, 'counts_as_spy_offense') and !$dominion->race->getUnitPerkValueForUnitSlot($slot, 'counts_as_spy_defense'))
                {
                    $wizUnits += $this->militaryCalculator->getTotalUnitsForSlot($dominion, $slot);
                    $wizUnits += $this->queueService->getTrainingQueueTotalByResource($dominion, "military_unit{$slot}");
                }
            }
        }

        return min($wizUnits, $this->getAvailableHousingFromWizardGuilds($dominion));
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

        $growthFactor = 0;
        // Growth only if food > 0 or race doesn't eat food.
        if($dominion->resource_food > 0 or $dominion->race->getPerkMultiplier('food_consumption') == -1)
        {
          $growthFactor = 0.03;
        }

        // Population births
        $birth = (($dominion->peasants - $this->getPopulationDrafteeGrowth($dominion)) * $growthFactor);

        // In case of 0 peasants:
        if($dominion->peasants === 0)
        {
            $birth = ($this->getMaxPopulation($dominion) - $this->getPopulation($dominion) - $this->getPopulationDrafteeGrowth($dominion)) * $growthFactor;
        }

        return $birth;
    }

    /**
     * Demon: Returns the amount of peasants sacrificed.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getPeasantsSacrificed(Dominion $dominion): int
    {
        $peasantsSacrificed = 0;
        for ($unitSlot = 1; $unitSlot <= 4; $unitSlot++)
        {
          if ($dominion->race->getUnitPerkValueForUnitSlot($unitSlot, 'sacrifices_peasants'))
          {
            $sacrificingUnits = $dominion->{"military_unit".$unitSlot};
            $peasantsSacrificedPerUnit = $dominion->race->getUnitPerkValueForUnitSlot($unitSlot, 'sacrifices_peasants');
            $peasantsSacrificed += floor($sacrificingUnits * $peasantsSacrificedPerUnit);
          }
        }
        return min($dominion->peasants, $peasantsSacrificed);
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
        $multiplier += (($dominion->building_temple / $this->landCalculator->getTotalLand($dominion)) * 6);

        # Look for population_growth in units
        for ($slot = 1; $slot <= 4; $slot++)
        {
            if($dominion->race->getUnitPerkValueForUnitSlot($slot, 'population_growth'))
            {
                $multiplier += ($dominion->{"military_unit".$slot} / $this->getMaxPopulation($dominion)) * $dominion->race->getUnitPerkValueForUnitSlot($slot, 'population_growth');
            }
        }

        // Spells
        $multiplier += $this->spellCalculator->getPassiveSpellPerkMultiplier($dominion, 'population_growth');

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

        $roomForPeasants = ($this->getMaxPopulation($dominion) - $this->getPopulation($dominion) - $this->getPopulationDrafteeGrowth($dominion));

        $currentPopulationChange = ($this->getPopulationBirth($dominion) - $this->getPopulationDrafteeGrowth($dominion));

        $maximumPopulationChange = min($roomForPeasants, $currentPopulationChange);

        return max($maximumPeasantDeath, $maximumPopulationChange);

         /*
        =MAX(
            -5% * peasants - drafteegrowth,
            -5% * peasants - drafteegrowth, // MAX PEASANT DEATH
            MIN(
                maxpop(nexthour) - (peasants - military) - drafteesgrowth,
                moddedbirth - drafteegrowth
                maxpop(nexthour) - (peasants - military) - drafteesgrowth, // MAX SPACE FOR PEASANTS
                moddedbirth - drafteegrowth // CURRENT BIRTH RATE
            )
        )
        */
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

        if($dominion->race->getPerkValue('gryphon_nests_drafts'))
        {
            if ($this->getPopulationMilitaryPercentage($dominion) < $dominion->draft_rate)
            {
                $draftees += round($dominion->building_gryphon_nest * 0.2);
            }

            $draftees = min($draftees, $dominion->peasants);
        }
        else
        {

            // Values (percentages)
            $growthFactor = 0.01;

            // Racial Spell: Swarming (Ants)
            $growthFactor *= 1 + $this->spellCalculator->getPassiveSpellPerkMultiplier($dominion, 'drafting');

            // Advancement: Conscription
            $growthFactor *= 1 + $dominion->getTechPerkMultiplier('drafting');

            if ($this->getPopulationMilitaryPercentage($dominion) < $dominion->draft_rate)
            {
                $draftees += round($dominion->peasants * $growthFactor);
            }

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

        $fromBuildings = 0;
        $fromUnits = 0;

        $jobsPerBuilding = 20;

        $jobsPerBuilding *= 1 + $dominion->getTechPerkMultiplier('jobs_per_building');

        $fromBuildings += ($jobsPerBuilding * (
                $dominion->building_alchemy
                + $dominion->building_farm
                + $dominion->building_smithy
                + $dominion->building_masonry
                + $dominion->building_ore_mine
                + $dominion->building_gryphon_nest
                + $dominion->building_tower
                + $dominion->building_wizard_guild
                + $dominion->building_temple
                + $dominion->building_gem_mine
                + $dominion->building_school
                + $dominion->building_lumberyard
                + $dominion->building_forest_haven
                + $dominion->building_factory
                + $dominion->building_guard_tower
                + $dominion->building_shrine
                + $dominion->building_dock
            ));

        for ($slot = 1; $slot <= 4; $slot++)
        {
            if($dominion->race->getUnitPerkValueForUnitSlot($slot, 'provides_jobs'))
            {
                $fromUnits += $this->militaryCalculator->getTotalUnitsForSlot($dominion, $slot) * $dominion->race->getUnitPerkValueForUnitSlot($slot, 'provides_jobs');
            }
        }

        $jobsFromBarren = 0;
        $jobsPerBarrenAcre = 0;
        $jobsPerBarrenAcre += $dominion->race->getPerkValue('extra_barren_jobs');

        foreach ($this->landHelper->getLandTypes($dominion) as $landType)
        {
            $jobsFromBarren += $this->landCalculator->getTotalBarrenLandByLandType($dominion, $landType) * ($jobsPerBarrenAcre + $dominion->race->getPerkValue('extra_barren_' . $landType . '_jobs'));
        }

        # Does not include Homes, Barracks, Ziggurats, Tissue, and Mycelia
        return ($fromBuildings + $fromUnits + $jobsFromBarren);
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
}
