<?php

namespace OpenDominion\Helpers;

use Illuminate\Support\Collection;
use OpenDominion\Models\Race;
use OpenDominion\Models\Deity;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\DominionDeity;

class DeityHelper
{

    public function getDeityPerksString(Deity $deity, DominionDeity $dominionDeity = null): array
    {
        $effectStrings = [];

        $deityEffects = [

            // Production / Resources
            'ore_production_mod' => '%+d%% ore production',
            'mana_production_mod' => '%+d%% mana production',
            'lumber_production_mod' => '%+d%% lumber production',
            'food_production_mod' => '%+d%% food production',
            'gems_production_mod' => '%+d%% gem production',
            'gold_production_mod' => '%+d%% gold production',
            'boat_production_mod' => '%+d%% boat production',
            'xp_generation_mod' => '%+d%% XP generation',

            'no_gold_production' => 'No gold production or revenue',
            'no_ore_production' => 'No ore production',
            'no_lumber_production' => 'No lumber production',
            'no_mana_production' => 'No mana production',
            'no_food_production' => 'No food production',
            'no_boat_production' => 'No boat production',
            'no_gems_production' => 'No gem production',

            'food_consumption_mod' => '%+d%% food consumption',

            'exchange_rate' => '%+d%% exchange rates',

            'xp_gains' => '%+d%% XP per acre gained',

            // Military
            'drafting' => '%+d%% drafting',
            'training_time_raw' => '%s ticks training time for military units (does not include Spies, Wizards, or Archmages)',
            'training_costs' => '%+d%% military unit training costs',
            'unit_gold_costs' => '%+d%% military unit gold costs',
            'unit_ore_costs' => '%+d%% military unit ore costs',
            'unit_lumber_costs' => '%+d%% military unit lumber costs',

            'faster_return' => 'Units return %s ticks faster from invasions',

            'can_kill_immortal' => 'Can kill some immortal units.',

            'unit_gold_costs' => '%+d%% unit gold costs.',
            'unit_ore_costs' => '%+d%% unit ore costs.',
            'unit_lumber_costs' => '%+d%% unit lumber costs.',
            'unit_mana_costs' => '%+d%% unit mana costs.',
            'unit_blood_costs' => '%+d%% unit blood costs.',
            'unit_food_costs' => '%+d%% unit food costs.',

            'prestige_gains' => '%+d%% prestige gains.',

            'cannot_send_expeditions' => 'Cannot send expeditions',

            // Population
            'population_growth' => '%+d%% population growth rate',
            'max_population' => '%+d%% population',
            'unit_specific_housing' => '%+d%% unit specific housing',

            // Magic
            'damage_from_spells' => '%+d%% damage from spells',
            'chance_to_reflect_spells' => '%+d%% chance to reflect spells',
            'reveal_ops' => 'Reveals the dominion casting deitys or spying on you',

            'wizard_strength' => '%+d%% wizard strength',
            'wizard_cost' => '%+d%% wizard cost',

            // Espionage
            'spy_strength' => '%+d%% spy strength',
            'immortal_spies' => 'Spies become immortal',
            'spy_strength_recovery' => '%+d%% spy strength recovery per tick',

            'gold_theft' => '%+d%% gold lost to theft.',
            'mana_theft' => '%+d%% mana lost to theft.',
            'lumber_theft' => '%+d%% lumber lost to theft.',
            'ore_theft' => '%+d%% ore lost to theft.',
            'gems_theft' => '%+d%% gems lost to theft.',
            'all_theft' => '%+d%% resources lost to theft',

            'gold_stolen' => '%+d%% gold theft.',
            'mana_stolen' => '%+d%% mana theft.',
            'lumber_stolen' => '%+d%% lumber theft.',
            'ore_stolen' => '%+d%% ore  theft.',
            'gems_stolen' => '%+d%% gem theft.',
            'amount_stolen' => '%+d%% resource theft',

            // Casualties
            'casualties' => '%+d%% casualties',
            'offensive_casualties' => '%+d%% casualties suffered when invading',
            'defensive_casualties' => '%+d%% casualties suffered when defending',

            'increases_enemy_casualties' => '%+d%% enemy casualties',
            'increases_enemy_casualties_on_defense' => '%+d%% enemy casualties when defending',
            'increases_enemy_casualties_on_offense' => '%+d%% enemy casualties when invading',

            // OP/DP
            'offensive_power' => '%+d%% offensive power',
            'defensive_power' => '%+d%% defensive power',

            'offensive_power_on_retaliation' => '%+d%% offensive power if target recently invaded your realm',

            'offensive_power_vs_no_deity' => '%+d%% offensive power vs dominions without a deity',
            'defensive_power_vs_no_deity' => '%+d%% defensive power vs dominions without a deity',

            'target_defensive_power_mod' => '%+d%% defensive modifier for target',

            // Improvements
            'improvements' => '%+d%% improvements',
            'improvement_points' => '%+d%% improvement points',
            'improvements_interest' => '%+d%% improvements interest',

            // Land and Construction
            'land_discovered' => '%+d%% land discovered on successful invasions',
            'construction_cost' => '%+d%% construction costs',
            'rezone_cost' => '%+d%% rezoning costs',
            'cannot_explore' => 'Cannot explore',
        ];

        foreach ($deity->perks as $perk)
        {
            if($dominionDeity)
            {
                $dominion = Dominion::findorfail($dominionDeity->dominion_id);

                $perkValue = $dominion->getDeityPerkValue($perk->key);
            }
            else
            {
                $perkValue = $perk->pivot->value;
            }

            $perkValue = str_replace('_', ' ',ucwords($perkValue));
            $perkValue = $perkValue > 0 ? '+' . display_number_format($perkValue, 4) : display_number_format($perkValue, 4);

            $effectStrings[] = sprintf($deityEffects[$perk->key], $perkValue);
        }

        return $effectStrings;
    }

    public function getDeitiesByRace(Race $race): Collection
    {
        $deities = collect(Deity::all()->keyBy('key')->sortBy('name')->where('enabled',1));

        foreach($deities as $deity)
        {
          if(
                (count($deity->excluded_races) > 0 and in_array($race->name, $deity->excluded_races)) or
                (count($deity->exclusive_races) > 0 and !in_array($race->name, $deity->exclusive_races))
            )
          {
              $deities->forget($deity->key);
          }
        }

        return $deities;
    }

    public function getExclusivityString(Deity $deity): string
    {

        $exclusivityString = '<br><small class="text-muted">';

        if($exclusives = count($deity->exclusive_races))
        {
            foreach($deity->exclusive_races as $raceName)
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
        elseif($excludes = count($deity->excluded_races))
        {
            $exclusivityString .= 'All except ';
            foreach($deity->excluded_races as $raceName)
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

}
