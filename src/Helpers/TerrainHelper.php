<?php

namespace OpenDominion\Helpers;

class TerrainHelper
{
    # For Race Terrain Perks:
    public function getPerkDescription(string $perkKey, float $perkValue, bool $showSecondHalf = true)
    {
        $perks =
        [
            'offensive_power_mod' => ['%+d%% offensive power', ' for every 1%% of this terrain.'],
            'defensive_power_mod' => ['%+d%% defensive power', ' for every 1%% of this terrain.'],

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

            'gold_production_mod' => ['%+d%% gold production', ' for every 1%% of this terrain.'],
            'food_production_mod' => ['%+d%% food production', ' for every 1%% of this terrain.'],
            'ore_production_mod' => ['%+d%% ore production', ' for every 1%% of this terrain.'],
            'lumber_production_mod' => ['%+d%% lumber production', ' for every 1%% of this terrain.'],
            'mana_production_mod' => ['%+d%% mana production', ' for every 1%% of this terrain.'],
            'gems_production_mod' => ['%+d%% gems production', ' for every 1%% of this terrain.'],
            'horse_production_mod' => ['%+d%% horse taming', ' for every 1%% of this terrain.'],
            'mud_production_mod' => ['%+d%% mud production', ' for every 1%% of this terrain.'],
            'swamp_gas_production_mod' => ['%+d%% swamp gas production', ' for every 1%% of this terrain.'],

            'xp_generation_mod' => ['%+d%% XP generation', ' for every 1%% of this terrain.'],

            'population_mod' => ['%+d%% population', ' for every 1%% of this terrain.'],
            'population_growth_mod' => ['%+d%% population', ' for every 1%% of this terrain.'],
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
        if(strpos($perkKey, '_mod'))
        {
            return 'mod';
        }

        return 'raw';
    }
}
