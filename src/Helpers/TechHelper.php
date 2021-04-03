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
            'defense' => '%s%% defensive power',
            'offense' => '%s%% offensive power',
            'military_cost' => '%s%% military training gold, ore, and lumber costs',


            'military_cost_food' => '%s%% military training food costs',
            'military_cost_mana' => '%s%% military training mana costs',

            // Casualties related
            'fewer_casualties_defense' => '%s%% fewer casualties on defense',
            'fewer_casualties_offense' => '%s%% fewer casualties on offense',

            // Logistics
            'construction_cost' => '%s%% construction costs',
            'explore_draftee_cost' => '%s draftee per acre explore cost (min 3)',
            'explore_gold_cost' => '%s%% exploring gold cost',
            'max_population' => '%s%% maximum population (multiplicative bonus)',
            'rezone_cost' => '%s%% rezoning costs',

            // Spy related
            'spy_cost' => '%s%% cost of spies',
            'spy_losses' => '%s%% spy losses on failed operations',
            'spy_strength' => '%s%% spy strength',
            'spy_strength_recovery' => '%s spy strength per hour',
            'amount_stolen' => '%s%% amount stolen',

            // Wizard related
            'spell_cost' => '%s%% cost of spells',
            'wizard_cost' => '%s%% cost of wizards',
            'wizard_strength' => '%s%% wizard strength',
            'wizard_strength_recovery' => '%s wizard strength per hour',

            // Resource related
            'food_production' => '%s%% food production',
            'gem_production' => '%s%% gem production',
            'lumber_production' => '%s%% lumber production',
            'mana_production' => '%s%% mana production',
            'ore_production' => '%s%% ore production',
            'gold_production' => '%s%% gold production',

            // ODA
            'prestige_gains' => '%s%% higher prestige gains',
            'improvements' => '%s%% higher improvement bonus',
            'conversions' => '%s%% more conversions (only applicable to Afflicted, Cult, and Sacred Order)',
            'barracks_housing' => '%s%% higher military housing in buildings that provide military housing',
            'gemcutting' => '%s%% more improvement points per gem',
            'gold_interest' => '%s%% interest on your gold stockpile per tick',
            'exchange_rate' => '%s%% better exchange rates',
            'jobs_per_building' => '%s%% more jobs per building',
            'drafting' => '%s%% drafting',

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
