<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Helpers\ConversionHelper;
use OpenDominion\Helpers\UnitHelper;

use OpenDominion\Models\Dominion;

use OpenDominion\Calculators\Dominion\CasualtiesCalculator;
use OpenDominion\Calculators\Dominion\ConversionCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Calculators\Dominion\ResourceCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;

class ResourceConversionCalculator
{

    protected $casualtiesCalculator;
    protected $conversionCalculator;
    protected $landCalculator;
    protected $militaryCalculator;
    protected $populationCalculator;
    protected $rangeCalculator;
    protected $resourceCalculator;
    protected $spellCalculator;

    protected $conversionHelper;
    protected $unitHelper;

    public function __construct()
    {
        $this->casualtiesCalculator = app(CasualtiesCalculator::class);
        $this->conversionCalculator = app(ConversionCalculator::class);
        $this->landCalculator = app(LandCalculator::class);
        $this->militaryCalculator = app(MilitaryCalculator::class);
        $this->populationCalculator = app(PopulationCalculator::class);
        $this->rangeCalculator = app(RangeCalculator::class);
        $this->resourceCalculator = app(ResourceCalculator::class);
        $this->spellCalculator = app(SpellCalculator::class);

        $this->conversionHelper = app(ConversionHelper::class);
        $this->unitHelper = app(UnitHelper::class);
    }

    public function getResourceConversions(Dominion $converter, Dominion $enemy, array $invasion, string $mode = 'offense'): array
    {
        $resourceConversionPerks = [
            'kills_into_resource_per_casualty',
            'kills_into_resource_per_casualty_on_success',
            'kills_into_resources_per_casualty',
            'kills_into_resource_per_value',
            'kills_into_resource_per_value_on_success',
            'kills_into_resources_per_value',
            'converts_displaced_peasants_into_resource',
            'converts_displaced_peasants_into_resources',

            'dies_into_resource',
            'dies_into_resource_on_success',
        ];

        $resourcesThatUseEntireBodies = ['body'];

        $convertingUnits = array_fill(1, $converter->race->units->count(), 0);

        foreach($converter->race->resources as $resourceKey)
        {
            $resourceConversions[$resourceKey] = 0;
        }

        # Check if any converting units were sent and survived
        foreach($converter->race->units as $unit)
        {
            if($mode == 'offense' and $this->unitHelper->checkUnitHasPerks($converter, $unit, $resourceConversionPerks) and isset($invasion['attacker']['units_surviving'][$unit->slot]))
            {
                $convertingUnits[$unit->slot] = 1;
            }

            if($mode == 'defense' and $this->unitHelper->checkUnitHasPerks($converter, $unit, $resourceConversionPerks) and isset($invasion['defender']['units_surviving'][$unit->slot]))
            {
                $convertingUnits[$unit->slot] = 1;
            }
        }

        if(!array_sum($convertingUnits))
        {
            return $resourceConversions;
        }

        $resourceConversions['bodies_taken'] = 0;

        if($mode == 'offense')
        {
            $converterUnits = $invasion['attacker']['units_surviving'];
            $converterUnitsLost = $invasion['attacker']['units_lost'];
            $enemyUnitsKilled = $invasion['defender']['units_lost'];
        }
        else
        {
            $converterUnits = $invasion['defender']['units_surviving'];
            $converterUnitsLost = $invasion['defender']['units_lost'];
            $enemyUnitsKilled = $invasion['attacker']['units_lost'];
        }

        $resourceConversions = $this->getResourceKeysArray($converter);

        $convertingUnits = array_fill(1, $converter->race->units->count(), []);

        foreach($converterUnits as $converterUnitSlot => $converterUnitAmount)
        {
            if(!in_array($converterUnitSlot, ['draftees','peasants','spies','wizards','archmages']))
            {    
                $unit = $this->unitHelper->getRaceUnitFromSlot($converter->race, $converterUnitSlot);

                if(
                    # Single
                    $this->unitHelper->checkUnitHasPerks($converter, $unit, ['kills_into_resource_per_casualty']) or 
                    ($invasion['result']['success'] and $mode == 'offense' and $this->unitHelper->checkUnitHasPerks($converter, $unit, ['kills_into_resource_per_casualty_on_success'])) or
                    (!$invasion['result']['success'] and $mode == 'defense' and $this->unitHelper->checkUnitHasPerks($converter, $unit, ['kills_into_resource_per_casualty_on_success'])) or 
                    $this->unitHelper->checkUnitHasPerks($converter, $unit, ['converts_displaced_peasants_into_resource']) or


                    $this->unitHelper->checkUnitHasPerks($converter, $unit, ['kills_into_resource_per_value']) or 
                    ($invasion['result']['success'] and $mode == 'offense' and $this->unitHelper->checkUnitHasPerks($converter, $unit, ['kills_into_resource_per_value_on_success'])) or
                    (!$invasion['result']['success'] and $mode == 'defense' and $this->unitHelper->checkUnitHasPerks($converter, $unit, ['kills_into_resource_per_value_on_success'])) or 

                    # Multi
                    $this->unitHelper->checkUnitHasPerks($converter, $unit, ['kills_into_resources_per_casualty']) or
                    ($invasion['result']['success'] and $mode == 'offense' and $this->unitHelper->checkUnitHasPerks($converter, $unit, ['kills_into_resources_per_casualty_on_success'])) or
                    (!$invasion['result']['success'] and $mode == 'defense' and $this->unitHelper->checkUnitHasPerks($converter, $unit, ['kills_into_resources_per_casualty_on_success'])) or
                    $this->unitHelper->checkUnitHasPerks($converter, $unit, ['converts_displaced_peasants_into_resources']) or

                    $this->unitHelper->checkUnitHasPerks($converter, $unit, ['kills_into_resources_per_value']) or
                    ($invasion['result']['success'] and $mode == 'offense' and $this->unitHelper->checkUnitHasPerks($converter, $unit, ['kills_into_resources_per_value_on_success'])) or
                    (!$invasion['result']['success'] and $mode == 'defense' and $this->unitHelper->checkUnitHasPerks($converter, $unit, ['kills_into_resources_per_value_on_success'])) or

                    # Dies into resource
                    $this->unitHelper->checkUnitHasPerks($converter, $unit, ['dies_into_resource']) or
                    ($invasion['result']['success'] and $mode == 'offense' and $this->unitHelper->checkUnitHasPerks($converter, $unit, ['dies_into_resource_on_success'])) or
                    (!$invasion['result']['success'] and $mode == 'defense' and $this->unitHelper->checkUnitHasPerks($converter, $unit, ['dies_into_resource_on_success']))
                    
                    )
                {
                    if($mode == 'offense')
                    {
                        $unitsRawOp = $this->militaryCalculator->getOffensivePowerRaw($converter, $enemy, null, [$converterUnitSlot => $converterUnitAmount]);
                        $totalSurvivingRawOp = $this->militaryCalculator->getOffensivePowerRaw($converter, $enemy, null, $invasion['attacker']['units_surviving']);

                        $convertingUnits[$converterUnitSlot] = [
                            'amount' => $converterUnitAmount,
                            'power' => $unitsRawOp,
                            'power_proportion' => $unitsRawOp / $totalSurvivingRawOp #$invasion['attacker']['op_raw']
                            ];
                    }
                    else
                    {
                        $unitsRawDp = $this->militaryCalculator->getDefensivePowerRaw($converter, $enemy, null, [$converterUnitSlot => $converterUnitAmount]);
                        $totalSurvivingRawDp = $this->militaryCalculator->getDefensivePowerRaw($converter, $enemy, null, $invasion['defender']['units_surviving']);

                        $convertingUnits[$converterUnitSlot] = [
                            'amount' => $converterUnitAmount,
                            'power' => $unitsRawDp,
                            'power_proportion' => $unitsRawDp / $totalSurvivingRawDp #$invasion['defender']['dp_raw']
                            ];
                    }

                    # Single resource per casualty
                    $resourcePerCasualtyConversionPerk = $converter->race->getUnitPerkValueForUnitSlot($converterUnitSlot, 'kills_into_resource_per_casualty');
                    is_array($resourcePerCasualtyConversionPerk) ?: $resourcePerCasualtyConversionPerk = $converter->race->getUnitPerkValueForUnitSlot($converterUnitSlot, 'kills_into_resource_per_casualty_on_success');

                    if($resourcePerCasualtyConversionPerk)
                    {
                        $resourceAmount = $resourcePerCasualtyConversionPerk[0];
                        $resourceKey = $resourcePerCasualtyConversionPerk[1];
    
                        foreach($enemyUnitsKilled as $enemyUnitKilledSlot => $enemyUnitKilledAmount)
                        {
                            if($this->conversionHelper->isSlotConvertible($enemyUnitKilledSlot, $enemy) and $enemyUnitKilledAmount > 0)
                            {
                                $resourceConversions[$resourceKey] += $enemyUnitKilledAmount * $resourceAmount * $convertingUnits[$converterUnitSlot]['power_proportion'];
                                $resourceConversions[$resourceKey] *= $this->conversionCalculator->getConversionReductionMultiplier($enemy);
                                $resourceConversions[$resourceKey] *= $this->getInvasionResultMultiplier($invasion, $mode);

                                if(in_array($resourceKey, $resourcesThatUseEntireBodies))
                                {
                                    $resourceConversions['bodies_spent'] += $enemyUnitKilledAmount;
                                }
                            }
                        }
                    }

                    # Multiple resources per casualty
                    $multiResourcePerCasualtyConversionPerk = $converter->race->getUnitPerkValueForUnitSlot($converterUnitSlot, 'kills_into_resources_per_casualty');
                    is_array($multiResourcePerCasualtyConversionPerk) ?: $multiResourcePerCasualtyConversionPerk = $converter->race->getUnitPerkValueForUnitSlot($converterUnitSlot, 'kills_into_resources_per_casualty_on_success');

                    if($multiResourcePerCasualtyConversionPerk)
                    {
                        foreach($multiResourcePerCasualtyConversionPerk as $multiResourcePerCasualtyConversionPerk)
                        {
                            $resourceAmount = $multiResourcePerCasualtyConversionPerk[0];
                            $resourceKey = $multiResourcePerCasualtyConversionPerk[1];
        
                            foreach($enemyUnitsKilled as $enemyUnitKilledSlot => $enemyUnitKilledAmount)
                            {
                                if($this->conversionHelper->isSlotConvertible($enemyUnitKilledSlot, $enemy) and $enemyUnitKilledAmount > 0)
                                {
                                    $resourceConversions[$resourceKey] += $enemyUnitKilledAmount * $resourceAmount * $convertingUnits[$converterUnitSlot]['power_proportion'];
                                    $resourceConversions[$resourceKey] *= $this->conversionCalculator->getConversionReductionMultiplier($enemy);
                                    $resourceConversions[$resourceKey] *= $this->getInvasionResultMultiplier($invasion, $mode);

                                    if(in_array($resourceKey, $resourcesThatUseEntireBodies))
                                    {
                                        $resourceConversions['bodies_spent'] += $enemyUnitKilledAmount;
                                    }
                                }
                            }
                        }
                    }

                    # Single resource per value killed
                    $resourcePerValueConversionPerk = $converter->race->getUnitPerkValueForUnitSlot($converterUnitSlot, 'kills_into_resource_per_value');
                    is_array($resourcePerValueConversionPerk) ?: $resourcePerValueConversionPerk = $converter->race->getUnitPerkValueForUnitSlot($converterUnitSlot, 'kills_into_resource_per_value_on_success');

                    if($resourcePerValueConversionPerk)
                    {
                        $resourceAmountPerValue = (float)$resourcePerValueConversionPerk[0];
                        $resourceKey = $resourcePerValueConversionPerk[1];
    
                        foreach($enemyUnitsKilled as $enemyUnitKilledSlot => $enemyUnitKilledAmount)
                        {
                            if($this->conversionHelper->isSlotConvertible($enemyUnitKilledSlot, $enemy) and $enemyUnitKilledAmount > 0)
                            {
                                if($mode == 'offense')
                                {
                                    $killedUnitsRawPower = $this->militaryCalculator->getDefensivePowerRaw($enemy, $converter, null, [$enemyUnitKilledSlot => $enemyUnitKilledAmount]);
                                }
                                else
                                {
                                    $killedUnitsRawPower = $this->militaryCalculator->getOffensivePowerRaw($enemy, $converter, null, [$enemyUnitKilledSlot => $enemyUnitKilledAmount]);
                                }

                                $resourceGained = $killedUnitsRawPower * $resourceAmountPerValue * $convertingUnits[$converterUnitSlot]['power_proportion'];
                                #$resourceConversions[$resourceKey] *= $this->conversionCalculator->getConversionReductionMultiplier($enemy);
                                $resourceGained *= $this->getInvasionResultMultiplier($invasion, $mode);

                                $resourceConversions[$resourceKey] += $resourceGained;

                                if(in_array($resourceKey, $resourcesThatUseEntireBodies))
                                {
                                    $resourceConversions['bodies_spent'] += $enemyUnitKilledAmount;
                                }
                            }
                        }
                    }

                    # Multiple resources per value killed
                    $multiResourcePerValueConversionPerk = $converter->race->getUnitPerkValueForUnitSlot($converterUnitSlot, 'kills_into_resources_per_value');
                    is_array($multiResourcePerValueConversionPerk) ?: $multiResourcePerValueConversionPerk = $converter->race->getUnitPerkValueForUnitSlot($converterUnitSlot, 'kills_into_resources_per_value_on_success');

                    if($multiResourcePerValueConversionPerk)
                    {
                        foreach($multiResourcePerValueConversionPerk as $multiResourcePerValueConversionPerk)
                        {
                            $resourceAmount = $multiResourcePerValueConversionPerk[0];
                            $resourceKey = $multiResourcePerValueConversionPerk[1];
        
                            foreach($enemyUnitsKilled as $enemyUnitKilledSlot => $enemyUnitKilledAmount)
                            {
                                if($this->conversionHelper->isSlotConvertible($enemyUnitKilledSlot, $enemy) and $enemyUnitKilledAmount > 0)
                                {
                                    if($mode == 'offense')
                                    {
                                        $killedUnitsRawPower = $this->militaryCalculator->getDefensivePowerRaw($enemy, $converter, null, [$enemyUnitKilledSlot => $enemyUnitKilledAmount]);
                                    }
                                    else
                                    {
                                        $killedUnitsRawPower = $this->militaryCalculator->getOffensivePowerRaw($enemy, $converter, null, [$enemyUnitKilledSlot => $enemyUnitKilledAmount]);
                                    }

                                    $resourceConversions[$resourceKey] += $killedUnitsRawPower * $resourceAmount * $convertingUnits[$converterUnitSlot]['power_proportion'];
                                    $resourceConversions[$resourceKey] *= $this->getInvasionResultMultiplier($invasion, $mode);

                                    if(in_array($resourceKey, $resourcesThatUseEntireBodies))
                                    {
                                        $resourceConversions['bodies_spent'] += $enemyUnitKilledAmount;
                                    }
                                }
                            }
                        }
                    }

                    # Single resource per peasant displaced
                    $resourcePerDisplacedPeasantConversionPerk = $converter->race->getUnitPerkValueForUnitSlot($converterUnitSlot, 'converts_displaced_peasants_into_resource');

                    if($resourcePerDisplacedPeasantConversionPerk and $mode == 'offense')
                    {
                        $resourceAmountPerDisplacedPeasant = $resourcePerDisplacedPeasantConversionPerk[0];
                        $resourceKey = $resourcePerDisplacedPeasantConversionPerk[1];

                        $landConquered = array_sum($invasion['attacker']['land_conquered']);
                        $displacedPeasants = intval(($enemy->peasants / $invasion['defender']['land_size']) * $landConquered);
    
                        $resourceConversions[$resourceKey] += $displacedPeasants * $resourceAmountPerDisplacedPeasant * $convertingUnits[$converterUnitSlot]['power_proportion'];
                        #$resourceConversions[$resourceKey] *= $this->conversionCalculator->getConversionReductionMultiplier($enemy);
                        $resourceConversions[$resourceKey] *= $this->getInvasionResultMultiplier($invasion, $mode);
                    }

                    # Multiple resources per value killed
                    $multiResourcePerDisplacedPeasantConversionPerk = $converter->race->getUnitPerkValueForUnitSlot($converterUnitSlot, 'converts_displaced_peasants_into_resources');

                    if($multiResourcePerDisplacedPeasantConversionPerk and $mode == 'offense')
                    {
                        foreach($multiResourcePerDisplacedPeasantConversionPerk as $multiResourcePerDisplacedPeasantConversionPerk)
                        {
                            $resourceAmount = $multiResourcePerDisplacedPeasantConversionPerk[0];
                            $resourceKey = $multiResourcePerDisplacedPeasantConversionPerk[1];

                            $landConquered = array_sum($invasion['attacker']['land_conquered']);
                            $displacedPeasants = intval(($enemy->peasants / $invasion['defender']['land_size']) * $landConquered);
        
                            $resourceConversions[$resourceKey] += $displacedPeasants * $resourceAmount * $convertingUnits[$converterUnitSlot]['power_proportion'];
                            #$resourceConversions[$resourceKey] *= $this->conversionCalculator->getConversionReductionMultiplier($enemy);
                            $resourceConversions[$resourceKey] *= $this->getInvasionResultMultiplier($invasion, $mode);
                        }
                    }

                    # Dies into single resource 
                    $resourcePerOwnCasualtyConversionPerk = $converter->race->getUnitPerkValueForUnitSlot($converterUnitSlot, 'dies_into_resource');
                    is_array($resourcePerOwnCasualtyConversionPerk) ?: $resourcePerOwnCasualtyConversionPerk = $converter->race->getUnitPerkValueForUnitSlot($converterUnitSlot, 'dies_into_resource_on_success');

                    if($resourcePerOwnCasualtyConversionPerk)
                    {
                        $resourceAmount = $resourcePerOwnCasualtyConversionPerk[0];
                        $resourceKey = $resourcePerOwnCasualtyConversionPerk[1];
    
                        $resourceConversions[$resourceKey] += $converterUnitsLost[$converterUnitSlot] * $resourceAmount;
                        #$resourceConversions[$resourceKey] *= $this->conversionCalculator->getConversionReductionMultiplier($enemy);
                        #$resourceConversions[$resourceKey] *= $this->getInvasionResultMultiplier($invasion, $mode);

                        if(in_array($resourceKey, $resourcesThatUseEntireBodies))
                        {
                            $resourceConversions['bodies_spent'] += $converterUnitsLost[$converterUnitSlot];
                        }
                    }
                }
            }
        }

        $resourceConversions = array_map('intval', $resourceConversions);

        return $resourceConversions;

    }
    
    protected function getResourceKeysArray(Dominion $dominion): array
    {
        $resourceConversions = [];

        foreach($dominion->race->resources as $resourceKey)
        {
            $resourceConversions[$resourceKey] = 0;
        }

        return $resourceConversions;
    }

    protected function getInvasionResultMultiplier(array $invasion, string $mode)
    {
        if($mode == 'offense')
        {
            if($invasion['result']['success'])
            {
                return 1;
            }
            elseif(!$invasion['result']['success'] and !$invasion['result']['overwhelmed'])
            {
                return 1 * $invasion['result']['op_dp_ratio'];
            }
            else
            {
                return 0;
            }
        }
        else
        {
            if($invasion['result']['success'])
            {
                return 1 * (1 / $invasion['result']['op_dp_ratio']);
            }
            else
            {
                return 1;
            }
        }
    }

}
