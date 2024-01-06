<?php

namespace OpenDominion\Helpers;

use OpenDominion\Models\Dominion;

use OpenDominion\Calculators\Dominion\CasualtiesCalculator;
use OpenDominion\Helpers\UnitHelper;

class ConversionHelper
{

    /** @var CasualtiesCalculator */
    protected $casualtiesCalculator;

    /** @var UnitHelper */
    protected $unitHelper;

    public function __construct()
    {
        $this->casualtiesCalculator = app(CasualtiesCalculator::class);

        $this->unitHelper = app(UnitHelper::class);
    }

    public function isSlotConvertible($slot, Dominion $dominion, array $unconvertibleAttributes = [], array $unconvertiblePerks = [], Dominion $enemy = null, array $invasion = [], $mode = 'offense', $type = 'units'): bool
    {
        if(empty($unconvertibleAttributes))
        {
            $unconvertibleAttributes = $this->getUnconvertibleAttributes($type);
        }

        if(empty($unconvertiblePerks))
        {
            $unconvertiblePerks = $this->getUnconvertiblePerks($type);
        }

        if($type == 'psionic')
        {   
            $unit = $slot;
            if(!in_array($slot, ['draftees',' peasants']))
            {
                $unit = $dominion->race->units->filter(function ($unit) use ($slot) {
                    return ($unit->slot == $slot);
                })->first();
            }
    
            if($this->casualtiesCalculator->isUnitImmortal($dominion, $enemy, $unit, $invasion, $mode))
            {
                return false;
            }
        }

        $isConvertible = false;

        if($slot === 'draftees' or $slot === 'peasants')
        {
            $isConvertible = true;
        }
        elseif(($slot == 'wizards' or $slot == 'archmages') and !$dominion->race->getPerkValue('immortal_wizards'))
        {
            $isConvertible = true;
        }
        elseif($slot == 'spies' and !$dominion->race->getPerkValue('immortal_spies'))
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

            # Check the unit perks
            $hasBadPerk = false;
            foreach($unconvertiblePerks as $perk)
            {
                if($dominion->race->getUnitPerkValueForUnitSlot($slot, $perk))
                {
                    $hasBadPerk = true;
                }
            }

            if(count(array_intersect($unconvertibleAttributes, $unitAttributes)) === 0 and !$hasBadPerk)
            {
                $isConvertible = true;
            }
        }

        return $isConvertible;

    }

    public function getUnconvertibleAttributes(string $conversionType = 'units'): array
    {
        
        $unconvertibleAttributes = [
            'ammunition',
            'aspect',
            'equipment',
            'ethereal',
            'fused',
            'immobile',
            'magical',
            'massive',
            'machine',
            'ship'
          ];

        # For Resource conversion, remove the 'massive' attribute
        if($conversionType == 'resource')
        {
            unset($unconvertibleAttributes['massive']);
        }

        # For Psionic conversion, remove the 'aspect', 'fused', 'mindless', and 'wise' attributes
        if($conversionType == 'psionic')
        {
            unset($unconvertibleAttributes['aspect']);
            unset($unconvertibleAttributes['fused']);
            $unconvertibleAttributes[] = 'mindless';
            $unconvertibleAttributes[] = 'wise';
        }

        return $unconvertibleAttributes;
    }
    
    public function getUnconvertiblePerks(string $conversionType = 'units'): array
    {
        $unconvertiblePerks = [
            'fixed_casualties',
            'dies_into',
            'dies_into_spy',
            'dies_into_wizard',
            'dies_into_archmage',
            'dies_into_multiple',
            'dies_into_resource',
            'dies_into_resources',
            'dies_into_multiple_on_offense',
            'dies_into_on_offense',
            'dies_into_multiple_on_victory'
          ];

          # For Resource conversion, remove the 'fixed_casualties' perk
          if($conversionType == 'resource')
          {
              unset($unconvertibleAttributes['fixed_casualties']);
          }

        return $unconvertiblePerks;
    }

}
