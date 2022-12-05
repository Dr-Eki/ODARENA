<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Models\Dominion;
use OpenDominion\Models\Resource;

use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Helpers\TitleHelper;

use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\ResourceCalculator;

class UnitReturnCalculator
{
    protected $militaryCalculator;
    protected $resourceCalculator;

    protected $theftHelper;
    protected $unitHelper;

    public function __construct()
    {
        $this->titleHelper = app(TitleHelper::class);
    }

    public function getUnitsReturningArray(Dominion $dominion, array $event, array $convertedUnits, string $eventType): array
    {
        $returningUnits = [];
        foreach($dominion->race->units as $unit)
        {
            $returningUnits['military_unit'.$unit->slot] = array_fill(1, 12, 0);
        }

        $returningUnits['military_spies'] = array_fill(1, 12, 0);
        $returningUnits['military_wizards'] = array_fill(1, 12, 0);
        $returningUnits['military_archmages'] = array_fill(1, 12, 0);

        $someWinIntoUnits = array_fill(1, $dominion->race->units->count(), 0);

        foreach($returningUnits as $unitKey => $values)
        {
            $unitType = str_replace('military_', '', $unitKey);
            $slot = str_replace('unit', '', $unitType);
            $amountReturning = 0;

            $returningUnitKey = $unitKey;

            if(in_array($slot, [1,2,3,4,5,6,7,8,9,10]))
            {
                # See if slot $slot has wins_into perk.
                if($event['result']['success'])
                {
                    if($dominion->race->getUnitPerkValueForUnitSlot($slot, 'wins_into'))
                    {
                        $returnsAsSlot = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'wins_into');
                        $returningUnitKey = 'military_unit' . $returnsAsSlot;
                    }
                    if($someWinIntoPerk = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'some_win_into'))
                    {
                        $ratio = (float)$someWinIntoPerk[0] / 100;
                        $newSlot = (int)$someWinIntoPerk[1];

                        if(isset($units[$slot]))
                        {
                            $newUnits = (int)floor($units[$slot] * $ratio);
                            $someWinIntoUnits[$newSlot] += $newUnits;
                            $amountReturning -= $newUnits;
                        }
                    }
                }

                # Remove the units from attacker and add them to $amountReturning.
                if (array_key_exists($slot, $units))
                {
                    $dominion->$unitKey -= $units[$slot];
                    $amountReturning += $dominion[$slot];
                }

                # Check if we have conversions for this unit type/slot
                if (array_key_exists($slot, $convertedUnits))
                {
                    $amountReturning += $convertedUnits[$slot];
                }

                # Check if we have some winning into
                if (array_key_exists($slot, $someWinIntoUnits))
                {
                    $amountReturning += $someWinIntoUnits[$slot];
                }

                # Default return time is 12 ticks.
                $ticks = $this->getUnitReturnTicksForSlot($dominion, $slot);

                # Default all returners to tick 12
                $returningUnits[$returningUnitKey][$ticks] += $amountReturning;

                # Look for dies_into and variations amongst the dead attacking units.
                if(isset($event['attacker']['units_lost'][$slot]))
                {
                    $casualties = $event['attacker']['units_lost'][$slot];

                    if($diesIntoPerk = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'dies_into'))
                    {
                        # Which unit do they die into?
                        $newUnitSlot = $diesIntoPerk[0];
                        $newUnitKey = "military_unit{$newUnitSlot}";
                        $newUnitSlotReturnTime = $this->getUnitReturnTicksForSlot($dominion, $newUnitSlot);

                        $returningUnits[$newUnitKey][$newUnitSlotReturnTime] += $casualties;
                    }

                    if($diesIntoPerk = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'dies_into_wizard'))
                    {
                        # Which unit do they die into?
                        $newUnitKey = "military_wizards";
                        $newUnitSlotReturnTime = 12;

                        $returningUnits[$newUnitKey][$newUnitSlotReturnTime] += $casualties;
                    }

                    if($diesIntoPerk = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'dies_into_spy'))
                    {
                        # Which unit do they die into?
                        $newUnitKey = "military_spies";
                        $newUnitSlotReturnTime = 12;

                        $returningUnits[$newUnitKey][$newUnitSlotReturnTime] += $casualties;
                    }

                    if($diesIntoPerk = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'dies_into_archmage'))
                    {
                        # Which unit do they die into?
                        $newUnitKey = "military_archmages";
                        $newUnitSlotReturnTime = 12;

                        $returningUnits[$newUnitKey][$newUnitSlotReturnTime] += $casualties;
                    }

                    if($diesIntoPerk = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'dies_into_on_offense'))
                    {
                        # Which unit do they die into?
                        $newUnitSlot = $diesIntoPerk[0];
                        $newUnitKey = "military_unit{$newUnitSlot}";
                        $newUnitSlotReturnTime = $this->getUnitReturnTicksForSlot($attacker, $newUnitSlot);

                        $returningUnits[$newUnitKey][$newUnitSlotReturnTime] += $casualties;
                    }

                    if($diesIntoMultiplePerk = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'dies_into_multiple'))
                    {
                        # Which unit do they die into?
                        $newUnitSlot = $diesIntoMultiplePerk[0];
                        $newUnitAmount = (float)$diesIntoMultiplePerk[1];
                        $newUnitKey = "military_unit{$newUnitSlot}";
                        $newUnitSlotReturnTime = $this->getUnitReturnTicksForSlot($dominion, $newUnitSlot);

                        $returningUnits[$newUnitKey][$newUnitSlotReturnTime] += floor($casualties * $newUnitAmount);
                    }

                    if($diesIntoMultiplePerk = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'dies_into_multiple_on_offense'))
                    {
                        # Which unit do they die into?
                        $newUnitSlot = $diesIntoMultiplePerk[0];
                        $newUnitAmount = (float)$diesIntoMultiplePerk[1];
                        $newUnitKey = "military_unit{$newUnitSlot}";
                        $newUnitSlotReturnTime = $this->getUnitReturnTicksForSlot($dominion, $newUnitSlot);

                        $returningUnits[$newUnitKey][$newUnitSlotReturnTime] += floor($casualties * $newUnitAmount);
                    }

                    if($event['result']['success'] and $diesIntoMultiplePerkOnVictory = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'dies_into_multiple_on_victory'))
                    {
                        # Which unit do they die into?
                        $newUnitSlot = $diesIntoMultiplePerkOnVictory[0];
                        $newUnitAmount = (float)$diesIntoMultiplePerkOnVictory[1];
                        $newUnitKey = "military_unit{$newUnitSlot}";
                        $newUnitSlotReturnTime = $this->getUnitReturnTicksForSlot($dominion, $newUnitSlot);

                        $returningUnits[$newUnitKey][$newUnitSlotReturnTime] += floor($casualties * $newUnitAmount);
                    }

                    if(!$event['result']['success'] and $diesIntoMultiplePerkOnVictory = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'dies_into_multiple_on_victory'))
                    {
                        # Which unit do they die into?
                        $newUnitSlot = $diesIntoMultiplePerkOnVictory[0];
                        $newUnitAmount = $diesIntoMultiplePerkOnVictory[2];
                        $newUnitKey = "military_unit{$newUnitSlot}";
                        $newUnitSlotReturnTime = $this->getUnitReturnTicksForSlot($dominion, $newUnitSlot);

                        $returningUnits[$newUnitKey][$newUnitSlotReturnTime] += floor($casualties * $newUnitAmount);
                    }

                    # Check for faster_return_from_time
                    if($fasterReturnFromTimePerk = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'faster_return_from_time'))
                    {

                        $hourFrom = $fasterReturnFromTimePerk[0];
                        $hourTo = $fasterReturnFromTimePerk[1];
                        if (
                            (($hourFrom < $hourTo) and (now()->hour >= $hourFrom and now()->hour < $hourTo)) or
                            (($hourFrom > $hourTo) and (now()->hour >= $hourFrom or now()->hour < $hourTo))
                        )
                        {
                            $ticksFaster = (int)$fasterReturnFromTimePerk[2];
                        }
                        else
                        {
                            $ticksFaster = 0;
                        }

                        $fasterReturningTicks = min(max(1, ($ticks - $ticksFaster)), 12);

                        # How many of $slot should return faster?
                        $unitsWithFasterReturnTime = $amountReturning;

                        $returningUnits[$unitKey][$fasterReturningTicks] += $unitsWithFasterReturnTime;
                        $returningUnits[$unitKey][$ticks] -= $unitsWithFasterReturnTime;
                    }

                    # Check for faster_return from buildings
                    if($buildingFasterReturnPerk = $dominion->getBuildingPerkMultiplier('faster_return'))
                    {
                        $fasterReturn = min(max(0, $buildingFasterReturnPerk), 1);
                        $normalReturn = 1 - $fasterReturn;
                        $ticksFaster = 6;

                        $fasterReturningTicks = min(max(1, ($ticks - $ticksFaster), 12));

                        $unitsWithFasterReturnTime = round($amountReturning * $buildingFasterReturnPerk);
                        $unitsWithRegularReturnTime = round($amountReturning - $amountWithFasterReturn);

                        $returningUnits[$unitKey][$fasterReturningTicks] += $unitsWithFasterReturnTime;
                        $returningUnits[$unitKey][$ticks] -= $unitsWithFasterReturnTime;
                    }

                    # Check for faster_return_units and faster_return_units_increasing from buildings
                    if($buildingFasterReturnPerk = $dominion->getBuildingPerkValue('faster_returning_units') or $buildingFasterReturnPerk = $dominion->getBuildingPerkValue('faster_returning_units_increasing'))
                    {
                        $fasterReturn = min(max(0, $buildingFasterReturnPerk), 1);
                        $normalReturn = 1 - $fasterReturn;
                        $ticksFaster = 4;

                        $fasterReturningTicks = min(max(1, ($ticks - $ticksFaster)), 12);

                        $unitsWithFasterReturnTime = min($buildingFasterReturnPerk, $amountReturning);
                        $unitsWithRegularReturnTime = round($amountReturning - $unitsWithFasterReturnTime);

                        $returningUnits[$unitKey][$fasterReturningTicks] += $unitsWithFasterReturnTime;
                        $returningUnits[$unitKey][$ticks] -= $unitsWithFasterReturnTime;
                    }
                }
            }
        }

        # Check for faster return from pairing perks
        foreach($returningUnits as $unitKey => $unitKeyTicks)
        {
            $unitType = str_replace('military_', '', $unitKey);
            $slot = str_replace('unit', '', $unitType);
            $amountReturning = 0;

            $returningUnitKey = $unitKey;

            if(in_array($slot, [1,2,3,4,5,6,7,8,9,10]))
            {
                $amountReturning = array_sum($returningUnits[$unitKey]);

                # Check for faster_return_if_paired
                if($fasterReturnIfPairedPerk = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'faster_return_if_paired'))
                {
                    $pairedUnitSlot = (int)$fasterReturnIfPairedPerk[0];
                    $pairedUnitKey = 'military_unit'.$pairedUnitSlot;
                    $ticksFaster = (int)$fasterReturnIfPairedPerk[1];
                    $pairedUnitKeyReturning = array_sum($returningUnits[$pairedUnitKey]);

                    # Determine new return speed
                    $fasterReturningTicks = min(max($ticks - $ticksFaster, 1), 12);

                    # How many of $slot should return faster?
                    $unitsWithFasterReturnTime = min($pairedUnitKeyReturning, $amountReturning);
                    $unitsWithRegularReturnTime = max(0, $units[$slot] - $unitsWithFasterReturnTime);

                    $returningUnits[$unitKey][$fasterReturningTicks] += $unitsWithFasterReturnTime;
                    $returningUnits[$unitKey][$ticks] -= $unitsWithFasterReturnTime;
                }

                # Check for faster_return_if_paired_multiple
                if($fasterReturnIfPairedMultiplePerk = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'faster_return_if_paired_multiple'))
                {
                    $pairedUnitSlot = (int)$fasterReturnIfPairedMultiplePerk[0];
                    $pairedUnitKey = 'military_unit'.$pairedUnitSlot;
                    $ticksFaster = (int)$fasterReturnIfPairedMultiplePerk[1];
                    $unitChunkSize = (int)$fasterReturnIfPairedMultiplePerk[2];
                    $pairedUnitKeyReturning = array_sum($returningUnits[$pairedUnitKey]);

                    # Determine new return speed
                    $fasterReturningTicks = min(max($ticks - $ticksFaster, 1), 12);

                    # How many of $slot should return faster?
                    $unitsWithFasterReturnTime = min($pairedUnitKeyReturning * $unitChunkSize, $amountReturning);
                    $unitsWithRegularReturnTime = max(0, $units[$slot] - $unitsWithFasterReturnTime);

                    $returningUnits[$unitKey][$fasterReturningTicks] += $unitsWithFasterReturnTime;
                    $returningUnits[$unitKey][$ticks] -= $unitsWithFasterReturnTime;
                }
            }
        }

        return $unitsReturning;
    }

    protected function getUnitReturnTicksForSlot(Dominion $dominion, int $slot): int
    {
        $ticks = 12;

        $unit = $dominion->race->units->filter(function ($unit) use ($slot) {
            return ($unit->slot === $slot);
        })->first();

        $ticks -= (int)$unit->getPerkValue('faster_return');
        $ticks -= (int)$dominion->getSpellPerkValue('faster_return');
        $ticks -= (int)$dominion->getAdvancementPerkValue('faster_return');
        $ticks -= (int)$dominion->realm->getArtefactPerkValue('faster_return');

        return min(max(1, $ticks), 12);
    }

}
