<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Models\Dominion;

use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;

class ConversionCalculator
{

    /*
    *   1/Fraction
    *   The amount of remaining casualties are available for conversion
    *   if the invasion is unsuccessful on defense and offense.
    */
    protected const DEFENSIVE_CONVERSIONS_FAILED_FRACTION = 9;
    protected const OFFENSIVE_CONVERSIONS_FAILED_FRACTION = 12;


    public function __construct()
    {
        $this->militaryCalculator = app(MilitaryCalculator::class);
        $this->rangeCalculator = app(RangeCalculator::class);
        $this->spellCalculator = app(SpellCalculator::class);
        $this->landCalculator = app(LandCalculator::class);
        $this->unitHelper = app(UnitHelper::class);
    }


    public function getSettings(): array
    {
        return $constants = [
            'DEFENSIVE_CONVERSIONS_FAILED_FRACTION' => static::DEFENSIVE_CONVERSIONS_FAILED_FRACTION,
            'DEFENSIVE_CONVERSIONS_FAILED_FRACTION' => static::OFFENSIVE_CONVERSIONS_FAILED_FRACTION
        ];
    }

    public function getConversions(Dominion $attacker, Dominion $defender, array $invasion): array
    {
        $conversions['attacker'] = array_fill(1, 4, 0);
        $conversions['defender'] = array_fill(1, 4, 0);

        # Land ratio: float
        $landRatio = $invasion['attacker']['landSize'] / $invasion['defender']['landSize'];

        # Attacker's raw OP
        $rawOp = 1; # In case someone sends with just a zero-OP unit (Snow Elf Hailstorm Cannon)
        foreach($invasion['attacker']['unitsSent'] as $slot => $amount)
        {
            $unit = $attacker->race->units->filter(function ($unit) use ($slot) {
                return ($unit->slot === $slot);
            })->first();

            $rawOp += $this->militaryCalculator->getUnitPowerWithPerks($attacker, $defender, $landRatio, $unit, 'offense') * $amount;
        }

        # Attacker's raw OP lost, from convertible units
        $rawOpLost = 0;
        foreach($invasion['attacker']['unitsLost'] as $slot => $amount)
        {
            $unit = $attacker->race->units->filter(function ($unit) use ($slot) {
                return ($unit->slot === $slot);
            })->first();

            if($this->isSlotConvertible($slot, $attacker))
            {
                $rawOpLost += $this->militaryCalculator->getUnitPowerWithPerks($attacker, $defender, $landRatio, $unit, 'offense') * $amount;
            }
        }

        # Defender's raw DP
        $rawDp = 0;
        foreach($invasion['defender']['unitsDefending'] as $slot => $amount)
        {
            if($slot === 'draftees')
            {
                $rawDp += $defender->military_draftees;
            }
            elseif($slot === 'peasants')
            {
                $rawDp += $defender->peasants;
            }
            else
            {
                $unit = $defender->race->units->filter(function ($unit) use ($slot) {
                    return ($unit->slot === $slot);
                })->first();

                $rawDp += $this->militaryCalculator->getUnitPowerWithPerks($defender, $attacker, $landRatio, $unit, 'defense') * $amount;
            }
        }

        # Defender's raw DP lost
        $rawDpLost = 0;
        foreach($invasion['defender']['unitsLost'] as $slot => $amount)
        {
            if($slot === 'draftees')
            {
                $rawDpLost += $defender->military_draftees;
            }
            elseif($slot === 'peasants')
            {
                $rawDpLost += $defender->peasants;
            }
            else
            {
                $unit = $defender->race->units->filter(function ($unit) use ($slot) {
                    return ($unit->slot === $slot);
                })->first();

                if($this->isSlotConvertible($slot, $defender))
                {
                  $rawDpLost += $this->militaryCalculator->getUnitPowerWithPerks($defender, $attacker, $landRatio, $unit, 'defense') * $amount;
                }
            }
        }

        $displacedPeasantsConversions = $this->getDisplacedPeasantsConversions($attacker, $defender, $invasion, $rawOp, $rawDp, $landRatio);

        $valueConversionsOnOffense = $this->getValueBasedConversions($attacker, $defender, $invasion, $rawOp, $rawDp, $landRatio, 'offense', $rawDpLost, 0);
        $valueConversionsOnDefense = $this->getValueBasedConversions($attacker, $defender, $invasion, $rawOp, $rawDp, $landRatio, 'defense', 0, $rawOpLost);

        $casualtiesBasedConversionsOnOffense = $this->getCasualtiesBasedConversions($attacker, $defender, $invasion, $rawOp, $rawDp, $landRatio, 'offense', $rawDpLost, 0);
        $casualtiesBasedConversionsOnDefense = $this->getCasualtiesBasedConversions($attacker, $defender, $invasion, $rawOp, $rawDp, $landRatio, 'defense', 0, $rawOpLost);

        $strengthBasedConversionsOnOffense = $this->getStrengthBasedConversions($attacker, $defender, $invasion, $rawOp, $rawDp, $landRatio, 'offense');
        $strengthBasedConversionsOnDefense = $this->getStrengthBasedConversions($attacker, $defender, $invasion, $rawOp, $rawDp, $landRatio, 'defense');

        $vampiricConversionsOnOffense = $this->getVampiricConversions($attacker, $defender, $invasion, $rawOp, $rawDp, $landRatio, 'offense');
        $vampiricConversionsOnDefense = $this->getVampiricConversions($attacker, $defender, $invasion, $rawOp, $rawDp, $landRatio, 'defense');

        foreach($conversions['attacker'] as $slot => $amount)
        {
            $conversions['attacker'][$slot] += $displacedPeasantsConversions[$slot];

            $conversions['attacker'][$slot] += $valueConversionsOnOffense[$slot];
            $conversions['attacker'][$slot] += $casualtiesBasedConversionsOnOffense[$slot];
            $conversions['attacker'][$slot] += $strengthBasedConversionsOnOffense[$slot];
            $conversions['attacker'][$slot] += $vampiricConversionsOnOffense[$slot];
        }

        foreach($conversions['defender'] as $slot => $amount)
        {
            $conversions['defender'][$slot] += $valueConversionsOnDefense[$slot];
            $conversions['defender'][$slot] += $casualtiesBasedConversionsOnDefense[$slot];
            $conversions['defender'][$slot] += $strengthBasedConversionsOnDefense[$slot];
            $conversions['defender'][$slot] += $vampiricConversionsOnDefense[$slot];
        }

        return $conversions;
    }

    /*
    *   Calculate how many peasants were displaced.
    */
    public function getDisplacedPeasantsConversions(Dominion $attacker, Dominion $defender, array $invasion, int $rawOp, int $rawDp, float $landRatio): array
    {
        $convertedUnits = array_fill(1, 4, 0);
        $convertingUnits = array_fill(1, 4, 0);

        if(!$invasion['result']['success'] or $defender->race->getPerkValue('can_only_abduct_own'))
        {
            return $convertedUnits;
        }

        $landConquered = array_sum($invasion['attacker']['landConquered']);
        $displacedPeasants = intval(($defender->peasants / $invasion['defender']['landSize']) * $landConquered);

        # Check that unitsSent contains displaced_peasants_conversion perk
        foreach($invasion['attacker']['unitsSent'] as $slot => $amount)
        {
            if($displacedPeasantsConversionPerk = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'displaced_peasants_conversion'))
            {
                $convertingUnits[$slot] += $amount;

                # Deduct lost units.
                if(isset($invasion['attacker']['unitsLost'][$slot]))
                {
                    $convertingUnits[$slot] -= $invasion['attacker']['unitsLost'][$slot];
                }
            }
        }

        # Calculate contribution (unit raw OP / total raw OP)
        foreach($convertingUnits as $slot => $amount)
        {
            $unit = $attacker->race->units->filter(function ($unit) use ($slot) {
                return ($unit->slot === $slot);
            })->first();

            $unitRawOp = $this->militaryCalculator->getUnitPowerWithPerks($attacker, $defender, $landRatio, $unit, 'offense');

            $sentUnitsOpRatio[$slot] = ($unitRawOp * $amount) / $rawOp;
         }

         # Populate $convertedUnits
         foreach($sentUnitsOpRatio as $slot => $ratio)
         {
            if($displacedPeasantsConversionPerk = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'displaced_peasants_conversion'))
            {
                $convertedUnits[$displacedPeasantsConversionPerk] += intval($displacedPeasants * $ratio);
            }
         }

         return $convertedUnits;
    }

    public function getValueBasedConversions(Dominion $attacker, Dominion $defender, array $invasion, int $rawOp, int $rawDp, float $landRatio, string $mode, int $rawDpLost, int $rawOpLost): array
    {
        $convertedUnits = array_fill(1, 4, 0);
        $convertingUnits = array_fill(1, 4, 0);

        if($mode === 'offense')
        {
            # Check that unitsSent contains displaced_peasants_conversion perk
            foreach($invasion['attacker']['unitsSent'] as $slot => $amount)
            {
                if($valueConversionPerk = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'value_conversion'))
                {
                    $convertingUnits[$slot] += $amount;

                    # Deduct lost units.
                    if(isset($invasion['attacker']['unitsLost'][$slot]))
                    {
                        $convertingUnits[$slot] -= $invasion['attacker']['unitsLost'][$slot];
                    }
                }
            }

            # If invasion is not successful, reduce raw DP lost
            if(!$invasion['result']['success'])
            {
                $rawDpLost /= static::OFFENSIVE_CONVERSIONS_FAILED_FRACTION;
            }

            # Calculate contribution (unit raw OP / total raw OP)
            # This determines how many units were killed by each converting unit
            foreach($convertingUnits as $slot => $amount)
            {
                $unit = $attacker->race->units->filter(function ($unit) use ($slot) {
                    return ($unit->slot === $slot);
                })->first();

                $unitRawOp = $this->militaryCalculator->getUnitPowerWithPerks($attacker, $defender, $landRatio, $unit, 'offense');

                $convertingUnitsOpRatio[$slot] = ($unitRawOp * $amount) / $rawOp;
             }

             foreach($convertingUnits as $slot => $amount)
             {
                  if($amount > 0)
                  {
                      $valueConversionPerk = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'value_conversion');
                      $conversionMultiplier = (float)$valueConversionPerk[0];
                      $convertedSlot = (int)$valueConversionPerk[1];

                      $convertedUnits[$convertedSlot] += (int)round(($rawDpLost * $convertingUnitsOpRatio[$slot]) * $conversionMultiplier);
                  }
             }

             # Cap at 0, in case of weird behaviour from negative OP/DP
             foreach($convertedUnits as $slot => $amount)
             {
                $convertedUnits[$slot] = max(0, $convertedUnits[$slot]);
             }

        }

        elseif($mode === 'defense')
        {
            # Check that unitsDefending contains displaced_peasants_conversion perk
            foreach($invasion['defender']['unitsDefending'] as $slot => $amount)
            {
                if($slot !== 'draftees' and $slot !== 'peasants')
                {
                    if($valueConversionPerk = $defender->race->getUnitPerkValueForUnitSlot($slot, 'value_conversion'))
                    {
                        $convertingUnits[$slot] += $amount;

                        # Deduct lost units.
                        if(isset($invasion['defender']['unitsLost'][$slot]))
                        {
                            $convertingUnits[$slot] -= $invasion['defender']['unitsLost'][$slot];
                        }
                    }
                }
            }

            # If invasion is successful, reduce raw OP lost to 1/6
            if($invasion['result']['success'])
            {
                $rawDpLost /= static::DEFENSIVE_CONVERSIONS_FAILED_FRACTION;
            }

            # Calculate contribution (unit raw DP / total raw DP)
            # This determines how many units were killed by each converting unit
            foreach($convertingUnits as $slot => $amount)
            {
                $unit = $defender->race->units->filter(function ($unit) use ($slot) {
                    return ($unit->slot === $slot);
                })->first();

                $unitRawDp = $this->militaryCalculator->getUnitPowerWithPerks($defender, $attacker, $landRatio, $unit, 'defense');

                $convertingUnitsDpRatio[$slot] = ($amount * $unitRawDp) / $rawDp;
             }

             foreach($convertingUnits as $slot => $amount)
             {
                  if($amount > 0)
                  {
                      $valueConversionPerk = $defender->race->getUnitPerkValueForUnitSlot($slot, 'value_conversion');
                      $conversionMultiplier = (float)$valueConversionPerk[0];
                      $convertedSlot = (int)$valueConversionPerk[1];

                      $convertedUnits[$convertedSlot] += (int)round(($rawDpLost  * (1 - $attacker->race->getPerkMultiplier('reduced_conversions')) * $convertingUnitsDpRatio[$slot]) * $conversionMultiplier);
                  }
             }

             # Cap at 0, in case of weird behaviour from negative OP/DP
             foreach($convertedUnits as $slot => $amount)
             {
                $convertedUnits[$slot] = max(0, $convertedUnits[$slot]);
             }

        }

        return $convertedUnits;
    }

    public function getCasualtiesBasedConversions(Dominion $attacker, Dominion $defender, array $invasion, int $rawOp, int $rawDp, float $landRatio, string $mode, int $rawDpLost, int $rawOpLost): array
    {
        $convertedUnits = array_fill(1, 4, 0);
        $convertingUnits = array_fill(1, 4, 0);

        if($mode === 'offense')
        {
            $defensiveCasualties = 0;
            foreach($invasion['defender']['unitsLost'] as $slot => $amount)
            {
                if($this->isSlotConvertible($slot, $defender))
                {
                    $defensiveCasualties += $amount;
                }
            }

            # If invasion is not successful, reduce defensive casualties
            if(!$invasion['result']['success'])
            {
                $defensiveCasualties /= static::OFFENSIVE_CONVERSIONS_FAILED_FRACTION;
            }

            # Check that unitsSent contains displaced_peasants_conversion perk
            foreach($invasion['attacker']['unitsSent'] as $slot => $amount)
            {
                if($valueConversionPerk = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'casualties_conversion'))
                {
                    $convertingUnits[$slot] += $amount;

                    # Deduct lost units.
                    if(isset($invasion['attacker']['unitsLost'][$slot]))
                    {
                        $convertingUnits[$slot] -= $invasion['attacker']['unitsLost'][$slot];
                    }
                }
            }

            # Calculate contribution (unit raw OP / total raw OP)
            # This determines how many units were killed by each converting unit
            foreach($convertingUnits as $slot => $amount)
            {
                $unit = $attacker->race->units->filter(function ($unit) use ($slot) {
                    return ($unit->slot === $slot);
                })->first();

                $unitRawOp = $this->militaryCalculator->getUnitPowerWithPerks($attacker, $defender, $landRatio, $unit, 'offense', null, $invasion['attacker']['unitsSent'], null);

                $convertingUnitsOpRatio[$slot] = ($unitRawOp * $amount) / $rawOp;
             }

             foreach($convertingUnits as $slot => $amount)
             {
                  if($amount > 0)
                  {
                      $valueConversionPerk = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'casualties_conversion');
                      $convertedSlot = (int)$valueConversionPerk[0];

                      $convertedUnits[$convertedSlot] += (int)round($defensiveCasualties * (1 - $defender->race->getPerkMultiplier('reduced_conversions')) * $convertingUnitsOpRatio[$slot]);
                  }
             }

             # Cap at 0, in case of weird behaviour from negative OP/DP
             foreach($convertedUnits as $slot => $amount)
             {
                $convertedUnits[$slot] = max(0, $convertedUnits[$slot]);
             }

        }

        if($mode === 'defense')
        {
            $offensiveCasualties = 0;
            foreach($invasion['attacker']['unitsLost'] as $slot => $amount)
            {
                if($this->isSlotConvertible($slot, $attacker))
                {
                    $offensiveCasualties += $amount;
                    #echo '<pre>[ATTACKER] Slot ' . $slot . ' is convertible.</pre>';
                }
            }

            # If invasion is successful, reduce offensive casualties
            if($invasion['result']['success'])
            {
                $offensiveCasualties /= static::DEFENSIVE_CONVERSIONS_FAILED_FRACTION;
            }

            # Check that unitsDefending contains casualties_conversion perk
            foreach($invasion['defender']['unitsDefending'] as $slot => $amount)
            {
                if($slot !== 'draftees' and $slot !== 'peasants')
                {
                    if($valueConversionPerk = $defender->race->getUnitPerkValueForUnitSlot($slot, 'casualties_conversion'))
                    {
                        $convertingUnits[$slot] += $amount;

                        # Deduct lost units.
                        if(isset($invasion['defender']['unitsLost'][$slot]))
                        {
                            $convertingUnits[$slot] -= $invasion['defender']['unitsLost'][$slot];
                        }
                    }
                }

            }

            # Calculate contribution (unit raw DP / total raw DP)
            # This determines how many units were killed by each converting unit
            foreach($convertingUnits as $slot => $amount)
            {
                $unit = $defender->race->units->filter(function ($unit) use ($slot) {
                    return ($unit->slot === $slot);
                })->first();

                $unitRawDp = $this->militaryCalculator->getUnitPowerWithPerks($defender, $attacker, $landRatio, $unit, 'defense');

                $convertingUnitsDpRatio[$slot] = ($unitRawDp * $amount) / $rawDp;
             }

             foreach($convertingUnits as $slot => $amount)
             {
                  if($amount > 0)
                  {
                      $valueConversionPerk = $defender->race->getUnitPerkValueForUnitSlot($slot, 'casualties_conversion');
                      $convertedSlot = (int)$valueConversionPerk[0];

                      $convertedUnits[$convertedSlot] += (int)round($offensiveCasualties * (1 - $defender->race->getPerkMultiplier('reduced_conversions')) * $convertingUnitsDpRatio[$slot]);
                  }
             }

             # Cap at 0, in case of weird behaviour from negative OP/DP
             foreach($convertedUnits as $slot => $amount)
             {
                $convertedUnits[$slot] = max(0, $convertedUnits[$slot]);
             }

        }

        return $convertedUnits;
    }

    public function getStrengthBasedConversions(Dominion $attacker, Dominion $defender, array $invasion, int $rawOp, int $rawDp, float $landRatio, string $mode): array
    {
        $convertedUnits = array_fill(1, 4, 0);
        $convertingUnits = array_fill(1, 4, 0);

        if($mode === 'offense')
        {
            if($attacker->getSpellPerkValue('no_conversions'))
            {
                return $convertedUnits;
            }

            $availableCasualties =
                [
                    'draftees' => ['amount' => 0, 'dp' => 0],
                           '1' => ['amount' => 0, 'dp' => 0],
                           '2' => ['amount' => 0, 'dp' => 0],
                           '3' => ['amount' => 0, 'dp' => 0],
                           '4' => ['amount' => 0, 'dp' => 0],
                ];

            # Check that unitsSent contains strength_conversion perk
            foreach($invasion['attacker']['unitsSent'] as $slot => $amount)
            {
                if($strengthBasedConversionPerk = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'strength_conversion'))
                {
                    $convertingUnits[$slot] += $amount;

                    # Deduct lost units.
                    if(isset($invasion['attacker']['unitsLost'][$slot]))
                    {
                        $convertingUnits[$slot] -= $invasion['attacker']['unitsLost'][$slot];
                    }
                }
            }

            # Calculate contribution (unit raw OP / total raw OP)
            # This determines how many units were killed by each converting unit
            foreach($convertingUnits as $slot => $amount)
            {
                $unit = $attacker->race->units->filter(function ($unit) use ($slot) {
                    return ($unit->slot === $slot);
                })->first();

                $unitRawOp = $this->militaryCalculator->getUnitPowerWithPerks($attacker, $defender, $landRatio, $unit, 'offense');

                $convertingUnitsOpRatio[$slot] = ($unitRawOp * $amount) / $rawOp;
             }

            foreach($invasion['defender']['unitsLost'] as $slot => $amount)
            {
                # Apply reduced conversions
                $amount *= (1 - ($defender->race->getPerkMultiplier('reduced_conversions')));

                # Drop if invasion is not successful
                if(!$invasion['result']['success'])
                {
                    $amount /= static::OFFENSIVE_CONVERSIONS_FAILED_FRACTION;
                }

                # Round it down
                #$amount = round($amount); -- Moved to later in the code

                if($slot === 'draftees' or $slot === 'peasants')
                {
                    $availableCasualties[$slot]['amount'] = $amount;

                    if($defender->race->getPerkValue('draftee_dp'))
                    {
                        $availableCasualties[$slot]['dp'] = $defender->race->getPerkValue('draftee_dp');
                    }
                    else
                    {
                        $availableCasualties[$slot]['dp'] = 1;
                    }
                }
                else
                {
                    # Get the $unit
                    $unit = $defender->race->units->filter(function ($unit) use ($slot) {
                            return ($unit->slot == $slot);
                        })->first();

                    if($this->isSlotConvertible($slot, $defender))
                    {
                        $availableCasualties[$slot]['amount'] = $amount;
                        $availableCasualties[$slot]['dp'] = (float)$this->militaryCalculator->getUnitPowerWithPerks($defender, $attacker, $landRatio, $unit, 'defense');
                    }
                }
            }

            # Loop through all available casualties
            foreach($availableCasualties as $casualty)
            {
                #echo "<pre>***\n";
                #echo "[DEFENDER] Unit amount: " . $casualty['amount'] . ' / Unit raw DP: ' .$casualty['dp']. "\n";
                # For each casualty unit, loop through units sent.
                foreach($convertingUnits as $convertingUnitSlot => $sentAmount)
                {
                    #echo "[ATTACKER] Unit slot: $unitSentSlot / Amount sent: $sentAmount / Raw OP ratio: {$sentUnitsOpRatio[$unitSentSlot]}\n";

                    $casualtyAmountAvailableToUnit = $casualty['amount'] * $convertingUnitsOpRatio[$convertingUnitSlot];
                    #$casualty['amount'] -= min($casualtyAmountAvailableToUnit, $casualty['amount']);

                    #echo "[ATTACKER] Unit slot $unitSentSlot killed $casualtyAmountAvailableToUnit of this unit.\n";

                    if($strengthConversion = $attacker->race->getUnitPerkValueForUnitSlot($convertingUnitSlot,'strength_conversion'))
                    {
                        $limit = (float)$strengthConversion[0];
                        $under = (int)$strengthConversion[1];
                        $over = (int)$strengthConversion[2];

                        if($casualty['dp'] <= $limit)
                        {
                            $slotConvertedTo = $under;
                            #echo "[DEFENDER] Unit raw DP is less than or equal to the limit ($limit). So it gets converted to $slotConvertedTo.\n";
                        }
                        else
                        {
                            $slotConvertedTo = $over;
                            #echo "[DEFENDER] Unit raw DP is greater the limit ($limit). So it gets converted to $slotConvertedTo.\n";
                        }

                        $convertedUnits[$slotConvertedTo] += (int)round($casualtyAmountAvailableToUnit);
                    }
                }
                #echo "***</pre>";
            }
        }

        if($mode === 'defense')
        {
            if($defender->getSpellPerkValue('no_conversions'))
            {
                return $convertedUnits;
            }

            $availableCasualties =
                [
                           '1' => ['amount' => 0, 'op' => 0],
                           '2' => ['amount' => 0, 'op' => 0],
                           '3' => ['amount' => 0, 'op' => 0],
                           '4' => ['amount' => 0, 'op' => 0],
                ];

            # Check that unitsDefending contains strength_conversion perk
            foreach($invasion['defender']['unitsDefending'] as $slot => $amount)
            {
                if($slot !== 'draftees' and $slot !== 'peasants')
                {
                    if($strengthBasedConversionPerk = $defender->race->getUnitPerkValueForUnitSlot($slot, 'strength_conversion'))
                    {
                        $convertingUnits[$slot] += $amount;

                        # Deduct lost units.
                        if(isset($invasion['defender']['unitsLost'][$slot]))
                        {
                            $convertingUnits[$slot] -= $invasion['defender']['unitsLost'][$slot];
                        }
                    }
                }
            }

            # Calculate contribution (unit raw DP / total raw DP)
            # This determines how many units were killed by each converting unit
            foreach($convertingUnits as $slot => $amount)
            {
                $unit = $defender->race->units->filter(function ($unit) use ($slot) {
                    return ($unit->slot === $slot);
                })->first();

                $unitRawDp = $this->militaryCalculator->getUnitPowerWithPerks($attacker, $defender, $landRatio, $unit, 'defense');

                $convertingUnitsDpRatio[$slot] = ($unitRawDp * $amount) / $rawDp;
             }

            foreach($invasion['attacker']['unitsLost'] as $slot => $amount)
            {
                # Apply reduced conversions
                $amount *= (1 - ($attacker->race->getPerkMultiplier('reduced_conversions')));

                # Drop if invasion is successful
                if($invasion['result']['success'])
                {
                    $amount /= static::DEFENSIVE_CONVERSIONS_FAILED_FRACTION;
                }

                # Get the $unit
                $unit = $attacker->race->units->filter(function ($unit) use ($slot) {
                        return ($unit->slot == $slot);
                    })->first();

                if($this->isSlotConvertible($slot, $attacker))
                {
                    $availableCasualties[$slot]['amount'] = $amount;
                    $availableCasualties[$slot]['op'] = (float)$this->militaryCalculator->getUnitPowerWithPerks($attacker, $defender, $landRatio, $unit, 'offense');
                }
            }

            # Loop through all available casualties
            foreach($availableCasualties as $casualty)
            {
                #echo "<pre>***\n";
                #echo "[ATTACKER] Unit amount: " . $casualty['amount'] . ' / Unit raw OP: ' .$casualty['op']. "\n";
                # For each casualty unit, loop through units sent.
                foreach($convertingUnits as $convertingUnitSlot => $defendingAmount)
                {
                    #echo "[DEFENDER] Unit slot: $convertingUnitSlot / Amount defending: $defendingAmount / Raw OP ratio: {$convertingUnitsDpRatio[$convertingUnitSlot]}\n";

                    $casualtyAmountAvailableToUnit = $casualty['amount'] * $convertingUnitsDpRatio[$convertingUnitSlot];

                    #echo "[DEFENDER] Unit slot $convertingUnitSlot killed $casualtyAmountAvailableToUnit of this unit.\n";

                    if($strengthConversion = $defender->race->getUnitPerkValueForUnitSlot($convertingUnitSlot, 'strength_conversion'))
                    {
                        $limit = (float)$strengthConversion[0];
                        $under = (int)$strengthConversion[1];
                        $over = (int)$strengthConversion[2];

                        if($casualty['op'] <= $limit)
                        {
                            $slotConvertedTo = $under;
                            #echo "[ATTACKER] Unit raw OP is less than or equal to the limit ($limit). So it gets converted to $slotConvertedTo.\n";
                        }
                        else
                        {
                            $slotConvertedTo = $over;
                            #echo "[ATTACKER] Unit raw OP is greater the limit ($limit). So it gets converted to $slotConvertedTo.\n";
                        }

                        #echo "[DEFENDER] New $slotConvertedTo units: " . (int)round($casualtyAmountAvailableToUnit) . ".\n";

                        $convertedUnits[$slotConvertedTo] += (int)round($casualtyAmountAvailableToUnit);

                    }
                }
            #echo "***</pre>";
            }

        }

        return $convertedUnits;

    }

    private function getVampiricConversions(Dominion $attacker, Dominion $defender, array $invasion, int $rawOp, int $rawDp, float $landRatio, string $mode): array
    {
        $convertedUnits = array_fill(1, 4, 0);
        $convertingUnits = array_fill(1, 4, 0);

        if($mode === 'offense')
        {
            $availableCasualties =
                [
                    'draftees' => ['amount' => 0, 'dp' => 0],
                           '1' => ['amount' => 0, 'dp' => 0],
                           '2' => ['amount' => 0, 'dp' => 0],
                           '3' => ['amount' => 0, 'dp' => 0],
                           '4' => ['amount' => 0, 'dp' => 0],
                ];

            # Check that unitsSent contains strength_conversion perk
            foreach($invasion['attacker']['unitsSent'] as $slot => $amount)
            {
                if($displacedPeasantsConversionPerk = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'vampiric_conversion'))
                {
                    $convertingUnits[$slot] += $amount;

                    # Deduct lost units.
                    if(isset($invasion['attacker']['unitsLost'][$slot]))
                    {
                        $convertingUnits[$slot] -= $invasion['attacker']['unitsLost'][$slot];
                    }
                }
            }

            # Calculate contribution (unit raw OP / total raw OP)
            # This determines how many units were killed by each converting unit
            foreach($convertingUnits as $slot => $amount)
            {
                $unit = $attacker->race->units->filter(function ($unit) use ($slot) {
                    return ($unit->slot === $slot);
                })->first();

                $unitRawOp = $this->militaryCalculator->getUnitPowerWithPerks($attacker, $defender, $landRatio, $unit, 'offense');

                $convertingUnitsOpRatio[$slot] = ($unitRawOp * $amount) / $rawOp;
             }

             foreach($invasion['defender']['unitsLost'] as $slot => $amount)
             {
                 # Apply reduced conversions
                 $amount *= (1 - ($defender->race->getPerkMultiplier('reduced_conversions')));

                 # Drop if invasion is not successful
                 if(!$invasion['result']['success'])
                 {
                     $amount /= static::OFFENSIVE_CONVERSIONS_FAILED_FRACTION;
                 }

                 # Round it down
                 #$amount = round($amount); -- Moved to later in the code

                 if($slot === 'draftees' or $slot === 'peasants')
                 {
                     $availableCasualties[$slot]['amount'] = $amount;

                     if($defender->race->getPerkValue('draftee_dp'))
                     {
                         $availableCasualties[$slot]['dp'] = $defender->race->getPerkValue('draftee_dp');
                     }
                     else
                     {
                         $availableCasualties[$slot]['dp'] = 1;
                     }
                 }
                 else
                 {
                     # Get the $unit
                     $unit = $defender->race->units->filter(function ($unit) use ($slot) {
                             return ($unit->slot == $slot);
                         })->first();

                     if($this->isSlotConvertible($slot, $defender))
                     {
                         $availableCasualties[$slot]['amount'] = $amount;
                         $availableCasualties[$slot]['dp'] = (float)$this->militaryCalculator->getUnitPowerWithPerks($defender, $attacker, $landRatio, $unit, 'defense');
                     }
                 }
             }

             # Loop through all available casualties
             foreach($availableCasualties as $casualty)
             {
                 foreach($convertingUnits as $convertingUnitSlot => $sentAmount)
                 {
                     $casualtyAmountAvailableToUnit = $casualty['amount'] * $convertingUnitsOpRatio[$convertingUnitSlot];

                     if($vampiricConversion = $attacker->race->getUnitPerkValueForUnitSlot($convertingUnitSlot,'vampiric_conversion'))
                     {
                         $unit1Range = [0.0, (float)$vampiricConversion[0]];
                         $unit2Range = [$unit1Range[1], (float)$vampiricConversion[1]];
                         $unit3Range = [$unit2Range[1], 1000000000.0];

                         if($casualty['dp'] >= $unit1Range[0] and $casualty['dp'] < $unit1Range[1])
                         {
                             $slotConvertedTo = 1;
                         }
                         elseif($casualty['dp'] >= $unit2Range[0] and $casualty['dp'] < $unit2Range[1])
                         {
                             $slotConvertedTo = 2;
                         }
                         elseif($casualty['dp'] >= $unit3Range[0])
                         {
                             $slotConvertedTo = 3;
                         }

                         $convertedUnits[$slotConvertedTo] += (int)round($casualtyAmountAvailableToUnit);
                     }
                 }
             }
        }
        if($mode === 'defense')
        {

            $availableCasualties =
                [
                           '1' => ['amount' => 0, 'op' => 0],
                           '2' => ['amount' => 0, 'op' => 0],
                           '3' => ['amount' => 0, 'op' => 0],
                           '4' => ['amount' => 0, 'op' => 0],
                ];

            # Check that unitsDefending contains vampiric_conversion perk
            foreach($invasion['defender']['unitsDefending'] as $slot => $amount)
            {
                if($slot !== 'draftees' and $slot !== 'peasants')
                {
                    if($vampiricConversionPerk = $defender->race->getUnitPerkValueForUnitSlot($slot, 'vampiric_conversion'))
                    {
                        $convertingUnits[$slot] += $amount;

                        # Deduct lost units.
                        if(isset($invasion['defender']['unitsLost'][$slot]))
                        {
                            $convertingUnits[$slot] -= $invasion['defender']['unitsLost'][$slot];
                        }
                    }
                }

            }

            # Calculate contribution (unit raw OP / total raw OP)
            # This determines how many units were killed by each converting unit
            foreach($convertingUnits as $slot => $amount)
            {
                $unit = $defender->race->units->filter(function ($unit) use ($slot) {
                    return ($unit->slot === $slot);
                })->first();

                $unitRawDp = $this->militaryCalculator->getUnitPowerWithPerks($attacker, $defender, $landRatio, $unit, 'defense');

                #echo "<pre>[DEFENDER] $unit->name has $unitRawDp raw DP.</pre>";

                $convertingUnitsDpRatio[$slot] = ($unitRawDp * $amount) / $rawDp;
             }

            foreach($invasion['attacker']['unitsLost'] as $slot => $amount)
            {
                # Apply reduced conversions
                $amount *= (1 - ($attacker->race->getPerkMultiplier('reduced_conversions')));

                # Drop if invasion is successful
                if($invasion['result']['success'])
                {
                    $amount /= static::DEFENSIVE_CONVERSIONS_FAILED_FRACTION;
                }

                # Get the $unit
                $unit = $attacker->race->units->filter(function ($unit) use ($slot) {
                        return ($unit->slot == $slot);
                    })->first();

                if($this->isSlotConvertible($slot, $attacker))
                {
                    $availableCasualties[$slot]['amount'] = $amount;
                    $availableCasualties[$slot]['op'] = (float)$this->militaryCalculator->getUnitPowerWithPerks($attacker, $defender, $landRatio, $unit, 'offense');
                    #echo '<pre>[ATTACKER] ' . $unit->name . ' is convertible.</pre>';
                }
                else
                {
                    #echo '<pre>[ATTACKER] ' . $unit->name . ' is not convertible.</pre>';
                }

                #echo '<pre>[ATTACKER] ' . $amount . ' ' . $unit->name . '(' . $availableCasualties[$slot]['op'] . ' OP, slot ' . $slot .  ') died and are available for conversion.</pre>';
            }

            #echo '<pre>';
            #print_r($convertingUnitsDpRatio);
            #echo '</pre>';

            # Loop through all available casualties
            foreach($availableCasualties as $casualty)
            {
                foreach($convertingUnits as $convertingUnitSlot => $sentAmount)
                {
                    $casualtyAmountAvailableToUnit = $casualty['amount'] * $convertingUnitsDpRatio[$convertingUnitSlot];

                    if($vampiricConversion = $defender->race->getUnitPerkValueForUnitSlot($convertingUnitSlot,'vampiric_conversion'))
                    {
                        $unit1Range = [0.0, (float)$vampiricConversion[0]];
                        $unit2Range = [$unit1Range[1], (float)$vampiricConversion[1]];
                        $unit3Range = [$unit2Range[1], 1000000000.0];

                        if($casualty['op'] >= $unit1Range[0] and $casualty['op'] < $unit1Range[1])
                        {
                            $slotConvertedTo = 1;
                        }
                        elseif($casualty['op'] >= $unit2Range[0] and $casualty['op'] < $unit2Range[1])
                        {
                            $slotConvertedTo = 2;
                        }
                        elseif($casualty['op'] >= $unit3Range[0])
                        {
                            $slotConvertedTo = 3;
                        }

                        #echo '<pre>Attacker ' . $slot . ' has ' . $casualty['op'] . ' raw OP is converted to ' . (int)round($casualtyAmountAvailableToUnit) . ' defender slot ' . $slotConvertedTo . '.</pre>';

                        $convertedUnits[$slotConvertedTo] += (int)round($casualtyAmountAvailableToUnit);
                    }
                }
            }

        }


        return $convertedUnits;

    }

    private function isSlotConvertible($slot, Dominion $dominion, array $unconvertibleAttributes = []): bool
    {
        if(empty($unconvertibleAttributes))
        {
            $unconvertibleAttributes = [
                'ammunition',
                'equipment',
                'magical',
                'massive',
                'machine',
                'ship'
              ];
        }


        $isConvertible = false;

        if($slot === 'draftees' or $slot === 'peasants')
        {
            $isConvertible = true;
        }
        else
        {
            # Get the $unit
            $unit = $dominion->race->units->filter(function ($unit) use ($slot) {
                    return ($unit->slot == $slot);
                })->first();

            # Get the unit attributes
            $unitAttributes = $this->unitHelper->getUnitAttributes($unit);

            #echo '<pre>Slot ' . $slot . ' for ' . $dominion->name . ': '; print_r(array_intersect($unconvertibleAttributes, $unitAttributes)); echo '</pre>';

            if(count(array_intersect($unconvertibleAttributes, $unitAttributes)) === 0 and !$dominion->race->getUnitPerkValueForUnitSlot($slot, 'fixed_casualties'))
            {
                $isConvertible = true;
            }
        }

        return $isConvertible;

    }

}
