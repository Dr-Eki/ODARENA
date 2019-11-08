<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\GuardMembershipService;

// Morale affects production
use OpenDominion\Calculators\Dominion\MilitaryCalculator;

class ProductionCalculator
{
    /** @var ImprovementCalculator */
    protected $improvementCalculator;

    /** @var LandCalculator */
    protected $landCalculator;

    /** @var PopulationCalculator */
    protected $populationCalculator;

    /** @var PrestigeCalculator */
    private $prestigeCalculator;

    /** @var SpellCalculator */
    protected $spellCalculator;

    /** @var GuardMembershipService */
    private $guardMembershipService;

    /** @var MilitaryCalculator */
    protected $militaryCalculator;

    /**
     * ProductionCalculator constructor.
     *
     * @param ImprovementCalculator $improvementCalculator
     * @param LandCalculator $landCalculator
     * @param PopulationCalculator $populationCalculator
     * @param PrestigeCalculator $prestigeCalculator
     * @param SpellCalculator $spellCalculator
     * @param GuardMembershipService $guardMembershipService
     * @param MilitaryCalculator $militaryCalculator
     */
    public function __construct(
        ImprovementCalculator $improvementCalculator,
        LandCalculator $landCalculator,
        PopulationCalculator $populationCalculator,
        PrestigeCalculator $prestigeCalculator,
        SpellCalculator $spellCalculator,
        GuardMembershipService $guardMembershipService,
        MilitaryCalculator $militaryCalculator)
    {
        $this->improvementCalculator = $improvementCalculator;
        $this->landCalculator = $landCalculator;
        $this->populationCalculator = $populationCalculator;
        $this->prestigeCalculator = $prestigeCalculator;
        $this->spellCalculator = $spellCalculator;
        $this->guardMembershipService = $guardMembershipService;
        $this->militaryCalculator = $militaryCalculator;
    }

    //<editor-fold desc="Platinum">

    /**
     * Returns the Dominion's platinum production.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getPlatinumProduction(Dominion $dominion): int
    {
        return floor($this->getPlatinumProductionRaw($dominion) * $this->getPlatinumProductionMultiplier($dominion));
    }

    /**
     * Returns the Dominion's raw platinum production.
     *
     * Platinum is produced by:
     * - Employed Peasants (2.7 per)
     * - Building: Alchemy (45 per, or 60 with Alchemist Flame racial spell active)
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getPlatinumProductionRaw(Dominion $dominion): float
    {
        $platinum = 0;

        // Values
        $peasantTax = 2.7;
        $spellAlchemistFlameAlchemyBonus = 15;
        $platinumPerAlchemy = 45;

        // Race specialty: Void peasants
        if($dominion->race->name === 'Void')
        {
            $platinum += $dominion->peasants * $peasantTax;
        }
        else
        {
            // Peasant Tax
            $platinum += ($this->populationCalculator->getPopulationEmployed($dominion) * $peasantTax);
        }

        // Spell: Alchemist Flame
        if ($this->spellCalculator->isSpellActive($dominion, 'alchemist_flame')) {
            $platinumPerAlchemy += $spellAlchemistFlameAlchemyBonus;
        }

        // Building: Alchemy
        $platinum += ($dominion->building_alchemy * $platinumPerAlchemy);

        return $platinum;
    }

    /**
     * Returns the Dominion's platinum production multiplier.
     *
     * Platinum production is modified by:
     * - Racial Bonus
     * - Spell: Midas Touch (+10%)
     * - Improvement: Science
     * - Guard Tax (-2%)
     * - Tech: Treasure Hunt (+12.5%) or Banker's Foresight (+5%)
     *
     * Platinum production multiplier is capped at +50%.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getPlatinumProductionMultiplier(Dominion $dominion): float
    {
        $multiplier = 0;

        // Values (percentages)
        $spellMidasTouch = 10;

        if($dominion->race->getPerkValue('guard_tax_exemption'))
        {
          $guardTax = 0;
        }
        else {
          $guardTax = -2;
        }

        // Racial Bonus
        $multiplier += $dominion->race->getPerkMultiplier('platinum_production');

        // Techs
        $multiplier += $dominion->getTechPerkMultiplier('platinum_production');

        // Spell: Midas Touch
        $multiplier += $this->spellCalculator->getActiveSpellMultiplierBonus($dominion, 'midas_touch', $spellMidasTouch);

        // Improvement: Markets (formerly "Science")
        $multiplier += $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'markets');

        // Guard Tax
        if ($this->guardMembershipService->isGuardMember($dominion)) {
            $multiplier += ($guardTax / 100);
        }

        // Beastfolk: Mountain increases platinum production.
        if($dominion->race->name == 'Beastfolk')
        {
          $multiplier += $dominion->{"land_mountain"} / $this->landCalculator->getTotalLand($dominion);
        }

        // Apply Morale multiplier to production multiplier
        return min(1.5, (1 + $multiplier) * $this->militaryCalculator->getMoraleMultiplier($dominion));
    }

    //</editor-fold>

    //<editor-fold desc="Food">

    /**
     * Returns the Dominion's food production.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getFoodProduction(Dominion $dominion): int
    {
        return floor($this->getFoodProductionRaw($dominion) * $this->getFoodProductionMultiplier($dominion));
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

        // Values
        $foodPerFarm = 80;
        $foodPerDock = 35;

        // Building: Farm
        $food += ($dominion->building_farm * $foodPerFarm);

        // Building: Dock
        $food += ($dominion->building_dock * $foodPerDock);

        // Unit Perk: Production Bonus (Growth Unit)
        $food += $dominion->getUnitPerkProductionBonus('food_production');

        // Racial Perk: peasants_produce_food
        if($dominion->race->getPerkValue('peasants_produce_food'))
        {
          $food += $dominion->peasants;
        }

        // Racial Spell: Metabolism (Growth) - Double food production
        if ($this->spellCalculator->isSpellActive($dominion, 'metabolism')) {
            $food *= 2;
        }

        return $food;
    }

    /**
     * Returns the Dominion's food production multiplier.
     *
     * Food production is modified by:
     * - Racial Bonus
     * - Spell: Gaia's Blessing (+20%) or Gaia's Watch (+10%)
     * - Improvement: Harbor
     * - Tech: Farmer's Growth (+10%)
     * - Prestige (+1% per 100 prestige, multiplicative)
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getFoodProductionMultiplier(Dominion $dominion): float
    {
        $multiplier = 0;

        // Values (percentages)
        $spellGaiasBlessing = 20;
        $spellGaiasWatch = 10;

        // Racial Bonus
        $multiplier += $dominion->race->getPerkMultiplier('food_production');

        // Techs
        $multiplier += $dominion->getTechPerkMultiplier('food_production');

        // Spell: Gaia's Blessing or Gaia's Watch
        $multiplier += $this->spellCalculator->getActiveSpellMultiplierBonus($dominion, [
            'gaias_blessing' => $spellGaiasBlessing,
            'gaias_watch' => $spellGaiasWatch,
        ]);

        // Improvement: Harbor
        $multiplier += $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'harbor');

        // Prestige Bonus
        $prestigeMultiplier = $this->prestigeCalculator->getPrestigeMultiplier($dominion);

        // Beastfolk: Water increases food production
        if($dominion->race->name == 'Beastfolk')
        {
          $multiplier += 5 * ($dominion->{"land_water"} / $this->landCalculator->getTotalLand($dominion));
        }

        // Apply Morale multiplier to production multiplier
        return ((1 + $multiplier) * (1 + $prestigeMultiplier)) * $this->militaryCalculator->getMoraleMultiplier($dominion);
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
        $consumption = 0;

        // Values
        $populationConsumption = 0.25;

        // Population Consumption
        $consumption += ($this->populationCalculator->getPopulation($dominion) * $populationConsumption);

        // Racial Bonus
        $consumption *= (1 + $dominion->race->getPerkMultiplier('food_consumption'));

        // Unit Perk: food_consumption
        $extraFoodEaten = 0;
        for ($unitSlot = 1; $unitSlot <= 4; $unitSlot++)
        {
          if ($dominion->race->getUnitPerkValueForUnitSlot($unitSlot, 'food_consumption'))
          {
            $extraFoodUnits = $dominion->{"military_unit".$unitSlot};
            $extraFoodEatenPerUnit = $dominion->race->getUnitPerkValueForUnitSlot($unitSlot, 'food_consumption');
            $extraFoodEaten += intval($extraFoodUnits * $extraFoodEatenPerUnit); 
          }
        }

        $consumption += $extraFoodEaten;

        return $consumption;
    }

    /**
     * Returns the Dominion's food decay.
     *
     * Food decays 1% per hour.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getFoodDecay(Dominion $dominion): float
    {
        $decay = 0;

        // Values (percentages)
        $foodDecay = 1;

        // Improvement: Granaries
        $multiplier = 1 - $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'granaries');

        $foodDecay *= $multiplier;

        $decay += ($dominion->resource_food * ($foodDecay / 100));

        return $decay;
    }

    /**
     * Returns the Dominion's net food change.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getFoodNetChange(Dominion $dominion): int
    {
        return round($this->getFoodProduction($dominion) - $this->getFoodConsumption($dominion) - $this->getFoodDecay($dominion));
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

        // Values
        $lumberPerLumberyard = 50;

        // Building: Lumberyard
        $lumber += ($dominion->building_lumberyard * $lumberPerLumberyard);

        // Unit Perk Production Bonus (Ant Unit: Worker Ant)
        $lumber += $dominion->getUnitPerkProductionBonus('lumber_production');

        return $lumber;
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

        // Values (percentages)
        $spellGaiasBlessing = 10;

        // Racial Bonus
        $multiplier += $dominion->race->getPerkMultiplier('lumber_production');

        // Techs
        $multiplier += $dominion->getTechPerkMultiplier('lumber_production');

        // Spell: Gaia's Blessing
        $multiplier += $this->spellCalculator->getActiveSpellMultiplierBonus($dominion, 'gaias_blessing', $spellGaiasBlessing);

        // Improvement: Forestry
        $multiplier += $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'forestry');

        // Apply Morale multiplier to production multiplier
        return (1 + $multiplier) * $this->militaryCalculator->getMoraleMultiplier($dominion);
    }

    /**
     * Returns the Dominion's lumber decay.
     *
     * Lumber decays 1% per hour.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getLumberDecay(Dominion $dominion): float
    {
        $decay = 0;

        // Values (percentages)
        $lumberDecay = 1;

        // Improvement: Granaries
        $multiplier = 1 - $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'granaries');

        $lumberDecay *= $multiplier;

        $decay += ($dominion->resource_lumber * ($lumberDecay / 100));

        return $decay;
    }

    /**
     * Returns the Dominion's net lumber change.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getLumberNetChange(Dominion $dominion): int
    {
        return round($this->getLumberProduction($dominion) - $this->getLumberDecay($dominion));
    }

    //</editor-fold>

    //<editor-fold desc="Mana">

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

        // Values
        $manaPerTower = 25;

        // Building: Tower
        $mana += ($dominion->building_tower * $manaPerTower);

        // Unit Perk Production Bonus (Void Unit: Vision)
        $mana += $dominion->getUnitPerkProductionBonus('mana_production');

        return $mana;
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
        $multiplier += $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'towers');

        // Racial Bonus
        $multiplier += $dominion->race->getPerkMultiplier('mana_production');

        // Techs
        $multiplier += $dominion->getTechPerkMultiplier('mana_production');

        return (1 + $multiplier);
    }

    /**
     * Returns the Dominion's mana decay.
     *
     * Mana decays 2% per hour.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getManaDecay(Dominion $dominion): float
    {
        $decay = 0;

        // Values (percentages)
        $manaDecay = 2;

        $decay += ($dominion->resource_mana * ($manaDecay / 100));

        // Unit Perk Production Bonus (Dimensionalists Units)
        $decay += $dominion->getUnitPerkProductionBonus('mana_drain');

        return $decay;
    }

    /**
     * Returns the Dominion's net mana change.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getManaNetChange(Dominion $dominion): int
    {
        $manaDecay = $this->getManaDecay($dominion);

        if($dominion->resource_mana < $manaDecay)
        {
          return round($dominion->resource_mana*-1);
        }
        else
        {
          return round($this->getManaProduction($dominion) - $this->getManaDecay($dominion));
        }

        #return round($this->getManaProduction($dominion) - $this->getManaDecay($dominion));
    }

    //</editor-fold>

    //<editor-fold desc="Ore">

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

        // Values
        $orePerOreMine = 60;

        // Building: Ore Mine
        $ore += ($dominion->building_ore_mine * $orePerOreMine);

        // Unit Perk Production Bonus (Dwarf Unit: Miner)
        $ore += $dominion->getUnitPerkProductionBonus('ore_production');

        return $ore;
    }

    /**
     * Returns the Dominion's ore production multiplier.
     *
     * Ore production is modified by:
     * - Racial Bonus
     * - Spell: Miner's Sight (+20%) or Mining Strength (+10%)
     * - Tech: Fruits of Labor (+20%)
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getOreProductionMultiplier(Dominion $dominion): float
    {
        $multiplier = 0;

        // Values (percentages)
        $spellMinersSight = 20;
        $spellMiningStrength = 10;

        // Racial Bonus
        $multiplier += $dominion->race->getPerkMultiplier('ore_production');

        // Techs
        $multiplier += $dominion->getTechPerkMultiplier('ore_production');

        // Spell: Miner's Sight or Mining Strength
        $multiplier += $this->spellCalculator->getActiveSpellMultiplierBonus($dominion, [
            'miners_sight' => $spellMinersSight,
            'mining_strength' => $spellMiningStrength,
        ]);

        // Improvement: Refinery
        $multiplier += $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'refinery');

        // Apply Morale multiplier to production multiplier
        return (1 + $multiplier) * $this->militaryCalculator->getMoraleMultiplier($dominion);
    }

    //</editor-fold>

    //<editor-fold desc="Gems">

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
     * - Building: Diamond Mine (15 per)
     * - Dwarf Unit: Miner (0.5 per)
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getGemProductionRaw(Dominion $dominion): float
    {
        $gems = 0;

        // Values
        $gemsPerDiamondMine = 15;

        // Building: Diamond Mine
        $gems += ($dominion->building_diamond_mine * $gemsPerDiamondMine);

        // Unit Perk Production Bonus (Dwarf Unit: Miner)
        $gems += $dominion->getUnitPerkProductionBonus('gem_production');

        return $gems;
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

        // Techs
        $multiplier += $dominion->getTechPerkMultiplier('gem_production');

        // Apply Morale multiplier to production multiplier
        return (1 + $multiplier) * $this->militaryCalculator->getMoraleMultiplier($dominion);
    }

    //</editor-fold>

    //<editor-fold desc="Tech">

    /**
     * Returns the Dominion's research point production.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getTechProduction(Dominion $dominion): int
    {
        return floor($this->getTechProductionRaw($dominion) * $this->getTechProductionMultiplier($dominion));
    }

    /**
     * Returns the Dominion's raw tech production.
     *
     * Research points are produced by:
     * - Building: School (15 per)
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getTechProductionRaw(Dominion $dominion): float
    {
        $tech = 0;

        // Values
        $techPerSchool = 0.5;

        // Building: School
        $tech += max(
            $dominion->building_school * $techPerSchool,
            $dominion->building_school * (1 - ($dominion->building_school / $this->landCalculator->getTotalLand($dominion)))
        );

        return $tech;
    }

    /**
     * Returns the Dominion's research point production multiplier.
     *
     * Research point production is modified by:
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
        return ($this->getBoatProductionRaw($dominion) * $this->getBoatProductionMultiplier($dominion));
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

        // Values
        $docksPerBoatPerTick = 20;

        $boats += ($dominion->building_dock / $docksPerBoatPerTick);

        return $boats;
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

        // Improvement: Harbor
        $multiplier += $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'harbor');

        // Beastfolk: Water increases boat production.
        if($dominion->race->name == 'Beastfolk')
        {
          $multiplier += 5 * ($dominion->{"land_water"} / $this->landCalculator->getTotalLand($dominion));
        }

        // Apply Morale multiplier to production multiplier
        return (1 + $multiplier) * $this->militaryCalculator->getMoraleMultiplier($dominion);
    }


        /**
         * Returns the Dominion's wild yeti production per hour.
         *
         * Boats are produced by:
         * - Building: Gryphon Nest (1 per)
         *
         * @param Dominion $dominion
         * @return float
         */
        public function getWildYetiProduction(Dominion $dominion): float
        {
            if(!$dominion->race->getPerkValue('gryphon_nests_generates_wild_yetis'))
            {
              return 0;
            }

            $wildYetis = 0;

            // Values
            $wildYetisPerGryphonNest = 0.1;

            $wildYetis += intval($dominion->building_gryphon_nest * $wildYetisPerGryphonNest);

            // Snow Elf: Spell (triples wild yeti production)
            if ($this->spellCalculator->isSpellActive($dominion, 'gryphons_call'))
            {
              $wildYetis = $wildYetis * 4;
            }

            return $wildYetis;
        }

        /**
         * Returns the Dominion's wild yeti escapees.
         *
         * Between 0% and 5% wild yetis escape.
         *
         * @param Dominion $dominion
         * @return float
         */
        public function getWildYetiEscaped(Dominion $dominion): float
        {
            if(!$dominion->race->getPerkValue('gryphon_nests_generates_wild_yetis'))
            {
              return 0;
            }

            $escaped = 0;

            // Escaped percentage
            $escaped = rand(0,5);

            $escaped += intval(($dominion->resource_wild_yeti * ($escaped / 100)));

            return $escaped;
        }

        /**
         * Returns the Dominion's net wild yeti change.
         *
         * @param Dominion $dominion
         * @return int
         */
        public function getWildYetiNetChange(Dominion $dominion): int
        {
            return intval($this->getWildYetiProduction($dominion));
            #return intval($this->getWildYetiProduction($dominion) - $this->getWildYetiEscaped($dominion));
        }

    //</editor-fold>
}
