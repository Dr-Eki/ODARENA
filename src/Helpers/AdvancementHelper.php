<?php

namespace OpenDominion\Helpers;

use Illuminate\Support\Collection;
use OpenDominion\Models\Race;
use OpenDominion\Models\Advancement;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\DominionAdvancement;

class AdvancementHelper
{

    # IMPROVEMENTS 2.0

    public function getAdvancementPerkDescription(string $advancementPerk): string
    {

        $advancementPerkDescriptions = [
            // Production and Resources
            'gold_production_mod' => 'gold production',
            'ore_production_mod' => 'ore production',
            'lumber_production_mod' => 'lumber production',
            'gems_production_mod' => 'gem production',
            'mana_production_mod' => 'mana production',
            'food_production_mod' => 'food production',
            'horse_production_mod' => 'horse taming',
            'blood_production_mod' => 'blood gathering',
            'pearls_production_mod' => 'pearl production',
            'marshling_production_mod' => 'marshling spawning',
            'miasma_production_mod' => 'miasma extraction',
            'sapling_production_mod' => 'sapling growth',
            'thunderstone_production_mod' => 'thunderstone discovery',

            'food_consumption_mod' => 'food consumption',

            'xp_generation_mod' => 'XP generation',

            'exchange_rate' => 'exchange rate',
            'resource_conversion' => 'resource conversion (passive and automatic conversion, not exchanged resources)',

            'xp_gains' => 'XP gained',
            'advancement_costs' => 'advancements costs',

            // Population and Housing
            'max_population' => 'population',
            'population_growth' => 'population growth',
            'military_housing' => 'military housing (not for unit-specific housing)',
            'unit_specific_housing' => 'unit specific housing',
            'base_morale' => 'morale',
            'jobs_per_building' => 'jobs per building',

            // Improvements
            'improvements' => 'improvements',
            'improvement_points' => 'improvement points',

            'lumber_improvement_points' => 'lumber improvement points',
            'gems_improvement_points' => 'gems improvement points',
            'ore_improvement_points' => 'ore improvement points',
            'gold_improvement_points' => 'gold improvement points',
            'miasma_improvement_points' => 'miasma improvement points',
            'pearls_improvement_points' => 'pearls improvement points',
            'blood_improvement_points' => 'blood improvement points',
            'mana_improvement_points' => 'mana improvement points',
            'food_improvement_points' => 'food improvement points',
            'stones_improvement_points' => 'stones improvement points',

            // Military and Training
            'offensive_power' => 'offensive power',
            'defensive_power' => 'defensive power',

            'drafting' => 'drafting',
            'training_time_raw' => 'ticks training time for military units (does not include Spies, Wizards, or Archmages)',
            'training_costs' => 'military unit training costs',
            'unit_gold_costs' => 'unit gold costs',
            'unit_ore_costs' => 'unit ore costs',
            'unit_lumber_costs' => 'unit lumber costs',
            'unit_mana_costs' => 'unit mana costs',
            'unit_blood_costs' => 'unit blood costs',
            'unit_food_costs' => 'unit food costs',

            'prestige_gains' => 'prestige gains',

            'unit_pairing' => 'unit limit pairing',
            'unit_send_capacity_mod' => 'unit sending capacity',

            // Land and Construction
            'land_discovered' => 'land discovered on successful invasions',
            'construction_cost' => 'construction costs',
            'construction_time' => 'construction time',
            'rezone_cost' => 'rezoning costs',

            'explore_gold_cost' => 'exploration gold costs',
            'land_discovered' => 'land discovered',

            'training_time_mod' => 'unit training time',
            'chance_of_instant_return' => 'chance of units returning instantly from invasion',

            // Casualties
            'casualties' => 'own casualties',
            'casualties_on_offense' => 'own offensive casualties',
            'casualties_on_defense' => 'own defensive casualties',
            'increases_enemy_casualties' => 'enemy casualties',

            'target_defensive_power_mod' => 'defensive modifiers for target',

            // Espionage
            'spy_costs' => 'spy costs',
            'spy_housing' => 'spy units housing',
            'spy_losses' => 'spy losses',
            'spy_strength' => 'spy strength',
            'spy_strength_recovery' => 'spy strength recovery',

            'amount_stolen' => 'amount stolen',
            'theft_protection' => 'theft protection (affects buildings that reduce theft, otherwise no effect)',

            'gold_theft' => 'gold theft',
            'ore_theft' => 'ore theft',
            'lumber_theft' => 'lumber theft',
            'gems_theft' => 'gem theft',
            'mana_theft' => 'mana theft',
            'food_theft' => 'food theft',
            'horse_theft' => 'horse theft',

            // Wizardry
            'wizard_costs' => 'wizard costs',
            'wizard_housing' => 'wizard units housing',
            'wizard_losses' => 'wizard losses',
            'wizard_strength' => 'wizard strength',
            'wizard_strength_recovery' => 'wizard strength recovery',
            'spell_damage' => 'spell_damage',
            'spell_costs' => 'spell costs',

            // Other, Special, One-Off
            'research_slots' => 'progress towards one extra research slot',
            'land_per_trade_slot' => 'land required per trade route slot',

        ];

        return isset($advancementPerkDescriptions[$advancementPerk]) ? $advancementPerkDescriptions[$advancementPerk] : 'Missing description';
    }


    public function getAdvancementsByRace(Race $race): Collection
    {
        $advancements = collect(Advancement::all()->keyBy('key')->sortBy('name')->where('enabled',1));

        foreach($advancements as $advancement)
        {
          if(
                (count($advancement->excluded_races) > 0 and in_array($race->name, $advancement->excluded_races)) or
                (count($advancement->exclusive_races) > 0 and !in_array($race->name, $advancement->exclusive_races))
            )
          {
              $advancements->forget($advancement->key);
          }
        }

        return $advancements;
    }

    public function getExclusivityString(Advancement $advancement): string
    {

        $exclusivityString = '<br><small class="text-muted">';

        if($exclusives = count($advancement->exclusive_races))
        {
            foreach($advancement->exclusive_races as $raceName)
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
        elseif($excludes = count($advancement->excluded_races))
        {
            $exclusivityString .= 'All except ';
            foreach($advancement->excluded_races as $raceName)
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

    public function hasExclusivity(Advancement $advancement): bool
    {
        return (count($advancement->exclusive_races) or count($advancement->excluded_races));
    }

    public function extractAdvancementPerkValuesForScribes(string $perkValue)
    {
        return $perkValue;
    }

}
