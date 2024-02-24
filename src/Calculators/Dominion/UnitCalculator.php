<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Models\Dominion;

use OpenDominion\Helpers\UnitHelper;

use OpenDominion\Calculators\Dominion\MagicCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;

use OpenDominion\Services\Dominion\QueueService;

class UnitCalculator
{

    /** @var MagicCalculator */
    protected $magicCalculator;

    /** @var MilitaryCalculator */
    protected $militaryCalculator;

    /** @var PopulationCalculator */
    protected $populationCalculator;

    /** @var QueueService */
    protected $queueService;

    /** @var UnitHelper */
    protected $unitHelper;

    public function __construct()
    {
        $this->magicCalculator = new MagicCalculator();
        $this->militaryCalculator = new MilitaryCalculator();
        $this->populationCalculator = new PopulationCalculator();
        $this->queueService = new QueueService();
        $this->unitHelper = new UnitHelper();
    }

    public function getUnitsGenerated(Dominion $dominion): array
    {
        $units = $this->getDominionUnitBlankArray($dominion);
        $unitsGenerated = $units;

        foreach($units as $slot => $zero)
        {
            $unitsToSummon = 0;
            $raceKey = str_replace(' ', '_', strtolower($dominion->race->name));

            $availablePopulation = $this->populationCalculator->getMaxPopulation($dominion) - $this->populationCalculator->getPopulationMilitary($dominion);

            // Myconid and Cult: Unit generation
            if($unitGenerationPerk = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'unit_production'))
            {
                $unitToGenerateSlot = $unitGenerationPerk[0];
                $unitAmountToGeneratePerGeneratingUnit = $unitGenerationPerk[1];
                $unitAmountToGenerate = $dominion->{'military_unit'.$slot} * $unitAmountToGeneratePerGeneratingUnit;

                $unitAmountToGenerate = max(0, min($unitAmountToGenerate, $availablePopulation));

                $unitsToSummon += $unitAmountToGenerate;

                $availablePopulation -= $unitAmountToGenerate;
            }

            # Passive unit generation from buildings
            $buildingUnitSummoningRaw = $dominion->getBuildingPerkValue($raceKey . '_unit' . $slot . '_production_raw');
            $buildingUnitSummoningRaw += $dominion->getBuildingPerkValue($raceKey . '_unit' . $slot . '_production_raw_capped');

            if($buildingUnitSummoningRaw > 0)
            {
                $unitSummoningMultiplier = 1;
                $unitSummoningMultiplier += $dominion->getBuildingPerkMultiplier($raceKey . '_unit' . $slot . '_production_mod');
                $unitSummoningMultiplier += $dominion->getSpellPerkMultiplier($raceKey . '_unit' . $slot . '_production_mod');
    
                if($unitProductionFromWizardRatioPerk = $dominion->getBuildingPerkValue('unit_production_from_wizard_ratio'))
                {
                    $unitSummoningMultiplier += $this->magicCalculator->getWizardRatio($dominion) / $unitProductionFromWizardRatioPerk;
                }
    
                $unitSummoning = $buildingUnitSummoningRaw * $unitSummoningMultiplier;
    
                # Check for capacity limit
                if($this->unitHelper->unitHasCapacityLimit($dominion, $slot))
                {
                    $maxCapacity = $this->unitHelper->getUnitMaxCapacity($dominion, $slot);
    
                    $usedCapacity = $dominion->{'military_unit' . $slot};
                    $usedCapacity += $this->queueService->getTrainingQueueTotalByResource($dominion, 'military_unit' . $slot);
                    $usedCapacity += $this->queueService->getStunQueueTotalByResource($dominion, 'military_unit' . $slot);
                    $usedCapacity += $this->queueService->getSummoningQueueTotalByResource($dominion, 'military_unit' . $slot);
                    $usedCapacity += $this->queueService->getEvolutionQueueTotalByResource($dominion, 'military_unit' . $slot);
                    $usedCapacity += $this->queueService->getInvasionQueueTotalByResource($dominion, 'military_unit' . $slot);
                    $usedCapacity += $this->queueService->getExpeditionQueueTotalByResource($dominion, 'military_unit' . $slot);
                    $usedCapacity += $this->queueService->getTheftQueueTotalByResource($dominion, 'military_unit' . $slot);
                    $usedCapacity += $this->queueService->getSabotageQueueTotalByResource($dominion, 'military_unit' . $slot);
                    $usedCapacity += $this->queueService->getStunQueueTotalByResource($dominion, 'military_unit' . $slot);
                    $usedCapacity += $this->queueService->getDesecrationQueueTotalByResource($dominion, 'military_unit' . $slot);
                    $usedCapacity += $this->queueService->getArtefactattackQueueTotalByResource($dominion, 'military_unit' . $slot);
    
                    $availableCapacity = max(0, $maxCapacity - $usedCapacity);
    
                    $unitsToSummon = floor(min($unitSummoning, $availableCapacity));
                }
                # If no capacity limit
                else
                {
                    $unitsToSummon = $unitSummoning;
                }
            }

            # Passive unit generation from decrees
            $decreeUnitSummoningRaw = $dominion->getDecreePerkValue($raceKey . '_unit' . $slot . '_production_raw');

            if($decreeUnitSummoningRaw)
            {
                $decreeUnitSummoningPerks = explode(',', $decreeUnitSummoningRaw);
                
                $slotProduced = (int)$decreeUnitSummoningPerks[0];
                $amountProduced = (float)$decreeUnitSummoningPerks[1];
                $slotProducing = (int)$decreeUnitSummoningPerks[2];

                $unitSummoningMultiplier = 1;
                $unitSummoningMultiplier += $dominion->getBuildingPerkMultiplier($raceKey . '_unit' . $slot . '_production_mod');
                $unitSummoningMultiplier += $dominion->getSpellPerkMultiplier($raceKey . '_unit' . $slot . '_production_mod');

                $unitSummoning = $dominion->{'military_unit' . $slotProducing} * $amountProduced * $unitSummoningMultiplier;
    
                # Check for capacity limit
                if($this->unitHelper->unitHasCapacityLimit($dominion, $slot))
                {
                    $maxCapacity = $this->unitHelper->getUnitMaxCapacity($dominion, $slot);
    
                    $usedCapacity = $dominion->{'military_unit' . $slot};
                    $usedCapacity += $this->queueService->getTrainingQueueTotalByResource($dominion, 'military_unit' . $slot);
                    $usedCapacity += $this->queueService->getStunQueueTotalByResource($dominion, 'military_unit' . $slot);
                    $usedCapacity += $this->queueService->getSummoningQueueTotalByResource($dominion, 'military_unit' . $slot);
                    $usedCapacity += $this->queueService->getEvolutionQueueTotalByResource($dominion, 'military_unit' . $slot);
                    $usedCapacity += $this->queueService->getInvasionQueueTotalByResource($dominion, 'military_unit' . $slot);
                    $usedCapacity += $this->queueService->getExpeditionQueueTotalByResource($dominion, 'military_unit' . $slot);
                    $usedCapacity += $this->queueService->getTheftQueueTotalByResource($dominion, 'military_unit' . $slot);
                    $usedCapacity += $this->queueService->getSabotageQueueTotalByResource($dominion, 'military_unit' . $slot);
                    $usedCapacity += $this->queueService->getStunQueueTotalByResource($dominion, 'military_unit' . $slot);
                    $usedCapacity += $this->queueService->getDesecrationQueueTotalByResource($dominion, 'military_unit' . $slot);
                    $usedCapacity += $this->queueService->getArtefactattackQueueTotalByResource($dominion, 'military_unit' . $slot);
    
                    $availableCapacity = max(0, $maxCapacity - $usedCapacity);
    
                    $unitsToSummon = floor(min($unitSummoning, $availableCapacity));
                }
                # If no capacity limit
                else
                {
                    $unitsToSummon = $unitSummoning;
                }
            }

            # Because you never know...
            $unitsToSummon = (int)max($unitsToSummon, 0);

            $unitsGenerated[$slot] += $unitsToSummon;
        }

        return $units;
    }


    public function getUnitsAttrited(Dominion $dominion): array
    {
        $units = $this->getDominionUnitBlankArray($dominion);
        $unitsAttrited = $units;

        $attritionMultiplier = $this->getAttritionMultiplier($dominion);

        foreach($units as $slot => $zero)
        {
            // Unit attrition
            if($unitAttritionPerk = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'attrition'))
            {
                $unitAttritionAmount = $dominion->{'military_unit'.$slot} * $unitAttritionPerk/100 * $attritionMultiplier;

                if($attritionProtection = $dominion->getBuildingPerkValue('attrition_protection'))
                {
                    $amountProtected = $attritionProtection[0];
                    $slotProtected = $attritionProtection[1];
                    $amountProtected *= 1 + $dominion->getImprovementPerkMultiplier('attrition_protection');

                    if($slot == $slotProtected)
                    {
                        $unitAttritionAmount -= $amountProtected;
                    }
                }

                if($unitAttritionProtectionPerNetVictoriesPerk = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'attrition_protection_from_net_victories'))
                {
                    $amountProtected = min($this->militaryCalculator->getTotalUnitsForSlot($dominion, $slot), $this->militaryCalculator->getNetVictories($dominion)) * $unitAttritionProtectionPerNetVictoriesPerk;

                    $unitAttritionAmount -= $amountProtected;
                }

                $unitAttritionAmount = max(0, min($unitAttritionAmount, $dominion->{'military_unit'.$slot})); # Sanity caps.

                ${'attritionUnit' . $slot} += round($unitAttritionAmount);
            }

            // Unit attrition if building limit exceeded
            if($unitBuildingLimitAttritionPerk = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'attrition_if_capacity_limit_exceeded') and $this->unitHelper->unitHasCapacityLimit($dominion, $slot))
            {
                $unitMaxCapacity = $this->unitHelper->getUnitMaxCapacity($dominion, $slot);

                $unitAmount = $dominion->{'military_unit'.$slot};

                $unitsSubjectToAttrition = $unitAmount - $unitMaxCapacity;

                $unitAttritionAmount = $unitsSubjectToAttrition * ($unitBuildingLimitAttritionPerk / 100);

                $unitAttritionAmount = max(0, min($unitAttritionAmount, $dominion->{'military_unit'.$slot})); # Sanity caps (greater than 0, and cannot exceed units at home)

                ${'attritionUnit' . $slot} += round($unitAttritionAmount);

            }
        }


        return $unitsAttrited;

    }

    public function getAttritionMultiplier(Dominion $dominion): float
    {
        $attritionMultiplier = 1;
        
        // Check for no-attrition perks.
        if($dominion->getSpellPerkValue('no_attrition'))
        {
            return 0;
        }

        # Cult unit attrition reduction
        if($dominion->race->name == 'Cult')
        {
            $attritionMultiplier -= ($dominion->military_unit3 + $dominion->military_unit4) / max($this->populationCalculator->getPopulationMilitary($dominion),1);
        }

        # Generic attrition perks
        $attritionMultiplier -= $dominion->getBuildingPerkMultiplier('reduces_attrition'); # Positive value, hence -
        $attritionMultiplier += $dominion->getImprovementPerkMultiplier('attrition_mod'); # Negative value, hence +
        $attritionMultiplier += $dominion->getDecreePerkMultiplier('attrition_mod'); # Negative value, hence +
        $attritionMultiplier += $dominion->getSpellPerkMultiplier('attrition_mod'); # Negative value, hence +
        $attritionMultiplier += $dominion->getTerrainPerkMultiplier('attrition_mod'); # Negative value, hence +

        # Cap at -100%
        $attritionMultiplier = max(-1, $attritionMultiplier);

        return $attritionMultiplier;
    }

    public function getDominionUnitBlankArray(Dominion $dominion): array
    {
        $units = [];

        foreach ($dominion->race->units as $unit) {
            $units[$unit->slot] = 0;
        }

        return $units;
    }
    
}
