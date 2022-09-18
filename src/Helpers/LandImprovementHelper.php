<?php

namespace OpenDominion\Helpers;

use OpenDominion\Models\Race;

class LandImprovementHelper
{
    public function getPerkDescription(string $perkKey, float $perkValue, bool $showSecondHalf = true)
    {
        $perks =
        [
            'offensive_power_mod' => ['%+d%% offensive power', ' for every 1%% of this land type.'],
            'defensive_power_mod' => ['%+d%% defensive power', ' for every 1%% of this land type.'],

            'gold_production_raw' => ['%s gold/tick', ' per acre.'],
            'food_production_raw' => ['%s food/tick', ' per acre.'],
            'ore_production_raw' => ['%s ore/tick', ' per acre.'],
            'lumber_production_raw' => ['%s lumber/tick', ' per acre.'],
            'mana_production_raw' => ['%s mana/tick', ' per acre.'],
            'gems_production_raw' => ['%s gems/tick', ' per acre.'],
            'horse_production_raw' => ['%s horses/tick', ' per acre.'],
            'mud_production_raw' => ['%s mud/tick', ' per acre.'],
            'swamp_gas_production_raw' => ['%s swamp gas/tick', ' per acre.'],
            'marshling_production_raw' => ['%s marshlings/tick', ' per acre.'],
            'yak_production_raw' => ['%s yaks/tick', ' per acre.'],


            'gold_production_mod' => ['%+d%% gold production', ' for every 1%% of this land type.'],
            'food_production_mod' => ['%+d%% food production', ' for every 1%% of this land type.'],
            'ore_production_mod' => ['%+d%% ore production', ' for every 1%% of this land type.'],
            'lumber_production_mod' => ['%+d%% lumber production', ' for every 1%% of this land type.'],
            'mana_production_mod' => ['%+d%% mana production', ' for every 1%% of this land type.'],
            'gems_production_mod' => ['%+d%% gems production', ' for every 1%% of this land type.'],
            'horse_production_mod' => ['%+d%% horse taming', ' for every 1%% of this land type.'],
            'mud_production_mod' => ['%+d%% mud production', ' for every 1%% of this land type.'],
            'swamp_gas_production_mod' => ['%+d%% swamp gas production', ' for every 1%% of this land type.'],

            'xp_generation_mod' => ['%+d%% XP generation', ' for every 1%% of this land type.'],

            'max_population' => ['%+d%% population', ' for every 1%% of this land type.'],
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
        if(strpos($perkKey, '_mod') or $perkKey == 'max_population')
        {
            return 'mod';
        }

        return 'raw';
    }
}
