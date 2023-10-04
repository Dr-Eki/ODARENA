<?php

namespace OpenDominion\Helpers;

class TerrainHelper
{
    # For Race Terrain Perks:
    public function getPerkDescription(string $perkKey, float $perkValue, bool $showSecondHalf = true)
    {
        if(!in_array(substr($perkKey, -4), ['_mod', '_raw']))
        {
            throw new \Exception('Invalid perk key: ' . $perkKey);
        }

        $perks =
        [
            # Military and Units
            'offensive_power_mod' => ['%+g%% offensive power', ' for every 1%% of this terrain.'],
            'defensive_power_mod' => ['%+g%% defensive power', ' for every 1%% of this terrain.'],
            'casualties_mod' => ['%+g%% casualties', ' for every 1%% of this terrain.'],
            'casualties_on_defense_mod' => ['%+g%% casualties on defense', ' for every 1%% of this terrain.'],
            'casualties_on_offense_mod' => ['%+g%% casualties on offense', ' for every 1%% of this terrain.'],
            'enemy_casualties_mod' => ['%+g%% enemy casualties', ' for every 1%% of this terrain.'],
            'enemy_casualties_on_defense_mod' => ['%+g%% enemy casualties on defense', ' for every 1%% of this terrain.'],
            'enemy_casualties_on_offense_mod' => ['%+g%% enemy casualties on offense', ' for every 1%% of this terrain.'],

            'attrition_mod' => ['%+g%% unit attrition', ' for every 1%% of this terrain.'],

            # Population and Housing
            'unit_specific_housing_mod' => ['%+g%% unit specific housing', ' for every 1%% of this terrain.'],
            'military_housing_mod' => ['%+g%% military housing', ' for every 1%% of this terrain.'],
            'population_mod' => ['%+g%% population', ' for every 1%% of this terrain.'],
            'population_growth_mod' => ['%+g%% population growth rate', ' for every 1%% of this terrain.'],

            # Resources and Production
            'gold_production_raw' => ['%s gold/tick', ' per acre of this terrain.'],
            'food_production_raw' => ['%s food/tick', ' per acre of this terrain.'],
            'ore_production_raw' => ['%s ore/tick', ' per acre of this terrain.'],
            'lumber_production_raw' => ['%s lumber/tick', ' per acre of this terrain.'],
            'mana_production_raw' => ['%s mana/tick', ' per acre of this terrain.'],
            'gems_production_raw' => ['%s gems/tick', ' per acre of this terrain.'],
            'horse_production_raw' => ['%s horses/tick', ' per acre of this terrain.'],
            'mud_production_raw' => ['%s mud/tick', ' per acre of this terrain.'],
            'swamp_gas_production_raw' => ['%s swamp gas/tick', ' per acre of this terrain.'],
            'marshling_production_raw' => ['%s marshlings/tick', ' per acre of this terrain.'],
            'yak_production_raw' => ['%s yaks/tick', ' per acre of this terrain.'],
            'magma_production_raw' => ['%s magma/tick', ' per acre of this terrain.'],
            'blood_production_raw' => ['%s blood/tick', ' per acre of this terrain.'],
            'thunderstone_production_raw' => ['%s blood/tick', ' per acre of this terrain.'],
            'miasma_production_raw' => ['%s miasma/tick', ' per acre of this terrain.'],
            'sapling_production_raw' => ['%s sapling/tick', ' per acre of this terrain.'],

            'gold_production_mod' => ['%+g%% gold production', ' for every 1%% of this terrain.'],
            'food_production_mod' => ['%+g%% food production', ' for every 1%% of this terrain.'],
            'ore_production_mod' => ['%+g%% ore production', ' for every 1%% of this terrain.'],
            'lumber_production_mod' => ['%+g%% lumber production', ' for every 1%% of this terrain.'],
            'mana_production_mod' => ['%+g%% mana production', ' for every 1%% of this terrain.'],
            'gems_production_mod' => ['%+g%% gems production', ' for every 1%% of this terrain.'],
            'horse_production_mod' => ['%+g%% horse taming', ' for every 1%% of this terrain.'],
            'mud_production_mod' => ['%+g%% mud production', ' for every 1%% of this terrain.'],
            'swamp_gas_production_mod' => ['%+g%% swamp gas production', ' for every 1%% of this terrain.'],
            'marshling_gas_production_mod' => ['%+g%% marshlings production', ' for every 1%% of this terrain.'],
            'yak_production_mod' => ['%+g%% yak production', ' for every 1%% of this terrain.'],
            'magma_production_mod' => ['%+g%% magma production', ' for every 1%% of this terrain.'],
            'blood_production_mod' => ['%+g%% blood production', ' for every 1%% of this terrain.'],
            'thunderstone_production_mod' => ['%+g%% thunderstone production', ' for every 1%% of this terrain.'],
            'miasma_production_mod' => ['%+g%% miasma production', ' for every 1%% of this terrain.'],
            'sapling_production_mod' => ['%+g%% sapling production', ' for every 1%% of this terrain.'],

            'prisoner_upkeep_mod' => ['%+g%% prisoner upkeep', ' for every 1%% of this terrain.'],

            'xp_generation_mod' => ['%+g%% XP generation', ' for every 1%% of this terrain.'],

            'exchange_rate_mod' => ['%+g%% exchange rate', ' for every 1%% of this terrain.'],

            # Improvements
            'improvements_mod' => ['%+g%% improvements', ' for every 1%% of this terrain.'],
            'improvement_points_mod' => ['%+g%% improvement points', ' for every 1%% of this terrain.'],
            'lumber_improvement_points_mod' => ['%+g%% lumber improvement points', ' for every 1%% of this terrain.'],
            'gems_improvement_points_mod' => ['%+g%% gems improvement points', ' for every 1%% of this terrain.'],
            'ore_improvement_points_mod' => ['%+g%% ore improvement points', ' for every 1%% of this terrain.'],

            # Land, Construction and Rezoning
            'construction_costs_mod' => ['%+g%% construction costs', ' for every 1%% of this terrain.'],
            'rezoning_costs_mod' => ['%+g%% rezoning costs', ' for every 1%% of this terrain.'],
            'building_construction_speed_mod' => ['%+g%% building construction speed', ' for every 1%% of this terrain.'],

            # Magic
            'spell_costs_mod' => ['%+g%% spell costs', ' for every 1%% of this terrain.'],
            'sorcery_costs_mod' => ['%+g%% sorcery spell costs', ' for every 1%% of this terrain.'],
            'sorcery_damage_dealt_mod' => ['%+g%% sorcery damage dealt', ' for every 1%% of this terrain.'],
            'sorcery_damage_suffered_mod' => ['%+g%% sorcery damage suffered', ' for every 1%% of this terrain.'],
            'wizard_power_mod' => ['%+g%% wizard power', ' for every 1%% of this terrain.'],

            # Espionage
            'sabotage_damage_dealt_mod' => ['%+g%% sabotage damage dealt', ' for every 1%% of this terrain.'],
            'sabotage_damage_suffered_mod' => ['%+g%% sabotage damage suffered', ' for every 1%% of this terrain.'],
            'spy_power_mod' => ['%+g%% spy power', ' for every 1%% of this terrain.'],

        ];

        $string = $perks[$perkKey][0];

        if($showSecondHalf)
        {
            $string .= $perks[$perkKey][1];
        }

        if($perkValue > 0 and $this->getPerkType($perkKey) == 'mod')
        {
            $perkValue = '+'.number_format($perkValue,2);
        }
        elseif (floor($perkValue) == $perkValue)
        {
            $perkValue = number_format($perkValue);
        }
        elseif($perkValue)
        {
            $perkValue = floatval($perkValue);
        }

        return sprintf($string, $perkValue);
    }

    public function getPerkType(string $perkKey): string
    {
        return (substr($perkKey, -4) === '_mod') ? 'mod' : (substr($perkKey, -4) === '_raw' ? 'raw' : '');
    }
    
}
