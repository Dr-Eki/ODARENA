<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Helpers\ConversionHelper;
use OpenDominion\Helpers\UnitHelper;

use OpenDominion\Models\Dominion;

use OpenDominion\Calculators\Dominion\CasualtiesCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;

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
        $this->casualtiesCalculator = app(CasualtiesCalculator::class);
        $this->landCalculator = app(LandCalculator::class);
        $this->militaryCalculator = app(MilitaryCalculator::class);
        $this->populationCalculator = app(PopulationCalculator::class);
        $this->rangeCalculator = app(RangeCalculator::class);
        $this->spellCalculator = app(SpellCalculator::class);

        $this->conversionHelper = app(ConversionHelper::class);
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
        $conversions['attacker'] = array_fill(1, $attacker->race->units->count(), 0);
        $conversions['defender'] = array_fill(1, $defender->race->units->count(), 0);

        # Land ratio: float
        $landRatio = $invasion['attacker']['land_size'] / $invasion['defender']['land_size'];

        # Attacker's raw OP
        $rawOp = 1; # In case someone sends with just a zero-OP unit (Snow Elf Hailstorm Cannon)
        foreach($invasion['attacker']['units_sent'] as $slot => $amount)
        {
            $unit = $attacker->race->units->filter(function ($unit) use ($slot) {
                return ($unit->slot === $slot);
            })->first();

            $rawOp += $this->militaryCalculator->getUnitPowerWithPerks($attacker, $defender, $landRatio, $unit, 'offense') * $amount;
        }

        # Attacker's raw OP lost, from convertible units
        $rawOpLost = 0;
        foreach($invasion['attacker']['units_lost'] as $slot => $amount)
        {
            $unit = $attacker->race->units->filter(function ($unit) use ($slot) {
                return ($unit->slot === $slot);
            })->first();

            if($this->conversionHelper->isSlotConvertible($slot, $attacker))
            {
                $rawOpLost += $this->militaryCalculator->getUnitPowerWithPerks($attacker, $defender, $landRatio, $unit, 'offense') * $amount;
            }
        }

        # Defender's raw DP
        $rawDp = 0;
        foreach($invasion['defender']['units_defending'] as $slot => $amount)
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

            $rawDp = max(10000, $rawDp); # Ugly hack for min DP
        }

        # Defender's raw DP lost
        $rawDpLost = 0;
        foreach($invasion['defender']['units_lost'] as $slot => $amount)
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

                if($this->conversionHelper->isSlotConvertible($slot, $defender))
                {
                  $rawDpLost += $this->militaryCalculator->getUnitPowerWithPerks($defender, $attacker, $landRatio, $unit, 'defense') * $amount;
                }
            }
        }

        $displacedPeasantsConversions = $this->getDisplacedPeasantsConversions($attacker, $defender, $invasion, $rawOp, $rawDp, $landRatio);
        $displacedPeasantsRandomSplitConversions = $this->getDisplacedPeasantsRandomSplitConversions($attacker, $defender, $invasion, $rawOp, $rawDp, $landRatio);

        $valueConversionsOnOffense = $this->getValueBasedConversions($attacker, $defender, $invasion, $rawOp, $rawDp, $landRatio, 'offense', $rawDpLost, 0);
        $valueConversionsOnDefense = $this->getValueBasedConversions($attacker, $defender, $invasion, $rawOp, $rawDp, $landRatio, 'defense', 0, $rawOpLost);

        $casualtiesBasedConversionsOnOffense = $this->getCasualtiesBasedConversions($attacker, $defender, $invasion, $rawOp, $rawDp, $landRatio, 'offense', $rawDpLost, 0);
        $casualtiesBasedConversionsOnDefense = $this->getCasualtiesBasedConversions($attacker, $defender, $invasion, $rawOp, $rawDp, $landRatio, 'defense', 0, $rawOpLost);

        $strengthBasedConversionsOnOffense = $this->getStrengthBasedConversions($attacker, $defender, $invasion, $rawOp, $rawDp, $landRatio, 'offense');
        $strengthBasedConversionsOnDefense = $this->getStrengthBasedConversions($attacker, $defender, $invasion, $rawOp, $rawDp, $landRatio, 'defense');

        #$vampiricConversionsOnOffense = $this->getVampiricConversions($attacker, $defender, $invasion, $rawOp, $rawDp, $landRatio, 'offense');
        #$vampiricConversionsOnDefense = $this->getVampiricConversions($attacker, $defender, $invasion, $rawOp, $rawDp, $landRatio, 'defense');

        foreach($conversions['attacker'] as $slot => $amount)
        {
            $conversions['attacker'][$slot] += $displacedPeasantsConversions[$slot];
            $conversions['attacker'][$slot] += $displacedPeasantsRandomSplitConversions[$slot];

            $conversions['attacker'][$slot] += $valueConversionsOnOffense[$slot];
            $conversions['attacker'][$slot] += $casualtiesBasedConversionsOnOffense[$slot];
            $conversions['attacker'][$slot] += $strengthBasedConversionsOnOffense[$slot];
            #$conversions['attacker'][$slot] += $vampiricConversionsOnOffense[$slot];
        }

        if($attacker->getSpellPerkValue('no_conversions'))
        {
            $conversions['attacker'] = array_fill(1, $attacker->race->units->count(), 0);
        }

        foreach($conversions['defender'] as $slot => $amount)
        {
            $conversions['defender'][$slot] += $valueConversionsOnDefense[$slot];
            $conversions['defender'][$slot] += $casualtiesBasedConversionsOnDefense[$slot];
            $conversions['defender'][$slot] += $strengthBasedConversionsOnDefense[$slot];
            #$conversions['defender'][$slot] += $vampiricConversionsOnDefense[$slot];
        }

        if($defender->getSpellPerkValue('no_conversions'))
        {
            $conversions['defender'] = array_fill(1, $defender->race->units->count(), 0);
        }

        return $conversions;
    }

    /*
    *   Calculate how many peasants were displaced.
    */
    public function getDisplacedPeasantsConversions(Dominion $attacker, Dominion $defender, array $invasion, int $rawOp, int $rawDp, float $landRatio): array
    {
        $convertedUnits = array_fill(1, $attacker->race->units->count(), 0);
        $convertingUnits = array_fill(1, $attacker->race->units->count(), 0);

        if(!$invasion['result']['success'])
        {
            return $convertedUnits;
        }

        $landConquered = $invasion['attacker']['land_conquered'];
        $displacedPeasants = intval(($defender->peasants / $invasion['defender']['land_size']) * $landConquered);

        # Apply reduced conversions
        $displacedPeasants *= $this->getConversionReductionMultiplier($defender);

        # Check that unitsSent contains displaced_peasants_conversion perk
        foreach($invasion['attacker']['units_sent'] as $slot => $amount)
        {
            if($displacedPeasantsConversionPerk = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'displaced_peasants_conversion'))
            {
                $convertingUnits[$slot] += $amount;
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

    /*
    *   Calculate how many peasants were displaced.
    */
    public function getDisplacedPeasantsRandomSplitConversions(Dominion $attacker, Dominion $defender, array $invasion, int $rawOp, int $rawDp, float $landRatio): array
    {
        $convertedUnits = array_fill(1, $attacker->race->units->count(), 0);
        $convertingUnits = array_fill(1, $attacker->race->units->count(), 0);

        if(!$invasion['result']['success'])
        {
            return $convertedUnits;
        }

        $landConquered = $invasion['attacker']['land_conquered'];
        $displacedPeasants = intval(($defender->peasants / $invasion['defender']['land_size']) * $landConquered);

        # Apply reduced conversions
        $displacedPeasants *= $this->getConversionReductionMultiplier($defender);

        # Check that unitsSent contains displaced_peasants_random_split_conversion perk
        foreach($invasion['attacker']['units_sent'] as $slot => $amount)
        {
            if($attacker->race->getUnitPerkValueForUnitSlot($slot, 'displaced_peasants_random_split_conversion'))
            {
                $convertingUnits[$slot] += $amount;
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
            if($displacedPeasantsConversionRandomSplitPerk = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'displaced_peasants_random_split_conversion'))
            {
                $rangeMin = (int)$displacedPeasantsConversionRandomSplitPerk[0];
                $rangeMax = (int)$displacedPeasantsConversionRandomSplitPerk[1];
                $primarySlotTo = (int)$displacedPeasantsConversionRandomSplitPerk[2];
                $fallbackSlotTo = (int)$displacedPeasantsConversionRandomSplitPerk[3];

                $primarySlotRatio = mt_rand($rangeMin, $rangeMax) / 100;
                $fallbackSlotRatio = 1 - $primarySlotRatio;

                $convertedDisplacedPeasants = $displacedPeasants * $ratio;
                
                $convertedUnits[$primarySlotTo] += round($convertedDisplacedPeasants * $primarySlotRatio);
                $convertedUnits[$fallbackSlotTo] += round($convertedDisplacedPeasants * $fallbackSlotRatio);
            }
         }

         return $convertedUnits;
    }

    public function getValueBasedConversions(Dominion $attacker, Dominion $defender, array $invasion, int $rawOp, int $rawDp, float $landRatio, string $mode, int $rawDpLost, int $rawOpLost): array
    {

        if($mode === 'offense')
        {
            $convertedUnits = array_fill(1, $attacker->race->units->count(), 0);
            $convertingUnits = array_fill(1, $attacker->race->units->count(), 0);

            # Check that unitsSent contains displaced_peasants_conversion perk
            foreach($invasion['attacker']['units_sent'] as $slot => $amount)
            {
                if($valueConversionPerk = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'value_conversion'))
                {
                    $convertingUnits[$slot] += $amount;
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
            $convertedUnits = array_fill(1, $defender->race->units->count(), 0);
            $convertingUnits = array_fill(1, $defender->race->units->count(), 0);
            # Check that units_defending contains displaced_peasants_conversion perk
            foreach($invasion['defender']['units_defending'] as $slot => $amount)
            {
                if($slot !== 'draftees' and $slot !== 'peasants')
                {
                    if($valueConversionPerk = $defender->race->getUnitPerkValueForUnitSlot($slot, 'value_conversion'))
                    {
                        $convertingUnits[$slot] += $amount;

                        # Deduct lost units.
                        /*
                        if(isset($invasion['defender']['units_lost'][$slot]))
                        {
                            $convertingUnits[$slot] -= $invasion['defender']['units_lost'][$slot];
                        }
                        */
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

                      $convertedUnits[$convertedSlot] += (int)round(($rawDpLost * $this->getConversionReductionMultiplier($attacker) * $convertingUnitsDpRatio[$slot]) * $conversionMultiplier);
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
            $convertedUnits = array_fill(1, $attacker->race->units->count(), 0);
            $convertingUnits = array_fill(1, $attacker->race->units->count(), 0);

            $defensiveCasualties = 0;
            foreach($invasion['defender']['units_lost'] as $slot => $amount)
            {
                if($this->conversionHelper->isSlotConvertible($slot, $defender))
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
            foreach($invasion['attacker']['units_sent'] as $slot => $amount)
            {
                if($valueConversionPerk = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'casualties_conversion'))
                {
                    $convertingUnits[$slot] += $amount;

                    # Deduct lost units.
                    /*
                    if(isset($invasion['attacker']['units_lost'][$slot]))
                    {
                        $convertingUnits[$slot] -= $invasion['attacker']['units_lost'][$slot];
                    }
                    */
                }
            }

            # Calculate contribution (unit raw OP / total raw OP)
            # This determines how many units were killed by each converting unit
            foreach($convertingUnits as $slot => $amount)
            {
                $unit = $attacker->race->units->filter(function ($unit) use ($slot) {
                    return ($unit->slot === $slot);
                })->first();

                $unitRawOp = $this->militaryCalculator->getUnitPowerWithPerks($attacker, $defender, $landRatio, $unit, 'offense', null, $invasion['attacker']['units_sent'], null);

                $convertingUnitsOpRatio[$slot] = ($unitRawOp * $amount) / $rawOp;
             }

             foreach($convertingUnits as $slot => $amount)
             {
                  if($amount > 0)
                  {
                      $valueConversionPerk = $attacker->race->getUnitPerkValueForUnitSlot($slot, 'casualties_conversion');
                      $convertedSlot = (int)$valueConversionPerk[0];

                      $convertedUnits[$convertedSlot] += (int)round($defensiveCasualties * $this->getConversionReductionMultiplier($defender) * $convertingUnitsOpRatio[$slot]);
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
            $convertedUnits = array_fill(1, $defender->race->units->count(), 0);
            $convertingUnits = array_fill(1, $defender->race->units->count(), 0);

            $offensiveCasualties = 0;
            foreach($invasion['attacker']['units_lost'] as $slot => $amount)
            {
                if($this->conversionHelper->isSlotConvertible($slot, $attacker))
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

            # Check that units_defending contains casualties_conversion perk
            foreach($invasion['defender']['units_defending'] as $slot => $amount)
            {
                if($slot !== 'draftees' and $slot !== 'peasants')
                {
                    if($valueConversionPerk = $defender->race->getUnitPerkValueForUnitSlot($slot, 'casualties_conversion'))
                    {
                        $convertingUnits[$slot] += $amount;

                        # Deduct lost units.
                        /*
                        if(isset($invasion['defender']['units_lost'][$slot]))
                        {
                            $convertingUnits[$slot] -= $invasion['defender']['units_lost'][$slot];
                        }
                        */
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

                      $convertedUnits[$convertedSlot] += (int)round($offensiveCasualties * $this->getConversionReductionMultiplier($defender) * $convertingUnitsDpRatio[$slot]);
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
            $convertedUnits = array_fill(1, $attacker->race->units->count(), 0);
            $convertingUnits = array_fill(1, $attacker->race->units->count(), 0);

            $availableCasualties =
                [
                    'draftees' => ['amount' => 0, 'dp' => 0],
                           '1' => ['amount' => 0, 'dp' => 0],
                           '2' => ['amount' => 0, 'dp' => 0],
                           '3' => ['amount' => 0, 'dp' => 0],
                           '4' => ['amount' => 0, 'dp' => 0],
                ];

            # Check that unitsSent contains strength_conversion perk
            foreach($invasion['attacker']['units_sent'] as $slot => $amount)
            {
                if($attacker->race->getUnitPerkValueForUnitSlot($slot, 'strength_conversion'))
                {
                    $convertingUnits[$slot] += $amount;
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

            foreach($invasion['defender']['units_lost'] as $slot => $amount)
            {
                # Apply reduced conversions
                $amount *= $this->getConversionReductionMultiplier($defender);

                # Drop if invasion is not successful
                if(!$invasion['result']['success'])
                {
                    $amount /= static::OFFENSIVE_CONVERSIONS_FAILED_FRACTION;
                }

                # Round it down
                #$amount = round($amount); -- Moved to later in the code

                if($slot === 'draftees')
                {
                    $availableCasualties[$slot]['amount'] = $amount;

                    $availableCasualties[$slot]['dp'] = ($defender->race->getPerkValue('draftee_dp') + $defender->getTechPerkValue('draftee_dp') )?: 1;

                }
                elseif($slot === 'peasants')
                {
                    $availableCasualties[$slot]['amount'] = $amount;

                    $availableCasualties[$slot]['dp'] = ($defender->race->getPerkValue('peasant_dp') + $defender->getTechPerkValue('peasant_dp')) ?: 0;

                }
                else
                {
                    # Get the $unit
                    $unit = $defender->race->units->filter(function ($unit) use ($slot) {
                            return ($unit->slot == $slot);
                        })->first();

                    if($this->conversionHelper->isSlotConvertible($slot, $defender))
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
            $convertedUnits = array_fill(1, $defender->race->units->count(), 0);
            $convertingUnits = array_fill(1, $defender->race->units->count(), 0);

            $availableCasualties =
                [
                           '1' => ['amount' => 0, 'op' => 0],
                           '2' => ['amount' => 0, 'op' => 0],
                           '3' => ['amount' => 0, 'op' => 0],
                           '4' => ['amount' => 0, 'op' => 0],
                ];

            # Check that units_defending contains strength_conversion perk
            foreach($invasion['defender']['units_defending'] as $slot => $amount)
            {
                if($slot !== 'draftees' and $slot !== 'peasants')
                {
                    if($strengthBasedConversionPerk = $defender->race->getUnitPerkValueForUnitSlot($slot, 'strength_conversion'))
                    {
                        $convertingUnits[$slot] += $amount;

                        # Deduct lost units.
                        /*
                        if(isset($invasion['defender']['units_lost'][$slot]))
                        {
                            $convertingUnits[$slot] -= $invasion['defender']['units_lost'][$slot];
                        }
                        */
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

            foreach($invasion['attacker']['units_lost'] as $slot => $amount)
            {
                # Apply reduced conversions
                $amount *= $this->getConversionReductionMultiplier($attacker);

                # Drop if invasion is successful
                if($invasion['result']['success'])
                {
                    $amount /= static::DEFENSIVE_CONVERSIONS_FAILED_FRACTION;
                }

                # Get the $unit
                $unit = $attacker->race->units->filter(function ($unit) use ($slot) {
                        return ($unit->slot == $slot);
                    })->first();

                if($this->conversionHelper->isSlotConvertible($slot, $attacker))
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

    public function getPsionicConversions(Dominion $cult, Dominion $enemy, array $invasion, string $mode): array
    {
        $conversions['psionic_conversions'] = [1 => 0];

        $conversions['psionic_losses'] = [
            1 => 0,
            2 => 0,
            3 => 0,
            4 => 0,
            'draftees' => 0,
            'peasants' => 0,
        ];

        if($cult->race->name !== 'Cult')
        {
            return $conversions;
        }

        if($mode == 'offense')
        {
            if(!$invasion['result']['success'])
            {
                return $conversions;
            }

            try
            {
                $psionicStrengthRatio = $invasion['attacker']['psionic_strength'] / $invasion['defender']['psionic_strength'];
            }
            catch(DivisionByZeroError $e)
            {
                $psionicStrengthRatio = $invasion['attacker']['psionic_strength'];
            }

            $psionicStrengthRatio = $psionicStrengthRatio > 2 ? 2 : $psionicStrengthRatio;

            # Look for reduces enemy casualties
            for ($slot = 1; $slot <= $enemy->race->units->count(); $slot++)
            {
                if($cult->race->getUnitPerkValueForUnitSlot($slot, 'reduces_enemy_casualties'))
                {
                    if(isset($invasion['attacker']['units_sent'][$slot]))
                    {
                        $casualtyReductionPerk = ($invasion['attacker']['units_sent'][$slot] / array_sum($invasion['attacker']['units_sent'])) / 2;
                    }
                }
            }

            $baseRatio = 0.01;
            $baseRatio *= min($invasion['result']['op_dp_ratio'], 1.25);
            $ratioMultiplier = 0;
            $ratioMultiplier += $psionicStrengthRatio;
            $ratioMultiplier += $casualtyReductionPerk;

            $ratio = $baseRatio * $ratioMultiplier;

            foreach($invasion['defender']['units_surviving'] as $slot => $amount)
            {
                if($this->conversionHelper->isSlotConvertible($slot, $enemy, [], [], true, $cult, $invasion, $mode))
                {
                    # Lazy because they all become Unit1/Thrall for now.
                    $amountConverted = intval(min($invasion['defender']['units_lost'][$slot], $amount, $amount * $ratio));
                    $conversions['psionic_conversions'][1] += $amountConverted;
                    $conversions['psionic_losses'][$slot] += $amountConverted;
                }
            }

            # Peasants
            $amountConverted = intval(min($enemy->peasants * $ratio, ($enemy->peasants-1000)));
            $conversions['psionic_conversions'][1] += $amountConverted;
            $conversions['psionic_losses']['peasants'] += $amountConverted;


        }
        if($mode == 'defense')
        {
            # Cult is the defender here


            if($invasion['result']['success'])
            {
                return $conversions;
            }

            try
            {
                $psionicStrengthRatio = $invasion['defender']['psionic_strength'] / $invasion['attacker']['psionic_strength'];
            }
            catch(DivisionByZeroError $e)
            {
                $psionicStrengthRatio = $invasion['defender']['psionic_strength'];
            }

            $psionicStrengthRatio = $psionicStrengthRatio > 2 ? 2 : $psionicStrengthRatio;
            
            # Look for reduces enemy casualties
            $totalUnitsAtHome = $this->militaryCalculator->getTotalUnitsAtHome($cult);
            for ($slot = 1; $slot <= $cult->race->units->count(); $slot++)
            {
                if($cult->race->getUnitPerkValueForUnitSlot($slot, 'reduces_enemy_casualties'))
                {
                    $casualtyReductionPerk = ($invasion['defender']['units_defending'][$slot] / $totalUnitsAtHome) / 2;
                }
            }

            $baseRatio = 0.005;
            $baseRatio *= min(1/$invasion['result']['op_dp_ratio']-1, 1.25);
            $ratioMultiplier = 0;
            $ratioMultiplier += $psionicStrengthRatio;
            $ratioMultiplier += $casualtyReductionPerk;

            $ratio = $baseRatio * $ratioMultiplier;

            foreach($invasion['attacker']['units_surviving'] as $slot => $amount)
            {
                # Lazy because they all become Unit1/Thrall for now.
                $amountConverted = intval(min($invasion['attacker']['units_lost'][$slot], $amount, $amount * $ratio));
                $conversions['psionic_conversions'][1] += $amountConverted;
                $conversions['psionic_losses'][$slot] += $amountConverted;
            }
        }

        return $conversions;
    }

    public function getPassiveConversions(Dominion $dominion): array
    {
        $convertedUnits = [];
        foreach($dominion->race->units as $unit)
        {
            $convertedUnits[$unit->slot] = 0;
        }

        $removedUnits = $convertedUnits;

        #$availablePopulation = $this->populationCalculator->getMaxPopulation($dominion) - $this->populationCalculator->getPopulationMilitary($dominion);

        # Check each unit slot for passive conversion
        for ($slot = 1; $slot <= $dominion->race->units->count(); $slot++)
        {
            if($passiveConversion = $dominion->race->getUnitPerkValueForUnitSlot($slot, 'passive_conversion'))
            {
                $fromSlot = (int)$passiveConversion[0];
                $toSlot = (int)$passiveConversion[1];
                $perConverter = (float)$passiveConversion[2];

                $convertingUnits = $dominion->{'military_unit' . $slot};

                $amountConverted = (int)floor(min($convertingUnits * $perConverter, $dominion->{'military_unit' . $fromSlot}));

                $convertedUnits[$toSlot] += $amountConverted;
                $removedUnits[$fromSlot] += $amountConverted;

                $convertedUnits[$toSlot] = min($convertedUnits[$toSlot], $dominion->{'military_unit' . $fromSlot});
                $removedUnits[$fromSlot] = min($removedUnits[$fromSlot], $dominion->{'military_unit' . $fromSlot});
            }
        }

        return ['units_converted' => $convertedUnits, 'units_removed' => $removedUnits];

    }

    public function getConversionReductionMultiplier(Dominion $dominion): float
    {
        if($dominion->getSpellPerkValue('cannot_be_converted'))
        {
            $multiplier = 0;
        }

        $multiplier = 1;

        # Faction perk
        $multiplier -= $dominion->race->getPerkMultiplier('reduced_conversions');
        $multiplier -= $dominion->realm->getArtefactPerkMultiplier('reduced_conversions');

        foreach($dominion->race->units as $unit)
        {
            if($dominion->race->getUnitPerkValueForUnitSlot($unit->slot, 'reduces_conversions'))
            {
                $multiplier += ($dominion->{'military_unit' . $unit->slot} / $this->militaryCalculator->getTotalUnitsAtHome($dominion, true, true, true)) / 2; # true = draftees; true = spies, wizards, archmages; true = peasants
            }
        }


        
        return max(0, $multiplier);

    }

}
