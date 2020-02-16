<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\QueueService;

class PopulationCalculator
{
    /** @var BuildingHelper */
    protected $buildingHelper;

    /** @var ImprovementCalculator */
    protected $improvementCalculator;

    /** @var LandCalculator */
    protected $landCalculator;

    /** @var MilitaryCalculator */
    protected $militaryCalculator;

    /** @var PrestigeCalculator */
    private $prestigeCalculator;

    /** @var QueueService */
    protected $queueService;

    /** @var SpellCalculator */
    protected $spellCalculator;

    /** @var UnitHelper */
    protected $unitHelper;

    /** @var bool */
    protected $forTick = false;

    /**
     * PopulationCalculator constructor.
     *
     * @param BuildingHelper $buildingHelper
     * @param ImprovementCalculator $improvementCalculator
     * @param LandCalculator $landCalculator
     * @param MilitaryCalculator $militaryCalculator
     * @param PrestigeCalculator $prestigeCalculator
     * @param QueueService $queueService
     * @param SpellCalculator $spellCalculator
     * @param UnitHelper $unitHelper
     */
    public function __construct(
        BuildingHelper $buildingHelper,
        ImprovementCalculator $improvementCalculator,
        LandCalculator $landCalculator,
        MilitaryCalculator $militaryCalculator,
        PrestigeCalculator $prestigeCalculator,
        QueueService $queueService,
        SpellCalculator $spellCalculator,
        UnitHelper $unitHelper
    ) {
        $this->buildingHelper = $buildingHelper;
        $this->improvementCalculator = $improvementCalculator;
        $this->landCalculator = $landCalculator;
        $this->militaryCalculator = $militaryCalculator;
        $this->prestigeCalculator = $prestigeCalculator;
        $this->queueService = $queueService;
        $this->spellCalculator = $spellCalculator;
        $this->unitHelper = $unitHelper;
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
          $military += $this->militaryCalculator->getTotalUnitsForSlot($dominion, $unitSlot);
          $military += $this->queueService->getTrainingQueueTotalByResource($dominion, "military_unit{$unitSlot}");
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
            + $this->getMaxPopulationMilitaryBonus($dominion)
        );
    }

    /**
     * Returns the Dominion's raw max population.
     *
     * Maximum population is determined by housing in homes, other buildings (sans barracks) and barren land.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getMaxPopulationRaw(Dominion $dominion): int
    {
        $population = 0;

        // Constructed buildings
        foreach ($this->buildingHelper->getBuildingTypes($dominion) as $buildingType) {
            switch ($buildingType) {
                case 'home':
                    $housing = 30;
                    break;

                case 'barracks':
                    $housing = 0;
                    break;

                case 'ziggurat':
                    $housing = 20;
                    break;

                case 'tissue':
                    $housing = 160;
                    break;

                case 'mycelia':
                    $housing = 30;
                    break;

                default:
                    $housing = 15;
                    break;
            }

            $population += ($dominion->{'building_' . $buildingType} * $housing);
        }

        // Constructing buildings
        $population += ($this->queueService->getConstructionQueueTotal($dominion) * 15);

        // Barren land
        $housingPerBarrenLand = (5 + $dominion->race->getPerkValue('extra_barren_max_population'));
        $population += ($this->landCalculator->getTotalBarrenLand($dominion) * $housingPerBarrenLand);

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
        $multiplier += $dominion->getTechPerkMultiplier('max_population');

        // Improvement: Keep
        $multiplier += $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'keep');

        // Improvement: Tissue (Growth)
        $multiplier += $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'tissue');

        // Beastfolk: Forest increases population
        if($dominion->race->name == 'Beastfolk')
        {
          $multiplier += ($dominion->{'land_forest'} / $this->landCalculator->getTotalLand($dominion));
        }

        // Prestige Bonus
        $prestigeMultiplier = $this->prestigeCalculator->getPrestigeMultiplier($dominion);

        return (1 + $multiplier) * (1 + $prestigeMultiplier);
    }

    /**
     * Returns the Dominion's max population military bonus.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getMaxPopulationMilitaryBonus(Dominion $dominion): float
    {

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

        return min(
            ($this->getPopulationMilitary($dominion) - $dominion->military_draftees),
            ($dominion->building_barracks * 36)
        );
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
        // Growth only if food > 0 or race doesn't eat food.
        if($dominion->resource_food > 0 or $dominion->race->getPerkMultiplier('food_consumption') == -1)
        {
          $growthFactor = 0.03;
        }

        // Population births
        $birth = (($dominion->peasants - $this->getPopulationDrafteeGrowth($dominion)) * $growthFactor);

        return $birth;
    }

    /**
     * Demon: Returns the amount of peasants sacrificed.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getPeasantsSacrified(Dominion $dominion): int
    {
        $peasantsSacrified = 0;
        for ($unitSlot = 1; $unitSlot <= 4; $unitSlot++)
        {
          if ($dominion->race->getUnitPerkValueForUnitSlot($unitSlot, 'sacrifices_peasants'))
          {
            $sacrificingUnits = $dominion->{"military_unit".$unitSlot};
            $peasantsSacrifiedPerUnit = $dominion->race->getUnitPerkValueForUnitSlot($unitSlot, 'sacrifices_peasants');
            $peasantsSacrified += floor($sacrificingUnits * $peasantsSacrifiedPerUnit);
          }
        }
        return min($dominion->peasants, $peasantsSacrified);
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

        # SPELLS

        // Spell: Harmony (+50%)
        if ($this->spellCalculator->isSpellActive($dominion, 'harmony'))
        {
            $multiplier += 0.50;
        }

        // Spell: Rainy Season (+100%)
        if ($this->spellCalculator->isSpellActive($dominion, 'rainy_season'))
        {
            $multiplier += 1.00;
        }

        // Spell: Plague (-25%)
        if ($this->spellCalculator->isSpellActive($dominion, 'plague'))
        {
            $multiplier -= 0.25;
        }

        // Spell: Great Fever (-100%)
        if ($this->spellCalculator->isSpellActive($dominion, 'great_fever'))
        {
            $multiplier -= 0.25;
        }

        # /SPELLS

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
        $currentPopulationChange = ($this->getPopulationBirth($dominion) - $this->getPopulationDrafteeGrowth($dominion) - $this->getPeasantsSacrified($dominion));

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

        // Values (percentages)
        $growthFactor = 0.01;

        // Racial Spell: Swarming (Ants)
        if ($this->spellCalculator->isSpellActive($dominion, 'swarming'))
        {
            $growthFactor = 0.02;
        }

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
        # Does not include Homes, Barracks, Ziggurats, Tissue, and Mycelia
        return (20 * (
                $dominion->building_alchemy
                + $dominion->building_farm
                + $dominion->building_smithy
                + $dominion->building_masonry
                + $dominion->building_ore_mine
                + $dominion->building_gryphon_nest
                + $dominion->building_tower
                + $dominion->building_wizard_guild
                + $dominion->building_temple
                + $dominion->building_diamond_mine
                + $dominion->building_school
                + $dominion->building_lumberyard
                + $dominion->building_forest_haven
                + $dominion->building_factory
                + $dominion->building_guard_tower
                + $dominion->building_shrine
                + $dominion->building_dock
            ));
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
