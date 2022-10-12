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
            'max_population' => '%+g%% population.',
            'population_growth' => '%+g%% population growth rate.',
            'drafting' => '%+g%% drafting.',
            'military_housing' => '%+g%% military housing.',

            # Production
            'gold_production_mod' => '%+g%% gold production.',
            'food_production_mod' => '%+g%% food production.',
            'lumber_production_mod' => '%+g%% lumber production.',
            'ore_production_mod' => '%+g%% ore production.',
            'gems_production_mod' => '%+g%% gem production.',
            'mana_production_mod' => '%+g%% mana production.',
            'pearls_production_mod' => '%+g%% pearl production.',
            'blood_production_mod' => '%+g%% blood production.',
            'horse_production_mod' => '%+g%% horse production.',
            'mud_production_mod' => '%+g%% mud production.',
            'swamp_gas_production_mod' => '%+g%% swamp gas production.',
            'magma_production_mod' => '%+g%% magma production.',
            'obsidian_production_mod' => '%+g%% obsidian production.',

            'xp_generation_mod' => '%+g%% XP generation.',
            'xp_gains' => '%+g%% XP gains.',

            'building_gold_mine_production_mod' => '%+g%% production from Gold Mines.',
            'building_gold_quarry_production_mod' => '%+g%% production from Gold Quarries.',

            'exchange_rate' => '%+g%% exchange rates.',

            'food_consumption_mod' => '%+g%% food consumption.',

            # Deity
            'deity_power' => '%+g%% deity perks.',

            'range_multiplier' => '%+gx range multiplier.',

            # Military
            'offensive_casualties' => '%+g%% casualties on offense.',
            'defensive_casualties' => '%+g%% casualties on defense.',
            
            'defensive_power_from_peasants' => '%g raw defensive power per peasant',

            'target_defensive_power_mod' => '%+g%% defensive modifier for target.',

            'increases_enemy_casualties' => '%+g%% enemy casualties.',
            'increases_enemy_casualties_on_defense' => '%+g%% enemy casualties on defense.',
            'increases_enemy_casualties_on_offense' => '%+g%% enemy casualties on offense.',

            'unit_costs' => '%+g%% unit costs.',
            'unit_gold_costs' => '%+g%% unit gold costs.',
            'unit_ore_costs' => '%+g%% unit ore costs.',
            'unit_lumber_costs' => '%+g%% unit lumber costs.',
            'unit_mana_costs' => '%+g%% unit mana costs.',
            'unit_blood_costs' => '%+g%% unit blood costs.',
            'unit_food_costs' => '%+g%% unit food costs.',

            'unit_gold_costs_from_wizard_ratio' => '%+g%% unit gold costs per 1 WPA.',
            'unit_lumber_costs_from_wizard_ratio' => '%+g%% unit lumber costs per 1 WPA.',

            'extra_units_trained' => '%+g additional units trained for free.',

            'morale_gains' => '%+g%% morale gains.',
            'base_morale' => '%+g base morale.',
            'prestige_gains' => '%+g%% prestige gains.',

            'land_discovered' => '%+g%% land discovered during invasions.',

            'reduces_attrition' => '%+g%% unit attrition.',

            'reduces_conversions' => '%+g%% conversions for enemies.',

            'training_time_mod' => '%+g%% training time.',

            'unit_pairing' => '%+g%% unit pairing capacity.',

            'unit_send_capacity_mod' => '%+g%% unit send capacity.',

            'undead_unit1_production_raw' => 'Each %3$s raises %2$s %1$s per tick.',
            'undead_unit2_production_raw' => 'Each %3$s raises %2$s %1$s per tick.',
            'undead_unit3_production_raw' => 'Each %3$s raises %2$s %1$s per tick.',
            'undead_unit4_production_raw' => 'Each %3$s raises %2$s %1$s per tick.',
            'undead_unit3_production_raw_from_crypt' => 'Each %3$s raises %2$s %1$s per tick from the crypt.',
            
            'attrition_mod' => '%+g%% attrition.',

            # OP/DP
            'offensive_power' => '%+g%% offensive power.',
            'defensive_power' => '%+g%% defensive power.',

            'can_send_cannonballs' => 'Can fire cannonballs.',
            'can_send_ammunition_units' => 'Can launch ammunition units.',

            # Improvements
            'improvements' => '%+g%% improvements.',
            'improvement_points' => '%+g%% improvement points when investing.',

            # Construction and Rezoning
            'construction_cost' => '%+g%% construction costs.',
            'rezone_cost' => '%+g%% rezoning costs.',

            'construction_cost_from_wizard_ratio' => '%+g%% construction costs per 1 WPA.',
            'construction_time_from_wizard_ratio' => '%+g%% construction time per 1 WPA.',

            # Espionage and Wizardry
            'spy_losses' => '%+g%% spy losses.',
            'spell_damage' => '%+g%% spell damage.',
            'spell_damage_dealt' => '%+g%% spell damage per 1 WPA.',
            'sorcery_damage_dealt_from_wizard_ratio' => '%+g%% spell damage per 1 WPA.',
            'spy_cost' => '%+g%% spy costs.',
            'wizard_cost' => '%+g%% wizard costs.',
            'spell_cost' => '%+g%% spell costs.',
            'spell_cost_from_wizard_ratio' => '%+g%% spell costs per 1 WPA.',
            'sorcery_cost_from_wizard_ratio' => '%+g%% sorcery costs per 1 WPA.',

            'gold_theft_reduction' => '%+g%% gold stolen from you.',
            'gems_theft_reduction' => '%+g%% gems stolen from you.',
            'ore_theft_reduction' => '%+g%% ore stolen from you.',
            'lumber_theft_reduction' => '%+g%% lumber stolen from you.',
            'food_theft_reduction' => '%+g%% food stolen from you.',
            'mana_theft_reduction' => '%+g%% mana stolen from you.',
            'horse_theft_reduction' => '%+g%% horses stolen from you.',

            'wizard_strength_recovery' => '%+g%% wizard strength recovery per tick.',
            'spy_strength_recovery' => '%+g%% wizard strength recovery per tick.',
            
            'spy_strength' => '%+g%% spy strength.',
            'spy_strength_on_defense' => '%+g%% spy strength on defense.',
            'spy_strength_on_offense' => '%+g%% spy strength on offense.',

            'wizard_strength' => '%+g%% wizard strength.',
            'wizard_strength_on_defense' => '%+g%% wizard strength on defense.',
            'wizard_strength_on_offense' => '%+g%% wizard strength on offense.',

            'wizards_count_as_spies' => 'Wizards also count as %+g %+g.',

            # Growth specific
            'generate_building' => 'Generate %+g.',
            'generate_building_plain' => 'Generate %+g on plains',
            'generate_building_mountain' => 'Generate %+g in mountains',
            'generate_building_hill' => 'Generate %+g on hills',
            'generate_building_swamp' => 'Generate %+g in swamps',
            'generate_building_water' => 'Generate %+g in water',
            'generate_building_forest' => 'Generate %+g in the forest',

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
