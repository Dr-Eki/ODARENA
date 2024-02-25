<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Models\Dominion;

use OpenDominion\Helpers\UnitHelper;

use OpenDominion\Calculators\Dominion\MagicCalculator;
#use OpenDominion\Calculators\Dominion\PopulationCalculator;

use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Services\Dominion\StatsService;

class UnitCalculator
{

    /** @var MagicCalculator */
    protected $magicCalculator;

    /** @var PopulationCalculator */
    protected $populationCalculator;

    /** @var QueueService */
    protected $queueService;

    /** @var StatsService */
    protected $statsService;

    /** @var UnitHelper */
    protected $unitHelper;

    public function __construct()
    {
        $this->magicCalculator = new MagicCalculator();
        #$this->populationCalculator = new PopulationCalculator();
        $this->queueService = new QueueService();
        $this->statsService = new StatsService();
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
                    $netVictories = $this->statsService->getStat($dominion, 'invasion_victories') - $this->statsService->getStat($dominion, 'defense_failures');
                    $unitSlotTotal = $this->getUnitTypeTotal($dominion, $slot, true, true);

                    $amountProtected = min($unitSlotTotal, $netVictories) * $unitAttritionProtectionPerNetVictoriesPerk;

                    $unitAttritionAmount -= $amountProtected;
                }

                $unitAttritionAmount = max(0, min($unitAttritionAmount, $dominion->{'military_unit'.$slot})); # Sanity caps.

                $unitsAttrited[$slot] += round($unitAttritionAmount);
            }

            // Unit attrition if building limit exceeded
            if($unitBuildingLimitAttritionPerk = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'attrition_if_capacity_limit_exceeded') and $this->unitHelper->unitHasCapacityLimit($dominion, $slot))
            {
                $unitMaxCapacity = $this->unitHelper->getUnitMaxCapacity($dominion, $slot);

                $unitAmount = $dominion->{'military_unit'.$slot};

                $unitsSubjectToAttrition = $unitAmount - $unitMaxCapacity;

                $unitAttritionAmount = $unitsSubjectToAttrition * ($unitBuildingLimitAttritionPerk / 100);

                $unitAttritionAmount = max(0, min($unitAttritionAmount, $dominion->{'military_unit'.$slot})); # Sanity caps (greater than 0, and cannot exceed units at home)

                $unitsAttrited[$slot] += round($unitAttritionAmount);

            }
        }

        return $unitsAttrited;

    }

    public function getUnitLandGeneration(Dominion $dominion): int
    {     

        $generatedLand = 0;

        foreach($this->getDominionUnitBlankArray($dominion) as $slot => $zero)
        {
            # Defensive Warts turn off land generation
            if($dominion->getSpellPerkValue('stop_land_generation'))
            {
                break;
            }

            if($dominion->race->getUnitPerkValueForUnitSlot($slot, 'land_per_tick'))
            {
                $landPerTick = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'land_per_tick') * (1 - ($dominion->land/12000));
                $multiplier = 1;
                $multiplier += $dominion->getSpellPerkMultiplier('land_generation_mod');
                $multiplier += $dominion->getImprovementPerkMultiplier('land_generation_mod');
    
                $landPerTick *= $multiplier;
    
                $generatedLand += $dominion->{"military_unit".$slot} * $landPerTick;
                $generatedLand = max($generatedLand, 0);
    

            }
        }

        return $generatedLand;
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

    public function getEvolutionMultiplier(Dominion $dominion): float
    {
        $evolutionMultiplier = 1;

        $evolutionMultiplier += $dominion->getSpellPerkMultiplier('unit_evolution_mod');

        return $evolutionMultiplier;
    }

    public function getDominionUnitBlankArray(Dominion $dominion): array
    {
        $units = [];

        foreach ($dominion->race->units as $unit) {
            $units[$unit->slot] = 0;
        }

        return $units;
    }


    public function getUnitTypeTotalReturning(Dominion $dominion, string $unitType): int
    {
        return $this->getUnitTypeTotal($dominion, $unitType, true, false, true);
    }

    public function getUnitTypeTotalIncoming(Dominion $dominion, string $unitType): int
    {
        return $this->getUnitTypeTotal($dominion, $unitType, true, true, false);
    }

    public function getUnitTypeTotalTrained(Dominion $dominion, string $unitType): int
    {
        return $this->getUnitTypeTotal($dominion, $unitType, false, false, true);
    }

    public function getUnitTypeTotalPaid(Dominion $dominion, string $unitType): int
    {
        return $this->getUnitTypeTotal($dominion, $unitType, false, false, false);
    }

    public function getUnitTypeTotalAtHome(Dominion $dominion, string $unitType): int
    {
        return $this->getUnitTypeTotal($dominion, $unitType, false, true, true);
    }

    public function getUnitTypeTotal(Dominion $dominion, string $unitType, bool $excludeAtHome = false, bool $excludeReturning = false, bool $excludeIncoming = false): int
    {
        # Incoming = units the dominion does not yet own, but are in the process of being summoned, trained, or evolved
        # Returning = units the dominion does own, but are returning from something (an action)
        
        $unitKey = $this->getUnitKey($unitType);

        if($unitKey === null)
        {
            dd($unitKey, $unitType);
            return 0;
        }

        $units = $excludeAtHome ? 0 : $dominion->{$unitKey};

        # Returning queues
        if(!$excludeReturning)
        {
            $units += $this->queueService->getArtefactattackQueueTotalByResource($dominion, $unitKey);
            $units += $this->queueService->getDesecrationQueueTotalByResource($dominion, $unitKey);
            $units += $this->queueService->getExpeditionQueueTotalByResource($dominion, $unitKey);
            $units += $this->queueService->getInvasionQueueTotalByResource($dominion, $unitKey);
            $units += $this->queueService->getSabotageQueueTotalByResource($dominion, $unitKey);
            $units += $this->queueService->getStunQueueTotalByResource($dominion, $unitKey);
            $units += $this->queueService->getTheftQueueTotalByResource($dominion, $unitKey);    
        }

        # Incoming queues
        if(!$excludeIncoming)
        {
            $units += $this->queueService->getEvolutionQueueTotalByResource($dominion, $unitKey);
            $units += $this->queueService->getTrainingQueueTotalByResource($dominion, $unitKey);
            $units += $this->queueService->getSummoningQueueTotalByResource($dominion, $unitKey);
        }

        return $units;
    }

    public function getQueuedReturningUnitTypeAtTick(Dominion $dominion, string $unitType, int $tick): int
    {
        return $this->getQueuedUnitTypeAtTick($dominion, $unitType, $tick, false, true);
    }

    public function getQueuedIncomingUnitTypeAtTick(Dominion $dominion, string $unitType, int $tick): int
    {
        return $this->getQueuedUnitTypeAtTick($dominion, $unitType, $tick, true, false);
    }

    public function getQueuedUnitTypeAtTick(Dominion $dominion, string $unitType, int $tick, bool $excludeReturning = false, bool $excludeIncoming = false): int
    {
        # Incoming = units the dominion does not yet own, but are in the process of being summoned, trained, or evolved
        # Returning = units the dominion does own, but are returning from something (an action)
        
        $unitKey = $this->getUnitKey($unitType);

        if($unitKey === null)
        {
            return 0;
        }

        $units = 0;

        # Returning queues
        if(!$excludeReturning)
        {
            $units += $this->queueService->getArtefactattackQueueAmount($dominion, $unitKey, $tick);
            $units += $this->queueService->getDesecrationQueueAmount($dominion, $unitKey, $tick);
            $units += $this->queueService->getExpeditionQueueAmount($dominion, $unitKey, $tick);
            $units += $this->queueService->getInvasionQueueAmount($dominion, $unitKey, $tick);
            $units += $this->queueService->getSabotageQueueAmount($dominion, $unitKey, $tick);
            $units += $this->queueService->getStunQueueAmount($dominion, $unitKey, $tick);
            $units += $this->queueService->getTheftQueueAmount($dominion, $unitKey, $tick);
        }

        # Incoming queues
        if(!$excludeIncoming)
        {
            $units += $this->queueService->getTrainingQueueAmount($dominion, $unitKey, $tick);
            $units += $this->queueService->getSummoningQueueAmount($dominion, $unitKey, $tick);
            $units += $this->queueService->getEvolutionQueueAmount($dominion, $unitKey, $tick);
        }

        return $units;
    }

    public function getUnitSlot($unitType): ?string
    {
        if (strpos($unitType, 'military_unit') === 0 || strpos($unitType, 'unit') === 0) {
            return preg_replace('/\D/', '', $unitType);
        }
    
        return null;
    }

    public function getUnitKey($unitType): ?string
    {

        $lookup = [
            'draftees' => 'military_draftees',
            'military_draftees' => 'military_draftees',
            'spies' => 'military_spies',
            'military_spies' => 'military_spies',
            'wizards' => 'military_wizards',
            'military_wizards' => 'military_wizards',
            'archmages' => 'military_archmages',
            'military_archmages' => 'military_archmages',
        ];
        
        $unitKey = $lookup[$unitType] ?? null;

        if($unitKey == null)
        {
            $unitKey = is_numeric($unitType) ? 'military_unit' . $unitType : 'military_unit' . $this->getUnitSlot($unitType);
        }

        return $unitKey;
    }

    public function getDominionUnitKeys(Dominion $dominion): array
    {
        $unitKeys = [];

        !$dominion->race->getPerkValue('no_drafting') ?: $unitKeys[] = 'draftees';

        foreach($dominion->race->units as $unit)
        {
            $unitKeys[] = 'unit'.$this->getUnitKey($unit->slot);
        }

        !$dominion->race->getPerkValue('cannot_train_spies') ?: $unitKeys[] = 'spies';
        !$dominion->race->getPerkValue('cannot_train_wizards') ?: $unitKeys[] = 'wizards';
        !$dominion->race->getPerkValue('cannot_train_archmages') ?: $unitKeys[] = 'archmages';

        return $unitKeys;

    }
}
