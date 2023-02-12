<?php

namespace OpenDominion\Helpers;

use OpenDominion\Models\Advancement;
use OpenDominion\Models\Building;
use OpenDominion\Models\Decree;
use OpenDominion\Models\DecreeState;
use OpenDominion\Models\Deity;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;
use OpenDominion\Models\Resource;
use OpenDominion\Models\Unit;

use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;

#use OpenDominion\Helpers\RaceHelper;

use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Services\Dominion\StatsService;

class UnitHelper
{

    public function __construct()
    {
        $this->buildingCalculator = app(BuildingCalculator::class);
        $this->landCalculator = app(LandCalculator::class);

        #$this->raceHelper = app(RaceHelper::class);

        $this->queueService = app(QueueService::class);
        $this->statsService = app(StatsService::class);
    }

    public function getUnitTypes(Race $race /*bool $hideSpecialUnits = false*/): array
    {
        foreach($race->units as $unit)
        {
            $data[] = 'unit' . $unit->slot;
        }

        if(!$race->getPerkValue('cannot_train_spies'))
        {
            $data[] = 'spies';
        }

        if(!$race->getPerkValue('cannot_train_wizards'))
        {
            $data[] = 'wizards';
        }

        if(!$race->getPerkValue('cannot_train_archmages'))
        {
            $data[] = 'archmages';
        }

        return $data;

    }

    public function getUnitName(string $unitType, Race $race): string
    {
        if (in_array($unitType, ['spies', 'wizards', 'archmages'], true))
        {
            return ucfirst($unitType);
        }

        $unitSlot = (((int)str_replace('unit', '', $unitType)) - 1);

        return $race->units[$unitSlot]->name;
    }

    public function isUnitOffensiveSpy(Unit $unit): bool
    {
        return ($unit->getPerkValue('counts_as_spy') or $unit->getPerkValue('counts_as_spy_offense'));
    }

    public function isUnitOffensiveWizard(Unit $unit): bool
    {
        return (
                $unit->getPerkValue('counts_as_wizard') or 
                $unit->getPerkValue('counts_as_wizard_offense') or 
                $unit->getPerkValue('counts_as_wizard_on_offense_from_land') or 
                $unit->getPerkValue('counts_as_wizard_from_land')
            );
    }

    public function getUnitHelpString(string $unitType, Race $race, array $unitPowerWithPerk = null): ?string
    {

        $helpStrings = [
            'draftees' => 'Used for exploring and training other units.',
            'spies' => 'Used for espionage.',
            'wizards' => 'Used for casting offensive spells.',
            'archmages' => 'Used for casting offensive spells. Twice as strong as regular wizards.',
        ];

        $perkTypeStrings = [
            // Conversions
            'conversion' => 'Converts enemy casualties into %s.',
            'staggered_conversion' => 'Converts some enemy casualties into %2$s against dominions %1$s%%+ of your size.',
            'cannot_be_converted' => 'Unit cannot be converted.',
            'vampiric_conversion' => 'Spreads vampirism.',
            'psionic_conversion' => 'Psionically converts enemy units to %2$s.',
            
            'displaced_peasants_conversion' => 'Converts displaced enemy peasants into %s.',
            'converts_displaced_peasants_into_resource' => 'Converts displaced enemy peasants into %1$s %2$s.',
            'displaced_peasants_random_split_conversion' => 'Converts %1$s%% to %2$s%% of displaced enemy peasants into %3$s, the rest to %4$s.',
            'strength_conversion' => 'Converts enemy casualties with %1$s or less raw OP or DP into %2$s and stronger enemies into %3$s.',
            'value_conversion' => 'Fuses %1$sx of killed enemy raw OP or DP into %2$s.',
            'casualties_conversion' => 'Converts enemy casualties into %s.',

            'passive_conversion' => 'Converts %3$s %1$s into %2$s each tick.',

            'captures_displaced_peasants' => 'Captures enemy displaced enemy peasants.',
            'kills_displaced_peasants' => 'Kills own displaced peasants.',

            // OP/DP related
            'offensive_power_mod' => '%+g%% offensive power for each unit trained.',
            'defensive_power_mod' => '%+g%% defensive power for each unit trained.',

            'offensive_power_vs_no_deity' => '+%s offensive power vs dominions with no deity.',
            'defensive_power_vs_no_deity' => '+%s defensive power vs dominions with no deity.',

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

            'offense_from_wizard_ratio' => 'Offense increased by %1$s * Wizard Ratio (offensive).',
            'defense_from_wizard_ratio' => 'Defense increased by %1$s * Wizard Ratio (defensive).',

            'fixed_offense_from_wizard_ratio' => 'Offense increased by %1$s if Wizard Ratio on offense is at least %2$s.',
            'fixed_defense_from_wizard_ratio' => 'Defense increased by %1$s if Wizard Ratio on defense is at least %2$s.',

            'offense_from_spy_ratio' => 'Offense increased by %1$s * Spy Ratio (offensive).',
            'defense_from_spy_ratio' => 'Offense increased by %1$s * Spy Ratio (defensive).',
            'offense_from_spy_ratio_capped' => 'Offense increased by %1$s * Spy Ratio (offensive), (max +%2$s).',
            'defense_from_spy_ratio_capped' => 'Offense increased by %1$s * Spy Ratio (defensive), (max +%2$s).',

            'offense_if_recently_invaded' => 'Offense increased by %1$s if recently invaded (in the last %2$s ticks, includes non-overwhelmed failed invasions).',
            'defense_if_recently_invaded' => 'Defense increased by %1$s if recently invaded (in the last %2$s ticks, includes non-overwhelmed failed invasions).',

            'offense_if_recently_victorious' => 'Offense increased by %1$s if recently victorious (in the last %2$s ticks).',
            'defense_if_recently_victorious' => 'Defense increased by %1$s if recently victorious (in the last %2$s ticks).',

            'offense_if_target_recently_invaded' => 'Offense increased by %1$s if target was invaded (in the last six hours, includes non-overwhelmed failed invasions).',
            'defense_if_target_recently_invaded' => 'Defense increased by %1$s if invader was invaded (in the last six hours, includes non-overwhelmed failed invasions).',

            'offense_if_target_is_larger' => 'Offense increased by %1$s if target is larger than you.',

            'offense_per_tick' => 'Offense increased by %1$s for each tick of the round (no max).',
            'defense_per_tick' => 'Defense increased by %1$s for each tick of the round (no max).',

            'offense_from_time' => 'Offense increased by %3$s between %1$s:00 and %2$s:00.',
            'defense_from_time' => 'Defense increased by %3$s between %1$s:00 and %2$s:00.',

            'offense_vs_barren_land' => 'Offense increased by 1 against every %1$s%% barren land of target (max +%2$s).',

            'offense_vs_resource' => 'Offense increased by 1 for every %2$s %1$s target has (max +%3$s).',
            'defense_vs_resource' => 'Defense increased by 1 for every %2$s %1$s attacker has (max +%3$s).',

            'offense_from_resource' => 'Offense increased by 1 for every %2$s %1$s (no max).',
            'offense_from_resource_exhausting' => 'Offense increased by 1 for every %2$s %1$s (no max). All %1$s is spent when the unit attacks.',
            'defense_from_resource' => 'Defense increased by 1 for every %2$s %1$s (no max).',
            'offense_from_resource_capped_exhausting' => 'Offense increased by %1$s if you have %2$s %3$s. The %3$s is spent when the unit attacks.',

            'offense_from_military_percentage' => 'Gains +1x(Military / Total Population) OP, max +1 at 100%% military.',

            'offense_from_victories' => 'Offense increased by %1$s for every victory (max +%2$s). Only successful attacks over 75%% count as victories.',
            'defense_from_victories' => 'Defense increased by %1$s for every victory (max +%2$s). Only successful attacks over 75%% count as victories.',

            'offense_from_net_victories' => 'Offense increased by %1$s for every net victory (max +%2$s, min 0).',
            'defense_from_net_victories' => 'Defense increased by %1$s for every net victory (max +%2$s, min 0).',

            'offense_from_recent_victories' => 'Offense increased by %1$s for every victory in the last %2$s ticks.',
            'defense_from_recent_victories' => 'Defense increased by %1$s for every victory in the last %2$s ticks.',

            'defense_mob' => 'Defense increased by +%1$s if your troops at home (including units with no defensive power) outnumber the invading units.',
            'offense_mob' => 'Offense increased by +%1$s if the troops you send outnumber the target\'s entire military at home (including units with no defensive power).',

            'offense_from_being_outnumbered' => 'Offense increased by +%1$s if total units sent are outnumbered by the target\'s entire military at home (including draftees and units with no defensive power).',
            'defense_from_being_outnumbered' => 'Defense increased by +%1$s if total units at home defending are outnumbered by the invading units.',

            'offense_from_spell' => 'Offense increased by %2$s if the spell %1$s is active.',
            'defense_from_spell' => 'Defense increased by %2$s if the spell %1$s is active.',

            'offense_from_advancements' => '+%3$s offensive power from %1$s level %2$s.',
            'defense_from_advancements' => '+%3$s defensive power from %1$s level %2$s.',

            'offense_from_title' => 'Offense increased by %2$s if ruled by a %1$s.',
            'defense_from_title' => 'Defense increased by %2$s if ruled by a %1$s.',

            'offense_from_deity' => 'Offense increased by %2$s if devoted to %1$s.',
            'defense_from_deity' => 'Defense increased by %2$s if devoted to %1$s.',

            'offense_vs_other_deity' => 'Offense increased by %1$s if target is devoted to no or another deity.',

            'offense_from_devotion' => 'Offense increased by %2$s for every tick devoted to %1$s (max +%3$s).',
            'defense_from_devotion' => 'Defense increased by %2$s for every tick devoted to %1$s (max +%3$s).',

            'defense_from_per_improvement' => 'Defense increased by %1$s for every individual improvement you have with at least %2$s points invested.',
            'offense_from_per_improvement' => 'Offense increased by %1$s for every individual improvement you have with at least %2$s points invested.',

            'offense_from_improvement_points' => 'Offense increased by %1$s for %2$s points invested (max +%3$s).',
            'defense_from_improvement_points' => 'Defense increased by %1$s for %2$s points invested (max +%3$s).',

            // Spy related
            'counts_as_spy' => 'Counts as %s spy.',
            'counts_as_spy_defense' => 'Counts as %s of a spy on defense.',
            'counts_as_spy_offense' => 'Counts as %s of a spy on offense.',

            'counts_as_spy_defense_from_time' => 'Counts as %3$s of a spy on defense between %1$s:00 and %2$s:00.',
            'counts_as_spy_offense_from_time' => 'Counts as %3$s of a spy on offense between %1$s:00 and %2$s:00.',

            'immortal_spy' => 'Immortal spy (cannot be killed when performing espionage).',
            'minimum_spa_to_train' => 'Must have at least %s Spy Ratio (on offense) to train.',

            'spy_from_title' => 'Counts as additional %2$s of a spy (offense and defense) if ruled by a %1$s.',

            'protects_resource_from_theft' => 'Protects %2$s %1$s from theft when at home.',

            'theft_carry_capacity' => '%+g%% max theft carry.',

            // Wizard related
            'counts_as_wizard' => 'Counts as %s wizard.',
            'counts_as_wizard_defense' => 'Counts as %s of a wizard on defense.',
            'counts_as_wizard_offense' => 'Counts as %s of a wizard on offense.',

            'counts_as_wizard_from_land' => 'Counts as %1$s wizard for every %2$s%% %3$s.',
            'counts_as_wizard_on_defense_from_land' => 'Counts as %1$s wizard on defense for every %2$s%% %3$s.',
            'counts_as_wizard_on_offense_from_land' => 'Counts as %1$s wizard on offense for every %2$s%% %3$s.',

            'immortal_wizard' => 'Immortal wizard (cannot be killed when casting spells).',
            'minimum_wpa_to_train' => 'Must have at least %s Wizard Ratio (on offense) to train.',
            'wizard_from_title' => 'Counts as additional %2$s of a wizard (offense and defense) if ruled by a %1$s.',

            // Casualties and death related
            'casualties' => '%+g%% casualties.',
            'casualties_on_defense' => '%+g%% casualties on defense.',
            'casualties_on_offense' => '%+g%% casualties on offense.',

            'casualties_from_wizard_ratio' => '%+g%% * Wizard Ratio casualties.',
            'immortal_from_wpa' => 'Immortal if Wizard Ratio is at least %s on offense (if invading) or on defense (if defending).',

            'casualties_from_spy_ratio' => '%+g%% * Spy Ratio casualties.',
            'immortal_from_spa' => 'Immortal if Spy Ratio is at least %s on offense (if invading) or on defense (if defending).',

            'casualties_on_victory' => '%+g%% casualties when successfully invading.',
            'casualties_on_fending_off' => '%+g%% casualties when successfully fending off an invasion.',

            'fixed_casualties' => 'Always suffers %d%% casualties.',
            'fixed_casualties_if_overwhelmed' => 'Always suffers %+g%% casualties if overwhelmed.',
            'casualties_from_title' => '%2$s%% fewer casualties if ruled by a %1$s.',
            'casualties_from_deity' => '%2$s%% fewer casualties if devoted to %1$s.', # Made by AI, use later?
            'casualties_from_recent_invasions_sent' => '%+g%% casualties per invasion sent in the last 12 ticks.',

            'immortal' => 'Immortal in combat.',
            'true_immortal' => 'Immortal in combat.',
            'spirit_immortal' => 'Immortal on offense and on succesful defense. Dies if successfully invaded.',
            'immortal_on_victory' => 'Immortal on invasion if victorious.',
            'immortal_on_fending_off' => 'Immortal if successfully fending off invader.',

            #'kills_immortal' => 'Kills immortal units.',

            'reduces_spell_damage' => 'Reduces spell damage suffered by (([Units] / [Land]) * %s)%%.',

            'reduces_casualties' => 'Reduces casualties.',
            'reduces_enemy_casualties' => 'Reduces casualties.',

            'increases_enemy_casualties' => 'Increases enemy casualties.',
            'increases_enemy_casualties_on_offense' => 'Increases enemy casualties on offense (defender suffers more casualties).',
            'increases_enemy_casualties_on_defense' => 'Increases enemy casualties on defense (attacker suffers more casualties).',

            'increases_own_casualties' => 'Increases own casualties.',
            'increases_own_casualties_on_offense' => 'Increases own casualties on offense.',
            'increases_own_casualties_on_defense' => 'Increases own casualties on defense.',

            'casualties_on_defense_from_land' => 'Casualties on defense reduced by 1%% for every %2$s%% %1$ss (max %3$s%% reduction).',
            'casualties_on_offense_from_land' => 'Casualties on offense reduced by 1% for every %2$s%% %1$ss (max %3$s%% reduction).',

            'casualties_on_defense_vs_land' => 'Casualties on defense reduced by 1%% against every %2$s%% %1$ss of attacker (max %3$s%% reduction).',
            'casualties_on_offense_vs_land' => 'Casualties on offense reduced by 1%% against every %2$s%% %1$ss of target (max %3$s%% reduction).',

            'only_dies_vs_raw_power' => 'Only dies against units with %s or more raw military power.',

            'dies_into' => 'Upon death, returns as %1$s.',# On defense, the change is instant. On offense, the new unit returns from battle with the other units.',
            'dies_into_wizard' => 'Upon death, returns as wizard.',
            'wins_into' => 'Upon successul invasion, returns as %s.',
            'fends_off_into' => 'Upon successully fending off invasion, becomes %s.',
            'dies_into_multiple' => 'Upon death, returns as %2$s %1$s.',# On defense, the change is instant. On offense, the new unit returns from battle with the other units.',

            'some_win_into' => 'Upon successul invasion, %1$s%% of these units return as %2$s.',
            'some_fend_off_into' => 'Upon successully fending off invasion, %1$s%% of these units become %2$s.',
            'some_die_into' => 'Upon death, %1$s%% of these units become %2$s.',

            'dies_into_resource' => 'Upon death, returns as %1$s %2$s.',
            'dies_into_resource_on_success' => 'Upon death on successful invasions or upon death on successfully fending off, returns as %1$s %2$s.',

            'kills_into_resource_per_casualty' => 'Kills enemy units into %1$s %2$s each.',
            'kills_into_resource_per_casualty_on_success' => 'On successful invasions or on successfully fending off, kills enemy units into %1$s %2$s.',
            'kills_into_resources_per_casualty' => 'Kills enemy units into %1$s %2$s.',
            'kills_into_resources_per_casualty_on_success' => 'On successful invasions or on successfully fending off, kills enemy units into returns as %1$s %2$s.',

            'kills_into_resource_per_value' => 'Kills enemy units into %1$s %2$s per 1 raw OP or DP.',
            'kills_into_resource_per_value_on_success' => 'On successful invasions or on successfully fending off, kills enemy units into %1$s %2$s per 1 raw OP or DP.',
            'kills_into_resources_per_value' => 'Kills enemy units into %1$s %2$s per 1 raw OP or DP.',
            'kills_into_resources_per_value_on_success' => 'On successful invasions or on successfully fending off, kills enemy units into %1$s %2$s per 1 raw OP or DP.',

            'releases_into_resource' => 'Releases into %1$s %2$s.',
            'releases_into_resources' => 'Releases into %1$s %2$s.',

            'dies_into_on_offense' => 'Upon death when invading, returns as %1$s.',
            'dies_into_on_defense' => 'Upon death when defending, returns as %1$s.',
            'dies_into_on_defense_instantly' => 'Upon death when defending, instantly becomes %1$s.',
            'dies_into_multiple_on_offense' => 'Upon death when invading, returns as %2$s %1$s.',
            'dies_into_multiple_on_defense' => 'Upon death when defending, returns as %2$s %1$s.',
            'dies_into_multiple_on_defense_instantly' => 'Upon death when defending, instantly becomes %2$s %1$s.',

            'dies_into_multiple_on_victory' => 'Upon death in succesful combat, returns as %2$s %1$s. If unsuccessful, returns as %3$s %1$s.',# On defense, the change is instant. On offense, the new unit returns from battle with the other units.',

            // Resource related
            'gold_production_raw' => 'Produces %s gold/tick.',
            'food_production_raw' => 'Produces %s food/tick.',
            'lumber_production_raw' => 'Gathers %s lumber/tick.',
            'ore_production_raw' => 'Mines %s ore/tick.',
            'mana_production_raw' => 'Gathers %s mana/tick.',
            'gems_production_raw' => 'Mines %s gems/tick.',
            'blood_production_raw' => 'Gathers %s blood/tick.',
            'swamp_gas_production_raw' => 'Produces %s swamp gas/tick.',
            'miasma_production_raw' => 'Generates %s miasma/tick.',
            'gunpowder_production_raw' => 'Produces %s gunpowder/tick.',

            'gold_production_raw_from_pairing' => 'Produces %2$s gold/tick if paired with %1$s at home.',
            'food_production_raw_from_pairing' => 'Produces %2$s food/tick if paired with %1$s at home.',
            'lumber_production_raw_from_pairing' => 'Gathers %2$s lumber/tick if paired with %1$s at home.',
            'ore_production_raw_from_pairing' => 'Mines %2$s ore/tick if paired with %1$s at home.',
            'mana_production_raw_from_pairing' => 'Gathers %2$s mana/tick if paired with %1$s at home.',
            'gems_production_raw_from_pairing' => 'Mines %2$s gems/tick if paired with %1$s at home.',
            'blood_production_raw_from_pairing' => 'Gathers %2$s blood/tick if paired with %1$s at home.',
            'gunpowder_production_raw_from_pairing' => 'Prepares %2$s gunpowder/tick if paired with %1$s at home.',

            'mana_production_raw_per_victory' => 'Gathers %s mana/tick per victory.',
            'gems_production_raw_per_victory' => 'Mines %s gems/tick per victory.',         

            'gold_production_raw_from_building_pairing' => '%3$s gold/tick (up to %1$s units per %2$s).',
            'food_production_raw_from_building_pairing' => '%3$s food/tick (up to %1$s units per %2$s).',
            'lumber_production_raw_from_building_pairing' => '%3$s lumber/tick (up to %1$s units per %2$s).',
            'ore_production_raw_from_building_pairing' => '%3$s ore/tick (up to %1$s units per %2$s).',
            'gems_production_raw_from_building_pairing' => '%3$s gems/tick (up to %1$s units per %2$s).',
            'gunpowder_production_raw_from_building_pairing' => '%3$s gunpowder/tick (up to %1$s units per %2$s).',

            'xp_generation_raw' => 'Each unit generates %s experience points per tick.',

            'food_consumption_raw' => 'Eats %s extra food.',

            'decay_protection' => 'Each units protects %1$s %2$s per tick from decay.',

            'plunders' => 'Plunders up to %2$s %1$s on attack.', # Multiple resources
            'plunder' => 'Plunders up to %2$s %1$s on attack.', # Single resource

            'destroy_resources_on_victory' => 'Destroys up to %2$s %1$s on victory.', # Multiple resources
            'destroy_resource_on_victory' => 'Destroys up to %2$s %1$s on victory.', # Single resource

            'destroy_resources' => 'Destroys up to %2$s %1$s on attack.', # Multiple resources
            'destroy_resource' => 'Destroys up to %2$s %1$s on attack.', # Single resource

            'mana_drain' => 'Each unit drains %s mana per tick.',
            'gold_upkeep_raw' => 'Costs %s gold per tick.',
            'lumber_upkeep_raw' => 'Costs %s lumber per tick.',
            'ore_upkeep_raw' => 'Costs %s ore per tick.',
            'brimmer_upkeep_raw' => 'Uses %s brimmer per tick.',
            'mana_upkeep_raw' => 'Drains %s mana per tick.',

            'production_from_title' => 'Produces %3$s %2$s per tick if ruled by a %1$s.',

            'spends_resource_on_offense' => 'Requires and uses up %2$s %1$s on attack.',
            'spends_resource_on_defense' => 'Requires and uses up %2$s %1$s to defend.',

            'gunpowder_max_storage' => 'Max %s gunpowder stored per Cannon.',

            // Return time
            'faster_return' => 'Returns %s ticks faster from battle.',
            'land_per_tick' => 'Discovers land each tick:<br><code>(%1$s * (1 - ([Land Size]/12,000)))</code>',
            #'sendable_with_zero_op' => 'Equippable (can be sent on invasion despite unit having 0 offensive power).', # Hidden
            'faster_return_if_paired' => 'Returns %2$s ticks faster if paired with a %1$s.',
            'faster_return_if_paired_multiple' => 'Returns %2$s ticks faster if paired with a %1$s (max %3$s per %1$s).',
            'instant_return' => 'Returns instantly from invasion.',

            'faster_return_from_time' => 'Returns %3$s ticks faster from battle if sent out between %1$s:00 and %2$s:00.',

            // Training
            'cannot_be_trained' => 'Cannot be trained.',
            'instant_training' => 'Appears immediately.',
            'does_not_kill' => 'Does not kill other units.',
            'no_draftee' => 'No draftee required to train.',
            'no_draftee_on_release' => 'No draftee returned if released.',

            'unit_production' => 'Produces %2$s %1$s per tick.',
            'attrition' => '%1$s%% attrition rate per tick.',
            'cannot_be_released' => 'Cannot be released',
            'reduces_unit_costs' => 'Reduces training costs by %1$s%% for every 1%% of population consisting of this unit. Max %2$s%% reduction.',

            'advancements_required_to_train' => 'Must have %1$s level %2$s to train this unit.',

            'attrition_protection_from_net_victories' => '%s unit protected from attrition per net victory.',

            // Limits
            'pairing_limit' => 'You can at most have %2$s of this unit per %1$s. Training is limited to number of %1$s at home.',
            'pairing_limit_including_away' => 'You can at most have %2$s of this unit per %1$s.',
            'land_limit' => 'You can at most have %2$s of this unit per acre of %1$s.',
            'total_land_limit' => 'You can at most have %2$s of this unit %1$s land.',
            'building_limit' => 'You can at most have %2$s of this unit per %1$s.',
            'building_limit_fixed' => 'You can at most have %2$s of this unit per %1$s.',
            'building_limit_prestige' => 'You can at most have %2$s of this unit per %1$s. Increased by prestige multiplier.',

            'victories_limit' => 'You can at most have %2$s of this unit per %1$s %2$s.',
            'net_victories_limit' => 'You can at most have %1$s of this unit per %2$s net victory.',

            'archmage_limit' => 'You can at most have %1$s of this unit per Archmage.',
            'wizard_limit' => 'You can at most have %1$s of this unit per Wizard.',
            'spy_limit' => 'You can at most have %1$s of this unit per Spy.',

            'amount_limit' => 'You can at most have %1$s of this unit.',

            // Population
            'does_not_count_as_population' => 'Does not count towards population. No housing required.',
            'population_growth' => 'Increases population growth by 2%% for every 1%% of population.',

            'houses_military_units' => 'Houses %1$s military units.',
            'norse_unit1_housing' => 'Houses %1$s Warriors.',
            'houses_people' => 'Houses %1$s people.',

            'provides_jobs' => 'Provides %1$s jobs.',

            'housing_count' => 'Takes up %1$s housing.',

            // Other
            'increases_morale_by_population' => '%+g%% base morale for every 1%% of population.',
            'increases_morale_fixed' => '%+g base morale.',
            'increases_morale_gains' => '%+g%% morale gains for every 1%% of units sent.',
            'increases_morale_gains_fixed' => 'Increases morale gains by %+g%% each of this unit sent.',

            'increases_prestige_interest' => '%+g%% prestige interest for each of this unit.',
            
            'lowers_target_morale_on_successful_invasion' => 'On successful invasion, lowers target\'s morale by %d%%.',

            'increases_prestige_gains' => '%+g%% prestige gains for every 1%% of units sent.',

            'stuns_units' => 'Stuns some units with up to %1$s DP for %2$s ticks, whereafter the units return unharmed.',

            'gold_improvements' => 'Increases improvement points from gold by (([Units]/[Land])/100)%%.',

            'cost_increase_to_train_per_unit' => 'Cost increased by %d%% each.',

            // Decree
            'decree_state_required_to_send' => '%1$s decree must be issued in %2$s state to send this unit.',

            // Damage
            'burns_peasants_on_attack' => 'Burns up to (%s%% x ([Raw OP] / [Total Raw OP]) x [OP:DP Ratio]) peasants on attack, unless overwhelmed.',
            'damages_improvements_on_attack' => 'Damages target\'s improvements by %s improvement points.',
            'eats_peasants_on_attack' => 'Eats %s peasants on invasion.',
            'eats_draftees_on_attack' => 'Eats %s draftees on invasion.',

            // Demonic
            'kill_peasants' => 'Eats %s peasants per tick.',

            // Myconid
            'decreases_info_ops_accuracy' => 'Decreases accuracy of Clear Sights performed on the dominion by 0.50%% for every 1%% of total military made up of this unit.',
        ];

        // Get unit - same logic as military page
        if (in_array($unitType, ['unit1', 'unit2', 'unit3', 'unit4', 'unit5', 'unit6', 'unit7', 'unit8', 'unit9', 'unit10']))
        {
            $unit = $race->units->filter(function ($unit) use ($unitType) {
                return ($unit->slot == (int)str_replace('unit', '', $unitType));
            })->first();

            if($unitPowerWithPerk)
            {
                $unitOp = $unitPowerWithPerk[0];
                $unitDp = $unitPowerWithPerk[1];
            }
            else
            {
                $unitOp = $unit->power_offense;
                $unitDp = $unit->power_defense;
            }

            $helpStrings[$unitType] = '<li><span data-toggle="tooltip" data-placement="top" title="Offensive power">OP: '. display_number_format($unitOp) . '</span>';
            $helpStrings[$unitType] .= ' / <span data-toggle="tooltip" data-placement="top" title="Defensive power">DP: ' . display_number_format($unitDp) . '</span>';

            if(!$race->getUnitPerkValueForUnitSlot($unit->slot,'cannot_be_trained'))
            {
                $helpStrings[$unitType] .= ' / <span data-toggle="tooltip" data-placement="top" title="Ticks to train">T: ' . $unit->training_time . '</span>';
            }

            $helpStrings[$unitType] .= '</li>';

            if($unit->deity)
            {
                $helpStrings[$unitType] .= '<li>Must be devoted to ' . $unit->deity->name . ' to train or send this unit.</li>';
            }

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

                // Special case for pairings
                if ($perk->key === 'defense_from_pairing' or
                    $perk->key === 'offense_from_pairing' or
                    $perk->key === 'pairing_limit' or
                    $perk->key === 'pairing_limit_including_away' or
                    $perk->key === 'gold_production_raw_from_pairing' or
                    $perk->key === 'lumber_production_raw_from_pairing' or
                    $perk->key === 'ore_production_raw_from_pairing' or
                    $perk->key === 'gems_production_raw_from_pairing' or
                    $perk->key === 'food_production_raw_from_pairing' or
                    $perk->key === 'blood_production_raw_from_pairing' or
                    $perk->key === 'gunpowder_production_raw_from_pairing'
                    )
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

                    $perkValue[1] = number_format($perkValue[1], 2);

                    #$perkValue = []
                }



                // Special case for casualties, casualties_on_defense, and casualties_on_offense
                if ($perk->key === 'casualties' || $perk->key === 'casualties_on_defense' || $perk->key === 'casualties_on_offense')
                {
                    $value = (float)$perkValue;

                    if($perkValue > 0)
                    {
                        $perkValue = '+' . $value;
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

                // Special case for returns faster if pairings (multiple units per super unit)
                if ($perk->key === 'faster_return_if_paired_multiple')
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

                // Special case for conversions
                if ($perk->key === 'psionic_conversion')
                {
                    $strengthMultiplier = (float)$perkValue[0];
                    $slotConvertedTo = (int)$perkValue[1];
                    
                    $unitToConvertTo = $race->units->filter(static function ($unit) use ($slotConvertedTo) {
                        return ($unit->slot === $slotConvertedTo);
                    })->first();

                    $perkValue = [$strengthMultiplier, $unitToConvertTo->name];
                }

                // Special case for displaced_peasants_random_split_conversion
                if ($perk->key === 'displaced_peasants_random_split_conversion')
                {
                    $rangeMin = (int)$perkValue[0];
                    $rangeMax = (int)$perkValue[1];
                    $primarySlotTo = (int)$perkValue[2];
                    $fallbackSlotTo = (int)$perkValue[3];

                    $primaryUnitTo = $race->units->filter(static function ($unit) use ($primarySlotTo) {
                        return ($unit->slot === $primarySlotTo);
                    })->first();

                    $fallbackUnitTo = $race->units->filter(static function ($unit) use ($fallbackSlotTo) {
                        return ($unit->slot === $fallbackSlotTo);
                    })->first();

                    $perkValue = [$rangeMin, $rangeMax, str_plural($primaryUnitTo->name), str_plural($fallbackUnitTo->name)];
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

                    $perkValue = [str_plural($unitFrom->name), str_plural($unitTo->name), $rate];
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

                if($perk->key === 'plunders')
                {
                    foreach ($perkValue as $index => $plunder) {
                        [$resource, $amount] = $plunder;

                        $perkValue[$index][1] = generate_sentence_from_array([$amount]);
                    }
                }

                if($perk->key === 'destroy_resource_on_victory' or $perk->key === 'spends_resource_on_offense' or $perk->key === 'spends_resource_on_defense')
                {
                    $resourceKey = (string)$perkValue[0];
                    $amount = (float)$perkValue[1];
                    $resource = Resource::where('key', $resourceKey)->firstOrFail();

                    # Don't pluralise some resources
                    if(in_array($resourceKey, ['brimmer','gunpowder']))
                    {
                        $perkValue = [$resource->name, $amount];
                    }
                    else
                    {
                        $perkValue = [str_plural($resource->name, $amount), $amount];
                    }

                }

                if($perk->key === 'offense_from_improvement_points' or $perk->key === 'defense_from_improvement_points')
                {
                    $perkValue = [(float)$perkValue[0], number_format($perkValue[1]), (float)$perkValue[2]];
                }

                // Special case for dies_into, wins_into ("change_into"), fends_off_into
                if (
                      $perk->key === 'dies_into'
                      or $perk->key === 'dies_into_on_offense'
                      or $perk->key === 'dies_into_on_defense'
                      or $perk->key === 'dies_into_on_defense_instantly'
                      or $perk->key === 'wins_into'
                      or $perk->key === 'fends_off_into'
                  )
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

                // Special case for some_die_into, some_win_into, some_fend_off_into
                if (
                      $perk->key === 'some_die_into'
                      or $perk->key === 'some_win_into'
                      or $perk->key === 'some_fend_off_into'
                  )
                {
                    $ratio = (float)$perkValue[0];
                    $unitSlotsToConvertTo = (int)$perkValue[1];
                    $unitNamesToConvertTo = [];

                    $unitToConvertTo = $race->units->filter(static function ($unit) use ($unitSlotsToConvertTo) {
                        return ($unit->slot === $unitSlotsToConvertTo);
                    })->first();

                    $perkValue[0] = $ratio;
                    $perkValue[1] = str_plural($unitToConvertTo->name);
                }

                $productionBuildingPairingPerks = [
                    'gold_production_raw_from_building_pairing',
                    'gunpowder_production_raw_from_building_pairing',
                    'food_production_raw_from_building_pairing',
                    'lumber_production_raw_from_building_pairing',
                    'ore_production_raw_from_building_pairing',
                    'gems_production_raw_from_building_pairing',
                    'mana_production_raw_from_building_pairing',
                ];
                if(in_array($perk->key, $productionBuildingPairingPerks))
                {
                    $unitsPerBuilding = (float)$perkValue[0];
                    $buildingKey = (string)$perkValue[1];
                    $amountProduced = (float)$perkValue[2];

                    $building = Building::where('key', $buildingKey)->first();

                    $perkValue = [intval(1/$unitsPerBuilding), $building->name, $amountProduced];
                    $nestedArrays = false;
                }


                if(in_array($perk->key, ['building_limit', 'building_limit_fixed', 'building_limit_prestige']))
                {
                    $unitsPerBuilding = (float)$perkValue[1];
                    $buildingKey = (string)$perkValue[0];

                    $building = Building::where('key', $buildingKey)->first();

                    $perkValue = [$building->name, $unitsPerBuilding];
                }

                if($perk->key === 'offense_from_devotion' or $perk->key === 'defense_from_devotion')
                {
                    $deityKey = (string)$perkValue[0];
                    $perTick = (float)$perkValue[1];
                    $max = (float)$perkValue[2];

                    $deity = Deity::where('key', $deityKey)->first();

                    $perkValue = [$deity->name, floatval($perTick), floatval($max)];
                    $nestedArrays = false;
                }

                if($perk->key === 'advancements_required_to_train')
                {
                    foreach($perkValue as $index => $advancementSet)
                    {
                        $advancementKey = (string)$advancementSet[0];
                        $levelRequired = (int)$advancementSet[1];
    
                        $advancement = Advancement::where('key', $advancementKey)->first();
    
                        $perkValue[] = [$advancement->name, $levelRequired];
                        unset($perkValue[$index]);
                    }
                }

                if($perk->key === 'offense_from_advancements' or $perk->key === 'defense_from_advancements')
                {
                    foreach($perkValue as $index => $advancementSet)
                    {
                        $advancementKey = (string)$advancementSet[0];
                        $levelRequired = (int)$advancementSet[1];
                        $power = (float)$advancementSet[2];
    
                        $advancement = Advancement::where('key', $advancementKey)->first();
    
                        $perkValue[] = [$advancement->name, $levelRequired, floatval($power)];
                        unset($perkValue[$index]);
                    }

                    #$nestedArrays = false;
                }
               
                // Special case for dies_into
                if (
                        $perk->key === 'dies_into_multiple'
                        or $perk->key === 'dies_into_multiple_on_offense'
                        or $perk->key === 'dies_into_multiple_on_defense'
                        or $perk->key === 'dies_into_multiple_on_defense_instantly'
                        or $perk->key === 'dies_into_multiple_on_victory'
                    )
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
                    $unitSlotToProduce = (int)$perkValue[0];
                    $amountToProduce = (float)($perkValue[1]);

                    $unitToProduce = $race->units->filter(static function ($unit) use ($unitSlotToProduce) {
                        return ($unit->slot === $unitSlotToProduce);
                    })->first();

                    $unitNameToProduce[] = str_plural($unitToProduce->name);

                    $perkValue[0] = generate_sentence_from_array($unitNameToProduce);
                    $perkValue[1] = $amountToProduce;
                }

                // Special case for unit_production
                if ($perk->key === 'decree_state_required_to_send')
                {
                    $decreeStateKey = (string)$perkValue;
                    $decreeState = DecreeState::where('key', $decreeStateKey)->first();
                    $decree = Decree::where('id', $decreeState->decree_id)->first();

                    $perkValue = [$decree->name, $decreeState->name];
                }

                if($perk->key === 'victories_limit' or 'net_victories_limit')
                {
                #    $perkValue = [$perkValue, str_plural('victory', $perkValue)];
                #    $nestedArrays = false;
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

    public function getUnitAttributes(Unit $unit): array
    {
        foreach($unit->type as $attribute)
        {
            $attributes[] = $attribute;
        }

        return $attributes;
    }

    public function getUnitAttributesList(string $unitType, Race $race = null): string
    {

        $attributeString = '<ul>';

        if ($race && in_array($unitType, ['unit1', 'unit2', 'unit3', 'unit4', 'unit5', 'unit6', 'unit7', 'unit8',  'unit9', 'unit10']))
        {
            $unit = $race->units->filter(function ($unit) use ($unitType) {
                return ($unit->slot == (int)str_replace('unit', '', $unitType));
            })->first();
        }

        foreach($unit->type as $attribute)
        {
            $attributes[] = ucwords($attribute);
        }

        sort($attributes);

        return generate_sentence_from_array($attributes);

        /*
        sort($attributes);
        $attributeString = '</ul>';
        foreach($attributes as $attribute)
        {
            $attributeString .= '<li>' . ucwords($attribute) . '</li>';
        }

        $attributeString .= '</ul>';
        return $attributeString;
        */
    }

    public function getPeasantHelpString(Race $race, Dominion $dominion = null): string
    {
        $helpString = '';
        if($peasantDp = $race->getPerkValue('peasant_dp'))
        {
            if($dominion)
            {
                $peasantDp += $dominion->getTechPerkValue('peasant_dp');
            }

            $helpString .= '<ul><li>DP: ' . $peasantDp . '</li></ul>';
        }

        return $helpString;

    }

    public function getDrafteeHelpString(Race $race, Dominion $dominion = null): string
    {
        $drafteeDp = $race->getPerkValue('draftee_dp') ?: 1;

        if($dominion)
        {
            $drafteeDp += $dominion->getTechPerkValue('draftee_dp');
        }

        return '<ul><li>DP: ' . $drafteeDp . '</li></ul>';

    }

    public function unitSlotHasAttributes(Race $race, $slot, array $searchAttributes): bool
    {
        if(is_int($slot))
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

            return true;

    }

    public function unitHasCapacityLimit(Dominion $dominion, int $slot): bool
    {
        if(
            $dominion->race->getUnitPerkValueForUnitSlot($slot, 'pairing_limit') or
            $dominion->race->getUnitPerkValueForUnitSlot($slot, 'pairing_limit_including_away') or
            $dominion->race->getUnitPerkValueForUnitSlot($slot, 'building_limit') or
            $dominion->race->getUnitPerkValueForUnitSlot($slot, 'land_limit') or
            $dominion->race->getUnitPerkValueForUnitSlot($slot, 'total_land_limit') or
            $dominion->race->getUnitPerkValueForUnitSlot($slot, 'archmage_limit') or
            $dominion->race->getUnitPerkValueForUnitSlot($slot, 'net_victories_limit') or
            $dominion->race->getUnitPerkValueForUnitSlot($slot, 'amount_limit')
          )
          {
              return true;
          }

        return false;
    }

    public function getUnitMaxCapacity(Dominion $dominion, int $slotLimited): int
    {
        $maxCapacity = 0;

        $raceKey = str_replace(' ', '_', strtolower($dominion->race->name));

        $limitMultiplier = 1;
        $limitMultiplier += $dominion->getImprovementPerkMultiplier('unit_pairing');
        $limitMultiplier += $dominion->getBuildingPerkMultiplier('unit_pairing');
        $limitMultiplier += $dominion->getSpellPerkMultiplier('unit_pairing');

        # Unit:unit limit
        if($pairingLimit = $dominion->race->getUnitPerkValueForUnitSlot($slotLimited, 'pairing_limit'))
        {
            $slotLimitedTo = (int)$pairingLimit[0];
            $perUnitLimitedTo = (float)$pairingLimit[1];

            $limitingUnits = $dominion->{'military_unit' . $slotLimitedTo};

            $maxCapacity = floor($limitingUnits * $perUnitLimitedTo * $limitMultiplier);
        }

        # Unit:unit limit (including_away)
        if($pairingLimit = $dominion->race->getUnitPerkValueForUnitSlot($slotLimited, 'pairing_limit_including_away'))
        {
            $slotLimitedTo = (int)$pairingLimit[0];
            $perUnitLimitedTo = (float)$pairingLimit[1];

            $limitingUnits = $dominion->{'military_unit' . $slotLimited};
            $limitingUnits += $this->queueService->getTrainingQueueTotalByResource($dominion, 'military_unit' . $slotLimitedTo);
            $limitingUnits += $this->queueService->getInvasionQueueTotalByResource($dominion, 'military_unit' . $slotLimitedTo);
            $limitingUnits += $this->queueService->getExpeditionQueueTotalByResource($dominion, 'military_unit' . $slotLimitedTo);
            $limitingUnits += $this->queueService->getTheftQueueTotalByResource($dominion, 'military_unit' . $slotLimitedTo);
            $limitingUnits += $this->queueService->getSabotageQueueTotalByResource($dominion, 'military_unit' . $slotLimitedTo);

            $maxCapacity = floor($limitingUnits * $perUnitLimitedTo * $limitMultiplier);
        }

        # Unit:building limit
        if($pairingLimit = $dominion->race->getUnitPerkValueForUnitSlot($slotLimited, 'building_limit'))
        {
            $buildingKeyLimitedTo = (string)$pairingLimit[0];
            $perBuildingLimitedTo = (float)$pairingLimit[1];

            $limitingBuildings = $this->buildingCalculator->getBuildingAmountOwned($dominion, Building::where('key', $buildingKeyLimitedTo)->first());

            # SNOW ELF
            if($dominion->getBuildingPerkValue($raceKey . '_unit' . $slotLimited . '_production_raw_capped') and $dominion->race->name == 'Snow Elf')
            {
                # Hardcoded 20% production cap
                $limitingBuildings = min($limitingBuildings, $this->landCalculator->getTotalLand($dominion) * 0.20);
            }

            $maxCapacity = floor($limitingBuildings * $perBuildingLimitedTo * $limitMultiplier);

        }

        # Unit:land limit
        if($pairingLimit = $dominion->race->getUnitPerkValueForUnitSlot($slotLimited, 'land_limit'))
        {
            $landTypeLimitedTo = (string)$pairingLimit[0];
            $perLandLimitedTo = (float)$pairingLimit[1];

            $limitingLand = $dominion->{'land_' . $landTypeLimitedTo};

            $maxCapacity = floor($limitingLand * $perLandLimitedTo * $limitMultiplier);
        }

        # Unit:total land limit
        if($pairingLimit = $dominion->race->getUnitPerkValueForUnitSlot($slotLimited, 'total_land_limit'))
        {
            $landAmountPerUnit = (int)$pairingLimit[0];

            $maxCapacity = floor($this->landCalculator->getTotalLand($dominion) / $landAmountPerUnit * $limitMultiplier);
        }

        # Unit:archmages limit
        if($pairingLimit = $dominion->race->getUnitPerkValueForUnitSlot($slotLimited, 'archmage_limit'))
        {
            $perArchmage = (float)$pairingLimit;
            $maxCapacity = floor($perArchmage * $dominion->military_archmages * $limitMultiplier);
        }

        # Unit:net_victories limit
        if($pairingLimit = $dominion->race->getUnitPerkValueForUnitSlot($slotLimited, 'net_victories_limit'))
        {
            $perNetVictory = (float)$pairingLimit[0];

            $netVictories = $this->statsService->getStat($dominion, 'invasion_victories') - $this->statsService->getStat($dominion, 'defense_failures');

            $maxCapacity = floor($perNetVictory * $netVictories);
        }

        # Unit limit
        if($pairingLimit = $dominion->race->getUnitPerkValueForUnitSlot($slotLimited, 'amount_limit'))
        {
            $maxCapacity = $pairingLimit;
        }

        return $maxCapacity;

    }

    public function checkUnitLimitForTraining(Dominion $dominion, int $slotLimited, int $amountToTrain): bool
    {
        if(!$this->unitHasCapacityLimit($dominion, $slotLimited))
        {
            return true;
        }

        $maxCapacity = $this->getUnitMaxCapacity($dominion, $slotLimited);

        $currentlyTrained = $dominion->{'military_unit' . $slotLimited};
        $currentlyTrained += $this->queueService->getTrainingQueueTotalByResource($dominion, 'military_unit' . $slotLimited);
        $currentlyTrained += $this->queueService->getInvasionQueueTotalByResource($dominion, 'military_unit' . $slotLimited);
        $currentlyTrained += $this->queueService->getExpeditionQueueTotalByResource($dominion, 'military_unit' . $slotLimited);
        $currentlyTrained += $this->queueService->getTheftQueueTotalByResource($dominion, 'military_unit' . $slotLimited);
        $currentlyTrained += $this->queueService->getSabotageQueueTotalByResource($dominion, 'military_unit' . $slotLimited);

        $totalWithAmountToTrain = $currentlyTrained + $amountToTrain;

        return $maxCapacity >= $totalWithAmountToTrain;

    }

    public function checkUnitLimitForInvasion(Dominion $dominion, int $slotLimited, int $amountToSend): bool
    {
        $maxCapacity = $this->getUnitMaxCapacity($dominion, $slotLimited);

        if($this->unitHasCapacityLimit($dominion, $slotLimited))
        {
            return $maxCapacity >= $amountToSend;
        }

        return true;
    }


    public function getSpiesHelpString(Dominion $dominion): string
    {
        $helpString = 'Spy strength: ' . number_format($dominion->spy_strength) . '%';

        if($dominion->spy_strength < 100)
        {
        #    $helpString .= ' (+' . $militaryCalculator->getSpyStrengthRegen($dominion) . '%/tick)';
        }

        return $helpString;
    }

    public function getUnitFromRaceUnitType(Race $race, string $unitKey)
    {
        if(in_array($unitKey, ['spies','wizards','archmages']))
        {
            return null;
        }

        $slot = (int)str_replace('unit', '', $unitKey);
        return $race->units->filter(function ($unit) use ($slot) {
            return ($unit->slot === $slot);
        })->first();
    }

    # This does not take cost into consideration
    public function isUnitTrainableByDominion($unit, Dominion $dominion): bool
    {

        if(is_a($unit, 'OpenDominion\Models\Unit', true))
        {
            if($dominion->race->getUnitPerkValueForUnitSlot($unit->slot, 'cannot_be_trained'))
            {
                return false;
            }

            if(isset($unit->deity))
            {
                if(!$dominion->hasDeity())
                {
                    return false;
                }
                elseif($dominion->deity->id !== $unit->deity->id)
                {
                    return false;
                }
            }
        }
        elseif($dominion->race->getPerkValue('cannot_train_' . $unit))
        {
            return false;
        }

        return true;
    }

    # This does not take cost or pairing limits into consideration
    public function isUnitSendableByDominion(Unit $unit, Dominion $dominion): bool
    {
        if($dominion->race->getUnitPerkValueForUnitSlot($unit->slot, 'cannot_be_sent'))
        {
            return false;
        }

        if(isset($unit->deity))
        {
            if(!$dominion->hasDeity())
            {
                return false;
            }
            elseif($dominion->deity->id !== $unit->deity->id)
            {
                return false;
            }
        }

        return true;
    }

    public function getUnitAttributePsionicStrengthValue(string $attribute): float
    {
        switch ($attribute)
        {
            case 'mindless':
                return -0.5;

            case 'intelligent':
                return 0.5;
            
            case 'wise':
                return 2;

            case 'psychic':
                return 4;
            
            default:
                0;
        }

        return 0;

    }

    public function getUnitCostString(Race $race, array $trainingCosts): string
    {
        $costString = '';
        $costs = [];
        foreach($trainingCosts as $costType => $value)
        {
            if($value != 0)
            {
                switch ($costType)
                {
                    case 'gems':
                        $costs[] = number_format($value) . ' ' . str_plural('gems', $value);
                        break;

                    case 'peasant':
                        $costs[] = number_format($value) . ' ' . str_plural((isset($race->peasants_alias) ? $race->peasants_alias : 'peasant'), $value);
                        break;

                    case 'draftee':
                    case 'draftees':
                        $costs[] = number_format($value) . ' ' . str_plural((isset($race->draftees_alias) ? $race->draftees_alias : 'draftee'), $value);
                        break;

                    case 'elk':
                    case 'horse':
                    case 'prayer':
                    case 'prisoner':
                    case 'sapling':
                    case 'yak':
                        $costs[] = number_format($value) . ' ' . str_plural($costType, $value);
                        break;

                    case 'prestige':
                        $costs[] = number_format($value) . ' Prestige';
                        break;

                    case 'soul':
                        $costs[] = number_format($value) . ' ' . str_plural('soul', $value);
                        break;

                    case 'unit1':
                    case 'unit2':
                    case 'unit3':
                    case 'unit4':
                    case 'unit5':
                    case 'unit6':
                    case 'unit7':
                    case 'unit8':
                    case 'unit9':
                    case 'unit10':
                        $costs[] = number_format($value) . ' ' . str_plural($this->getUnitName($costType, $race), $value);
                        break;

                    case 'morale':
                        $costs[] = number_format($value) . '% morale';
                        break;

                    case 'spy':
                    case 'spies':
                        $costs[] = number_format($value) . ' ' . str_plural('Spy', $value);
                        break;

                    case 'wizard':
                    case 'wizards':
                        $costs[] = number_format($value) . ' ' . str_plural('Wizard', $value);
                        break;

                    case 'archmage':
                    case 'archmages':
                        $costs[] = number_format($value) . ' ' . str_plural('Archmage', $value);
                        break;

                    case 'spy_strength':
                        $costs[] = number_format($value) . '% Spy Strength';
                        break;

                    case 'crypt_body':
                        $costs[] = number_format($value) . ' crypt body';
                        break;

                    case 'wizard_strength':
                        $costs[] = number_format($value) . '% Wizard Strength';
                        break;

                    default:
                        $costs[] = number_format($value) . ' ' . $costType;
                        break;
                }
            }
        }
        $costsCount = count($costs)-1;
        #$i = 1;
        foreach($costs as $index => $cost)
        {
            $costString .= $cost;
            if($index < $costsCount)
            {
                $costString .= ', <br>';
            }
        }

        return $costString;
    }

    public function getRaceUnitFromSlot(Race $race, int $slot): Unit
    {
        return $race->units->filter(function ($unit) use ($slot) {
            return ($unit->slot === $slot);
        })->first();
    }

    public function checkUnitHasPerks(Dominion $dominion, Unit $unit, array $perkKeys): bool
    {
        foreach($perkKeys as $perkKey)
        {
            if($dominion->race->getUnitPerkValueForUnitSlot($unit->slot, $perkKey))
            {
                return true;
            }
        }

        return false;
    }

}
