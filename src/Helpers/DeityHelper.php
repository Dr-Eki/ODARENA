<?php

namespace OpenDominion\Helpers;

use Illuminate\Support\Collection;
use OpenDominion\Models\Race;
use OpenDominion\Models\Deity;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\DominionDeity;
use OpenDominion\Models\Spell;

class DeityHelper
{

    public function getDeityPerksString(Deity $deity, DominionDeity $dominionDeity = null): array
    {
        $effectStrings = [];

        $deityEffects = [

            // Production / Resources
            'ore_production_mod' => '%+g%% ore production',
            'mana_production_mod' => '%+g%% mana production',
            'lumber_production_mod' => '%+g%% lumber production',
            'food_production_mod' => '%+g%% food production',
            'gems_production_mod' => '%+g%% gem production',
            'gold_production_mod' => '%+g%% gold production',
            'boat_production_mod' => '%+g%% boat production',
            'xp_generation_mod' => '%+g%% XP generation',

            'no_gold_production' => 'No gold production or revenue',
            'no_ore_production' => 'No ore production',
            'no_lumber_production' => 'No lumber production',
            'no_mana_production' => 'No mana production',
            'no_food_production' => 'No food production',
            'no_boat_production' => 'No boat production',
            'no_gems_production' => 'No gem production',

            'food_consumption_mod' => '%+g%% food consumption',

            'exchange_rate' => '%+g%% exchange rates',

            'xp_gains' => '%+g%% XP per acre gained',

            // Military
            'drafting' => '%+g%% drafting',
            'training_time_raw' => '%s ticks training time for military units (does not include Spies, Wizards, or Archmages)',
            'training_costs' => '%+g%% military unit training costs',
            'unit_gold_costs' => '%+g%% military unit gold costs',
            'unit_ore_costs' => '%+g%% military unit ore costs',
            'unit_lumber_costs' => '%+g%% military unit lumber costs',

            'faster_return' => 'Units return %s ticks faster from invasions',

            'can_kill_immortal' => 'Can kill some immortal units.',

            'unit_gold_costs' => '%+g%% unit gold costs.',
            'unit_ore_costs' => '%+g%% unit ore costs.',
            'unit_lumber_costs' => '%+g%% unit lumber costs.',
            'unit_mana_costs' => '%+g%% unit mana costs.',
            'unit_blood_costs' => '%+g%% unit blood costs.',
            'unit_food_costs' => '%+g%% unit food costs.',

            'prestige_gains' => '%+g%% prestige gains.',

            'cannot_send_expeditions' => 'Cannot send expeditions',

            // Population
            'population_growth' => '%+g%% population growth rate',
            'max_population' => '%+g%% population',
            'unit_specific_housing' => '%+g%% unit specific housing',

            // Magic
            'damage_from_spells' => '%+g%% damage from spells',
            'chance_to_reflect_spells' => '%+g%% chance to reflect spells',
            'reveal_ops' => 'Reveals the dominion casting deitys or spying on you',

            'wizard_strength' => '%+g%% wizard strength',
            'wizard_cost' => '%+g%% wizard cost',

            // Espionage
            'spy_strength' => '%+g%% spy strength',
            'immortal_spies' => 'Spies become immortal',
            'spy_strength_recovery' => '%+g%% spy strength recovery per tick',

            'gold_theft' => '%+g%% gold lost to theft.',
            'mana_theft' => '%+g%% mana lost to theft.',
            'lumber_theft' => '%+g%% lumber lost to theft.',
            'ore_theft' => '%+g%% ore lost to theft.',
            'gems_theft' => '%+g%% gems lost to theft.',
            'all_theft' => '%+g%% resources lost to theft',

            'gold_stolen' => '%+g%% gold theft.',
            'mana_stolen' => '%+g%% mana theft.',
            'lumber_stolen' => '%+g%% lumber theft.',
            'ore_stolen' => '%+g%% ore  theft.',
            'gems_stolen' => '%+g%% gem theft.',
            'amount_stolen' => '%+g%% resource theft',

            // Casualties
            'casualties' => '%+g%% casualties',
            'offensive_casualties' => '%+g%% casualties suffered when invading',
            'defensive_casualties' => '%+g%% casualties suffered when defending',

            'increases_enemy_casualties' => '%+g%% enemy casualties',
            'increases_enemy_casualties_on_defense' => '%+g%% enemy casualties when defending',
            'increases_enemy_casualties_on_offense' => '%+g%% enemy casualties when invading',

            // OP/DP
            'offensive_power' => '%+g%% offensive power',
            'defensive_power' => '%+g%% defensive power',

            'offensive_power_on_retaliation' => '%+g%% offensive power if target recently invaded your realm',

            'offensive_power_vs_no_deity' => '%+g%% offensive power vs dominions without a deity',
            'defensive_power_vs_no_deity' => '%+g%% defensive power vs dominions without a deity',

            'target_defensive_power_mod' => '%+g%% defensive modifier for target',

            // Improvements
            'improvements' => '%+g%% improvements',
            'improvement_points' => '%+g%% improvement points',
            'improvements_interest' => '%+g%% improvements interest',

            // Land and Construction
            'land_discovered' => '%+g%% land discovered on successful invasions',
            'construction_cost' => '%+g%% construction costs',
            'rezone_cost' => '%+g%% rezoning costs',
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

    public function getDeitySpells(Deity $deity)
    {
        return Spell::all()->where('enabled',1)->where('deity_id', $deity->id);
    }
}
