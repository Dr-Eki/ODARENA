<?php

namespace OpenDominion\Helpers;

use OpenDominion\Models\Race;
use OpenDominion\Models\Unit;
use OpenDominion\Models\Tech;

class UnitHelper
{
      public function getUnitTypes(bool $hideSpecialUnits = false): array
    {
        $data = [
            'unit1',
            'unit2',
            'unit3',
            'unit4',
        ];

        if (!$hideSpecialUnits) {
            $data = array_merge($data, [
                'spies',
                'wizards',
                'archmages',
            ]);
        }

        return $data;
    }

    public function getUnitName(string $unitType, Race $race): string
    {
        if (in_array($unitType, ['spies', 'wizards', 'archmages'], true)) {
            return ucfirst($unitType);
        }

        $unitSlot = (((int)str_replace('unit', '', $unitType)) - 1);

        return $race->units[$unitSlot]->name;
    }


    public function getUnitHelpString(string $unitType, Race $race): ?string
    {

        $helpStrings = [
            'draftees' => 'Used for exploring and training other units.',
            'unit1' => ' ',
            'unit2' => ' ',
            'unit3' => ' ',
            'unit4' => ' ',
            'spies' => 'Used for espionage.',
            'wizards' => 'Used for casting offensive spells.',
            'archmages' => 'Used for casting offensive spells. Twice as strong as regular wizards.',
        ];

        $perkTypeStrings = [
            // Conversions
            'conversion' => 'Converts some enemy casualties into %s.',
            'staggered_conversion' => 'Converts some enemy casualties into %2$s against dominions %1$s%%+ of your size.',
            'cannot_be_converted' => 'Unit cannot be converted.',
            'vampiric_conversion' => 'Spreads vampirism.',

            'displaced_peasants_conversion' => 'Converts enemy peasants formerly living on land conquered on invasion into %s.',
            'strength_conversion' => 'Converts enemy casualties with %1$s or less raw OP or DP into %2$s or, if stronger than %1$s, into %3$s.',
            'value_conversion' => 'Fuses %1$sx of killed enemy raw OP or DP into %2$s.',
            'casualties_conversion' => 'Converts enemy casualties into %s.',

            'passive_conversion' => 'Converts %3$s %1$s into 1 %2$s each tick, increased by (%4$s / Total Land)%%.',

            // OP/DP related
            'defense_from_building' => 'Defense increased by 1 for every %2$s%% %1$ss (max +%3$s).',
            'offense_from_building' => 'Offense increased by 1 for every %2$s%% %1$ss (max +%3$s).',

            'defense_from_buildings' => 'Defense increased by 1 for every %2$s%% %1$s, max +%3$s. Includes buildings under construction.',
            'offense_from_buildings' => 'Offense increased by 1 for every %2$s%% %1$s, max +%3$s. Includes buildings under construction.',

            'defense_from_land' => 'Defense increased by 1 for every %2$s%% %1$ss (max +%3$s).',
            'offense_from_land' => 'Offense increased by 1 for every %2$s%% %1$ss (max +%3$s).',

            'offense_vs_land' => 'Offense increased by 1 against every %2$s%% %1$ss of target (max +%3$s).',
            'defense_vs_land' => 'Defense increased by 1 for every %2$s%% %1$ss of attacker (max +%3$s).',

            'defense_from_pairing' => 'Defense increased by %2$s when paired with %3$s %1$s at home.',
            'offense_from_pairing' => 'Offense increased by %2$s when paired with %3$s %1$s on attack.',

            'defense_from_prestige' => 'Defense increased by 1 for every %1$s prestige (max +%2$s).',
            'offense_from_prestige' => 'Offense increased by 1 for every %1$s prestige (max +%2$s).',

            'defense_vs_prestige' => 'Defense increased by 1 against every %1$s prestige of attacker (max +%2$s).',
            'offense_vs_prestige' => 'Offense increased by 1 against every %1$s prestige of target (max +%2$s).',

            'defense_vs_building' => 'Defense increased by 1 against every %2$s%% %1$ss of attacker (max +%3$s).',
            'offense_vs_building' => 'Offense increased by 1 against every %2$s%% %1$ss of target (max +%3$s).',

            'offense_staggered_land_range' => 'Offense increased by %2$s against dominions %1$s%%+ of your size.',

            'offense_raw_wizard_ratio' => 'Offense increased by %1$s * Raw Wizard Ratio (max +%2$s).',
            'offense_wizard_ratio' => 'Offense increased by %1$s * Wizard Ratio (max +%2$s).',

            'offense_raw_spy_ratio' => 'Offense increased by %1$s * Raw Spy Ratio (max +%2$s).',
            'offense_spy_ratio' => 'Offense increased by %1$s * Spy Ratio (max +%2$s).',

            'offense_if_recently_invaded' => 'Offense increased by %1$s if recenty invaded (in the last six hours, includes non-overwhelmed failed invasions).',
            'defense_if_recently_invaded' => 'Defense increased by %1$s if recenty invaded (in the last six hours, includes non-overwhelmed failed invasions).',

            'offense_per_hour' => 'Offense increased by %1$s for every hour of the round (max +%2$s).',
            'defense_per_hour' => 'Defense increased by %1$s for every hour of the round (max +%2$s).',

            'offense_from_time' => 'Offense increased by %3$s between %1$s:00 and %2$s:00.',
            'defense_from_time' => 'Defense increased by %3$s between %1$s:00 and %2$s:00.',

            'offense_vs_barren_land' => 'Offense increased by 1 against every %1$s%% barren land of target (max +%2$s).',

            'offense_vs_resource' => 'Offense increased by 1 for every %2$s %1$s target has (max +%3$s).',
            'defense_vs_resource' => 'Defense increased by 1 for every %2$s %1$s attacker has (max +%3$s).',

            'offense_from_resource' => 'Offense increased by 1 for every %2$s %1$s (no max).',
            'offense_from_resource_exhausting' => 'Offense increased by 1 for every %2$s %1$s (no max). All %1$s is spent when the unit attacks.',
            'defense_from_resource' => 'Defense increased by 1 for every %2$s %1$s (max +%3$s).',

            'offense_from_military_percentage' => 'Gains +1x(Military / Total Population) OP, max +1 at 100%% military.',
            'offense_from_victories' => 'Offense increased by %1$s for every victory (max +%2$s). Only successful attacks over 75%% count as victories.',
            'defense_from_victories' => 'Defense increased by %1$s for every victory (max +%2$s). Only successful attacks over 75%% count as victories.',

            'offense_from_net_victories' => 'Offense increased by %1$s for every net victory (max +%2$s, min 0). Net Victories is Victories less times invaded. Only successful attacks over 75%% count as victories. Any successful invasion suffered counts as an invasion.',
            'defense_from_net_victories' => 'Defense increased by %1$s for every net victory (max +%2$s, min 0). Net Victories is Victories less times invaded. Only successful attacks over 75%% count as victories. Any successful invasion suffered counts as an invasion.',

            'defense_mob' => 'Defense increased by +%1$s if your troops at home (including units with no defensive power) outnumber the invading units.',
            'offense_mob' => 'Offense increased by +%1$s if the troops you send outnumber the target\'s entire military at home (including units with no defensive power).',

            'offense_from_spell' => 'Offense increased by %2$s if the spell %1$s is active.',
            'defense_from_spell' => 'Defense increased by %2$s if the spell %1$s is active.',

            'offense_from_advancements' => 'Offense increased by %2$s if you unlock %1$s.',
            'defense_from_advancements' => 'Defense increased by %2$s if you unlock %1$s.',

            'offense_from_title' => 'Offense increased by %2$s if ruled by a %1$s.',
            'defense_from_title' => 'Defense increased by %2$s if ruled by a %1$s.',

            // Spy related
            'counts_as_spy_defense' => 'Counts as %s of a spy on defense.',
            'counts_as_spy_offense' => 'Counts as %s of a spy on offense.',
            'immortal_spy' => 'Immortal spy (cannot be killed when performing espionage).',
            'minimum_spa_to_train' => 'Must have at least %s Spy Ratio (on offense) to train.',

            'spy_from_title' => 'Counts as additional %2$s of a spy (offense and defense) if ruled by a %1$s.',

            'protects_resource_from_theft' => 'Protects %2$s %1$s from theft when at home.',

            // Wizard related
            'counts_as_wizard_defense' => 'Counts as %s of a wizard on defense.',
            'counts_as_wizard_offense' => 'Counts as %s of a wizard on offense.',
            'immortal_wizard' => 'Immortal wizard (cannot be killed when casting spells).',
            'minimum_wpa_to_train' => 'Must have at least %s Wizard Ratio (on offense) to train.',
            'wizard_from_title' => 'Counts as additional %2$s of a wizard (offense and defense) if ruled by a %1$s.',

            // Casualties and death related
            'fewer_casualties' => '%s%% fewer casualties.',
            'fewer_casualties_defense' => '%s%% fewer casualties on defense.',
            'fewer_casualties_offense' => '%s%% fewer casualties on offense.',
            'fixed_casualties' => 'Always suffers %s%% casualties.',

            'immortal' => 'Immortal in combat. Only dies against Tiranthael\'s Blessing or Qur Zealots.',
            'true_immortal' => 'Immortal in combat.',
            'spirit_immortal' => 'Immortal on offense and on succesful defense. Dies if successfully invaded.',
            'immortal_on_victory' => 'Immortal in combat if invasion is successful.',
            'immortal_on_fending_off' => 'Immortal in combat if successfully fending off invader.',

            'immortal_vs_land_range' => 'Near immortal when attacking dominions %s%%+ of your size, except when overwhelmed on attack.',

            'kills_immortal' => 'Kills immortal units.',

            'reduces_casualties' => 'Reduces combat losses.',
            'increases_casualties_on_offense' => 'Increases enemy casualties on offense (defender suffers more casualties).',
            'increases_casualties_on_defense' => 'Increases enemy casualties on defense (attacker suffers more casualties).',

            'fewer_casualties_defense_from_land' => 'Casualties on defense reduced by 1%% for every %2$s%% %1$ss (max %3$s%% reduction).',
            'fewer_casualties_offense_from_land' => 'Casualties on offense reduced by 1% for every %2$s%% %1$ss (max %3$s%% reduction).',

            'fewer_casualties_defense_vs_land' => 'Casualties on defense reduced by 1%% against every %2$s%% %1$ss of attacker (max %3$s%% reduction).',
            'fewer_casualties_offense_vs_land' => 'Casualties on offense reduced by 1%% against every %2$s%% %1$ss of target (max %3$s%% reduction).',

            'only_dies_vs_raw_power' => 'Only dies against units with %s or more raw military power.',

            'dies_into' => 'Upon death, returns as %1$s.',# On defense, the change is instant. On offense, the new unit returns from battle with the other units.',
            'wins_into' => 'Upon successul invasion, returns as %s.',
            'fends_off_into' => 'Upon successully fending off invasion, becomes %s.',
            'dies_into_multiple' => 'Upon death, returns as %2$s %1$s.',# On defense, the change is instant. On offense, the new unit returns from battle with the other units.',

            'dies_into_multiple_on_victory' => 'Upon death in succesful combat, returns as %2$s %1$s. If unsuccessful, returns as %3$s %1$s.',# On defense, the change is instant. On offense, the new unit returns from battle with the other units.',

            // Resource related
            'ore_production' => 'Each unit produces %s units of ore per tick.',
            'mana_production' => 'Each unit generates %s mana per tick.',
            'lumber_production' => 'Each unit collects %s lumber per tick.',
            'food_production' => 'Each unit produces %s food per tick.',
            'gem_production' => 'Each unit mines %s gems per tick.',
            'tech_production' => 'Each unit produces %s experience points per tick.',
            'gold_production' => 'Each unit produces %s gold points per tick.',

            'food_consumption' => 'Eats %s halms of food extra.',

            'decay_protection' => 'Each units protects %1$s %2$s per tick from decay.',

            'plunders' => 'Plunders up to %2$s %1$s on attack.', # Multiple resources
            'plunder' => 'Plunders up to %2$s %1$s on attack.', # Single resource

            'mana_drain' => 'Each unit drains %s mana per tick.',
            'upkeep_gold' => 'Costs %s gold per tick.',
            'upkeep_lumber' => 'Costs %s lumber per tick.',
            'upkeep_ore' => 'Costs %s ore per tick.',

            'destroys_souls' => 'Releases souls.',

            'production_from_title' => 'Produces %3$s %2$s per tick if ruled by a %1$s.',

            // Misc
            'faster_return' => 'Returns %s ticks faster from battle.',
            'land_per_tick' => 'Explores %1$s acres of home land per tick.',
            #'sendable_with_zero_op' => 'Equippable (can be sent on invasion despite unit having 0 offensive power).', # Hidden
            'faster_return_if_paired' => 'Returns %2$s ticks faster if paired with a %1$s.',

            // Training
            'cannot_be_trained' => 'Cannot be trained.',
            'instant_training' => 'Appears immediately.',

            'afterlife_norse' => 'Upon honourable death (successfully invading another dominion over 75%% your size or successfully fending off any invader), becomes a legendary champion.',
            'does_not_kill' => 'Does not kill other units.',
            'no_draftee' => 'No draftee required to train.',

            'unit_production' => 'Produces %s per tick.',

            'attrition' => '%1$s%% attrition rate per tick.',

            'cannot_be_released' => 'Cannot be released',

            'reduces_unit_costs' => 'Reduces training costs by %1$s%% for every 1%% of population consisting of this unit. Max %2$s%% reduction.',

            'advancements_required_to_train' => 'Must have %1$s to train this unit.',

            // Limits
            'pairing_limit' => 'You can at most have %2$s of this unit per %1$s. Training is limited to %1$s at home.',
            'land_limit' => 'You can at most have %2$s of this unit per acre of %1$s.',
            'building_limit' => 'You can at most have %2$s of this unit per %1$s',
            'building_limit_increasable' => 'You can at most have %2$s of this unit per %1$s. Increased by %4$ xx your %3$s improvements.',
            'building_limit_prestige' => 'You can at most have %2$s of this unit per %1$s. Increased by prestige multiplier.',

            'victories_limit' => 'You can at most have %2$s of this unit per %1$s victories.',

            'pairing_limit_increasable' => 'You can at most have %2$s of this unit per %1$s. Increased by %4$ xx your %3$s improvements.',
            'archmage_limit' => 'You can at most have %1$s of this unit per Archmage. Increased by %3$ xx your %2$s improvements.',
            'wizard_limit' => 'You can at most have %1$s of this unit per Wizard. Increased by %3$ xx your %2$s improvements.',
            'spy_limit' => 'You can at most have %1$s of this unit per Spy. Increased by %3$ xx your %2$s improvements.',

            'amount_limit' => 'You can at most have %1$s of this unit.',

            // Population
            'does_not_count_as_population' => 'Does not count towards population. No housing required.',
            'population_growth' => 'Increases population growth by 2%% for every 1%% of population.',

            'houses_military_units' => 'Houses %1$s military units.',
            'houses_people' => 'Houses %1$s people.',

            'provides_jobs' => 'Provides %1$s jobs.',

            'housing_count' => 'Takes up %1$s housing (instead of 1).',

            // Other
            'increases_morale' => 'Increases base morale by %s%% for every 1%% of population.',
            'increases_prestige_gains' => 'Increases prestige gains by %s%% for every 1%% of units sent.',
            'stuns_units' => 'Stuns some units with up to %1$s DP for %2$s ticks, whereafter the units return unharmed.',

            // Damage
            'burns_peasants_on_attack' => 'Burns %s peasants on successful invasion.',
            'damages_improvements_on_attack' => 'Damages target\'s castle: %s improvement points, spread proportionally across all invested improvements.',
            'eats_peasants_on_attack' => 'Eats %s peasants on successful invasion.',
            'eats_draftees_on_attack' => 'Eats %s draftees on successful invasion.',

            // Demonic
            'kills_peasants' => 'Eats %s peasants per tick.',
            'sacrifices_peasants' => 'Sacrifices %s peasants per tick for one soul, 1.5 gallons of blood, and 2 halms of food per peasant.',

            // Myconid
            'decreases_info_ops_accuracy' => 'Decreases accuracy of Clear Sights performed on the dominion by 0.50%% for every 1%% of total military made up of this unit.',
        ];

        // Get unit - same logic as military page
        if (in_array($unitType, ['unit1', 'unit2', 'unit3', 'unit4']))
        {
            $unit = $race->units->filter(function ($unit) use ($unitType) {
                return ($unit->slot == (int)str_replace('unit', '', $unitType));
            })->first();

            list($type, $proficiency) = explode(' ', $helpStrings[$unitType]);

            $helpStrings[$unitType] .= '<li>OP: '. number_format($unit->power_offense,2) . ' / DP: ' . number_format($unit->power_defense,2) . ' / T: ' . $unit->training_time .  '</li>';
            #$helpStrings[$unitType] .= '<li>Attributes: '. $this->getUnitAttributesString($unitType, $race) . '</li>';

            foreach ($unit->perks as $perk)
            {
                if (!array_key_exists($perk->key, $perkTypeStrings))
                {
                    //\Debugbar::warning("Missing perk help text for unit perk '{$perk->key}'' on unit '{$unit->name}''.");
                    continue;
                }

                $perkValue = $perk->pivot->value;

                // Handle array-based perks
                $nestedArrays = false;

                // todo: refactor all of this
                // partially copied from Race::getUnitPerkValueForUnitSlot
                if (str_contains($perkValue, ','))
                {
                    $perkValue = explode(',', $perkValue);

                    foreach ($perkValue as $key => $value)
                    {
                        if (!str_contains($value, ';'))
                        {
                            continue;
                        }

                        $nestedArrays = true;
                        $perkValue[$key] = explode(';', $value);
                    }
                }

                // building_limit
                if ($perk->key === 'building_limit')
                {
                    $perkValue[1] = ' of this unit per ' . $perkValue[0];
                    if(isset($perkValue[2]))
                    {
                        $perkValue[1] .= ', increased by ' . $perkValue[2] . ' improvements.';
                    }
                    else
                    {
                        $perkValue[1] .= '.';
                    }
                }

                // Special case for pairings
                if ($perk->key === 'defense_from_pairing' || $perk->key === 'offense_from_pairing' || $perk->key === 'pairing_limit')
                {
                    $slot = (int)$perkValue[0];
                    $pairedUnit = $race->units->filter(static function ($unit) use ($slot) {
                        return ($unit->slot === $slot);
                    })->first();

                    $perkValue[0] = $pairedUnit->name;
                    if (isset($perkValue[2]) && $perkValue[2] > 0)
                    {
                        $perkValue[0] = str_plural($perkValue[0]);
                    }
                    else
                    {
                        $perkValue[2] = 1;
                    }
                }

                // Special case for returns faster if pairings
                if ($perk->key === 'faster_return_if_paired')
                {
                    $slot = (int)$perkValue[0];
                    $pairedUnit = $race->units->filter(static function ($unit) use ($slot) {
                        return ($unit->slot === $slot);
                    })->first();

                    $perkValue[0] = $pairedUnit->name;
                    if (isset($perkValue[2]) && $perkValue[2] > 0)
                    {
                        $perkValue[0] = str_plural($perkValue[0]);
                    }
                    else
                    {
                        $perkValue[2] = 1;
                    }
                }

                // Special case for pairing_limit_increasable
                if ($perk->key === 'pairing_limit_increasable')
                {
                    $slot = (int)$perkValue[0];
                    $pairedUnit = $race->units->filter(static function ($unit) use ($slot) {
                        return ($unit->slot === $slot);
                    })->first();

                    $perkValue[0] = $pairedUnit->name;
                }

                // Special case for conversions
                if ($perk->key === 'conversion' or $perk->key === 'displaced_peasants_conversion' or $perk->key === 'casualties_conversion')
                {
                    $unitSlotsToConvertTo = array_map('intval', str_split($perkValue));
                    $unitNamesToConvertTo = [];

                    foreach ($unitSlotsToConvertTo as $slot) {
                        $unitToConvertTo = $race->units->filter(static function ($unit) use ($slot) {
                            return ($unit->slot === $slot);
                        })->first();

                        $unitNamesToConvertTo[] = str_plural($unitToConvertTo->name);
                    }

                    $perkValue = generate_sentence_from_array($unitNamesToConvertTo);
                }
                if($perk->key === 'staggered_conversion')
                {
                    foreach ($perkValue as $index => $conversion) {
                        [$convertAboveLandRatio, $slots] = $conversion;

                        $unitSlotsToConvertTo = array_map('intval', str_split($slots));
                        $unitNamesToConvertTo = [];

                        foreach ($unitSlotsToConvertTo as $slot) {
                            $unitToConvertTo = $race->units->filter(static function ($unit) use ($slot) {
                                return ($unit->slot === $slot);
                            })->first();

                            $unitNamesToConvertTo[] = str_plural($unitToConvertTo->name);
                        }

                        $perkValue[$index][1] = generate_sentence_from_array($unitNamesToConvertTo);
                    }
                }
                if($perk->key === 'strength_conversion')
                {
                    $limit = (float)$perkValue[0];
                    $under = (int)$perkValue[1];
                    $over = (int)$perkValue[2];

                    $underLimitUnit = $race->units->filter(static function ($unit) use ($under)
                        {
                            return ($unit->slot === $under);
                        })->first();

                    $overLimitUnit = $race->units->filter(static function ($unit) use ($over)
                        {
                            return ($unit->slot === $over);
                        })->first();

                    $perkValue = [$limit, str_plural($underLimitUnit->name), str_plural($overLimitUnit->name)];
                }
                if($perk->key === 'passive_conversion')
                {
                    $slotFrom = (int)$perkValue[0];
                    $slotTo = (int)$perkValue[1];
                    $rate = (float)$perkValue[2];
                    $building = (string)$perkValue[3];

                    $unitFrom = $race->units->filter(static function ($unit) use ($slotFrom)
                        {
                            return ($unit->slot === $slotFrom);
                        })->first();

                    $unitTo = $race->units->filter(static function ($unit) use ($slotTo)
                        {
                            return ($unit->slot === $slotTo);
                        })->first();

                    $perkValue = [$unitFrom->name, $unitTo->name, $rate, $building];
                }
                if($perk->key === 'value_conversion')
                {
                    $multiplier = (float)$perkValue[0];
                    $convertToSlot = (int)$perkValue[1];

                    $unitToConvertTo = $race->units->filter(static function ($unit) use ($convertToSlot)
                        {
                            return ($unit->slot === $convertToSlot);
                        })->first();

                    $perkValue = [$multiplier, str_plural($unitToConvertTo->name)];
                }

                if($perk->key === 'defense_from_buildings')
                {
                    $buildings = (array)$perkValue[0];
                    $ratio = (float)$perkValue[1];
                    $max = (float)$perkValue[2];

                    foreach ($buildings as $index => $building)
                    {
                        $buildings[$index] = str_plural(ucwords(str_replace('_',' ', $building)));
                    }

                    $buildingsString = generate_sentence_from_array($buildings) . ' (total)';

                    $perkValue = [$buildingsString, $ratio, $max];
                    $nestedArrays = false;

                }

                if($perk->key === 'advancements_required_to_train')
                {
                    $advancementKeys = explode(';',$perkValue);
                    $advancements = [];

                    foreach ($advancementKeys as $index => $advancementKey)
                    {
                        $advancement = Tech::where('key', $advancementKey)->firstOrFail();

                        $advancements[$index] = $advancement->name . ' level ' . $advancement->level;
                    }

                    $advancementsString = generate_sentence_from_array($advancements);

                    $perkValue = $advancementsString;
                    #$nestedArrays = false;

                }

                if($perk->key === 'plunders')
                {
                    foreach ($perkValue as $index => $plunder) {
                        [$resource, $amount] = $plunder;

                        $perkValue[$index][1] = generate_sentence_from_array([$amount]);
                    }
                }

                // Special case for dies_into, wins_into ("change_into"), fends_off_into
                if ($perk->key === 'dies_into' or $perk->key === 'wins_into' or $perk->key === 'fends_off_into')
                {
                    $unitSlotsToConvertTo = array_map('intval', str_split($perkValue));
                    $unitNamesToConvertTo = [];

                    foreach ($unitSlotsToConvertTo as $slot) {
                        $unitToConvertTo = $race->units->filter(static function ($unit) use ($slot) {
                            return ($unit->slot === $slot);
                        })->first();

                        $unitNamesToConvertTo[] = $unitToConvertTo->name;
                    }

                    $perkValue = generate_sentence_from_array($unitNamesToConvertTo);
                }

                // Special case for returns faster if pairings
                if ($perk->key === 'dies_into_multiple')
                {
                    $slot = (int)$perkValue[0];
                    $pairedUnit = $race->units->filter(static function ($unit) use ($slot) {
                        return ($unit->slot === $slot);
                    })->first();

                    $amount = (int)$perkValue[1];

                    $perkValue[0] = $pairedUnit->name;
                    if (isset($perkValue[1]) && $perkValue[1] > 0)
                    {
                        $perkValue[0] = str_plural($perkValue[0]);
                    }
                    else
                    {
                        $perkValue[1] = 1;
                    }
                }

/*
                // Special case for vampiric_conversion
                if($perk->key === 'vampiric_conversion')
                {
                    foreach ($perkValue as $unitKilledStrength => $conversion)
                    {
                        $slot = 1;
                        [$unitKilledStrength, $i] = $conversion;

                        #dd($conversion);

                        $unitSlotsToConvertTo = $slot;
                        $unitNamesToConvertTo = [];

                        $unitToConvertTo = $race->units->filter(static function ($unit) use ($slot) {
                            return ($unit->slot === $slot);
                        })->first();
                        $unitNamesToConvertTo[] = str_plural($unitToConvertTo->name);

                        $perkValue[$unitKilledStrength][1] = generate_sentence_from_array($unitNamesToConvertTo);

                        $slot++;
                    }
                }
*/
                // Special case for unit_production
                if ($perk->key === 'unit_production')
                {
                    $unitSlotToProduce = intval($perkValue[0]);

                    $unitToProduce = $race->units->filter(static function ($unit) use ($unitSlotToProduce) {
                        return ($unit->slot === $unitSlotToProduce);
                    })->first();

                    $unitNameToProduce[] = str_plural($unitToProduce->name);

                    $perkValue = generate_sentence_from_array($unitNameToProduce);
                }


                if (is_array($perkValue))
                {
                    if ($nestedArrays)
                    {
                        foreach ($perkValue as $nestedKey => $nestedValue)
                        {
                            foreach($nestedValue as $key => $value)
                            {
                                $nestedValue[$key] = str_replace('Level','level',str_replace('And','and',ucwords(str_replace('level','level ',str_replace('_', ' ',$value)))));
                            }
                            $helpStrings[$unitType] .= ('<li>' . vsprintf($perkTypeStrings[$perk->key], $nestedValue) . '</li>');
                        }
                    }
                    else
                    {
                        #var_dump($perkValue);
                        foreach($perkValue as $key => $value)
                        {
                            $perkValue[$key] = str_replace('Level','level',str_replace(' And', ' and',ucwords(str_replace('_', ' ',$value))));
                        }
                        $helpStrings[$unitType] .= ('<li>' . vsprintf($perkTypeStrings[$perk->key], $perkValue) . '</li>');
                    }
                }
                else
                {
                    $perkValue = str_replace('Level','level',str_replace('And','and',str_replace('_', ' ',ucwords($perkValue))));
                    $helpStrings[$unitType] .= ('<li>' . sprintf($perkTypeStrings[$perk->key], $perkValue) . '</li>');
                }
            }
        }

        if(strlen($helpStrings[$unitType]) == 0)
        {
          $helpStrings[$unitType] = '<i>No special abilities</i>';
        }
        else
        {
          $helpStrings[$unitType] = '<ul>' . $helpStrings[$unitType] . '</ul>';
        }

        return $helpStrings[$unitType] ?: null;
    }

    public function getUnitAttributes(Unit $unit)
    {
        foreach($unit->type as $attribute)
        {
            $attributes[] = $attribute;
        }

        return $attributes;
    }

    public function getUnitAttributesString(string $unitType, Race $race = null): string
    {

        $attributeString = '';

        if ($race && in_array($unitType, ['unit1', 'unit2', 'unit3', 'unit4']))
        {
            $unit = $race->units->filter(function ($unit) use ($unitType) {
                return ($unit->slot == (int)str_replace('unit', '', $unitType));
            })->first();
        }

        foreach($unit->type as $attribute)
        {
            $attributes[] = $attribute;
        }


        sort($attributes);
        $count = count($attributes);

        $i = $count;
        foreach($attributes as $attribute)
        {
            $attributeString .= ucwords($attribute);
            $attributeString .= ', ';

        }

        return $attributeString;
    }

    public function getUnitAttributesList(string $unitType, Race $race = null): string
    {

        $attributeString = '<ul>';

        if ($race && in_array($unitType, ['unit1', 'unit2', 'unit3', 'unit4']))
        {
            $unit = $race->units->filter(function ($unit) use ($unitType) {
                return ($unit->slot == (int)str_replace('unit', '', $unitType));
            })->first();
        }

        foreach($unit->type as $attribute)
        {
            $attributes[] = $attribute;
        }


        sort($attributes);
        $attributeString = '</ul>';
        foreach($attributes as $attribute)
        {
            $attributeString .= '<li>' . ucwords($attribute) . '</li>';
        }

        $attributeString .= '</ul>';
        return $attributeString;
    }

    public function getDrafteeHelpString(Race $race): ?string
    {
        $drafteeDp = 1;

        $drafteeDp = $race->getPerkValue('draftee_dp') ? $race->getPerkValue('draftee_dp') : 1;

        return '<ul><li>DP: ' . $drafteeDp . '</li></ul>';

    }

    public function unitSlotHasAttributes(Race $race, int $slot, array $searchAttributes): bool
    {
        # Get the $unit
        $unit = $race->units->filter(function ($unit) use ($slot) {
                return ($unit->slot == $slot);
            })->first();

        # Get the unit attributes
        $unitAttributes = $this->getUnitAttributes($unit);

        if(count(array_intersect($searchAttributes, $unitAttributes)) > 0)
        {
            return true;
        }

        return false;

    }


}
