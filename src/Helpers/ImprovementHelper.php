<?php

namespace OpenDominion\Helpers;
use Illuminate\Support\Collection;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;

use OpenDominion\Models\Improvement;
use OpenDominion\Models\ImprovementPerk;

class ImprovementHelper
{

    # IMPROVEMENTS 2.0

    public function getImprovementPerkDescription(string $improvementPerk): string
    {

        $improvementPerkDescriptions = [
            'gold_production_mod' => 'gold production',
            'ore_production_mod' => 'ore production',
            'lumber_production_mod' => 'lumber production',
            'gems_production_mod' => 'gem production',
            'mana_production_mod' => 'mana production',
            'food_production_mod' => 'food production',
            'horse_production_mod' => 'horse taming',
            'blood_production_mod' => 'horse taming',
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

            'population' => 'population',
            'population_growth' => 'population growth',
            'unit_specific_housing' => 'unit specific housing',

            'jobs_per_building' => 'jobs per building',

            'construction_cost' => 'construction costs',
            'rezone_cost' => 'rezoning costs',
            'construction_time' => 'construction time',

            'explore_gold_cost' => 'exploration gold costs',
            'land_discovered' => 'land discovered',

            'unit_gold_costs' => 'unit gold costs',
            'unit_ore_costs' => 'unit ore costs',
            'unit_lumber_costs' => 'unit lumber costs',
            'unit_gem_costs' => 'unit gem costs',
            'unit_mana_costs' => 'unit mana costs',
            'unit_food_costs' => 'unit food costs',

            'training_time_mod' => 'unit training time',
            'chance_of_instant_return' => 'chance of units returning instantly from invasion',

            'offensive_power' => 'offensive power',
            'defensive_power' => 'defensive power',

            'casualties' => 'own casualties',
            'casualties_on_offense' => 'own offensive casualties',
            'casualties_on_defense' => 'own defensive casualties',
            'increases_enemy_casualties' => 'enemy casualties',

            'target_defensive_power_mod' => 'defensive modifiers for target',

            'prestige_gains' => 'prestige gains',
            'base_morale' => 'morale',

            'spy_strength' => 'spy strength',
            'spy_losses' => 'spy losses',
            'spy_housing' => 'spy units housing',

            'wizard_strength' => 'wizard strength',
            'wizard_losses' => 'wizard losses',
            'spell_damage' => 'spell damage',
            'wizard_housing' => 'wizard units housing',

            'gold_theft' => 'gold theft',
            'ore_theft' => 'ore theft',
            'lumber_theft' => 'lumber theft',
            'gems_theft' => 'gem theft',
            'mana_theft' => 'mana theft',
            'food_theft' => 'food theft',
            'horse_theft' => 'horse theft',

            'theft_protection' => 'theft protection (affects buildings that reduce theft, otherwise no effect)',
            'amount_stolen' => 'amount stolen',

            'unit_pairing' => 'unit limit pairing',

            'attrition_protection' => 'attrition protection',

            'improvement_points' => 'improvement points value from investments',

            'title_bonus' => 'ruler title bonus',

            'expedition_land_gains' => 'expedition land gains',

        ];

        return isset($improvementPerkDescriptions[$improvementPerk]) ? $improvementPerkDescriptions[$improvementPerk] : 'Missing description';
    }

    /*
    *   Returns buildings available for the race.
    *   If $landType is present, only return buildings for the race for that land type.
    */
    public function getImprovementsByRace(Race $race): Collection
    {
        $improvements = collect(Improvement::all()->keyBy('key')->sortBy('name')->where('enabled',1));

        foreach($improvements as $improvement)
        {
          if(
                (count($improvement->excluded_races) > 0 and in_array($race->name, $improvement->excluded_races)) or
                (count($improvement->exclusive_races) > 0 and !in_array($race->name, $improvement->exclusive_races))
            )
          {
              $improvements->forget($improvement->key);
          }
        }

        return $improvements;
    }

    public function getImprovementKeys(): array
    {
        return Improvement::where('enabled',1)->get('key')->all();
    }

    public function extractImprovementPerkValuesForScribes(string $perkValue)
    {
        if (str_contains($perkValue, ','))
        {
            $perkValues = explode(',', $perkValue);

            foreach($perkValues as $key => $value)
            {
                if (!str_contains($value, ';'))
                {
                    continue;
                }

                $perkValues[$key] = explode(';', $value);
            }
        }

        return $perkValues;
    }

    public function getExclusivityString(Improvement $improvement): string
    {

        $exclusivityString = '<br><small class="text-muted">';

        if($exclusives = count($improvement->exclusive_races))
        {
            foreach($improvement->exclusive_races as $raceName)
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
        elseif($excludes = count($improvement->excluded_races))
        {
            $exclusivityString .= 'All except ';
            foreach($improvement->excluded_races as $raceName)
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
