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
            'max_population' => '%+d%% population.',
            'population_growth' => '%+d%% population growth rate.',
            'drafting' => '%+d%% drafting.',
            'military_housing' => '%+d%% military housing.',

            # Production
            'gold_production_mod' => '%+d%% gold production.',
            'food_production_mod' => '%+d%% food production.',
            'lumber_production_mod' => '%+d%% lumber production.',
            'ore_production_mod' => '%+d%% ore production.',
            'gems_production_mod' => '%+d%% gem production.',
            'mana_production_mod' => '%+d%% mana production.',
            'pearls_production_mod' => '%+d%% pearl production.',
            'blood_production_mod' => '%+d%% blood production.',
            'horse_production_mod' => '%+d%% horse production.',
            'mud_production_mod' => '%+d%% mud production.',
            'swamp gas_production_mod' => '%+d%% swamp gas production.',
            'xp_generation_mod' => '%+d%% XP generation.',
            'xp_gains' => '%+d%% XP gains.',

            'building_gold_mine_production_mod' => '%+d%% production from Gold Mines.',
            'building_gold_quarry_production_mod' => '%+d%% production from Gold Quarries.',

            'exchange_rate' => '%+d%% exchange rates.',

            'food_consumption_mod' => '%+d%% food consumption.',

            # Deity
            'deity_power' => '%+d%% deity perks.',

            'range_multiplier' => '%sx range multiplier.',

            # Military
            'offensive_casualties' => '%+d%% casualties on offense.',
            'defensive_casualties' => '%+d%% casualties on defense.',

            'target_defensive_power_mod' => '%+d%% defensive modifier for target.',

            'increases_enemy_casualties' => '%+d%% enemy casualties.',
            'increases_enemy_casualties_on_defense' => '%+d%% enemy casualties on defense.',
            'increases_enemy_casualties_on_offense' => '%+d%% enemy casualties on offense.',

            'unit_costs' => '%+d%% unit costs.',
            'unit_gold_costs' => '%+d%% unit gold costs.',
            'unit_ore_costs' => '%+d%% unit ore costs.',
            'unit_lumber_costs' => '%+d%% unit lumber costs.',
            'unit_mana_costs' => '%+d%% unit mana costs.',
            'unit_blood_costs' => '%+d%% unit blood costs.',
            'unit_food_costs' => '%+d%% unit food costs.',

            'unit_gold_costs_from_wizard_ratio' => '%+d%% unit gold costs per 1 WPA.',
            'unit_lumber_costs_from_wizard_ratio' => '%+d%% unit lumber costs per 1 WPA.',

            'extra_units_trained' => '%s additional units trained for free.',

            'morale_gains' => '%+d%% morale gains.',
            'base_morale' => '%+d%% base morale.',
            'prestige_gains' => '%+d%% prestige gains.',

            'land_discovered' => '%+d%% land discovered during invasions.',

            'reduces_attrition' => '%+d%% unit attrition.',

            'reduces_conversions' => '%+d%% conversions for enemies.',

            'training_time_mod' => '%+d%% training time.',

            'unit_pairing' => '%+d%% unit pairing capacity.',

            'undead_unit1_production_raw' => 'Each %3$s raises %2$s %1$s per tick.',
            'undead_unit2_production_raw' => 'Each %3$s raises %2$s %1$s per tick.',
            'undead_unit3_production_raw' => 'Each %3$s raises %2$s %1$s per tick.',
            'undead_unit4_production_raw' => 'Each %3$s raises %2$s %1$s per tick.',
            'undead_unit3_production_raw_from_crypt' => 'Each %3$s raises %2$s %1$s per tick from the crypt.',
            
            'attrition_mod' => '%+d%% attrition.',

            # OP/DP
            'offensive_power' => '%+d%% offensive power.',
            'defensive_power' => '%+d%% defensive power.',

            'can_send_cannonballs' => 'Can fire cannonballs.',
            'can_send_ammunition_units' => 'Can launch ammunition units.',

            # Improvements
            'improvements' => '%+d%% improvements.',
            'improvement_points' => '%+d%% improvement points when investing.',

            # Construction and Rezoning
            'construction_cost' => '%+d%% construction costs.',
            'rezone_cost' => '%+d%% rezoning costs.',

            'construction_cost_from_wizard_ratio' => '%+d%% construction costs per 1 WPA.',
            'construction_time_from_wizard_ratio' => '%+d%% construction time per 1 WPA.',

            # Espionage and Wizardry
            'spy_losses' => '%+d%% spy losses.',
            'spell_damage' => '%+d%% spell damage.',
            'spy_cost' => '%+d%% spy costs.',
            'wizard_cost' => '%+d%% wizard costs.',
            'spell_cost' => '%+d%% spell costs.',
            'spell_cost_from_wizard_ratio' => '%+d%% spell costs per 1 WPA.',
            'sorcery_cost_from_wizard_ratio' => '%+d%% sorcery costs per 1 WPA.',

            'gold_theft_reduction' => '%+d%% gold stolen from you.',
            'gems_theft_reduction' => '%+d%% gems stolen from you.',
            'ore_theft_reduction' => '%+d%% ore stolen from you.',
            'lumber_theft_reduction' => '%+d%% lumber stolen from you.',
            'food_theft_reduction' => '%+d%% food stolen from you.',
            'mana_theft_reduction' => '%+d%% mana stolen from you.',
            'horse_theft_reduction' => '%+d%% horses stolen from you.',

            'wizard_strength_recovery' => '%+d%% wizard strength recovery per tick.',
            'spy_strength_recovery' => '%+d%% wizard strength recovery per tick.',
            
            'spy_strength' => '%+d%% spy strength.',
            'spy_strength_on_defense' => '%+d%% spy strength on defense.',
            'spy_strength_on_offense' => '%+d%% spy strength on offense.',

            'wizard_strength' => '%+d%% wizard strength.',
            'wizard_strength_on_defense' => '%+d%% wizard strength on defense.',
            'wizard_strength_on_offense' => '%+d%% wizard strength on offense.',

            'wizards_count_as_spies' => 'Wizards also count as %s %s.',

            # Growth specific
            'generate_building' => 'Generate %s.',
            'generate_building_plain' => 'Generate %s on plains',
            'generate_building_mountain' => 'Generate %s in mountains',
            'generate_building_hill' => 'Generate %s on hills',
            'generate_building_swamp' => 'Generate %s in swamps',
            'generate_building_water' => 'Generate %s in water',
            'generate_building_forest' => 'Generate %s in the forest',

            # Legion specific
            'show_of_force_invading_annexed_barbarian' => '',
            'distribute_discovered_land_to_annexed_dominions' => '',
            'autonomous_barbarians' => '',
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

            if($perk->key === 'undead_unit1_production_raw' or
                $perk->key === 'undead_unit2_production_raw' or
                $perk->key === 'undead_unit3_production_raw' or
                $perk->key === 'undead_unit3_production_raw_from_crypt' or
                $perk->key === 'undead_unit4_production_raw'
            )
            {
                $slotProduced = (int)$perkValue[0];
                $amountProduced = (float)$perkValue[1];
                $slotProducing = (int)$perkValue[2];
                $race = Race::where('name', 'Undead')->first();

                $unitProduced = $race->units->filter(static function ($unit) use ($slotProduced)
                    {
                        return ($unit->slot === $slotProduced);
                    })->first();

                $unitProducing = $race->units->filter(static function ($unit) use ($slotProducing)
                    {
                        return ($unit->slot === $slotProducing);
                    })->first();

                $perkValue = [str_plural($unitProduced->name, $amountProduced), floatval($amountProduced), $unitProducing->name];
            }

            if($perk->key == 'wizards_count_as_spies')
            {
                $perkValue = [$perkValue, str_plural('spy', $perkValue)];
            }

            if($perk->key == 'range_multiplier')
            {
                $perkValue = number_format($perkValue, 2);
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
                    #$perkValue = $perkValue > 0 ? '+' . $perkValue : $perkValue;
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
        $decrees = Decree::all()->where('enabled',1)->sortBy('name');

        foreach($decrees as $id => $decree)
        {
            if(!$this->isDecreeAvailableToRace($race, $decree))
            {
                $decrees->forget($id);
            }
        }

        return $decrees;
    }

    public function isDecreeAvailableToRace(Race $race, Decree $decree): bool
    {
        $isAvailable = true;

        #dd($decree->exclusive_races, $decree->excluded_races);

        if(count($decree->exclusive_races) > 0 and !in_array($race->name, $decree->exclusive_races))
        {
            return false;
        }

        if(count($decree->excluded_races) > 0 and in_array($race->name, $decree->excluded_races))
        {
            return false;
        }

        return $isAvailable;
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
        foreach($dominion->decreeStates as $decreeState)
        {
            if($decreeState->decree_id == $decree->id)
            {
                return true;
            }
        }
        return false;
    }

    public function getDominionDecreeState(Dominion $dominion, Decree $decree): DominionDecreeState
    {
        return DominionDecreeState::where('dominion_id', $dominion->id)->where('decree_id', $decree->id)->first();
        #return $dominion->decreeStates->where('decree_id', $decree->id)->first();
    }

}
