<?php

// We want strict types here.
declare(strict_types=1);


namespace OpenDominion\Calculators\Dominion;

use Illuminate\Support\Str;

use OpenDominion\Models\Dominion;
use OpenDominion\Models\Resource;
use OpenDominion\Models\Unit;

use OpenDominion\Helpers\UnitHelper;


use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Services\Dominion\StatsService;

class UnitCalculator
{

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
        $this->queueService = app(QueueService::class);
        $this->statsService = app(StatsService::class);
        $this->unitHelper = app(UnitHelper::class);
    }

    private function getPopulationCalculator() {
        if (!$this->populationCalculator) {
            $this->populationCalculator = app(PopulationCalculator::class);
        }
        return $this->populationCalculator;
    }

    public function getUnitsGenerated(Dominion $dominion): array
    {
        $units = $this->getDominionUnitBlankArray($dominion);
        $unitsGenerated = $units;

        if($dominion->isAbandoned() or $dominion->round->hasEnded() or $dominion->isLocked())
        {
            return $unitsGenerated;
        }


        if($raceUnitsGenerationBuildingPerks = $dominion->getBuildingPerkValue($dominion->race->key . '_units_production'))
        {
            foreach($raceUnitsGenerationBuildingPerks as $raceUnitsGenerationBuildingPerk)
            {
                foreach($raceUnitsGenerationBuildingPerk as $buildingKey => $raceUnitsGenerationBuildingPerkData)
                {
                    
                    $buildingAmount = (float)$raceUnitsGenerationBuildingPerkData['buildings_amount'];
                    $amountPerBuilding = (float)$raceUnitsGenerationBuildingPerkData['amount_per_building'];
                    $generatedUnitSlots = (array)$raceUnitsGenerationBuildingPerkData['generated_unit_slots'];
        
                    foreach($generatedUnitSlots as $key => $slot)
                    {
                        $multiplier = 1;
                        $multiplier += $dominion->getImprovementPerkMultiplier($dominion->race->key . '_unit' . $slot . '_generation_mod');
        
                        $amountGenerated = $buildingAmount * $amountPerBuilding;

                        $amountGenerated *= $multiplier;
                        $unitsGenerated[$slot] += (int)floor($amountGenerated);
                    }
                }
            }

        }

        foreach($units as $slot => $zero)
        {
            $unitsToSummon = 0;
            $raceKey = $dominion->race->key;

            // Original line:
            // $availablePopulation = $this->populationCalculator->getMaxPopulation($dominion) - $this->getMilitaryUnitsTotal($dominion);

            // Modified to use the getter method:
            $availablePopulation = $this->getPopulationCalculator()->getMaxPopulation($dominion) - $this->getMilitaryUnitsTotal($dominion);

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
        
                $unitSummoning = $buildingUnitSummoningRaw * $unitSummoningMultiplier;
    
                # Check for capacity limit
                if($this->unitHasCapacityLimit($dominion, $slot))
                {
                    $maxCapacity = $this->getUnitMaxCapacity($dominion, $slot);
    
                    $usedCapacity = $this->getUnitTypeTotalPaid($dominion, $slot);

                    /*
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
                    */
    
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

        return $unitsGenerated;
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
            if($unitBuildingLimitAttritionPerk = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'attrition_if_capacity_limit_exceeded') and $this->unitHasCapacityLimit($dominion, $slot))
            {
                $unitMaxCapacity = $this->getUnitMaxCapacity($dominion, $slot);

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

        return (int)$generatedLand;
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
            $attritionMultiplier -= ($dominion->military_unit3 + $dominion->military_unit4) / max($this->getMilitaryUnitsTotal($dominion),1);
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
        return (int)$this->getUnitTypeTotal($dominion, $unitType, true, false, true);
    }

    public function getUnitTypeTotalIncoming(Dominion $dominion, string $unitType): int
    {
        return (int)$this->getUnitTypeTotal($dominion, $unitType, true, true, false);
    }

    public function getUnitTypeTotalTrained(Dominion $dominion, string $unitType): int
    {
        return (int)$this->getUnitTypeTotal($dominion, $unitType, false, false, true);
    }

    public function getUnitTypeTotalPaid(Dominion $dominion, string $unitType): int
    {
        return (int)$this->getUnitTypeTotal($dominion, $unitType, false, false, false);
    }

    public function getUnitTypeTotalAtHome(Dominion $dominion, string $unitType): int
    {
        return (int)$this->getUnitTypeTotal($dominion, $unitType, false, true, true);
    }

    public function getUnitTypeTotal(Dominion $dominion, string $unitType, bool $excludeAtHome = false, bool $excludeReturning = false, bool $excludeIncoming = false): int
    {
        # Incoming = units the dominion does not yet own, but are in the process of being summoned, trained, or evolved
        # Returning = units the dominion does own, but are returning from something (an action)
        
        $unitKey = $this->getUnitKey($unitType);

        if($unitKey === null)
        {
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

        return (int)$units;
    }

    public function getQueuedReturningUnitTypeAtTick(Dominion $dominion, string $unitType, int $tick): int
    {
        return (int)$this->getQueuedUnitTypeAtTick($dominion, $unitType, $tick, false, true);
    }

    public function getQueuedIncomingUnitTypeAtTick(Dominion $dominion, string $unitType, int $tick): int
    {
        return (int)$this->getQueuedUnitTypeAtTick($dominion, $unitType, $tick, true, false);
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

        return (int)$units;
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

    public function getMilitaryUnitsTotal(Dominion $dominion): int
    {
        $total = 0;

        foreach($this->getDominionUnitKeys($dominion) as $unitKey)
        {
            $total += $dominion->{$unitKey};
        }

        return (int)$total;
    }

    # This does not take cost into consideration
    public function isUnitTrainableByDominion($unit, Dominion $dominion): bool
    {

        if(is_a($unit, 'OpenDominion\Models\Unit', true))
        {
            if($dominion->race->getUnitPerkValueForUnitSlot($unit->slot, 'cannot_be_trained'))
            {
                return false;
            }

            if(!$this->checkUnitAndDominionDeityMatch($dominion, $unit))
            {
                return false;
            }
        }
        elseif($dominion->race->getPerkValue('cannot_train_' . $unit))
        {
            return false;
        }

        return true;
    }

    public function checkUnitAndDominionDeityMatch(Dominion $dominion, Unit $unit): bool
    {
        if(isset($unit->deity))
        {
            if(!$dominion->hasDeity())
            {
                return false;
            }
            elseif($dominion->deity->id !== $unit->deity->id)
            {
                return false;
            }
        }

        return true;
    }

    # This does not take cost or pairing limits into consideration
    public function isUnitSendableByDominion(Unit $unit, Dominion $dominion): bool
    {
        if(!$this->checkUnitAndDominionDeityMatch($dominion, $unit))
        {
            return false;
        }

        return true;
    }

    public function unitHasCapacityLimit(Dominion $dominion, int $slot): bool
    {
        $perkKeys = [
            'pairing_limit',
            'pairing_limit_including_away',
            'building_limit',
            'archmage_limit',
            'net_victories_limit',
            'stat_pairing_limit',
            'amount_limit',
            #'minimum_victories'
        ];
    
        foreach ($perkKeys as $perkKey) {
            if ($dominion->race->getUnitPerkValueForUnitSlot($slot, $perkKey)) {
                return true;
            }
        }
    
        return false;
    }

    public function getUnitMaxCapacityMultiplier(Dominion $dominion, int $slotLimited): float
    {
        $limitMultiplier = 1;
        $limitMultiplier += $dominion->getImprovementPerkMultiplier('unit_pairing');
        $limitMultiplier += $dominion->getBuildingPerkMultiplier('unit_pairing');
        $limitMultiplier += $dominion->getBuildingPerkMultiplier('unit_pairing_capped');
        $limitMultiplier += $dominion->getSpellPerkMultiplier('unit_pairing');
        $limitMultiplier += $dominion->getTerrainPerkMultiplier('unit_pairing_mod');

        return $limitMultiplier;
    }

    public function getUnitMaxCapacity(Dominion $dominion, int $slotLimited): int
    {

        $limitMultiplier = $this->getUnitMaxCapacityMultiplier($dominion, $slotLimited);

        # Unit:unit limit
        if($pairingLimit = $dominion->race->getUnitPerkValueForUnitSlot($slotLimited, 'pairing_limit'))
        {
            $slotLimitedTo = (int)$pairingLimit[0];
            $perUnitLimitedTo = (float)$pairingLimit[1];

            $limitingUnits = $dominion->{'military_unit' . $slotLimitedTo};

            return floorInt($limitingUnits * $perUnitLimitedTo * $limitMultiplier);
        }

        # Unit:unit limit (including_away)
        if($pairingLimit = $dominion->race->getUnitPerkValueForUnitSlot($slotLimited, 'pairing_limit_including_away'))
        {
            $slotLimitedTo = (string)$pairingLimit[0];
            $perUnitLimitedTo = (float)$pairingLimit[1];

            $limitingUnits = $this->getUnitTypeTotalPaid($dominion, $slotLimitedTo);

            return floorInt($limitingUnits * $perUnitLimitedTo * $limitMultiplier);
        }

        # Unit:building limit
        if($pairingLimit = $dominion->race->getUnitPerkValueForUnitSlot($slotLimited, 'building_limit'))
        {
            $buildingKeyLimitedTo = (string)$pairingLimit[0];
            $perBuildingLimitedTo = (float)$pairingLimit[1];

            $limitingBuildings = $dominion->{'building_' . $buildingKeyLimitedTo};
            
            # SNOW ELF
            if($dominion->getBuildingPerkValue($dominion->race->key . '_unit' . $slotLimited . '_production_raw_capped') and $dominion->race->name == 'Snow Elf')
            {
                # Hardcoded 20% production cap
                $limitingBuildings = min($limitingBuildings, $dominion->land * 0.20);
            }

            return floorInt($limitingBuildings * $perBuildingLimitedTo * $limitMultiplier);

        }

        # Unit:archmages limit
        if($pairingLimit = $dominion->race->getUnitPerkValueForUnitSlot($slotLimited, 'archmage_limit'))
        {
            $perArchmage = (float)$pairingLimit;
            return floorInt($perArchmage * $dominion->military_archmages * $limitMultiplier);
        }

        # Unit:net_victories limit
        if($pairingLimit = $dominion->race->getUnitPerkValueForUnitSlot($slotLimited, 'net_victories_limit'))
        {
            $perNetVictory = (float)$pairingLimit[0];

            $netVictories = $this->statsService->getStat($dominion, 'invasion_victories') - $this->statsService->getStat($dominion, 'defense_failures');

            return floorInt($perNetVictory * $netVictories);
        }

        # Unit:stat_pairing_limit limit
        if($statPairingLimit = $dominion->race->getUnitPerkValueForUnitSlot($slotLimited, 'stat_pairing_limit'))
        {
            $statKey = (string)$statPairingLimit[0];
            $perStat = (float)$statPairingLimit[1];

            $statValue = $this->statsService->getStat($dominion, $statKey);

            return floorInt($statValue * $perStat);
        }

        # Unit limit
        if($pairingLimit = $dominion->race->getUnitPerkValueForUnitSlot($slotLimited, 'amount_limit'))
        {
            return floorInt($pairingLimit);
        }

        return 0;

    }

    public function checkUnitLimitForTraining(Dominion $dominion, int $slotLimited, int $amountToTrain): bool
    {
        if(!$this->unitHasCapacityLimit($dominion, $slotLimited))
        {
            return true;
        }

        $maxCapacity = $this->getUnitMaxCapacity($dominion, $slotLimited);

        $currentlyTrained = $this->getUnitTypeTotalPaid($dominion, 'military_unit' . $slotLimited);
        /*
        $dominion->{'military_unit' . $slotLimited};
        $currentlyTrained += $this->queueService->getTrainingQueueTotalByResource($dominion, 'military_unit' . $slotLimited);
        $currentlyTrained += $this->queueService->getSummoningQueueTotalByResource($dominion, 'military_unit' . $slotLimited);
        $currentlyTrained += $this->queueService->getEvolutionQueueTotalByResource($dominion, 'military_unit' . $slotLimited);
        $currentlyTrained += $this->queueService->getInvasionQueueTotalByResource($dominion, 'military_unit' . $slotLimited);
        $currentlyTrained += $this->queueService->getExpeditionQueueTotalByResource($dominion, 'military_unit' . $slotLimited);
        $currentlyTrained += $this->queueService->getTheftQueueTotalByResource($dominion, 'military_unit' . $slotLimited);
        $currentlyTrained += $this->queueService->getSabotageQueueTotalByResource($dominion, 'military_unit' . $slotLimited);
        $currentlyTrained += $this->queueService->getDesecrationQueueTotalByResource($dominion, 'military_unit' . $slotLimited);
        $currentlyTrained += $this->queueService->getStunQueueTotalByResource($dominion, 'military_unit' . $slotLimited);
        $currentlyTrained += $this->queueService->getArtefactattackQueueTotalByResource($dominion, 'military_unit' . $slotLimited);
        */

        $totalWithAmountToTrain = $currentlyTrained + $amountToTrain;

        return $maxCapacity >= $totalWithAmountToTrain;

    }

    public function checkUnitLimitForInvasion(Dominion $dominion, int $slotLimited, int $amountToSend): bool
    {
        $maxCapacity = $this->getUnitMaxCapacity($dominion, $slotLimited);

        if($this->unitHasCapacityLimit($dominion, $slotLimited))
        {
            return $maxCapacity >= $amountToSend;
        }

        return true;
    }

    public function getUnitCapacityAvailable(Dominion $dominion, int $unitSlot): int
    {

        if(!$this->unitHasCapacityLimit($dominion, $unitSlot))
        {
            return -1;
        }

        $maxCapacity = $this->getUnitMaxCapacity($dominion, $unitSlot);

        $usedCapacity = $this->getUnitTypeTotalPaid($dominion, 'military_unit' . $unitSlot);

        return max(0, $maxCapacity - $usedCapacity);

    }

    public function getUnitCostValue(Unit $unit): float
    {
        $cost = 0;

        foreach($unit->cost as $resourceKey => $resourceCost)
        {

            if($resourceKey == 'draftees' or $resourceKey == 'peasants')
            {
                $cost += $resourceCost;
                continue;
            }
            
            if($resourceKey == 'spies' or $resourceKey == 'wizards')
            {
                $cost += $resourceCost * 500;
                continue;
            }
            
            if($resourceKey == 'archmages')
            {
                $cost += $resourceCost * 1000;
                continue;
            }

            if(Str::startsWith($resourceKey, 'unit'))
            {
                $subUnitSlot = (int)substr($resourceKey, 4);
                $subUnit = Unit::where('slot', $subUnitSlot)->where('race_id', $unit->race->id)->first();
                $cost += $this->getUnitCostValue($subUnit) * $resourceCost;
                continue;
            }

            $resource = Resource::fromKey($resourceKey);

            $cost += $resource->trade->buy * $resourceCost;
        }

        return $cost;
    }

}
