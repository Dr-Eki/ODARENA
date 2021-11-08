<?php

namespace OpenDominion\Helpers;

use Illuminate\Support\Collection;
use OpenDominion\Models\Race;
use OpenDominion\Models\Resource;
use OpenDominion\Models\Spell;

# ODA
use OpenDominion\Models\Dominion;

class SpellHelper
{
    # ROUND 37

    public function getSpellClass(Spell $spell)
    {
        $classes = [
            'active'  => 'Impact',
            'passive' => 'Aura',
            'invasion'=> 'Invasion',
            'info'    => 'Information'
        ];

        return $classes[$spell->class];
    }

    public function getSpellScope(Spell $spell)
    {
        $scopes = [
            'self'      => 'Self',
            'friendly'  => 'Friendly',
            'hostile'   => 'Hostile'
        ];

        return $scopes[$spell->scope];
    }

    public function getSpellEffectsString(Spell $spell): array
    {

        $effectStrings = [];

        $spellEffects = [

            // Info
            'clear_sight' => 'Reveal status screen',
            'vision' => 'Reveal advancements',
            'revelation' => 'Reveal active spells',

            'fog_of_war' => 'Hidden from Insight',

            // Production

            'ore_production_raw_mod' => '%s%% raw ore production',
            'mana_production_raw_mod' => '%s% raw mana production',
            'lumber_production_raw_mod' => '%s%% raw lumber production',
            'food_production_raw_mod' => '%s%% raw food production',
            'gems_production_raw_mod' => '%s%% raw gem production',
            'gold_production_raw_mod' => '%s%% raw gold production',

            'ore_production_mod' => '%s%% ore production',
            'mana_production_mod' => '%s%% mana production',
            'lumber_production_mod' => '%s%% lumber production',
            'food_production_mod' => '%s%% food production',
            'gems_production_mod' => '%s%% gem production',
            'gold_production_mod' => '%s%% gold production',
            'pearls_production_mod_production' => '%s%% pearl production',
            'cosmic_alignment_production_mod' => '%s%% Cosmic Alignment discovery',

            'food_consumption_mod' => '%s%% food consumption',

            'tech_production' => '%s%% XP generation',

            'alchemy_production' => '+%s gold production per alchemy',

            'food_production_raw' => '%s%% raw food production',

            'food_production_docks' => '%s%% food production from Docks',

            'no_gold_production' => 'No gold production or revenue',
            'no_ore_production' => 'No ore production',
            'no_lumber_production' => 'No lumber production',
            'no_mana_production' => 'No mana production',
            'no_food_production' => 'No food production',
            'no_boat_production' => 'No boat production',
            'no_gems_production' => 'No gem production',

            'rezone_all_land' => 'Rezones %1s%% of all other land types to %2$s.',

            'resource_theft' => 'Displaces %2$s%% of the target\'s %1$s and returns it to the caster.',

            // Military
            'drafting' => '+%s%% drafting',
            'training_time_raw' => '%s ticks training time for military units (does not include Spies, Wizards, or Archmages)',
            'training_costs' => '+%s%% military unit training costs',
            'unit_gold_costs' => '%s%% military unit gold costs',
            'unit_ore_costs' => '%s%% military unit ore costs',
            'unit_lumber_costs' => '%s%% military unit lumber costs',

            'cannot_invade' => 'Cannot invade',

            'additional_units_trained_from_land' => '1%% extra %1$s%% for every %3$s%% %2$s.',

            'faster_return' => 'Units return %s ticks faster from invasions',

            'increase_morale' => 'Restores target morale by %s%% (up to maximum of 100%%).',
            'decrease_morale' => 'Lowers target morale by %s%% (minimum 0%%).',

            'kills_draftees' => 'Kills %1$s%% of the target\'s draftees.',

            'kills_faction_units_percentage' => 'Kills %3$s%% of %1$s %2$s.',
            'kills_faction_units_amount' => 'Kills %3$s%s of %1$s %2$s.',

            'summon_units_from_land' => 'Summon up to %2$s %1$s per acre of %3$s.',
            'summon_units_from_land_by_time' => 'Summon up to %2$s %1$s per acre of %4$s. Amount summoned when cast increased by %3$s%%  per hour into the round.',


            'can_kill_immortal' => 'Can kill some immortal units.',

            'no_drafting' => 'No draftees are drafted.',

            // Improvements
            'improvements_damage' => 'Destroys %s%% of the target\'s improvements.',

            // Population
            'population_growth' => '%s%% population growth rate',
            'kills_peasants' => 'Kills %1$s%% of the target\'s peasants.',

            // Resources
            'destroys_resource' => 'Destroys %2$s%% of the target\'s %1$s.',

            'resource_conversion' => 'Converts %3$s%% of your %1$s to %2$s at a rate of %4$s:1.',

            'peasant_to_resources_conversion' => 'Sacrifice %1$s%% of your sinners for %2$s each.',

            // Magic
            'damage_from_spells' => '%s%% damage from spells',
            'chance_to_reflect_spells' => '%s%% chance to reflect spells',
            'reveal_ops' => 'Reveals the dominion casting spells or spying on you',
            'damage_from_fireballs' => '%s%% damage from fireballs',
            'damage_from_lightning_bolts' => '%s%% damage from lightning bolts',
            'wizard_strength' => '%s%% wizard strength',

            // Espionage
            'disband_spies' => 'Disbands %s%% of enemy spies.',
            'spy_strength' => '%s%% spy strength',
            'immortal_spies' => 'Spies become immortal',
            'spy_strength_recovery' => '%s%% spy strength recovery per tick',

            'gold_theft' => '%s%% gold lost to theft.',
            'mana_theft' => '%s%% mana lost to theft.',
            'lumber_theft' => '%s%% lumber lost to theft.',
            'food_theft' => '%s%% food lost to theft.',
            'ore_theft' => '%s%% ore lost to theft.',
            'gems_theft' => '%s%% gems lost to theft.',
            'all_theft' => '%s%% resources lost to theft',

            'blind_to_reptilian_spies_on_info' => 'Spies blind to Reptilian spies on information gathering ops.',
            'blind_to_reptilian_spies_on_theft' => 'Spies blind to Reptilian spies on theft.',

            // Conversions
            'conversions' => '%s%% conversions',
            'converts_crypt_bodies' => 'Every %1$s %2$ss raise dead a body from the crypt into one %3$s per tick.',
            'convert_enemy_casualties_to_food' => 'Enemy casualties converted to food.',
            'no_conversions' => 'No enemy units are converted.',

            'convert_peasants_to_champions' => 'All peasants converted to champions each tick.',

            // Casualties
            'increases_enemy_draftee_casualties' => '%s%% enemy draftee casualties',
            'increases_casualties_on_offense' => '%s%% enemy casualties when invading',
            'increases_casualties_on_defense' => '%s%% enemy casualties when defending',

            'casualties' => '%s%% casualties',
            'offensive_casualties' => '%s%% casualties suffered when invading',
            'defensive_casualties' => '%s%% casualties suffered when defending',

            // OP/DP
            'offensive_power' => '%s%% offensive power',
            'defensive_power' => '%s%% defensive power',

            'target_defensive_power_mod' => '%s%% defensive modifiers for target',

            'offensive_power_on_retaliation' => '%s%% offensive power if target recently invaded your realm',

            'defensive_power_vs_insect_swarm' => '%s%% defensive power if attacker has Insect Swarm',
            'offensive_power_vs_insect_swarm' => '%s%% offensive power if target has Insect Swarm',

            'reduces_target_raw_defense_from_land' => 'Targets raw defensive power lowered by %1$s%% for every %2$s%% of your own %3$s, max %4$s%% reduction ',# 1,5,forest,10 # -1% raw DP, per 5% forest, max -10%

            'increases_casualties_on_offense_from_wizard_ratio' => 'Enemy casualties increased by %s%% for every 1 wizard ratio.',

            'immune_to_temples' => 'Defensive modifiers are not affected by Temples and any other defensive modifier reductions.',

            'defensive_power_from_peasants' => '%s raw defensive power per peasant',

            // Improvements
            'invest_bonus' => '%s%% improvement points from investments made while spell is active',
            'improvements' => '%s%% improvements',

            // Explore
            'land_discovered' => '%s%% land discovered on successful invasions',
            'stop_land_generation' => 'Stops land generation from units',
            'cannot_explore' => 'Cannot explore',

            // Buildings and Land
            'buildings_destroyed' => '%s%% of all buildings destroyed per tick',
            'barren_land_rezoned' => 'All barren land becomes %1$s',

            // Special
            'opens_portal' => 'Opens a portal required to teleport otherwordly units to enemy lands',

            'burns_extra_buildings' => 'Destroy up to 10%% additional buildings when successfully invading someone, if buildings are built with lumber. Dragons must account for at least 90%% of the offensive power.',

            'stasis' => 'Freezes time. No production, cannot take actions, and cannot have actions taken against it. Units returning from battle continue to return but do not finish and arrive home until Stasis is over.',

            // Cult
            'mind_control' => 'When defending, each Mystic takes control of one invading unit\'s mind. Mindcontrolled units provide 2 raw DP. Only units which have the attribute Sentient and neither of the attributes Ammunition, Equipment, Magical, Massive, Mechanical, Mindless, Ship, or Wise can be mindcontrolled.',
            'menticide' => 'Mindcontrolled units permanently join the Cult as Thralls or are executed.',
            'cogency' => 'Wizards and wizard units that fail hostile spells against the Cult have a chance of joining the Cult instead of dying.',
            'enthralling' => 'When the target releases units, there is a chance some of the units join the Cult as Thralls.',
            'persuasion' => 'Captured spies and spy wizards have a chance to join the Cult as Thralls instead of being executed.',
            'treachery' => 'Some resources stolen by the target are instead diverted to the Cult.',

            // Invasion spells
            'kills_peasants_and_converts_for_caster_unit' => 'Kills %1$s%% of target\'s peasants per tick and converts them into Abominations.',
            'annexes_target' => 'Annexes the target, turning them into a vassal.',

        ];

        foreach ($spell->perks as $perk)
        {
            if (!array_key_exists($perk->key, $spellEffects))
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


            /*****/

            if($perk->key === 'kills_faction_units_percentage' or $perk->key === 'kills_faction_units_amount')
            {
                $faction = (string)$perkValue[0];
                $slot = (int)$perkValue[1];
                $percentage = (float)$perkValue[2];

                $race = Race::where('name', $faction)->first();

                $unit = $race->units->filter(static function ($unit) use ($slot)
                    {
                        return ($unit->slot === $slot);
                    })->first();

                $perkValue = [$faction, str_plural($unit->name), $percentage];
            }

            if($perk->key === 'summon_units_from_land')
            {
                $unitSlots = (array)$perkValue[0];
                $maxPerAcre = (float)$perkValue[1];
                $landType = (string)$perkValue[2];

                // Rue the day this perk is used for other factions.
                $race = Race::where('name', 'Weres')->firstOrFail();

                foreach ($unitSlots as $index => $slot)
                {
                    $slot = (int)$slot;
                    $unit = $race->units->filter(static function ($unit) use ($slot)
                        {
                            return ($unit->slot === $slot);
                        })->first();


                    $units[$index] = str_plural($unit->name);
                }

                $unitsString = generate_sentence_from_array($units);

                $perkValue = [$unitsString, $maxPerAcre, $landType];
                $nestedArrays = false;

            }

            if($perk->key === 'summon_units_from_land_by_time')
            {
                $unitSlots = (array)$perkValue[0];
                $basePerAcre = (float)$perkValue[1];
                $hourlyPercentIncrease = (float)$perkValue[2];
                $landType = (string)$perkValue[3];

                // Rue the day this perk is used for other factions.
                $race = Race::where('name', 'Weres')->firstOrFail();

                foreach ($unitSlots as $index => $slot)
                {
                    $slot = (int)$slot;
                    $unit = $race->units->filter(static function ($unit) use ($slot)
                        {
                            return ($unit->slot === $slot);
                        })->first();


                    $units[$index] = str_plural($unit->name);
                }

                $unitsString = generate_sentence_from_array($units);

                $perkValue = [$unitsString, $basePerAcre, $hourlyPercentIncrease, $landType];
                $nestedArrays = false;

            }


            if($perk->key === 'peasant_to_resources_conversion')
            {
                $ratio = (float)$perkValue[0];
                unset($perkValue[0]);

                // Rue the day this perk is used for other factions.

                foreach ($perkValue as $index => $resourcePair)
                {
                    $resource = Resource::where('key', $resourcePair[1])->firstOrFail();
                    $resources[$index] = $resourcePair[0] . ' ' . str_singular($resource->name);
                }

                $resourcesString = generate_sentence_from_array($resources);
                $resourcesString = str_replace(' And ', ' and ', $resourcesString);

                $perkValue = [$ratio, $resourcesString];
                $nestedArrays = false;

            }


            if($perk->key === 'converts_crypt_bodies')
            {
                $race = Race::where('name', 'Undead')->firstOrFail();

                $raisingUnits = (int)$perkValue[0];
                $raisingUnitsSlot = (int)$perkValue[1];
                $unitsRaisedSlot = (int)$perkValue[2];

                # Get the raising unit
                $raisingUnit = $race->units->filter(static function ($unit) use ($raisingUnitsSlot)
                        {
                            return ($unit->slot === $raisingUnitsSlot);
                        })->first();

                # Get the raised unit
                $raisedUnit = $race->units->filter(static function ($unit) use ($unitsRaisedSlot)
                        {
                            return ($unit->slot === $unitsRaisedSlot);
                        })->first();
                #$unitsString = generate_sentence_from_array([$createdUnit, $createdUnit]);

                $perkValue = [$raisingUnits, $raisingUnit->name, $raisedUnit->name];

                #$perkValue = [$unitsString, $maxPerAcre, $landType];
            }



            /*****/

            if (is_array($perkValue))
            {
                if ($nestedArrays)
                {
                    foreach ($perkValue as $nestedKey => $nestedValue)
                    {
                        foreach($nestedValue as $key => $value)
                        {
                            $nestedValue[$key] = ucwords(str_replace('level','level ',str_replace('_', ' ',$value)));
                        }
                        $effectStrings[] = vsprintf($spellEffects[$perk->key], $nestedValue);
                    }
                }
                else
                {
                    #var_dump($perkValue);
                    foreach($perkValue as $key => $value)
                    {
                        $perkValue[$key] = ucwords(str_replace('_', ' ',$value));
                    }
                    $effectStrings[] = vsprintf($spellEffects[$perk->key], $perkValue);
                }
            }
            else
            {
                $perkValue = str_replace('_', ' ',ucwords($perkValue));
                $effectStrings[] = sprintf($spellEffects[$perk->key], $perkValue);
            }
        }

        return $effectStrings;
    }

    public function getExclusivityString(Spell $spell): string
    {

        $exclusivityString = '<br><small class="text-muted">';

        if($exclusives = count($spell->exclusive_races))
        {
            foreach($spell->exclusive_races as $raceName)
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
        elseif($excludes = count($spell->excluded_races))
        {
            $exclusivityString .= 'All except ';
            foreach($spell->excluded_races as $raceName)
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
