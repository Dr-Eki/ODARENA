<?php

namespace OpenDominion\Helpers;

use Illuminate\Support\Collection;
use OpenDominion\Models\Race;
use OpenDominion\Models\Artefact;
use OpenDominion\Models\Realm;
use OpenDominion\Models\RealmArtefact;

class ArtefactHelper
{

    public function getArtefactPerksString(Artefact $artefact, RealmArtefact $realmArtefact = null): array
    {
        $effectStrings = [];

        $artefactEffects = [

            // Production / Resources
            'ore_production_mod' => '%+d%% ore production',
            'mana_production_mod' => '%+d%% mana production',
            'lumber_production_mod' => '%+d%% lumber production',
            'food_production_mod' => '%+d%% food production',
            'gems_production_mod' => '%+d%% gem production',
            'gold_production_mod' => '%+d%% gold production',
            'boat_production_mod' => '%+d%% boat production',
            'xp_generation_mod' => '%+d%% XP generation',
            'kelp_production_mod' => '%+d%% kelp production',
            'pearls_production_mod' => '%+d%% pearls production',

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
            'drafting' => '+%+d%% drafting',
            'training_time_raw' => '%s ticks training time for military units (does not include Spies, Wizards, or Archmages)',
            'training_costs' => '+%+d%% military unit training costs',
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

            'ship_unit_costs' => '%+d%% costs for ship units.',
            'machine_unit_costs' => '%+d%% costs for ship units.',

            'prestige_gains' => '%+d%% prestige gains.',
            'prestige_gains_on_retaliation' => '%+d%% prestige gains on retaliation.',

            'cannot_send_expeditions' => 'Cannot send expeditions.',

            'reduced_conversions' => '%+d%% conversions.',

            'crypt_decay' => '%+d%% crypt decay.',

            // Population
            'population_growth' => '%+d%% population growth rate',
            'max_population' => '%+d%% population',

            // Magic
            'damage_from_spells' => '%+d%% damage from spells',
            'chance_to_reflect_spells' => '%+d%% chance to reflect spells',
            'reveal_ops' => 'Reveals the dominion casting artefacts or spying on you',

            'wizard_strength' => '%+d%% wizard strength',
            'wizard_cost' => '%+d%% wizard cost',

            'base_wizard_strength' => '%+d%% base wizard strength.',

            'sorcery_spell_duration' => '%+d%% sorcery spell duration.',
        
            'fog_duration_raw' => '%+g ticks Fog duration.',

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


            'increases_enemy_draftee_casualties' => '%+d%% enemy draftee casualties',

            // OP/DP
            'offensive_power' => '%+d%% offensive power',
            'defensive_power' => '%+d%% defensive power',

            'governor_offensive_power' => '%+d%% offensive power for realm governor.',

            'offensive_power_on_retaliation' => '%+d%% offensive power if target recently invaded your realm',

            'target_defensive_power_mod' => '%+d%% defensive modifier for target',

            // Improvements
            'improvements' => '%+d%% improvements',
            'improvement_points' => '%+d%% improvement points',

            'lumber_improvement_points' => '%+d%% lumber improvement points',
            'gems_improvement_points' => '%+d%% lumber improvement points',
            'ore_improvement_points' => '%+d%% lumber improvement points',

            // Land and Construction
            'land_discovered' => '%+d%% land discovered on successful invasions',
            'construction_cost' => '%+d%% construction costs',
            'water_construction_cost' => '%+d%% construction costs on water',
            'rezone_cost' => '%+d%% rezoning costs',
            'cannot_explore' => 'Cannot explore',

            'conquered_land_rezoned_to_water' => '%+d%% conquered land rezoned to water',

            // Special, one-off

            'water_buildings_effect' => '%+d%% effect from water buildings (all perks and housing)',
        ];

        foreach ($artefact->perks as $perk)
        {
            if($realmArtefact)
            {
                $realm = Realm::findorfail($realmArtefact->realm_id);

                $perkValue = $realm->getArtefactPerkValue($perk->key);
            }
            else
            {
                $perkValue = $perk->pivot->value;
            }

            if($perkValue > 0)
            {
                $perkValue = '+' . $perkValue;
            }

            $perkValue = str_replace('_', ' ',ucwords($perkValue));
            $effectStrings[] = sprintf($artefactEffects[$perk->key], $perkValue);
        }

        return $effectStrings;
    }

    public function getArtefactHelpString(Artefact $artefact): string
    {
        $helpString = '<ul>';

        foreach($this->getArtefactPerksString($artefact) as $effect)
        {
            $helpString .= '<li>' . ucfirst($effect) . '</li>';
        }

        $helpString .= '</ul>';

        $helpString .= $this->getExclusivityString($artefact);

        return $helpString;
    }

    public function getExclusivityString(Artefact $artefact): string
    {

        $exclusivityString = '<br><small class="text-muted">';

        if($exclusives = count($artefact->exclusive_races))
        {
            foreach($artefact->exclusive_races as $raceName)
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
        elseif($excludes = count($artefact->excluded_races))
        {
            $exclusivityString .= 'All except ';
            foreach($artefact->excluded_races as $raceName)
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
            $exclusivityString .= 'All';
        }

        $exclusivityString .= '</small>';

        return $exclusivityString;

    }

}
