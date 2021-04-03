<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Models\Dominion;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Spell;
use OpenDominion\Services\Dominion\GuardMembershipService;
use OpenDominion\Services\Dominion\QueueService;

use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\LandImprovementCalculator;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Helpers\LandHelper;

class ProductionCalculator
{
    public function __construct()
    {
        $this->improvementCalculator = app(ImprovementCalculator::class);
        $this->landCalculator = app(LandCalculator::class);
        $this->landHelper = app(LandHelper::class);
        $this->populationCalculator = app(PopulationCalculator::class);
        $this->prestigeCalculator = app(PrestigeCalculator::class);
        $this->spellCalculator = app(SpellCalculator::class);
        $this->guardMembershipService = app(GuardMembershipService::class);
        $this->militaryCalculator = app(MilitaryCalculator::class);
        $this->landImprovementCalculator = app(LandImprovementCalculator::class);
        $this->unitHelper = app(UnitHelper::class);
        $this->spellDamageCalculator = app(SpellDamageCalculator::class);
        $this->queueService = app(QueueService::class);
    }

    /**
     * Returns the Dominion's gold production.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getGoldProduction(Dominion $dominion): int
    {
        $gold = 0;

        $gold = floor($this->getGoldProductionRaw($dominion) * $this->getGoldProductionMultiplier($dominion));

        return max(0,$gold);
    }

    /**
     * Returns the Dominion's raw gold production.
     *
     * Gold is produced by:
     * - Employed Peasants (2.7 per)
     * - Building: Alchemy (45 per, or 60 with Alchemist Flame racial spell active)
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getGoldProductionRaw(Dominion $dominion): float
    {
        $gold = 0;

        if($dominion->getSpellPerkValue('no_gold_production') or $dominion->race->getPerkValue('no_gold_production') or ($dominion->race->getPerkValue('peasants_produce_food') and $dominion->race->name === 'Growth'))
        {
            return $gold;
        }

        // Values
        $peasantTax = 2.7;

        // Race specialty: Swarm peasants
        if($dominion->race->getPerkValue('unemployed_peasants_produce_gold'))
        {
            $gold += $dominion->peasants * $dominion->race->getPerkValue('unemployed_peasants_produce_gold');
        }
        // Myconid: no plat from peasants
        elseif($dominion->race->name == 'Myconid')
        {
            $gold = 0;
        }
        else
        {
            // Peasant Tax
            $gold += ($this->populationCalculator->getPopulationEmployed($dominion) * $peasantTax);
        }

        // Building: Alchemy
        $gold += $dominion->getBuildingPerkValue('gold_production');

        // Unit Perk: Production Bonus
        $gold += $dominion->getUnitPerkProductionBonus('gold_production');

        // Unit Perk Production Reduction
        $upkeep = $dominion->getUnitPerkProductionBonus('upkeep_gold');

        // Unit Perk: production_from_title
        $gold += $dominion->getUnitPerkProductionBonusFromTitle('gold');

        $gold = max(0, $gold-$upkeep);

        return $gold;
    }

    /**
     * Returns the Dominion's gold production multiplier.
     *
     * Gold production is modified by:
     * - Racial Bonus
     * - Spell: Midas Touch (+10%)
     * - Improvement: Science
     * - Guard Tax (-2%)
     * - Tech: Treasure Hunt (+12.5%) or Banker's Foresight (+5%)
     *
     * Gold production multiplier is capped at +50%.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getGoldProductionMultiplier(Dominion $dominion): float
    {
        $multiplier = 0;

        // Racial Bonus
        $multiplier += $dominion->race->getPerkMultiplier('gold_production');

        // Techs
        $multiplier += $dominion->getTechPerkMultiplier('gold_production');

        // Buildings
        $multiplier += $dominion->getBuildingPerkMultiplier('gold_production_modifier');

        // Improvement: Markets
        $multiplier += $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'markets');

        // Land improvements
        $multiplier += $this->landImprovementCalculator->getGoldProductionBonus($dominion);

        // Spells
        $multiplier += $dominion->getSpellPerkMultiplier('gold_production');

        // Apply Morale multiplier to production multiplier
        return (1 + $multiplier) * $this->militaryCalculator->getMoraleMultiplier($dominion);
    }

    /**
     * Returns the Dominion's food production.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getFoodProduction(Dominion $dominion): int
    {
        return max(0, floor($this->getFoodProductionRaw($dominion) * $this->getFoodProductionMultiplier($dominion)));
    }

    /**
     * Returns the Dominion's raw food production.
     *
     * Food is produced by:
     * - Building: Farm (80 per)
     * - Building: Dock (35 per)
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getFoodProductionRaw(Dominion $dominion): float
    {
        $food = 0;

        if($dominion->getSpellPerkValue('no_food_production') or ($dominion->race->getPerkValue('no_food_consumption') and $dominion->race->name !== 'Growth') or $dominion->race->getPerkValue('no_food_production'))
        {
            return $food;
        }

        // Building
        $food += $dominion->getBuildingPerkValue('food_production');

        // Unit Perk: Production Bonus (Growth Unit)
        $food += $dominion->getUnitPerkProductionBonus('food_production');

        // Unit Perk: sacrified peasants
        $food += $this->populationCalculator->getPeasantsSacrificed($dominion) * 2;

        // Racial Perk: peasants_produce_food
        if($dominion->race->getPerkValue('peasants_produce_food'))
        {
          $food += $dominion->peasants * $dominion->race->getPerkValue('peasants_produce_food');
        }

        $food *= 1 + $dominion->getSpellPerkMultiplier('food_production_raw');

        return max(0,$food);
    }

    /**
     * Returns the Dominion's food production multiplier.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getFoodProductionMultiplier(Dominion $dominion): float
    {
        $multiplier = 0;

        // Racial Bonus
        $multiplier += $dominion->race->getPerkMultiplier('food_production');

        // Techs
        $multiplier += $dominion->getTechPerkMultiplier('food_production');

        // Spells
        $multiplier += $dominion->getSpellPerkMultiplier('food_production');

        // Buildings
        $multiplier += $dominion->getBuildingPerkMultiplier('food_production_modifier');

        // Improvement: Harbor
        $multiplier += $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'harbor');

        // Improvement: Tissue (Growth)
        $multiplier += $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'tissue');

        // Prestige Bonus
        $prestigeMultiplier = $this->prestigeCalculator->getPrestigeMultiplier($dominion);

        // Land improvements
        $multiplier += $this->landImprovementCalculator->getFoodProductionBonus($dominion);

        // Apply Morale multiplier to production multiplier

        $moraleMultiplier = $this->militaryCalculator->getMoraleMultiplier($dominion);
        if($dominion->getSpellPerkMultiplier('food_production') > 0)
        {
            $moraleMultiplier = max(1, $moraleMultiplier);
        }

        return max(-1, ((1 + $multiplier) * (1 + $prestigeMultiplier)) * $moraleMultiplier);
    }

    /**
     * Returns the Dominion's food consumption.
     *
     * Each unit in a Dominion's population eats 0.25 food per hour.
     *
     * Food consumption is modified by Racial Bonus.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getFoodConsumption(Dominion $dominion): float
    {
        $consumers = 0;
        $consumption = 0;
        $multiplier = 0;

        if($dominion->race->getPerkValue('no_food_consumption'))
        {
            return 0;
        }

        $nonConsumingUnitAttributes = [
            'ammunition',
            'equipment',
            'magical',
            'machine',
            'ship',
            'ethereal'
          ];

        $consumers += $dominion->peasants;

        # Check each Unit for does_not_count_as_population perk.
        for ($slot = 1; $slot <= 4; $slot++)
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

              if (!$dominion->race->getUnitPerkValueForUnitSlot($slot, 'does_not_count_as_population') and count(array_intersect($nonConsumingUnitAttributes, $unitAttributes)) === 0)
              {
                  $consumers += $dominion->{'military_unit'.$slot};
                  $consumers += $this->queueService->getTrainingQueueTotalByResource($dominion, "military_unit{$slot}");
              }
        }

        $consumers += $dominion->military_draftees;
        $consumers += $dominion->military_spies;
        $consumers += $dominion->military_wizards;
        $consumers += $dominion->military_archmages;

        // Values
        $populationConsumption = 0.25;

        // Population Consumption
        $consumption += $consumers * $populationConsumption;

        // Racial Bonus
        $multiplier = $dominion->race->getPerkMultiplier('food_consumption');

        // Invasion Spell: Unhealing Wounds (+10% consumption)
        if ($multiplier !== -1.00 and $this->spellCalculator->isSpellActive($dominion, 'festering_wounds'))
        {
            $spell = Spell::where('key', 'festering_wounds')->first();
            $multiplier += 0.10 * $this->spellDamageCalculator->getDominionHarmfulSpellDamageModifier($dominion, null, $spell, null);
        }

        // Unit Perk: food_consumption
        $extraFoodEaten = 0;
        for ($unitSlot = 1; $unitSlot <= 4; $unitSlot++)
        {
            if ($dominion->race->getUnitPerkValueForUnitSlot($unitSlot, 'food_consumption'))
            {
                $extraFoodUnits = $dominion->{'military_unit'.$unitSlot};
                $extraFoodEatenPerUnit = $dominion->race->getUnitPerkValueForUnitSlot($unitSlot, 'food_consumption');
                $extraFoodEaten += intval($extraFoodUnits * $extraFoodEatenPerUnit);
            }
        }

        $consumption += $extraFoodEaten;

        # Add multiplier.
        $consumption *= (1 + $multiplier);

        return $consumption;
    }

    /**
     * Returns the Dominion's net food change.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getFoodNetChange(Dominion $dominion): int
    {
        return round($this->getFoodProduction($dominion) - $this->getFoodConsumption($dominion) - $this->getContribution($dominion, 'food'));
    }

    public function isOnBrinkOfStarvation(Dominion $dominion): bool
    {
        if($dominion->resource_food + $this->getFoodNetChange($dominion) < 0)
        {
            return true;
        }
        return false;
    }

    //</editor-fold>

    //<editor-fold desc="Lumber">

    /**
     * Returns the Dominion's lumber production.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getLumberProduction(Dominion $dominion): int
    {
        return floor($this->getLumberProductionRaw($dominion) * $this->getLumberProductionMultiplier($dominion));
    }

    /**
     * Returns the Dominion's raw lumber production.
     *
     * Lumber is produced by:
     * - Building: Lumberyard (50 per)
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getLumberProductionRaw(Dominion $dominion): float
    {
        $lumber = 0;

        if($dominion->getSpellPerkValue('no_lumber_production'))
        {
            return $lumber;
        }

        // Values
        $lumberPerLumberyard = 50;

        // Building: Lumberyard
        $lumber += $dominion->getBuildingPerkValue('lumber_production');

        // Unit Perk Production Bonus (Ant Unit: Worker Ant)
        $lumber += $dominion->getUnitPerkProductionBonus('lumber_production');

        // Unit Perk Production Reduction
        $upkeep = $dominion->getUnitPerkProductionBonus('upkeep_lumber');

        // Unit Perk: production_from_title
        $lumber += $dominion->getUnitPerkProductionBonusFromTitle('lumber');

        // Faction Perk: barren_forest_lumber_production
        foreach ($this->landHelper->getLandTypes($dominion) as $landType)
        {
            $lumber += $this->landCalculator->getTotalBarrenLand($dominion, $landType) * $dominion->race->getPerkValue('barren_' . $landType . '_lumber_production');
        }

        return max(0,$lumber - $upkeep);
    }

    /**
     * Returns the Dominion's lumber production multiplier.
     *
     * Lumber production is modified by:
     * - Racial Bonus
     * - Spell: Gaia's Blessing (+10%)
     * - Tech: Fruits of Labor (20%)
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getLumberProductionMultiplier(Dominion $dominion): float
    {
        $multiplier = 0;

        // Racial Bonus
        $multiplier += $dominion->race->getPerkMultiplier('lumber_production');

        // Techs
        $multiplier += $dominion->getTechPerkMultiplier('lumber_production');

        // Buildings
        $multiplier += $dominion->getBuildingPerkMultiplier('lumber_production_modifier');

        // Title
        if(isset($dominion->title))
        {
            $multiplier += $dominion->title->getPerkMultiplier('lumber_production') * $dominion->title->getPerkBonus($dominion);
        }

        // Spells
        $multiplier += $dominion->getSpellPerkMultiplier('lumber_production');

        // Improvement: Forestry
        $multiplier += $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'forestry');

        // Apply Morale multiplier to production multiplier
        return (1 + $multiplier) * $this->militaryCalculator->getMoraleMultiplier($dominion);
    }

    /**
     * Returns the Dominion's mana production.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getManaProduction(Dominion $dominion): int
    {
        return floor($this->getManaProductionRaw($dominion) * $this->getManaProductionMultiplier($dominion));
    }

    public function getManaNetChange(Dominion $dominion): int
    {
        return round($this->getManaProduction($dominion) - $this->getContribution($dominion, 'mana'));
    }

    /**
     * Returns the Dominion's raw mana production.
     *
     * Mana is produced by:
     * - Building: Tower (25 per)
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getManaProductionRaw(Dominion $dominion): float
    {
        $mana = 0;

        if($dominion->getSpellPerkValue('no_mana_production'))
        {
            return $mana;
        }

        // Buildings
        $mana += $dominion->getBuildingPerkValue('mana_production');

        // Unit Perk Production Bonus
        $mana += $dominion->getUnitPerkProductionBonus('mana_production');

        // Unit Perk: production_from_title
        $mana += $dominion->getUnitPerkProductionBonusFromTitle('mana');

        // Perk: draftee mana production
        $mana += $dominion->military_draftees * $dominion->race->getPerkValue('draftees_produce_mana');

        // Perk: peasants mana production
        $mana += $dominion->peasants * $dominion->race->getPerkValue('peasants_produce_mana');

        return max(0,$mana);
    }

    /**
     * Returns the Dominion's mana production multiplier.
     *
     * Mana production is modified by:
     * - Racial Bonus
     * - Tech: Enchanted Lands (+15%)
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getManaProductionMultiplier(Dominion $dominion): float
    {
        $multiplier = 0;

        // Improvement: Tower
        #$multiplier += $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'towers');

        // Spells
        $multiplier += $dominion->getSpellPerkMultiplier('mana_production');

        // Improvement: Spires
        $multiplier += $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'spires');

        // Racial Bonus
        $multiplier += $dominion->race->getPerkMultiplier('mana_production');

        // Techs
        $multiplier += $dominion->getTechPerkMultiplier('mana_production');

        // Buildings
        $multiplier += $dominion->getBuildingPerkMultiplier('mana_production_modifier');

        return (1 + $multiplier);
    }

    /**
     * Returns the Dominion's ore production.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getOreProduction(Dominion $dominion): int
    {
        return floor($this->getOreProductionRaw($dominion) * $this->getOreProductionMultiplier($dominion));
    }

    /**
     * Returns the Dominion's raw ore production.
     *
     * Ore is produced by:
     * - Building: Ore Mine (60 per)
     * - Dwarf Unit: Miner (2 per)
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getOreProductionRaw(Dominion $dominion): float
    {
        $ore = 0;

        if($dominion->getSpellPerkValue('no_ore_production'))
        {
            return $ore;
        }

        // Values
        $orePerOreMine = 60;

        // Building
        $ore += $dominion->getBuildingPerkValue('ore_production');

        // Unit Perk Production Bonus (Dwarf Unit: Miner)
        $ore += $dominion->getUnitPerkProductionBonus('ore_production');

        // Unit Perk Production Reduction
        $upkeep = $dominion->getUnitPerkProductionBonus('upkeep_ore');

        // Unit Perk: production_from_title
        $ore += $dominion->getUnitPerkProductionBonusFromTitle('ore');

        return max(0,$ore - $upkeep);
    }

    /**
     * Returns the Dominion's ore production multiplier.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getOreProductionMultiplier(Dominion $dominion): float
    {
        $multiplier = 0;

        // Racial Bonus
        $multiplier += $dominion->race->getPerkMultiplier('ore_production');

        // Techs
        $multiplier += $dominion->getTechPerkMultiplier('ore_production');

        // Buildings
        $multiplier += $dominion->getBuildingPerkMultiplier('ore_production_modifier');

        // Title
        if(isset($dominion->title))
        {
            $multiplier += $dominion->title->getPerkMultiplier('ore_production') * $dominion->title->getPerkBonus($dominion);
        }

        // Improvement: Refinery
        $multiplier += $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'refinery');

        // Spells
        $multiplier += $dominion->getSpellPerkMultiplier('ore_production');

        $multiplier = max(-1, $multiplier);

        // Apply Morale multiplier to production multiplier
        return (1 + $multiplier) * $this->militaryCalculator->getMoraleMultiplier($dominion);
    }

    /**
     * Returns the Dominion's gem production.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getGemProduction(Dominion $dominion): int
    {
        return floor($this->getGemProductionRaw($dominion) * $this->getGemProductionMultiplier($dominion));
    }

    /**
     * Returns the Dominion's raw gem production.
     *
     * Gems are produced by:
     * - Building: Gem Mine (15 per)
     * - Dwarf Unit: Miner (0.5 per)
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getGemProductionRaw(Dominion $dominion): float
    {
        $gems = 0;

        if($dominion->getSpellPerkValue('no_gem_production'))
        {
            return $gems;
        }

        // Buildings
        $gems += $dominion->getBuildingPerkValue('gem_production');

        // Unit Perk Production Bonus (Dwarf Unit: Miner)
        $gems += $dominion->getUnitPerkProductionBonus('gem_production');

        // Unit Perk: production_from_title
        $gems += $dominion->getUnitPerkProductionBonusFromTitle('gem');

        return max(0,$gems);
    }

    /**
     * Returns the Dominion's gem production multiplier.
     *
     * Gem production is modified by:
     * - Racial Bonus
     * - Tech: Fruits of Labor (+10%) and Miner's Refining (+5%)
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getGemProductionMultiplier(Dominion $dominion): float
    {
        $multiplier = 0;

        // Racial Bonus
        $multiplier += $dominion->race->getPerkMultiplier('gem_production');

        // Spell
        $multiplier += $dominion->getSpellPerkMultiplier('gem_production');

        // Techs
        $multiplier += $dominion->getTechPerkMultiplier('gem_production');

        // Buildings
        $multiplier += $dominion->getBuildingPerkMultiplier('gem_production_modifier');

        // Title
        if(isset($dominion->title))
        {
            $multiplier += $dominion->title->getPerkMultiplier('gem_production') * $dominion->title->getPerkBonus($dominion);
        }

        $multiplier += $this->spellCalculator->getPassiveSpellPerkMultiplier($dominion, 'gem_production');

        $multiplier = max(-1, $multiplier);

        // Apply Morale multiplier to production multiplier
        return (1 + $multiplier) * $this->militaryCalculator->getMoraleMultiplier($dominion);
    }

    //</editor-fold>

    //<editor-fold desc="Tech">

    /**
     * Returns the Dominion's experience point production.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getTechProduction(Dominion $dominion): int
    {
        return floor($this->getTechProductionRaw($dominion) * $this->getTechProductionMultiplier($dominion));
    }

    /**
     * Returns the Dominion's raw tech production (experience points, XP).
     *
     * Experience points are produced by:
     * - Prestige: Prestige/tick
     *
     * @param Dominion $dominion
     * @return float
     */
     public function getTechProductionRaw(Dominion $dominion): float
     {

         if($dominion->getSpellPerkValue('no_tech_production'))
         {
             return 0;
         }

         $tech = max(0, $dominion->prestige);

         $tech += $dominion->getUnitPerkProductionBonus('tech_production');

         // Unit Perk: production_from_title
         $tech += $dominion->getUnitPerkProductionBonusFromTitle('tech');

         return max(0,$tech);
     }

    /**
     * Returns the Dominion's experience point production multiplier.
     *
     * Experience point production is modified by:
     * - Racial Bonus
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getTechProductionMultiplier(Dominion $dominion): float
    {
        $multiplier = 0;

        // Racial Bonus
        $multiplier += $dominion->race->getPerkMultiplier('tech_production');

        // Buildings
        $multiplier += $dominion->getBuildingPerkMultiplier('tech_production_modifier');

        // Title
        if(isset($dominion->title))
        {
            $multiplier += $dominion->title->getPerkMultiplier('tech_production') * $dominion->title->getPerkBonus($dominion);
        }

        // Spell
        $multiplier += $this->spellCalculator->getPassiveSpellPerkMultiplier($dominion, 'tech_production');

        # Observatory
        $multiplier += $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'observatory');

        return (1 + $multiplier);
    }

    //</editor-fold>

    //<editor-fold desc="Boats">

    /**
     * Returns the Dominion's boat production per hour.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getBoatProduction(Dominion $dominion): float
    {
        return 0;
        #return ($this->getBoatProductionRaw($dominion) * $this->getBoatProductionMultiplier($dominion));
    }

    /**
     * Returns the Dominion's raw boat production per hour.
     *
     * Boats are produced by:
     * - Building: Dock (20 per)
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getBoatProductionRaw(Dominion $dominion): float
    {
        $boats = 0;

        if($dominion->getSpellPerkValue('no_boat_production'))
        {
            return $boats;
        }

        // Buildings
        $boats += $dominion->getBuildingPerkValue('boat_production');

        // Unit Perk: production_from_title
        $boats += $dominion->getUnitPerkProductionBonusFromTitle('boats');

        return max(0,$boats);
    }

    /**
     * Returns the Dominions's boat production multiplier.
     *
     * Boat production is modified by:
     * - Improvement: Harbor
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getBoatProductionMultiplier(Dominion $dominion): float
    {
        $multiplier = 0;

        // Spells
        $multiplier += $dominion->getSpellPerkMultiplier('boat_production');

        // Improvement: Harbor
        $multiplier += $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'harbor');

        // Land improvements
        $multiplier += $this->landImprovementCalculator->getBoatProductionBonus($dominion);

        // Apply Morale multiplier to production multiplier
        return (1 + $multiplier) * $this->militaryCalculator->getMoraleMultiplier($dominion);
    }

    /**
     * Returns the Dominion's soul production, based on peasants sacrificed.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getSoulProduction(Dominion $dominion): float
    {
        return $this->populationCalculator->getPeasantsSacrificed($dominion) * 1;
    }

    /**
     * Returns the Dominion's blood production, based on peasants sacrificed.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getBloodProduction(Dominion $dominion): float
    {
        return $this->populationCalculator->getPeasantsSacrificed($dominion) * 1.5;
    }

    public function getContributionRate(Realm $realm): float
    {
        return max(min($realm->contribution / 100, 0.10),0);
    }

    public function getContribution(Dominion $dominion, string $resource): float
    {

        $contribution = 0;

        if($resource === 'food')
        {
            $contribution = $this->getFoodProduction($dominion) * $this->getContributionRate($dominion->realm);
        }
        elseif($resource === 'mana')
        {
            $contribution = $this->getManaProduction($dominion) * $this->getContributionRate($dominion->realm);
        }
        else
        {
            return $contribution;
        }

        if($contribution > $dominion->{'resource_'.$resource})
        {
            $contribution = ($dominion->{'resource_'.$resource} / 2);
        }

        return floor(max(0, $contribution));

    }

}
