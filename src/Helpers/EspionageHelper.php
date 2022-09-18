<?php

namespace OpenDominion\Helpers;

use Illuminate\Support\Collection;
use OpenDominion\Models\Building;
use OpenDominion\Models\Improvement;
use OpenDominion\Models\Race;
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
            'kill_draftees' => 'Assassinate draftees (base damage %s).',
            'kill_peasants' => 'Assassinate peasants (base damage %s).',

            'decrease_wizard_strength' => 'Reduce wizard strength (base damage %+d%%).',
            'decrease_morale' => 'Reduce morale (base damage %+d%%).',

            'sabotage_building' => 'Sabotage %1$s buildings (base damage %2$s).',
            'sabotage_construction' => 'Sabotage buildings under construction (base damage %1$s).',
            'sabotage_improvement' => 'Sabotage %1$s improvements (base damage %2$s).',

        ];

        foreach ($spyop->perks as $perk)
        {
            if (!array_key_exists($perk->key, $spyopEffects))
            {
                //\Debugbar::warning("Missing perk help text for unit perk '{$perk->key}'' on unit '{$unit->name}''.");
                continue;
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

            // Special case for conversions
            if ($perk->key === 'sabotage_improvement')
            {
                $improvementKey = (string)$perkValue[0];
                $damage = (float)$perkValue[1];

                $improvement = Improvement::where('key', $improvementKey)->first();

                $perkValue = [$improvement->name, number_format($damage)];

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
                    #var_dump($perkValue);
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
