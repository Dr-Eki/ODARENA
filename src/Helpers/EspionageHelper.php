<?php

namespace OpenDominion\Helpers;

use Log;
use Illuminate\Support\Collection;
use OpenDominion\Models\Building;
use OpenDominion\Models\Improvement;
use OpenDominion\Models\Resource;
use OpenDominion\Models\Spyop;

class EspionageHelper
{

    public function getSpyopScope(Spyop $spyop)
    {
        $scopes = [
            'self'      => 'Self',
            'friendly'  => 'Friendly',
            'hostile'   => 'Hostile'
        ];

        return $scopes[$spyop->scope];
    }

    public function getSpyopEffectsString(Spyop $spyop): array
    {

        $effectStrings = [];

        $spyopEffects = [
            'military_draftees' => 'Assassinate draftees (base damage %g%%)',
            'peasants' => 'Assassinate peasants (base damage %g%%)',

            'wizard_strength' => 'Reduce wizard strength (base damage %g%%)',
            'morale' => 'Reduce morale (base damage %g%%)',

            'construction' => 'Sabotage buildings under construction (base damage %g%%)',

            'building' => 'Sabotage %1$s (base damage %2$g%%)',
            'improvement' => 'Sabotage %1$s (base damage %2$g%%)',
            'resource' => 'Sabotage %1$s (base damage %2$g%%)',

            'buildings' => 'Sabotage %1$s (base damage %2$g%%)',
            'improvements' => 'Sabotage %1$s (base damage %2$g%%)',
            'resources' => 'Sabotage %1$s (base damage %2$g%%)',

            'convert_peasants_to_vampires_unit1' => 'Convert the peasants to Servants',

        ];

        foreach ($spyop->perks as $perk)
        {
            if (!array_key_exists($perk->key, $spyopEffects))
            {
                dd('Missing perk description for ' . $perk->key);
            }

            $perkValue = $perk->pivot->value;

            // Handle array-based perks
            $nestedArrays = false;

            // todo: refactor all of this
            // partially copied from Race::getUnitPerkValueForUnitSlot
            if (str_contains($perkValue, ','))
            {
                $perkValue = explode(',', $perkValue);

                foreach ($perkValue as $key => $value)
                {
                    if (!str_contains($value, ';'))
                    {
                        continue;
                    }

                    $nestedArrays = true;
                    $perkValue[$key] = explode(';', $value);
                }
            }

            // Special case for building
            if ($perk->key === 'building')
            {
                $buildingKey = (string)$perkValue[0];
                $damage = (float)$perkValue[1];

                $building = Building::where('key', $buildingKey)->first();

                $perkValue = [$building->name, number_format($damage)];
            }

            // Special case for buildings
            if($perk->key === 'buildings')
            {
                foreach($perkValue as $index => $buildingDamage)
                {
                    $buildingKey = (string)$buildingDamage[0];
                    $damage = (float)$buildingDamage[1];

                    $building = Building::where('key', $buildingKey)->first();

                    $perkValue[] = [$building->name, $damage];
                    unset($perkValue[$index]);
                }
            }

            // Special case for improvement
            if ($perk->key === 'improvement')
            {
                $improvementKey = (string)$perkValue[0];
                $damage = (float)$perkValue[1];

                $improvement = Improvement::where('key', $improvementKey)->first();

                $perkValue = [$improvement->name, number_format($damage)];
            }

            // Special case for improvements
            if($perk->key === 'improvements')
            {
                foreach($perkValue as $index => $improvementDamage)
                {
                    $improvementKey = (string)$improvementDamage[0];
                    $damage = (float)$improvementDamage[1];

                    $improvement = Improvement::where('key', $improvementKey)->first();

                    $perkValue[] = [$improvement->name, $damage];
                    unset($perkValue[$index]);
                }
            }

            // Special case for resource
            if ($perk->key === 'resource')
            {
                $resourceKey = (string)$perkValue[0];
                $damage = (float)$perkValue[1];

                $resource = Resource::where('key', $resourceKey)->first();

                $perkValue = [$resource->name, number_format($damage)];
            }

            // Special case for resources
            if($perk->key === 'resources')
            {
                foreach($perkValue as $index => $resourceDamage)
                {
                    $resourceKey = (string)$resourceDamage[0];
                    $damage = (float)$resourceDamage[1];

                    $resource = Resource::where('key', $resourceKey)->first();

                    $perkValue[] = [$resource->name, $damage];
                    unset($perkValue[$index]);
                }
            }



            if (is_array($perkValue))
            {
                if ($nestedArrays)
                {
                    foreach ($perkValue as $nestedKey => $nestedValue)
                    {
                        foreach($nestedValue as $key => $value)
                        {
                            $nestedValue[$key] = ucwords(str_replace('level','level ',str_replace('_', ' ',$value)));
                        }
                        $effectStrings[] = vsprintf($spyopEffects[$perk->key], $nestedValue);
                    }
                }
                else
                {
                    foreach($perkValue as $key => $value)
                    {
                        $perkValue[$key] = ucwords(str_replace('_', ' ',$value));
                    }
                    $effectStrings[] = vsprintf($spyopEffects[$perk->key], $perkValue);
                }
            }
            else
            {
                $effectStrings[] = sprintf($spyopEffects[$perk->key], $perkValue);
            }
        }

        return $effectStrings;
    }

    public function getExclusivityString(Spyop $spyop): string
    {

        $exclusivityString = '<br><small class="text-muted"><em>';

        if($exclusives = count($spyop->exclusive_races))
        {
            foreach($spyop->exclusive_races as $raceName)
            {
                $exclusivityString .= $raceName;
                if($exclusives > 1)
                {
                    $exclusivityString .= ', ';
                }
                $exclusives--;
            }

            $exclusivityString .= ' only';
        }
        elseif($excludes = count($spyop->excluded_races))
        {
            $exclusivityString .= 'All except ';
            foreach($spyop->excluded_races as $raceName)
            {
                $exclusivityString .= $raceName;
                if($excludes > 1)
                {
                    $exclusivityString .= ', ';
                }
                $excludes--;
            }
        }
        else
        {
            $exclusivityString .= 'All factions';
        }

        $exclusivityString .= '</em></small>';

        return $exclusivityString;

    }


}
