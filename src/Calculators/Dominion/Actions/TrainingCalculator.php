<?php

namespace OpenDominion\Calculators\Dominion\Actions;

use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Building;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Calculators\Dominion\BuildingCalculator;

# ODA
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Helpers\RaceHelper;
use OpenDominion\Calculators\Dominion\PopulationCalculator;

class TrainingCalculator
{
    /** @var LandCalculator */
    protected $landCalculator;

    /** @var UnitHelper */
    protected $unitHelper;

    /** @var ImprovementCalculator */
    protected $improvementCalculator;

    /** @var MilitaryCalculator */
    protected $militaryCalculator;

    /** @var QueueService */
    protected $queueService;

    /** @var SpellCalculator */
    protected $spellCalculator;

    /** @var RaceHelper */
    protected $raceHelper;

    /** @var PopulationCalculator */
    protected $populationCalculator;

    /**
     * TrainingCalculator constructor.
     *
     * @param LandCalculator $landCalculator
     * @param UnitHelper $unitHelper
     */
    public function __construct(
          LandCalculator $landCalculator,
          UnitHelper $unitHelper,
          ImprovementCalculator $improvementCalculator,
          MilitaryCalculator $militaryCalculator,
          QueueService $queueService,
          SpellCalculator $spellCalculator,
          RaceHelper $raceHelper,
          PopulationCalculator $populationCalculator,
          BuildingCalculator $buildingCalculator
          )
    {
        $this->landCalculator = $landCalculator;
        $this->unitHelper = $unitHelper;
        $this->improvementCalculator = $improvementCalculator;
        $this->militaryCalculator = $militaryCalculator;
        $this->queueService = $queueService;
        $this->spellCalculator = $spellCalculator;
        $this->raceHelper = $raceHelper;
        $this->populationCalculator = $populationCalculator;
        $this->buildingCalculator = $buildingCalculator;
    }

    /**
     * Returns the Dominion's training costs per unit.
     *
     * @param Dominion $dominion
     * @return array
     */
    public function getTrainingCostsPerUnit(Dominion $dominion): array
    {
        $costsPerUnit = [];

        $spyCostMultiplier = $this->getSpyCostMultiplier($dominion);
        $wizardCostMultiplier = $this->getWizardCostMultiplier($dominion);

        // Values
        $units = $dominion->race->units;

        $spyCost = $this->raceHelper->getSpyCost($dominion->race);
        $wizardCost = $this->raceHelper->getWizardCost($dominion->race);
        $archmageCost = $this->raceHelper->getArchmageCost($dominion->race);

        $spyCost['trainedFrom'] = 'draftees';
        $wizardCost['trainedFrom'] = 'draftees';

        # Generally, do not mess with thi sone.
        $archmageCost['trainedFrom'] = 'wizards';

        foreach ($this->unitHelper->getUnitTypes() as $unitType) {
            $cost = [];

            switch ($unitType) {
                case 'spies':
                    $cost[$spyCost['trainedFrom']] = 1;
                    $cost[$spyCost['resource']] = round($spyCost['amount'] * $spyCostMultiplier);
                    break;

                case 'wizards':
                    $cost[$spyCost['trainedFrom']] = 1;
                    $cost[$wizardCost['resource']] = round($wizardCost['amount'] * $wizardCostMultiplier);
                    break;

                case 'archmages':
                    $cost[$archmageCost['trainedFrom']] = 1;
                    $cost[$archmageCost['resource']] = round($archmageCost['amount'] * $wizardCostMultiplier);
                    break;

                default:
                    $unitSlot = (((int)str_replace('unit', '', $unitType)) - 1);

                    $gold = $units[$unitSlot]->cost_gold;
                    $ore = $units[$unitSlot]->cost_ore;

                    // New unit cost resources
                    $food = $units[$unitSlot]->cost_food;
                    $mana = $units[$unitSlot]->cost_mana;
                    $gem = $units[$unitSlot]->cost_gem;
                    $lumber = $units[$unitSlot]->cost_lumber;
                    $prestige = $units[$unitSlot]->cost_prestige;
                    $boat = $units[$unitSlot]->cost_boat;
                    $champion = $units[$unitSlot]->cost_champion;
                    $soul = $units[$unitSlot]->cost_soul;
                    $morale = $units[$unitSlot]->cost_morale;
                    $peasant = $units[$unitSlot]->cost_peasant;
                    $wild_yeti = $units[$unitSlot]->cost_wild_yeti;
                    $blood = $units[$unitSlot]->cost_blood;

                    $unit1 = $units[$unitSlot]->cost_unit1;
                    $unit2 = $units[$unitSlot]->cost_unit2;
                    $unit3 = $units[$unitSlot]->cost_unit3;
                    $unit4 = $units[$unitSlot]->cost_unit4;

                    $spy = $units[$unitSlot]->cost_spy;
                    $wizard = $units[$unitSlot]->cost_wizard;
                    $archmage = $units[$unitSlot]->cost_archmage;

                    #if ($gold > 0) {
                        $cost['gold'] = (int)ceil($gold * $this->getSpecialistEliteCostMultiplier($dominion, 'gold'));
                    #}

                    #if ($ore > 0) {
                        $cost['ore'] = $ore;
                        $cost['ore'] = (int)ceil($ore * $this->getSpecialistEliteCostMultiplier($dominion, 'ore'));
                    #}

                    // FOOD cost for units
                    #if ($food > 0) {
                        $cost['food'] = $food;
                        $cost['food'] = (int)ceil($food * $this->getSpecialistEliteCostMultiplier($dominion, 'food'));
                    #}
                    // MANA cost for units
                    #if ($mana > 0) {
                        $cost['mana'] = $mana;
                        $cost['mana'] = (int)ceil($mana * $this->getSpecialistEliteCostMultiplier($dominion, 'mana'));
                    #}
                    // GEM cost for units
                    #if ($gem > 0) {
                        $cost['gem'] = $gem;
                        $cost['gem'] = (int)ceil($gem * $this->getSpecialistEliteCostMultiplier($dominion, 'gem'));
                    #}
                    // LUMBER cost for units
                    #if ($lumber > 0) {
                        $cost['lumber'] = $lumber;
                        $cost['lumber'] = (int)ceil($lumber * $this->getSpecialistEliteCostMultiplier($dominion, 'lumber'));
                    #}
                    // PRESTIGE cost for units
                    #if ($prestige > 0) {
                        $cost['prestige'] = $prestige;
                        $cost['prestige'] = (int)ceil($prestige * $this->getSpecialistEliteCostMultiplier($dominion, 'prestige'));
                    #}

                    // BOAT cost for units
                    #if ($boat > 0) {
                        $cost['boat'] = $boat;
                        $cost['boat'] = (int)ceil($boat * $this->getSpecialistEliteCostMultiplier($dominion, 'boat'));
                    #}

                    // CHAMPION cost for units
                    #if ($champion > 0) {
                        $cost['champion'] = $champion;
                        $cost['champion'] = (int)ceil($champion * $this->getSpecialistEliteCostMultiplier($dominion, 'champion'));
                    #}

                    // SOUL cost for units
                    #if ($soul > 0) {
                        $cost['soul'] = $soul;
                        $cost['soul'] = (int)ceil($soul * $this->getSpecialistEliteCostMultiplier($dominion, 'soul'));
                    #}

                    // BLOOD cost for units
                    if ($blood > 0) {
                        $cost['blood'] = $blood;
                        $cost['blood'] = (int)ceil($blood * $this->getSpecialistEliteCostMultiplier($dominion, 'blood'));
                    }

                    // UNIT1 cost for units
                    #if ($unit1 > 0) {
                        $cost['unit1'] = $unit1;
                        $cost['unit1'] = (int)ceil($unit1 * $this->getSpecialistEliteCostMultiplier($dominion, 'unit1'));
                    #}

                    // UNIT2 cost for units
                    #if ($unit2 > 0) {
                        $cost['unit2'] = $unit2;
                        $cost['unit2'] = (int)ceil($unit2 * $this->getSpecialistEliteCostMultiplier($dominion, 'unit2'));
                    #}

                    // UNIT3 cost for units
                    #if ($unit3 > 0) {
                        $cost['unit3'] = $unit3;
                        $cost['unit3'] = (int)ceil($unit3 * $this->getSpecialistEliteCostMultiplier($dominion, 'unit3'));
                    #}

                    // UNIT4 cost for units
                    #if ($unit4 > 0) {
                        $cost['unit4'] = $unit4;
                        $cost['unit4'] = (int)ceil($unit4 * $this->getSpecialistEliteCostMultiplier($dominion, 'unit4'));
                    #}

                    // MORALE cost for units
                    #if ($morale > 0) {
                        $cost['morale'] = $morale;
                        $cost['morale'] = (int)ceil($morale * $this->getSpecialistEliteCostMultiplier($dominion, 'morale'));
                    #}

                    // PEASANT cost for units
                    #if (peasant > 0) {
                        $cost['peasant'] = $peasant;
                        $cost['peasant'] = (int)ceil($peasant * $this->getSpecialistEliteCostMultiplier($dominion, 'peasant'));
                    #}

                    // WILD YETI cost for units
                    #if ($wild_yeti > 0) {
                        $cost['wild_yeti'] = $wild_yeti;
                        $cost['wild_yeti'] = (int)ceil($wild_yeti * $this->getSpecialistEliteCostMultiplier($dominion, 'wild_yeti'));
                    #}

                    // SPY cost for units
                    #if ($spy > 0) {
                        $cost['spy'] = $spy;
                        $cost['spy'] = (int)ceil($spy * $this->getSpecialistEliteCostMultiplier($dominion, 'spy'));
                    #}

                    // WIZARD cost for units
                    #if ($wizard > 0) {
                        $cost['wizard'] = $wizard;
                        $cost['wizard'] = (int)ceil($wizard * $this->getSpecialistEliteCostMultiplier($dominion, 'wizard'));
                    #}

                    // ARCHMAGE cost for units
                    #if ($archmage > 0) {
                        $cost['archmage'] = $archmage;
                        $cost['archmage'] = (int)ceil($archmage * $this->getSpecialistEliteCostMultiplier($dominion, 'archmage'));
                    #}

                    if($dominion->race->getUnitPerkValueForUnitSlot(intval(str_replace('unit','',$unitType)), 'no_draftee') == 1)
                    {
                        $cost['draftees'] = 0;
                    }
                    # Check for housing_count
                    elseif($nonStandardHousing = $dominion->race->getUnitPerkValueForUnitSlot(intval(str_replace('unit','',$unitType)), 'housing_count'))
                    {
                        $cost['draftees'] = min($nonStandardHousing, 1);
                    }
                    else
                    {
                        $cost['draftees'] = 1;
                    }

                    break;
            }

            $costsPerUnit[$unitType] = $cost;
        }

        return $costsPerUnit;
    }

    /**
     * Returns the Dominion's max military trainable population.
     *
     * @param Dominion $dominion
     * @return array
     */
    public function getMaxTrainable(Dominion $dominion): array
    {
        $trainable = [];

        $fieldMapping = [
            'gold' => 'resource_gold',
            'ore' => 'resource_ore',
            'draftees' => 'military_draftees',
            'wizards' => 'military_wizards',

            //New unit cost resources

            'food' => 'resource_food',
            'mana' => 'resource_mana',
            'gem' => 'resource_gems',
            'lumber' => 'resource_lumber',
            'prestige' => 'prestige',
            'boat' => 'resource_boats',
            'champion' => 'resource_champion',
            'soul' => 'resource_soul',
            'morale' => 'morale',
            'peasant' => 'peasants',
            'wild_yeti' => 'resource_wild_yeti',
            'blood' => 'resource_blood',

            'unit1' => 'military_unit1',
            'unit2' => 'military_unit2',
            'unit3' => 'military_unit3',
            'unit4' => 'military_unit4',

            'spy' => 'military_spies',
            'wizard' => 'military_wizards',
            'archmage' => 'military_archmages',
        ];

        $costsPerUnit = $this->getTrainingCostsPerUnit($dominion);

        foreach ($costsPerUnit as $unitType => $costs)
        {
            $trainableByCost = [];

            foreach ($costs as $type => $value)
            {
                if($value !== 0)
                {
                  $trainableByCost[$type] = (int)floor($dominion->{$fieldMapping[$type]} / $value);
                }
            }

            $trainable[$unitType] = min($trainableByCost);

            $slot = intval(str_replace('unit','',$unitType));
            # Look for building_limit
            if($buildingLimit = $dominion->race->getUnitPerkValueForUnitSlot($slot,'building_limit'))
            {
                $buildingKeyLimitedTo = $buildingLimit[0]; # Land type
                $unitsPerBuilding = (float)$buildingLimit[1]; # Units per building

                # Improvement that can raise the limit
                if(isset($buildingLimit[2]))
                {
                    $unitsPerBuilding *= (1 + $this->improvementCalculator->getImprovementMultiplierBonus($dominion, $buildingLimit[2]));
                }

                $building = Building::where('key', $buildingKeyLimitedTo)->first();
                $amountOfLimitingBuilding = $this->buildingCalculator->getBuildingAmountOwned($dominion, $building);

                $maxAdditionalPermittedOfThisUnit = intval($amountOfLimitingBuilding * $unitsPerBuilding) - $this->militaryCalculator->getTotalUnitsForSlot($dominion, $slot) - $this->queueService->getTrainingQueueTotalByResource($dominion, 'military_unit'.$slot);

                $trainable[$unitType] = min($trainable[$unitType], $maxAdditionalPermittedOfThisUnit);
            }

            # Look for land_limit
            if($landLimit = $dominion->race->getUnitPerkValueForUnitSlot($slot,'land_limit'))
            {
                $landLimitedToLandType = 'land_' . $landLimit[0]; # Land type
                $unitsPerAcre = (float)$landLimit[1]; # Units per

                $acresOfLimitingLandType = $dominion->{$landLimitedToLandType};

                $maxAdditionalPermittedOfThisUnit = floor($acresOfLimitingLandType * $unitsPerAcre) - $this->militaryCalculator->getTotalUnitsForSlot($dominion, $slot) - $this->queueService->getTrainingQueueTotalByResource($dominion, 'military_unit'.$slot);

                $trainable[$unitType] = min($trainable[$unitType], $maxAdditionalPermittedOfThisUnit);
            }

            # Look for pairing_limit
            if($pairingLimit = $dominion->race->getUnitPerkValueForUnitSlot($slot,'pairing_limit'))
            {
              $pairingLimitedBy = intval($pairingLimit[0]);
              $pairingLimitedTo = $pairingLimit[1];

              $pairingLimitedByTrained = $dominion->{'military_unit'.$pairingLimitedBy};
              #$pairingLimitedByTrained = $this->militaryCalculator->getTotalUnitsForSlot($dominion, $pairingLimitedBy))

              $maxAdditionalPermittedOfThisUnit = intval($pairingLimitedByTrained * $pairingLimitedTo) - $this->militaryCalculator->getTotalUnitsForSlot($dominion, $slot) - $this->queueService->getTrainingQueueTotalByResource($dominion, 'military_unit'.$slot);

              $trainable[$unitType] = min($trainable[$unitType], $maxAdditionalPermittedOfThisUnit);
            }

            # Look for pairing_limit_increasable
            if($pairingLimitedIncreasable = $dominion->race->getUnitPerkValueForUnitSlot($slot,'pairing_limit_increasable'))
            {
                $unitLimitedTo = (float)$pairingLimitedIncreasable[0]; # Units paired-limited to
                $unitsPerLimitingUnit = (float)$pairingLimitedIncreasable[1]; # Number of this unit per unit paired-limited to
                $extendingImprovement = (string)$pairingLimitedIncreasable[2]; # Improvement which can increase this limit
                $improvementMultiplier = (float)$pairingLimitedIncreasable[3]; # Multiplier used to extend the increase from improvement

                $unitsPerLimitingUnit *= 1 + ($this->improvementCalculator->getImprovementMultiplierBonus($dominion, $extendingImprovement) * $improvementMultiplier);

                $maxAdditionalPermittedOfThisUnit = intval($dominion->{'military_unit'.$unitLimitedTo} * $unitsPerLimitingUnit) - $this->militaryCalculator->getTotalUnitsForSlot($dominion, $slot) - $this->queueService->getTrainingQueueTotalByResource($dominion, 'military_unit'.$slot);

                $trainable[$unitType] = min($trainable[$unitType], $maxAdditionalPermittedOfThisUnit);
            }

            # Look for archmage_limit
            if($archmageLimit = $dominion->race->getUnitPerkValueForUnitSlot($slot,'archmage_limit'))
            {
                $unitsPerArchmage = (float)$archmageLimit[0]; # Units per archmage
                $improvementToIncrease = $archmageLimit[1]; # Resource that can raise the limit
                $improvementMultiplier = $archmageLimit[2]; # Multiplier used to extend the increase from improvement

                $unitsPerArchmage *= (1 + ($this->improvementCalculator->getImprovementMultiplierBonus($dominion, $improvementToIncrease) * $improvementMultiplier));

                $maxAdditionalPermittedOfThisUnit = intval($dominion->military_archmages * $unitsPerArchmage) - $this->militaryCalculator->getTotalUnitsForSlot($dominion, $slot) - $this->queueService->getTrainingQueueTotalByResource($dominion, 'military_unit'.$slot);

                $trainable[$unitType] = min($trainable[$unitType], $maxAdditionalPermittedOfThisUnit);
            }

            # Look for wizard_limit
            if($wizardLimit = $dominion->race->getUnitPerkValueForUnitSlot($slot,'wizard_limit'))
            {
                $unitsPerWizard = (float)$wizardLimit[0]; # Units per archmage
                $improvementToIncrease = $wizardLimit[1]; # Resource that can raise the limit

                $unitsPerWizard *= (1 + $this->improvementCalculator->getImprovementMultiplierBonus($dominion, $improvementToIncrease));

                $maxAdditionalPermittedOfThisUnit = intval($dominion->military_wizards * $unitsPerWizard) - $this->militaryCalculator->getTotalUnitsForSlot($dominion, $slot) - $this->queueService->getTrainingQueueTotalByResource($dominion, 'military_unit'.$slot);

                $trainable[$unitType] = min($trainable[$unitType], $maxAdditionalPermittedOfThisUnit);
            }

            # Look for spy_limit
            if($spyLimit = $dominion->race->getUnitPerkValueForUnitSlot($slot,'spy_limit'))
            {
                $unitsPerSpy = (float)$spyLimit[0]; # Units per archmage
                $improvementToIncrease = $spyLimit[1]; # Resource that can raise the limit

                $unitsPerSpy *= (1 + $this->improvementCalculator->getImprovementMultiplierBonus($dominion, $improvementToIncrease));

                $maxAdditionalPermittedOfThisUnit = intval($dominion->military_spies * $unitsPerWizard) - $this->militaryCalculator->getTotalUnitsForSlot($dominion, $slot) - $this->queueService->getTrainingQueueTotalByResource($dominion, 'military_unit'.$slot);

                $trainable[$unitType] = min($trainable[$unitType], $maxAdditionalPermittedOfThisUnit);
            }

            $trainable[$unitType] = max(0, $trainable[$unitType]);

        }
        return $trainable;
    }

    /**
     * Returns the Dominion's training cost multiplier.
     *
     * @param Dominion $dominion
     * @param string $resourceType
     * @return float
     */
    public function getSpecialistEliteCostMultiplier(Dominion $dominion, string $resourceType): float
    {
        $multiplier = 0;

        // Values (percentages)
        $smithiesReduction = 2;
        $smithiesReductionMax = 40;

        # Smithies: discount Gold (for all) and Ore (for non-Gnomes)
        # Armory: discounts Gold and Ore (for all)
        # Techs: discounts Gold, Ore, and Lumber (for all); Food ("Lean Mass" techs); Mana ("Magical Weapons" techs)

        // Only discount these resources.
        $discountableResourceTypesByArmory = ['gold', 'ore'];
        $discountableResourceTypesByTech = ['gold', 'ore', 'lumber'];
        $discountableResourceTypesByUnitBonus = ['gold', 'ore', 'lumber', 'mana', 'food'];

        $discountableResourceTypesByTechFood = ['food'];
        $discountableResourceTypesByTechMana = ['mana'];

        $racesExemptFromOreDiscountBySmithies = ['Gnome', 'Imperial Gnome'];

        // Buildings
        $multiplier -= $dominion->getBuildingPerkMultiplier('unit_' . $resourceType . '_costs');

        // Armory
        if(in_array($resourceType,$discountableResourceTypesByArmory))
        {
            $multiplier -= $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'armory');
        }

        // Techs
        if(in_array($resourceType,$discountableResourceTypesByTech))
        {
            $multiplier += $dominion->getTechPerkMultiplier('military_cost');
        }

        // Units
        if(in_array($resourceType,$discountableResourceTypesByUnitBonus))
        {
            $reducingUnits = 0;
            for ($slot = 1; $slot <= 4; $slot++)
            {
                if($reducesUnitCosts = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'reduces_unit_costs'))
                {
                    $reductionPerPercentOfUnit = (float)$reducesUnitCosts[0];
                    $maxReduction = (float)$reducesUnitCosts[1] / 100;
                    $unitMultiplier = min(($dominion->{'military_unit'.$slot} / $this->populationCalculator->getPopulation($dominion)) * $reductionPerPercentOfUnit, $maxReduction);
                    $multiplier -= $unitMultiplier;
                }
            }

        }

        // Title
        if(isset($dominion->title))
        {
            $multiplier += $dominion->title->getPerkMultiplier('unit_' . $resourceType . '_costs') * $dominion->title->getPerkBonus($dominion);
        }

        if(in_array($resourceType,$discountableResourceTypesByTechFood))
        {
            $multiplier += $dominion->getTechPerkMultiplier('military_cost_food');
        }

        if(in_array($resourceType,$discountableResourceTypesByTechMana))
        {
            $multiplier += $dominion->getTechPerkMultiplier('military_cost_mana');
        }

        # Cap reduction at -50%
        $multiplier = max(-0.50, $multiplier);

        # Spells: can take reduction below 50%!
        $multiplier += $dominion->getSpellPerkMultiplier('unit_' . $resourceType . '_costs');

        # Sanity cap, so it doesn't go under -1.
        $multiplier = max(-1, $multiplier);

        return (1 + $multiplier);
    }

    /**
     * Returns the Dominion's training gold cost multiplier for spies.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getSpyCostMultiplier(Dominion $dominion): float
    {
        $multiplier = 0;

        // Techs
        $multiplier += $dominion->getTechPerkMultiplier('spy_cost');

        // Buildings
        $multiplier -= $dominion->getBuildingPerkMultiplier('spy_cost');

        // Cap $multiplier at -50%
        $multiplier = max($multiplier, -0.50);

        return (1 + $multiplier);
    }

    /**
     * Returns the Dominion's training gold cost multiplier for wizards and archmages.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getWizardCostMultiplier(Dominion $dominion): float
    {
        $multiplier = 0;

        // Techs
        $multiplier += $dominion->getTechPerkMultiplier('wizard_cost');

        // Buildings
        $multiplier -= $dominion->getBuildingPerkMultiplier('wizard_cost');

        // Cap $multiplier at -100%
        $multiplier = max($multiplier, -1);

        return (1 + $multiplier);
    }

}
