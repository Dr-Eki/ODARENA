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
            'offensive_power_mod' => ['%+g%% offensive power', ' for every 1%% of this terrain.'],
            'defensive_power_mod' => ['%+g%% defensive power', ' for every 1%% of this terrain.'],

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

            'gold_production_mod' => ['%+g%% gold production', ' for every 1%% of this terrain.'],
            'food_production_mod' => ['%+g%% food production', ' for every 1%% of this terrain.'],
            'ore_production_mod' => ['%+g%% ore production', ' for every 1%% of this terrain.'],
            'lumber_production_mod' => ['%+g%% lumber production', ' for every 1%% of this terrain.'],
            'mana_production_mod' => ['%+g%% mana production', ' for every 1%% of this terrain.'],
            'gems_production_mod' => ['%+g%% gems production', ' for every 1%% of this terrain.'],
            'horse_production_mod' => ['%+g%% horse taming', ' for every 1%% of this terrain.'],
            'mud_production_mod' => ['%+g%% mud production', ' for every 1%% of this terrain.'],
            'swamp_gas_production_mod' => ['%+g%% swamp gas production', ' for every 1%% of this terrain.'],
            'xp_generation_mod' => ['%+g%% XP generation', ' for every 1%% of this terrain.'],

            'population_mod' => ['%+g%% population', ' for every 1%% of this terrain.'],
            'population_growth_mod' => ['%+g%% population', ' for every 1%% of this terrain.'],
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
