<?php

namespace OpenDominion\Helpers;
use Illuminate\Support\Collection;
use OpenDominion\Models\Building;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;
use OpenDominion\Models\Resource;
use OpenDominion\Models\Tech;


class BuildingHelper
{

    public function getBuildingKeys(): Collection
    {
        return Building::where('enabled',1)->pluck('key');
    }

    public function getBuildingDescription(Building $building): ?string
    {

        $helpStrings[$building->name] = '';

        $perkTypeStrings = [
            # Housing
            'housing' => 'Houses %g people.',
            'housing_increasing' => 'Houses %1$s people, increased by %2$s per tick.',

            'military_housing' => 'Houses %g military units.',
            'military_housing_increasing' => 'Houses %1$s military units, increased by %2$s per tick.',

            'draftee_housing' => 'Houses %g draftees.',

            'wizard_housing' => 'Houses %1$s wizards and units that count as wizards.',
            'spy_housing' => 'Houses %1$s spies and units that count as spies.',
            'draftee_housing' => 'Houses %g draftees.',
            'peasant_housing' => 'Houses %g peasants.',

            'artillery_unit1_housing' => 'Houses %g Sappers.',
            'afflicted_unit1_housing' => 'Houses %g Abominations.',
            'aurei_unit1_housing' => 'Houses %g Alchemists.',
            'dwarg_unit1_housing' => 'Houses %g Miners.',
            'cires_unit1_housing' => 'Houses %g Standard Bearers.',
            'cires_unit2_housing' => 'Houses %g Archers.',
            'sacred_order_unit2_housing' => 'Houses %g Clerics.',
            'sacred_order_unit3_housing' => 'Houses %g Fanatics.',
            'sacred_order_unit4_housing' => 'Houses %g Paladins.',
            'snow_elf_unit1_housing' => 'Houses %g Arbalists.',
            'troll_unit2_housing' => 'Houses %g Forest Trolls.',
            'troll_unit4_housing' => 'Houses %g Mountain Trolls.',
            'vampires_unit1_housing' => 'Houses %g Servants.',
            'revenants_unit1_housing' => 'Houses %g Lesser Zombies.',
            'revenants_unit2_housing' => 'Houses %g Zombies.',
            'revenants_unit3_housing' => 'Houses %g Greater Zombies.',

            'ammunition_units_housing' => 'Houses %g ammunition units.',

            'dimensionalists_unit1_production_raw' => 'Summons %g Tl\'Tl per tick.',
            'dimensionalists_unit2_production_raw' => 'Summons %g Sft\'Rm per tick.',
            'dimensionalists_unit3_production_raw' => 'Summons %g Ze\'Tk per tick.',
            'dimensionalists_unit4_production_raw' => 'Summons %g Frs\'Kl per tick.',

            'aurei_unit2_production_raw_capped' => 'Summons %1$s Gold Wyrms per tick (up to %2$s%% of your land).',

            'dimensionalists_unit1_production_raw_capped' => 'Summons %1$s Tl\'Tl per tick (with to %2$s%% of your land).',
            'dimensionalists_unit2_production_raw_capped' => 'Summons %1$s Sft\'Rm per tick (up to %2$s%% of your land).',
            'dimensionalists_unit3_production_raw_capped' => 'Summons %1$s Ze\'Tk per tick (up to %2$s%% of your land).',
            'dimensionalists_unit4_production_raw_capped' => 'Summons %1$s Frs\'Kl per tick (up to %2$s%% of your land).',

            'dimensionalists_unit1_production_mod' => '%2$s%% Tl\'Tl summoning rate for every %1$s%% (max %3$s%%).',
            'dimensionalists_unit2_production_mod' => '%2$s%% Sft\'Rm summoning rate for every %1$s%% (max %3$s%%).',
            'dimensionalists_unit3_production_mod' => '%2$s%% Ze\'Tk summoning rate for every %1$s%% (max %3$s%%).',
            'dimensionalists_unit4_production_mod' => '%2$s%% Frs\'Kl summoning rate for every %1$s%% (max %3$s%%).',

            'unit_production_from_wizard_ratio' => 'Summoning increased by (Wizard Ratio / %g)%%.',

            'snow_elf_unit4_production_raw' => 'Attracts %g Gryphons per tick.',
            'snow_elf_unit4_production_raw_capped' => 'Summons %1$s Gryphons per tick (up to %2$s%% of your land).',
            'snow_elf_unit4_production_mod' => '%2$s%% Gryphon arrival rate for every %1$s%% (max %3$s%%).',

            'jobs' => 'Provides %g jobs.',

            'population_growth' => '%2$s%% population growth rate for every %1$s%%.',

            'drafting' => 'Drafting increased by %2$s%% for every %1$s%% (max +%3$s%%).',

            'crypt_bodies_decay_protection' => 'Protects %g crypt bodies from decay.',

            # Production
            'gold_production_raw' => 'Produces %g gold per tick.',
            'food_production_raw' => 'Produces %g food per tick.',
            'lumber_production_raw' => 'Produces %g lumber per tick.',
            'ore_production_raw' => 'Produces %g ore per tick.',
            'gems_production_raw' => 'Produces %g gems per tick.',
            'mana_production_raw' => 'Produces %g mana per tick.',
            'pearls_production_raw' => 'Produces %g pearls per tick.',
            'horse_production_raw' => 'Produces %g horses per tick.',
            'mud_production_raw' => 'Produces %g mud per tick.',
            'swamp_gas_production_raw' => 'Produces %g swamp gas per tick.',
            'marshling_production_raw' => 'Produces %g marshlings per tick.',
            'yak_production_raw' => 'Breeds %g yak per tick.',
            'kelp_production_raw' => 'Grows %g kelp per tick.',
            'gunpowder_production_raw' => 'Produces %g gunpowder per tick.',

            'xp_generation_raw' => 'Generates %g XP per tick.',

            'gold_upkeep_raw' => 'Costs %g gold per tick.',
            'food_upkeep_raw' => 'Uses %g food per tick.',
            'lumber_upkeep_raw' => 'Costs %g lumber per tick.',
            'ore_upkeep_raw' => 'Costs %g ore per tick.',
            'gems_upkeep_raw' => 'Costs %g gems per tick.',
            'mana_upkeep_raw' => 'Drains %g mana per tick.',
            'pearls_upkeep_raw' => 'Costs %g pearls per tick.',
            'prisoner_upkeep_raw' => 'Works %g prisoners per tick to death.',

            'gold_production_mod' => 'Gold production increased by %2$s%% for every %1$s%% (max +%3$s%%).',
            'food_production_mod' => 'Food production increased by %2$s%% for every %1$s%% (max +%3$s%%).',
            'lumber_production_mod' => 'Lumber production increased by %2$s%% for every %1$s%% (max +%3$s%%).',
            'ore_production_mod' => 'Ore production increased by %2$s%% for every %1$s%% (max +%3$s%%).',
            'gems_production_mod' => 'Gem production increased by %2$s%% for every %1$s%% (max +%3$s%%).',
            'mana_production_mod' => 'Mana production increased by %2$s%% for every %1$s%% (max +%3$s%%).',
            'pearls_production_mod' => 'Pearl production increased by %2$s%% for every %1$s%% (max +%3$s%%).',
            'blood_production_mod' => 'Blood production increased by %2$s%% for every %1$s%% (max +%3$s%%).',
            'horse_production_mod' => 'Horse production increased by %2$s%% for every %1$s%% (max +%3$s%%).',
            'mud_production_mod' => 'Mud production increased by %2$s%% for every %1$s%% (max +%3$s%%).',
            'swamp_gas_production_mod' => 'Swamp gas production increased by %2$s%% for every %1$s%% (max +%3$s%%).',

            'xp_generation_mod' => 'XP generation increased by %2$s%% for every %1$s%% (max +%3$s%%).',

            'resource_conversion' => 'Converts %1$s %2$s into %3$s %4$s per tick.',
            'peasants_conversion' => 'Converts %1$s peasants into %2$s %3$s per tick.',
            'peasants_conversions' => 'Converts %1$s of your peasants into %2$s each per tick.',

            'gold_production_depleting_raw' => 'Produces %1$s gold per tick (reduced by %2$s per tick of the round down to 0).',
            'food_production_depleting_raw' => 'Produces %1$s food per tick (reduced by %2$s per tick of the round down to 0).',
            'lumber_production_depleting_raw' => 'Produces %1$s lumber per tick (reduced by %2$s per tick of the round down to 0).',
            'ore_production_depleting_raw' => 'Produces %1$s ore per tick (reduced by %2$s per tick of the round down to 0).',
            'gems_production_depleting_raw' => 'Produces %1$s gems per tick (reduced by %2$s per tick of the round down to 0).',
            'mana_production_depleting_raw' => 'Produces %1$s mana per tick (reduced by %2$s per tick of the round down to 0).',

            'gold_production_increasing_raw' => 'Produces %1$s gold per tick (increased by %2$s per tick of the round).',
            'food_production_increasing_raw' => 'Produces %1$s food per tick (increased by %2$s per tick of the round).',
            'lumber_production_increasing_raw' => 'Produces %1$s lumber per tick (increased by %2$s per tick of the round).',
            'ore_production_increasing_raw' => 'Produces %1$s ore per tick (increased by %2$s per tick of the round).',
            'gems_production_increasing_raw' => 'Produces %1$s gems per tick (increased by %2$s per tick of the round).',
            'mana_production_increasing_raw' => 'Produces %1$s mana per tick (increased by %2$s per tick of the round).',

            'thunderstone_production_raw_random' => '%1$s%% chance to discover a thunderstone.',

            'gunpowder_storage_raw' => '%g gunpowder storage.',

            'ore_production_raw_from_prisoner' => 'Produces %1$s ore per tick per prisoner up to a maximum of %2$s prisoners.',
            'gold_production_raw_from_prisoner' => 'Produces %1$s gold per tick per prisoner up to a maximum of %2$s prisoners.',
            'gems_production_raw_from_prisoner' => 'Produces %1$s gems per tick per prisoner up to a maximum of %2$s prisoners.',

            'light_production_raw_from_time' => 'Gathers %3$s light between %1$s:00 and %2$s:00.',

            'draftee_generation' => 'Generates %g draftees per tick (limited by population).',

            'exchange_rate' => 'Resource exchange rates improved by %2$s%% for every %1$s%% (max +%3$s%%).',

            # Military
            'casualties_on_offense' => 'Offensive casualties decreased by %2$s%% for every %1$s%% (max %3$s%% reduction).',
            'casualties_on_defense' => 'Defensive casualties decreased by %2$s%% for every %1$s%% (max %3$s%% reduction).',

            'increases_enemy_casualties_on_defense' => 'Increases enemy casualties on offense by %2$s%% for every %1$s%% (max %3$s%%).',
            'increases_enemy_casualties_on_defense' => 'Increases enemy casualties on defense by %2$s%% for every %1$s%% (max %3$s%%).',

            'unit_gold_costs' => 'Unit gold costs %2$s%% for every %1$s%% (max %3$s%% reduction).',
            'unit_ore_costs' => 'Unit ore costs %2$s%% for every %1$s%% (max %3$s%% reduction).',
            'unit_lumber_costs' => 'Unit lumber costs %2$s%% for every %1$s%% (max %3$s%% reduction).',
            'unit_mana_costs' => 'Unit mana costs %2$s%% for every %1$s%% (max %3$s%% reduction).',
            'unit_blood_costs' => 'Unit blood costs %2$s%% for every %1$s%% (max %3$s%% reduction).',
            'unit_food_costs' => 'Unit food costs %2$s%% for every %1$s%% (max %3$s%% reduction).',

            'extra_units_trained' => '%2$s%% additional units trained for free for every %1$s%% (max %3$s%% extra units).',

            'faster_return' =>  '%2$s%% of units sent on invasion return %4$s ticks faster for every %1$s%% (max %3$s%% of all units).',
            'faster_returning_units_increasing' =>  'Each building enables %1$s units sent on invasion to return four ticks faster, increased by %2$s per tick.',
            'faster_returning_units' =>  'Each building enables %1$s units sent on invasion to return four ticks faster.',

            'morale_gains' => 'Morale gains increased by %2$s%% for every %1$s%% (max +%3$s%%)',
            'base_morale' => 'Morale increased by %2$s%% for every %1$s%% (max +%3$s%%)',
            'prestige_gains' => 'Prestige gains increased by %2$s%% for every %1$s%% (max +%3$s%%)',

            'land_discovered' => 'Land discovered during invasions increased by %2$s%% for every %1$s%% (max +%3$s%%).',

            'reduces_attrition' => 'Reduces unit attrition by %2$s%% for every %1$s%%.',
            'attrition_protection' => 'Keeps %1$s %2$s from attrition.',

            'reduces_conversions' => 'Reduces conversions for enemies by %2$s%% for every %1$s%%.',

            'training_time_mod' => '%2$s%% training time for every %1$s%% (max %3$s%%).',

            'unit_pairing' => '%2$s%% unit pairing for every %1$s%%.',

            'unit_send_capacity' => '%+g units sendable.',

            # OP/DP
            'raw_defense' => 'Provides %g raw defensive power.',
            'offensive_power' => 'Offensive power increased by %2$s%% for every %1$s%% (max +%3$s%% OP)',
            'defensive_power' => 'Defensive power increased by %2$s%% for every %1$s%% (max +%3$s%% DP).',
            'target_defensive_power_mod' => '%2$s%% target defensive modifiers for every %1$s%% (max -%3$s%% or 0%% defensive modifiers).',

            'attacker_offensive_power_mod' => 'Invading force\'s total offensive power reduced by %2$s%% for every %1$s%% (max -%3$s%% OP).',

            # Improvements
            'improvements' => 'Improvements increased by %2$s%% for every %1$s%%.',
            'improvements_capped' => 'Improvements increased by %2$s%% for every %1$s%% (max +%3$s%%).',
            'improvements_interest' => 'Improvements interest increased by %2$s%% for every %1$s%% (max +%3$s%%).',

            'invest_bonus' => 'Improvement points worth increased by %2$s%% for every %1$s%% (max +%3$s%%)',
            'gold_invest_bonus' => 'Gold improvement points worth increased by %2$s%% for every %1$s%% (max +%3$s%%)',
            'food_invest_bonus' => 'Food improvement points worth increased by %2$s%% for every %1$s%% (max +%3$s%%)',
            'ore_invest_bonus' => 'Ore improvement points worth increased by %2$s%% for every %1$s%% (max +%3$s%%)',
            'lumber_invest_bonus' => 'Lumber improvement points worth increased by %2$s%% for every %1$s%% (max +%3$s%%)',
            'mana_invest_bonus' => 'Mana improvement points worth increased by %2$s%% for every %1$s%% (max +%3$s%%)',
            'blood_invest_bonus' => 'Blood improvement points worth increased by %2$s%% for every %1$s%% (max +%3$s%%)',
            'soul_invest_bonus' => 'Soul improvement points worth increased by %2$s%% for every %1$s%% (max +%3$s%%)',

            # Construction and Rezoning
            'construction_cost' => 'Construction costs decreased by %2$s%% for every %1$s%% (max %3$s%% reduction).',
            'rezone_cost' => 'Rezoning costs decreased by %2$s%% for every %1$s%% (max %3$s%% reduction).',
            'construction_time' => 'Construction time decreased by %2$s%% for every %1$s%% (max %3$s%% reduction).',
            
            'destroys_itself_and_land' => '%g collapse per tick and the land is lost.',
            'destroys_itself' => '%g collapse per tick, leaving barren land.',

            # Espionage and Wizardry
            'spy_losses' => 'Spy losses decreased by %2$s%% for every %1$s%%.',
            'damage_from_fireball' => 'Damage from fireballs reduced by %2$s%% for every %1$s%%.',
            'lightning_bolt_damage' => 'Damage from lightning bolts reduced by %2$s%% for every %1$s%%.',
            'wizard_cost' => 'Wizard and arch mage training costs decreased by %2$s%% for every %1$s%% (max %3$s%% reduction).',
            'spell_cost' => 'Spell mana costs decreased by %2$s%% for every %1$s%% (max %3$s%% reduction).',

            'gold_theft_reduction' => 'Gold stolen from you reduced by %2$s%% for every %1$s%%.',
            'gems_theft_reduction' => 'Gems stolen from you reduced by %2$s%% for every %1$s%%.',
            'ore_theft_reduction' => 'Ore stolen from you reduced by %2$s%% for every %1$s%%.',
            'lumber_theft_reduction' => 'Lumber stolen from you reduced by %2$s%% for every %1$s%%.',
            'food_theft_reduction' => 'Food stolen from you reduced by %2$s%% for every %1$s%%.',
            'mana_theft_reduction' => 'Mana stolen from you reduced by %2$s%% for every %1$s%%.',
            'horse_theft_reduction' => 'Horses stolen from you reduced by %2$s%% for every %1$s%%.',

            'gold_theft_protection' => 'Protects %g gold from theft.',
            'gems_theft_protection' => 'Protects %g gems from theft.',
            'ore_theft_protection' => 'Protects %g ore from theft.',
            'lumber_theft_protection' => 'Protects %g lumber from theft.',
            'food_theft_protection' => 'Protects %g food from theft.',
            'mana_theft_protection' => 'Protects %g mana from theft.',
            'horse_theft_protection' => 'Protects %g horses from theft.',
            'blood_theft_protection' => 'Protects %g blood from theft.',

            'wizard_strength_recovery' => 'Wizard strength recovery increased by %2$s%% for every %1$s%% (max +%3$s%%).',
            'spy_strength' => 'Spy strength increased by %2$s%% for every %1$s%% (max +%3$s%%).',
            'spy_strength_on_defense' => 'Spy strength on defense increased by %2$s%% for every %1$s%%.',
            'spy_strength_on_offense' => 'Spy strength on offense increased by %2$s%% for every %1$s%%.',
            'wizard_strength' => 'Wizard strength increased by %2$s%% for every %1$s%% (max +%3$s%%).',
            'wizard_strength_on_defense' => 'Wizard strength on defense increased by %2$s%% for every %1$s%%.',
            'wizard_strength_on_offense' => 'Wizard strength on offense increased by %2$s%% for every %1$s%%.',

            # Other/special
            'deity_power' => 'Increases deity perks %2$s%% for every %1$s%% (max +%3$s%%)',
            'research_required_to_build' => 'Requires %s research to build.',


            'arwe_unit_housing' => 'Houses %1$s %2$s.',
            'gorm_unit_housing' => 'Houses %1$s %2$s.',
            'therozou_unit_housing' => 'Houses %1$s %2$s.',

        ];



        foreach ($building->perks as $perk)
        {

            if (!array_key_exists($perk->key, $perkTypeStrings))
            {
                continue;
            }

            $perkValue = $perk->pivot->value;

            $nestedArrays = false;

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

            # SPECIAL DESCRIPTION PERKS

            foreach(Race::where('playable', 1)->pluck('key')->toArray() as $raceKey)
            {
                if($perk->key == ($raceKey . '_unit_housing'))
                {
                    $race = Race::firstWhere('key', $raceKey); 
    
                    if(is_array($perkValue[0]))
                    {
                        foreach($perkValue as $key => $perkValueData)
                        {
                            $unitSlot = (int)$perkValueData[0];
                            $amountHoused = (float)$perkValueData[1];
        
                            $unit = $race->units->firstWhere('slot', $unitSlot);
            
                            $perkValue[$key] = [$amountHoused, str_plural($unit->name, $amountHoused)];
                        }
                    }
                    else
                    {
                        $unitSlot = (int)$perkValue[0];
                        $amountHoused = (float)$perkValue[1];
        
                        $unit = $race->units->firstWhere('slot', $unitSlot);
    
                        $perkValue = [$amountHoused, str_plural($unit->name, $amountHoused)];
                        #dump('this one');
                    }

                    #dd($perkValue);
                }
            }



            if($perk->key === 'research_required_to_build')
            {
                $techKey = $perkValue;
                $tech = Tech::where('key', $techKey)->first();

                $perkValue = $tech->name;
                $nestedArrays = false;

            }

            if($perk->key === 'peasants_conversions')
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
                #$resourcesString = str_replace(' And ', ' and ', $resourcesString);

                $perkValue = [$ratio, $resourcesString];
                $nestedArrays = false;

            }

            if($perk->key === 'attrition_protection')
            {
                $amount = (float)$perkValue[0];
                $slot = (int)$perkValue[1];
                $raceName = (string)$perkValue[2];

                $race = Race::where('name', $raceName)->firstOrFail();

                $unit = $race->units->filter(function ($unit) use ($slot)
                {
                    return ($unit->slot === $slot);
                })->first();

                $perkValue = [$amount, str_plural($unit->name, $amount)];
                $nestedArrays = false;

            }

            if($perk->key == 'destroys_itself_and_land')
            {
                $perkValue = $perkValue[0];
                #$perkValue = number_format(1/$perkValue[0]);
            }

            # END SPECIAL DESCRIPTION PERKS


            if (is_array($perkValue))
            {
                if ($nestedArrays)
                {
                    foreach ($perkValue as $nestedKey => $nestedValue)
                    {
                        $helpStrings[$building->name] .= ('<li>' . vsprintf($perkTypeStrings[$perk->key], $nestedValue) . '</li>');
                    }
                }
                else
                {
                    $helpStrings[$building->name] .= ('<li>' . vsprintf($perkTypeStrings[$perk->key], $perkValue) . '</li>');
                }
            }
            else
            {
                $helpStrings[$building->name] .= ('<li>' . sprintf($perkTypeStrings[$perk->key], $perkValue) . '</li>');
            }
        }

        if(strlen($helpStrings[$building->name]) == 0)
        {
          $helpStrings[$building->name] = '<i>No special abilities</i>';
        }
        else
        {
          $helpStrings[$building->name] = '<ul>' . $helpStrings[$building->name] . '</ul>';
        }

        return $helpStrings[$building->name] ?: null;
    }

    /*
    *   Returns buildings available for the race.
    *   If $landType is present, only return buildings for the race for that land type.
    */
    public function getBuildingsByRace(Race $race, string $landType = null): Collection
    {
        $buildings = collect(Building::all()->keyBy('key')->sortBy('land_type')->sortBy('name')->where('enabled',1));

        if($landType)
        {
            $buildings = $buildings->where('land_type', $landType);
        }

        foreach($buildings as $building)
        {
          if(
                (count($building->excluded_races) > 0 and in_array($race->name, $building->excluded_races)) or
                (count($building->exclusive_races) > 0 and !in_array($race->name, $building->exclusive_races))
            )
          {
              $buildings->forget($building->key);
          }
        }

        return $buildings;
    }

    public function getExclusivityString(Building $building): string
    {

        $exclusivityString = '<br><small class="text-muted">';

        if($exclusives = count($building->exclusive_races))
        {
            foreach($building->exclusive_races as $raceName)
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
        elseif($excludes = count($building->excluded_races))
        {
            $exclusivityString .= 'All except ';
            foreach($building->excluded_races as $raceName)
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

    public function getBuildingName(string $buildingKey): string
    {
        return Building::where('key', $buildingKey)->first()->name;
    }

}
