<?php

namespace OpenDominion\Helpers;
use Illuminate\Support\Collection;
use OpenDominion\Models\Building;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Decree;
use OpenDominion\Models\DecreeState;
use OpenDominion\Models\DominionDecreeState;
use OpenDominion\Models\Race;

class DecreeHelper
{

    public function getDecreeStateDescription(DecreeState $decreeState): ?string
    {

        $helpStrings[$decreeState->name] = '';

        $perkTypeStrings = [
            # Housing and Population
            'max_population' => '%s%% population.',
            'population_growth' => '%s%% population growth rate.',
            'drafting' => '%s%% drafting.',
            'military_housing' => '%s%% military housing.',

            # Production
            'gold_production_mod' => '%s%% gold production.',
            'food_production_mod' => '%s%% food production.',
            'lumber_production_mod' => '%s%% lumber production.',
            'ore_production_mod' => '%s%% ore production.',
            'gems_production_mod' => '%s%% gem production.',
            'mana_production_mod' => '%s%% mana production.',
            'pearls_production_mod' => '%s%% pearl production.',
            'blood_production_mod' => '%s%% blood production.',
            'horse_production_mod' => '%s%% horse production.',
            'mud_production_mod' => '%s%% mud production.',
            'swamp gas_production_mod' => '%s%% swamp gas production.',
            'xp_generation_mod' => '%s%% XP generation.',
            'xp_gains' => '%s%% XP gains.',

            'building_gold_mine_production_mod' => '%s%% gold mine production.',
            'building_gold_quarry_production_mod' => '%s%% gold quarry production.',

            'exchange_rate' => '%s%% exchange rates.',

            'food_consumption_mod' => '%s%% food consumption.',

            # Military
            'offensive_casualties' => '%s%% casualties on offense.',
            'defensive_casualties' => '%s%% casualties on defense.',

            'increases_enemy_casualties' => '%s%% enemy casualties.',
            'increases_enemy_casualties_on_defense' => '%s%% enemy casualties on defense.',
            'increases_enemy_casualties_on_offense' => '%s%% enemy casualties on offense.',

            'unit_costs' => '%s%% unit costs.',
            'unit_gold_costs' => '%s%% unit gold costs.',
            'unit_ore_costs' => '%s%% unit ore costs.',
            'unit_lumber_costs' => '%s%% unit lumber costs.',
            'unit_mana_costs' => '%s%% unit mana costs.',
            'unit_blood_costs' => '%s%% unit blood costs.',
            'unit_food_costs' => '%s%% unit food costs.',

            'extra_units_trained' => '%s additional units trained for free.',

            'morale_gains' => '%s%% morale gains.',
            'base_morale' => '%s%% base morale.',
            'prestige_gains' => '%s%% prestige gains.',

            'land_discovered' => '%s%% land discovered during invasions.',

            'reduces_attrition' => '%s%% unit attrition.',

            'reduces_conversions' => '%s%% conversions for enemies.',

            'training_time_mod' => '%s%% training time.',

            'unit_pairing' => '%s%% unit pairing capacity.',

            # OP/DP
            'offensive_power' => '%s%% offensive power.',
            'defensive_power' => '%s%% defensive power.',

            # Improvements
            'improvements' => '%s%% improvements.',
            'improvement_points' => '%s%% improvement points when investing.',

            # Construction and Rezoning
            'construction_cost' => '%s%% construction costs.',
            'rezone_cost' => '%s%% rezoning costs.',

            # Espionage and Wizardry
            'spy_losses' => '%s%% spy losses.',
            'spell_damage' => '%s%% spell damage.',
            'wizard_cost' => '%s%% wizard costs.',
            'spell_cost' => '%s%% spell costs.',

            'gold_theft_reduction' => '%s%% gold stolen from you.',
            'gems_theft_reduction' => '%s%% gems stolen from you.',
            'ore_theft_reduction' => '%s%% ore stolen from you.',
            'lumber_theft_reduction' => '%s%% lumber stolen from you.',
            'food_theft_reduction' => '%s%% food stolen from you.',
            'mana_theft_reduction' => '%s%% mana stolen from you.',
            'horse_theft_reduction' => '%s%% horses stolen from you.',

            'wizard_strength_recovery' => '%s%% wizard strength recovery per tick.',
            'spy_strength_recovery' => '%s%% wizard strength recovery per tick.',
            
            'spy_strength' => '%s%% spy strength.',
            'spy_strength_on_defense' => '%s%% spy strength on defense.',
            'spy_strength_on_offense' => '%s%% spy strength on offense.',

            'wizard_strength' => '%s%% wizard strength.',
            'wizard_strength_on_defense' => '%s%% wizard strength on defense.',
            'wizard_strength_on_offense' => '%s%% wizard strength on offense.',

            # Growth specific
            'generate_building' => 'Generate %s.',
            'generate_building_plain' => 'Generate %s on plains',
            'generate_building_mountain' => 'Generate %s in mountains',
            'generate_building_hill' => 'Generate %s on hills',
            'generate_building_swamp' => 'Generate %s in swamps',
            'generate_building_water' => 'Generate %s in water',
            'generate_building_forest' => 'Generate %s in the forest',
        ];

        foreach ($decreeState->perks as $perk)
        {
            if (!array_key_exists($perk->key, $perkTypeStrings))
            {
                continue;
            }

            $perkValue = $perk->pivot->value;

            $nestedArrays = false;
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

            # SPECIAL DESCRIPTION PERKS

            if($perk->key === 'generate_building' or
                $perk->key === 'generate_building_plain' or
                $perk->key === 'generate_building_mountain' or
                $perk->key === 'generate_building_hill' or
                $perk->key === 'generate_building_swamp' or
                $perk->key === 'generate_building_water' or
                $perk->key === 'generate_building_forest')
            {
                $building = Building::where('key', $perkValue)->first();

                $perkValue = $building->name;

            }

            if (is_array($perkValue))
            {
                if ($nestedArrays)
                {
                    foreach ($perkValue as $nestedKey => $nestedValue)
                    {
                        $helpStrings[$decreeState->name] .= '<li>' . vsprintf($perkTypeStrings[$perk->key], $nestedValue) . '</li>';
                    }
                }
                else
                {
                    $perkValue = $perkValue > 0 ? '+' . $perkValue : $perkValue;
                    $helpStrings[$decreeState->name] .= '<li>' . vsprintf($perkTypeStrings[$perk->key], $perkValue) . '</li>';
                }
            }
            else
            {
                $perkValue = $perkValue > 0 ? '+' . $perkValue : $perkValue;
                
                $helpStrings[$decreeState->name] .= '<li>' . sprintf($perkTypeStrings[$perk->key], $perkValue) . '</li>';
            }
        }

        if(strlen($helpStrings[$decreeState->name]) == 0)
        {
            $helpStrings[$decreeState->name] = '<i>No special abilities</i>';
        }
        else
        {
            $helpStrings[$decreeState->name] = '<ul>' . $helpStrings[$decreeState->name] . '</ul>';
        }

        return $helpStrings[$decreeState->name] ?: null;
    }

    /*
    *   Returns decrees available for the race.
    *   If $landType is present, only return decrees for the race for that land type.
    */
    public function getDecreesByRace(Race $race): Collection
    {
        $decrees = collect(Decree::all()->sortBy('name')->where('enabled',1));

        foreach($decrees as $decree)
        {
          if(
                (count($decree->excluded_races) > 0 and in_array($race->name, $decree->excluded_races)) or
                (count($decree->exclusive_races) > 0 and !in_array($race->name, $decree->exclusive_races))
            )
          {
              $decrees->forget($decree->key);
          }
        }

        return $decrees;
    }

    public function getExclusivityString(Decree $decree): string
    {

        $exclusivityString = '<br><small class="text-muted">';

        if($exclusives = count($decree->exclusive_races))
        {
            foreach($decree->exclusive_races as $raceName)
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
        elseif($excludes = count($decree->excluded_races))
        {
            $exclusivityString .= 'All except ';
            foreach($decree->excluded_races as $raceName)
            {
                $exclusivityString .= $raceName;
                if($excludes > 1)
                {
                    $exclusivityString .= ', ';
                }
                $excludes--;
            }
        }

        $exclusivityString .= '</small>';

        return $exclusivityString;

    }

    public function isDominionDecreeIssued(Dominion $dominion, Decree $decree): bool
    {
        return $dominion->decreeStates->contains($decree);
    }

    public function getDominionDecreeState(Dominion $dominion, Decree $decree): DominionDecreeState
    {
        return DominionDecreeState::where('dominion_id', $dominion->id)->where('decree_id', $decree->id)->first();
        #return $dominion->decreeStates->where('decree_id', $decree->id)->first();
    }

}
