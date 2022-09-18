<?php

namespace OpenDominion\Helpers;

use OpenDominion\Models\Tech;

class TechHelper
{
    public function getTechs()
    {
        return Tech::all()->keyBy('key');
    }

    public function getTechDescription(Tech $tech): string
    {
        $perkTypeStrings = [
            // Military related
            'defense' => '%+d%% defensive power',
            'offense' => '%+d%% offensive power',
            'military_cost' => '%+d%% military training gold, ore, and lumber costs',

            'military_cost_food' => '%+d%% military training food costs',
            'military_cost_mana' => '%+d%% military training mana costs',

            // Casualties related
            'casualties_on_defense' => '%+d%% casualties on defense',
            'casualties_on_offense' => '%+d%% casualties on offense',

            // Logistics
            'construction_cost' => '%+d%% construction costs',
            'explore_draftee_cost' => '%s draftee per acre explore cost (min 3)',
            'explore_gold_cost' => '%+d%% exploring gold cost',
            'max_population' => '%+d%% maximum population (multiplicative bonus)',
            'rezone_cost' => '%+d%% rezoning costs',

            // Spy related
            'spy_cost' => '%+d%% cost of spies',
            'spy_losses' => '%+d%% spy losses on failed operations',
            'spy_strength' => '%+d%% spy power',
            'spy_strength_recovery' => '%s spy strength per tick',
            'amount_stolen' => '%+d%% amount stolen',

            // Wizard related
            'spell_cost' => '%+d%% cost of spells',
            'wizard_cost' => '%+d%% cost of wizards',
            'wizard_strength' => '%+d%% wizard power',
            'wizard_strength_recovery' => '%s wizard strength recovery per tick',

            // Resource related
            'food_production_mod' => '%+d%% food production',
            'gems_production_mod' => '%+d%% gem production',
            'lumber_production_mod' => '%+d%% lumber production',
            'mana_production_mod' => '%+d%% mana production',
            'ore_production_mod' => '%+d%% ore production',
            'gold_production_mod' => '%+d%% gold production',

            'food_production_raw' => '%s% food/tick production',
            'gems_production_raw' => '%s% gem/tick production',
            'lumber_production_raw' => '%s% lumber/tick production',
            'mana_production_raw' => '%s% mana/tick production',
            'ore_production_raw' => '%s% ore/tick production',
            'gold_production_raw' => '%s% gold/tick production',

            // ODA
            'prestige_gains' => '%+d%% higher prestige gains',
            'improvements' => '%+d%% higher improvement bonus',
            'conversions' => '%+d%% more conversions (only applicable to Afflicted, Cult, and Sacred Order)',
            'barracks_housing' => '%+d%% higher military housing in buildings that provide military housing',
            'military_housing' => '%+d%% more military housing in buildings that provide military housing',
            'gold_interest' => '%+d%% interest on your gold stockpile per tick',
            'exchange_rate' => '%+d%% exchange rates',
            'jobs_per_building' => '%+d%% more jobs per building',
            'drafting' => '%+d%% drafting',

            // Improvements
            'gemcutting' => '%+d%% more improvement points per gem',
            'gold_invest_bonus' => '%+d%% more improvement points per gold',
            'ore_invest_bonus' => '%+d%% more improvement points per ore',
            'gems_invest_bonus' => '%+d%% more improvement points per gem',
            'lumber_invest_bonus' => '%+d%% more improvement points per lumber',

        ];

        $perkStrings = [];
        foreach ($tech->perks as $perk) {
            if (isset($perkTypeStrings[$perk->key])) {
                $perkValue = (float)$perk->pivot->value;
                if ($perkValue < 0) {
                    $perkStrings[] = vsprintf($perkTypeStrings[$perk->key], $perkValue);
                } else {
                    $perkStrings[] = vsprintf($perkTypeStrings[$perk->key], '+' . $perkValue);
                }
            }
        }

        return implode( ', ', $perkStrings);
    }
}
