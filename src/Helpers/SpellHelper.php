<?php

namespace OpenDominion\Helpers;

use Illuminate\Support\Collection;
use OpenDominion\Models\Deity;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;
use OpenDominion\Models\Resource;
use OpenDominion\Models\Spell;

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

        if(isset($spell->deity))
        {
            $effectStrings[] = 'Can only be cast if devoted to ' . $spell->deity->name . '.';
        }

        $spellEffects = [

            // Info
            'fog_of_war' => 'Hidden from Insight',

            // Production
            'ore_production_raw_mod' => '%+g%% raw ore production',
            'mana_production_raw_mod' => '%s% raw mana production',
            'lumber_production_raw_mod' => '%+g%% raw lumber production',
            'food_production_raw_mod' => '%+g%% raw food production',
            'gems_production_raw_mod' => '%+g%% raw gem production',
            'gold_production_raw_mod' => '%+g%% raw gold production',

            'elk_production_raw_from_land' => 'Grants one elk per %1$s acres of %2$s each tick.',

            'ore_production_mod' => '%+g%% ore production',
            'mana_production_mod' => '%+g%% mana production',
            'lumber_production_mod' => '%+g%% lumber production',
            'food_production_mod' => '%+g%% food production',
            'gems_production_mod' => '%+g%% gem collection',
            'gold_production_mod' => '%+g%% gold production',
            'swamp_gas_production_mod' => '%+g%% swamp gas production',
            'miasma_production_mod' => '%+g%% miasma extraction',
            'pearls_production_mod_production' => '%+g%% pearl production',
            'cosmic_alignment_production_mod' => '%+g%% Cosmic Alignment discovery',
            'magma_production_mod' => '%+g%% magma collection',
            'obsidian_production_mod' => '%+g%% obsidian generation',
            'strength_gain_mod' => '%+g%% strength gains',
            'xp_generation_mod' => '%+g%% experience points generation',

            'building_gold_mine_production_mod' => '%+g%% Gold Mine production',
            'building_gold_quarry_production_mod' => '%+g%% Gold Quarry production',

            'food_consumption_mod' => '%+g%% food consumption',

            'tech_production' => '%+g%% XP generation',

            'alchemy_production' => '%s gold production per alchemy',

            'food_production_raw' => '%+g%% raw food production',

            'food_production_docks' => '%+g%% food production from Docks',

            'no_gold_production' => 'No gold production or revenue',
            'no_ore_production' => 'No ore production',
            'no_lumber_production' => 'No lumber production',
            'no_mana_production' => 'No mana production',
            'no_food_production' => 'No food production',
            'no_boat_production' => 'No boat production',
            'no_gems_production' => 'No gem production',

            'rezone_all_land' => 'Rezones %1s%% of all other land types to %2$s.',
            'land_generation_mod' => '%+g%% land generated.',

            'resource_theft' => 'Displaces %2$s%% of the target\'s %1$s and returns it to the caster.',

            'resource_lost_on_invasion' => '%1s%% of %2$s if invaded (excluding overwhelmed invasions).',

            // Military
            'drafting' => '%+g%% drafting',
            'training_time_raw' => '%s ticks training time for military units (does not include Spies, Wizards, or Archmages)',
            'training_costs' => '%+g%% military unit training costs',
            'unit_gold_costs' => '%+g%% military unit gold costs',
            'unit_ore_costs' => '%+g%% military unit ore costs',
            'unit_lumber_costs' => '%+g%% military unit lumber costs',
            'unit_magma_costs' => '%+g%% military unit magma costs',

            'cannot_send_expeditions' => 'Cannot send expeditions',

            'cannot_invade' => 'Cannot invade',
            'cannot_be_invaded' => 'Cannot be invaded',

            'additional_units_trained_from_land' => '1%% extra %1$s%% for every %3$s%% %2$s.',

            'can_send_cannonballs' => 'Can fire cannonballs.',
            'can_send_ammunition_units' => 'Can launch ammunition units.',

            'faster_return' => 'Units return %s ticks faster from invasions',

            'increase_morale' => 'Restores target morale by %+g%% (up to maximum of 100%%).',
            'decrease_morale' => 'Lowers target morale by %+g%% (minimum 0%%).',
            'increase_morale_from_net_victories' => 'Increases morale by %+g%% per net victory (minimum +0%%).',

            'kill_draftees' => 'Kills %1$s%% of the target\'s draftees.',

            'kill_faction_units_percentage' => 'Kills %3$s%% of %1$s %2$s.',
            'kills_faction_units_amount' => 'Kills %3$s%s of %1$s %2$s.',

            'summon_units_from_land' => 'Summon up to %2$s %1$s per acre of %3$s.',
            'summon_units_from_land_by_time' => 'Summon %2$s %1$s per acre of %4$s. Amount summoned increased by %3$s%% per tick into the round.',

            'marshling_random_resource_to_units_conversion' => 'Turns %1$s%% x Wizard Ratio (max %2$s%%) of your %3$s into random amounts of %4$s.',

            'can_kill_immortal' => 'Can kill some immortal units.',

            'no_drafting' => 'No draftees are drafted.',

            'aurei_unit_conversion' => 'Converts %3$s %1$s into %3$s %2$s',

            'no_attrition' => 'No unit attrition',

            'prestige_gains' => '%+g%% prestige gains',

            'defense_from_resource' => '%1$s raw defensive power per %2$s.',
            'offense_from_resource' => '%1$s raw offensive power per %2$s.',

            // Improvements
            'improvements_damage' => 'Destroys %+g%% of the target\'s improvements.',

            // Population
            'population_growth' => '%+g%% population growth rate',
            'kill_peasants' => 'Kills %1$s%% of the target\'s peasants.',
            'peasants_converted' => '%+g%% peasants killed in Mass Graves',

            // Resources
            'destroy_resource' => 'Destroys %2$s%% of the target\'s %1$s.',

            'resource_conversion' => 'Converts %3$s%% of your %1$s to %2$s at a rate of %4$s:1.',

            'resource_conversion_capped' => 'Converts %3$s%% of your %1$s (up to %5$s %1$s) to %2$s at a rate of %4$s:1.',

            'peasant_to_resources_conversion' => 'Sacrifice %1$s%% of your sinners for %2$s each.',

            // Magic
            'damage_from_spells' => '%+g%% damage from spells',
            'chance_to_reflect_spells' => '%+g%% chance to reflect spells',
            'reveal_ops' => 'Reveals the dominion casting spells or spying on you',
            'damage_from_fireball' => '%+g%% damage from fireballs',
            'damage_from_lightning_bolt' => '%+g%% damage from lightning bolts',
            'wizard_strength' => '%+g%% wizard strength',
            'reset_spell_cooldowns' => 'Resets spell cooldowns.',

            'spreads_spell' => 'Spreads %s to any dominion which invades or is invaded by this dominion.',

            'sorcery_damage_suffered' => '%+g%% sorcery damage suffered',
            'sorcery_damage_dealt' => '%+g%% sorcery damage dealt',

            // Espionage
            'disband_spies' => 'Disbands %+g%% of enemy spies.',
            'spy_strength' => '%+g%% spy strength',
            'immortal_spies' => 'Spies become immortal',
            'spy_strength_recovery' => '%+g%% spy strength recovery per tick',

            'gold_theft' => '%+g%% gold lost to theft.',
            'mana_theft' => '%+g%% mana lost to theft.',
            'lumber_theft' => '%+g%% lumber lost to theft.',
            'food_theft' => '%+g%% food lost to theft.',
            'ore_theft' => '%+g%% ore lost to theft.',
            'gems_theft' => '%+g%% gems lost to theft.',
            'kelp_theft' => '%+g%% gems lost to theft.',
            'pearls_theft' => '%+g%% gems lost to theft.',
            'all_theft' => '%+g%% resources lost to theft',

            'blind_to_reptilian_spies_on_info' => 'Spies blind to Reptilian spies on information gathering ops.',
            'blind_to_reptilian_spies_on_theft' => 'Spies blind to Reptilian spies on theft.',
            'blind_to_reptilian_spies_on_sabotage' => 'Spies blind to Reptilian spies on sabotage.',

            'cannot_steal' => 'Cannot steal.',
            'cannot_be_stolen_from' => 'Cannot be stolen from.',

            'cannot_sabotage' => 'Cannot sabotage.',
            'cannot_be_sabotages' => 'Cannot be sabotaged.',

            // Conversions
            'conversions' => '%+g%% conversions',
            'converts_crypt_bodies' => 'Every %1$s %2$ss raise dead a body from the crypt into one %3$s per tick.',
            'convert_enemy_casualties_to_food' => 'Enemy casualties converted to food.',
            'no_conversions' => 'No enemy units or peasants are converted.',

            'cannot_be_converted' => 'Units cannot be converted by the enemy.',

            'convert_peasants_to_prestige' => 'Sacrifice %1$s peasants for %2$ss prestige.',

            'some_win_into_mod' => 'Increases ratio of units turning into another unit on victory.',

            // Casualties
            'increases_enemy_draftee_casualties' => '%+g%% enemy draftee casualties',
            'increases_enemy_casualties_on_offense' => '%+g%% enemy casualties when invading',
            'increases_enemy_casualties_on_defense' => '%+g%% enemy casualties when defending',

            'casualties' => '%+g%% casualties',
            'offensive_casualties' => '%+g%% casualties suffered when invading',
            'defensive_casualties' => '%+g%% casualties suffered when defending',

            // OP/DP
            'offensive_power' => '%+g%% offensive power',
            'defensive_power' => '%+g%% defensive power',

            'target_defensive_power_mod' => '%+g%% defensive modifiers for target',

            'offensive_power_on_retaliation' => '%+g%% offensive power if target recently invaded your realm',

            'defensive_power_vs_blight' => '%+g%% defensive power if attacker has Insect Swarm',
            'offensive_power_vs_blight' => '%+g%% offensive power if target has Insect Swarm',

            'reduces_target_raw_defense_from_land' => 'Targets raw defensive power lowered by %1$s%% for every %2$s%% of your own %3$s, max %4$s%% reduction.',# 1,5,forest,10 # -1% raw DP, per 5% forest, max -10%

            'increases_enemy_casualties_on_offense_from_wizard_ratio' => 'Enemy casualties on offense increased by %+g%% for every 1 wizard ratio.',
            'increases_enemy_casualties_on_defense_from_wizard_ratio' => 'Enemy casualties on defense increased by %+g%% for every 1 wizard ratio.',

            'immune_to_temples' => 'Defensive modifiers are not affected by Temples and any other defensive modifier reductions.',

            'defensive_power_from_peasants' => '%s raw defensive power per peasant',

            'offensive_power_from_devotion' => '%2$s%% offensive power for every tick devoted to %1$s (max %3$s%%).',# 1,5,forest,10 # -1% raw DP, per 5% forest, max -10%
            'defense_from_devotion' => '%2$s%% offensive power for every tick devoted to %1$s (max %3$s%%).',# 1,5,forest,10 # -1% raw DP, per 5% forest, max -10%

            // Improvements
            'invest_bonus' => '%+g%% improvement points from investments made while spell is active',
            'improvements' => '%+g%% improvements',

            // Explore
            'land_discovered' => '%+g%% land discovered on successful invasions',
            'stop_land_generation' => 'Stops land generation from units',
            'cannot_explore' => 'Cannot explore',

            // Buildings and Land
            'no_land_discovered' => 'No land discovered on invasions.',
            'construction_cost' => '%+g%% construction costs',
            'rezone_cost' => '%+g%% rezoning costs',

            // Special
            'opens_portal' => 'Opens a portal required to teleport otherwordly units to enemy lands',

            'stasis' => 'Freezes time. No production, cannot take actions, and cannot have actions taken against it. Units returning from battle continue to return but do not finish and arrive home until Stasis is over.',

            'cannot_take_hostile_actions' => 'Cannot take hostile actions against other dominions.',
            'cannot_receive_hostile_actions' => 'Cannot receive hostile actions against from dominions.',

            // Cult
            'cogency' => 'Wizards and wizard units that fail hostile spells against the Cult have a chance of joining the Cult instead of dying.',
            'enthralling' => 'When the target releases units, there is a chance some of the units join the Cult as Thralls.',
            'persuasion' => 'Captured spies and spy wizards have a chance to join the Cult as Thralls instead of being executed.',
            'treachery' => 'Some resources stolen by the target are instead diverted to the Cult.',

            // Invasion spells
            'kill_peasants_and_converts_for_caster_unit' => 'Kills %1$s%% of target\'s peasants per tick and converts them into Abominations.',
            'annexes_target' => 'Annexes the target, turning them into a vassal.',

            // Artefacts
            'artefact_damage' => '%1$s damage per acre to artefact aegis. Damage increased by %1$s%% for every 1 WPA.',

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
            if($perk->key == 'resource_conversion_capped')
            {
                $fromResourceKey = (string)$perkValue[0];
                $toResourceKey = (string)$perkValue[1];
                $fromRatio = (float)$perkValue[2];
                $toRatio = (int)$perkValue[3];
                $maxFrom = (int)$perkValue[4];

                $fromResource = Resource::where('key', $fromResourceKey)->first();
                $toResource = Resource::where('key', $toResourceKey)->first();
                $maxFrom = number_format($maxFrom);

                $perkValue = [$fromResource->name, $toResource->name, $fromRatio, $toRatio, $maxFrom];

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

            // Special case for dies_into, wins_into ("change_into"), fends_off_into
            if ($perk->key === 'offensive_power_from_devotion' or $perk->key === 'defense_from_devotion')
            {
                $deityKey = $perkValue[0];
                $perTick = (float)$perkValue[1];
                $max = (int)$perkValue[2];

                if($perTick > 0)
                {
                    $perTick = '+'.$perTick;
                }

                $deity = Deity::where('key', $deityKey)->first();

                $perkValue = [$deity->name, $perTick, $max];
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

            if($perk->key === 'kill_faction_units_percentage' or $perk->key === 'kills_faction_units_amount')
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

            if($perk->key === 'aurei_unit_conversion')
            {
                $fromSlot = (int)$perkValue[0];
                $toSlot = (int)$perkValue[1];
                $amount = (float)$perkValue[2];

                $race = Race::where('name', 'Aurei')->firstOrFail();

                $fromUnit = $race->units->filter(static function ($unit) use ($fromSlot)
                    {
                        return ($unit->slot === $fromSlot);
                    })->first();


                $toUnit = $race->units->filter(static function ($unit) use ($toSlot)
                    {
                        return ($unit->slot === $toSlot);
                    })->first();

                $amount = number_format($amount);

                $perkValue = [$fromUnit->name, $toUnit->name, $amount];
                $nestedArrays = false;

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

            if($perk->key === 'marshling_random_resource_to_units_conversion')
            {
                $ratioPerWpa = (float)$perkValue[0];
                $maxRatio = (float)$perkValue[1];
                $resourceKey = (string)$perkValue[2];
                $unitSlots = (array)$perkValue[3];

                // Rue the day this perk is used for other factions.
                $race = Race::where('name', 'Marshling')->firstOrFail();
                $resource = Resource::where('key', $resourceKey)->firstOrFail();

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

                $perkValue = [$ratioPerWpa, $maxRatio, str_plural($resource->name), $unitsString];
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

            // Special case for dies_into, wins_into ("change_into"), fends_off_into
            if ($perk->key === 'defense_from_resource' or $perk->key === 'offense_from_resource' or  $perk->key === 'resource_lost_on_invasion')
            {
                $firstValue = (float)$perkValue[0];
                $resourceKey = (string)$perkValue[1];

                if($firstValue > 1000)
                {
                    $firstValue = number_format($firstValue);
                }

                $resource = Resource::where('key', $resourceKey)->first();


                $perkValue = [$firstValue, $resource->name];
            }

            // Special case for elk_production_raw_from_land
            if($perk->key === 'elk_production_raw_from_land')
            {
                $unitsPerAcre = (float)$perkValue[0];
                $landType = (string)$perkValue[1];

                $perkValue = [number_format(intval(1/$unitsPerAcre)), ucwords($landType)];
                #$nestedArrays = false;
            }

            // Special case for spread_spell
            if($perk->key === 'spread_spell')
            {
                $spellKey = (string)$perkValue[0];

                $perkValue = Spell::where('key', $spellKey)->first()->name;
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
                            #$perkValue[$key] = (is_numeric($value) and $value > 0) ? '+' . $value : $value;
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
                        #$perkValue[$key] = (is_numeric($value) and $value > 0) ? '+' . $value : $value;
                    }

                    $effectStrings[] = vsprintf($spellEffects[$perk->key], $perkValue);
                }
            }
            else
            {
                $perkValue = str_replace('_', ' ',ucwords($perkValue));

                #$perkValue = $perkValue > 0 ? '+' . $perkValue : $perkValue;
                $effectStrings[] = sprintf($spellEffects[$perk->key], $perkValue);
            }
        }

        return $effectStrings;
    }

    public function isSpellAvailableToRace(Race $race, Spell $spell): bool
    {
        $isAvailable = true;

        if(count($spell->exclusive_races) > 0 and !in_array($race->name, $spell->exclusive_races))
        {
            $isAvailable = false;
        }

        if(count($spell->excluded_races) > 0 and in_array($race->name, $spell->excluded_races))
        {
            $isAvailable = false;
        }

        return $isAvailable;
    }

    public function isSpellAvailableToDominion(Dominion $dominion, Spell $spell): bool
    {

        if(isset($spell->deity))
        {
            if(!$dominion->hasDeity())
            {
                return false;
            }
            elseif($dominion->deity->id !== $spell->deity->id)
            {
                return false;
            }
        }

        return true;
    }

    public function spellHasDefensivePerk(Spell $spell): bool
    {
        $defensivePerks = [
            'defensive_power',
            'defense_from_resource',
            'defensive_power_from_peasants'
        ];

        #if($spell->)

        return false;
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

    public function getBreakSpellHelperString(Spell $spell): string
    {
        $breakSpellHelperString = '<br><small class="text-muted">';

        if($spell->break_spell_helper)
        {
            $breakSpellHelperString .= '<br>' . $spell->break_spell_helper;
        }

        $breakSpellHelperString .= '</small>';

        return $breakSpellHelperString;
    }

}
