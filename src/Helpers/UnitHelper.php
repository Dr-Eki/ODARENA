<?php

namespace OpenDominion\Helpers;

use OpenDominion\Models\Race;

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
            'draftees' => 'Used for exploring and training other units. Provides 1 DP.',
            'unit1' => 'Offensive specialist',
            'unit2' => 'Defensive specialist',
            'unit3' => 'Defensive elite',
            'unit4' => 'Offensive elite',
            'spies' => 'Used for espionage.',
            'wizards' => 'Used for casting offensive spells.',
            'archmages' => 'Used for casting offensive spells. Twice as strong as regular wizards.',
        ];

        // todo: refactor this. very inefficient
        $perkTypeStrings = [
            // Conversions
            'conversion' => 'Converts some enemy casualties into %s.',
            'staggered_conversion' => 'Converts some enemy casualties into %2$s against dominions %1$s%%+ of your size.',

            // OP/DP related
            'defense_from_building' => 'Defense increased by 1 for every %2$s%% %1$ss (max +%3$s).',
            'offense_from_building' => 'Offense increased by 1 for every %2$s%% %1$ss (max +%3$s).',

            'defense_from_land' => 'Defense increased by 1 for every %2$s%% %1$ss (max +%3$s).',
            'offense_from_land' => 'Offense increased by 1 for every %2$s%% %1$ss (max +%3$s).',

            'defense_from_pairing' => 'Defense increased by %2$s when paired with %3$s %1$s at home.',
            'offense_from_pairing' => 'Offense increased by %2$s when paired with %3$s %1$s on attack.',

            'defense_from_prestige' => 'Defense increased by 1 for every %1$s prestige (max +%2$s).',
            'offense_from_prestige' => 'Offense increased by 1 for every %1$s prestige (max +%2$s).',

            'defense_vs_building' => 'Defense increased by 1 for every %2$s%% %1$ss of defender (max +%3$s).',
            'offense_vs_building' => 'Offense increased by 1 for every %2$s%% %1$ss of defender (max +%3$s).',

            'defense_vs_goblin' => 'Defense increased by %s against goblins.',
            'offense_vs_goblin' => 'Offense increased by %s against goblins.',
            'defense_vs_kobold' => 'Defense increased by %s against kobolds.',
            'offense_vs_kobold' => 'Offense increased by %s against kobolds.',
            'defense_vs_wood_elf' => 'Defense increased by %s against wood elves.',
            'offense_vs_wood_elf' => 'Offense increased by %s against wood elves.',

            'offense_staggered_land_range' => 'Offense increased by %2$s against dominions %1$s%%+ of your size.',

            'offense_raw_wizard_ratio' => 'Offense increased by %1$s * Raw Wizard Ratio (max +%2$s).',

            // Spy related
            'counts_as_spy_defense' => 'Each unit counts as %s of a spy on defense.',
            'counts_as_spy_offense' => 'Each unit counts as %s of a spy on offense.',

            // Wizard related
            'counts_as_wizard_defense' => 'Each unit counts as %s of a wizard on defense.',
            'counts_as_wizard_offense' => 'Each unit counts as %s of a wizard on offense.',

            // Casualties related
            'fewer_casualties' => '%s%% fewer casualties.',
            'fewer_casualties_defense' => '%s%% fewer casualties on defense.',
            'fewer_casualties_offense' => '%s%% fewer casualties on offense.',
            'fixed_casualties' => 'ALWAYS suffers %s%% casualties.',

            'immortal' => 'Almost never dies.',
            'immortal_except_vs' => 'Almost never dies, except vs %s.',
            'immortal_vs_land_range' => 'Almost never dies when attacking dominions %s%%+ of your size.',

            'reduce_combat_losses' => 'Reduces combat losses.',

            // Resource related
            'ore_production' => 'Each unit produces %s units of ore per tick.',
            'plunders_resources_on_attack' => 'Plunders resources on attack.',
            'sink_boats_defense' => 'Sinks boats when defending.',
            'sink_boats_offense' => 'Sinks boats when attacking.',

            // Misc
            'faster_return' => 'Returns %s ticks faster from battle.',

            // ODA
            'mana_production' => 'Each unit generates %s mana per tick.',
            'lumber_production' => 'Each unit collects %s lumber per tick.',
            'food_production' => 'Each unit produces %s food per tick.',
            'true_immortal' => 'Immortal. Only dies when overwhelmed.',
            'afterlife_norse' => 'Upon honourable death (successful invasions over 75%), becomes a legendary champion and can be recalled into services as an Einherjar.',
            'does_not_kill' => 'Does not kill other units.',
            'no_draftee' => 'No draftee required to train.',
            'min_power_to_kill' => 'Only dies against units with %s or more OP (when defending) or DP (when invading), unless overwhelmed.',
            'gem_production' => 'Each unit mines %s gems per tick.',
            'offense_vs_land' => 'Offense increased by 1 for every %2$s%% %1$ss of defender (max +%3$s).',
            'pairing_limit' => 'You can at most have %2$s of this unit per %1$s.',
            'does_not_count_as_population' => 'Does not count towards population. No housing required.',
            'mana_drain' => 'Each unit drains %s mana per tick.',
            'cannot_be_trained' => 'Unit cannot be trained.',
            'instant_training' => 'Summoned immediately.',
            'faster_training' => 'Trains %s ticks faster.',
            'offense_raw_spy_ratio' => 'Offense increased by %1$s * Raw Spy Ratio (max +%2$s).',
            'offense_if_recently_invaded' => 'Offense increased by %1$s if recenty invaded.',
            'defense_if_recently_invaded' => 'Defense increased by %1$s if recenty invaded.',

            'offense_per_hour' => 'Offense increased by %1$s for every hour of the round (max +%2$s).',
            'defense_per_hour' => 'Defense increased by %1$s for every hour of the round (max +%2$s).',

            # TBD
            'offense_vs_barren_land' => 'Offense increased by 1 for every %1$s%% barren land of defender (max +%2$s).',
            'converts_to_cocoons' => 'Converts casualties to cocoons.',
            'upgrade_survivors' => 'Survivors return as %s after succesful invasions.',
            'kills_peasants' => 'Eats %s peasants per tick.',
            'burns_peasants_on_attack' => 'Burns %s peasants on successful invasion.',


            'unit_production' => 'Produces %2$s %1$s per tick.',
            'land_per_tick' => 'Explores %1$s acres of home land per tick.',
            'dies_into' => 'When killed, turns into a %1$.',

            # Abandoned
            'reduced_casualties_by_land' => 'Casualties increased by 1%% for every %2$s%% %1$ss (max -%3$s).',


        ];

        // Get unit - same logic as military page
        if (in_array($unitType, ['unit1', 'unit2', 'unit3', 'unit4'])) {
            $unit = $race->units->filter(function ($unit) use ($unitType) {
                return ($unit->slot == (int)str_replace('unit', '', $unitType));
            })->first();

            list($type, $proficiency) = explode(' ', $helpStrings[$unitType]);
            if ($unit->type) {
                list($type, $proficiency) = explode('_', $unit->type);
                $type = ucfirst($type);
            }   $proficiency .= '.';
            $helpStrings[$unitType] = "$type $proficiency";

            foreach ($unit->perks as $perk) {
                if (!array_key_exists($perk->key, $perkTypeStrings)) {
                    //\Debugbar::warning("Missing perk help text for unit perk '{$perk->key}'' on unit '{$unit->name}''.");
                    continue;
                }

                $perkValue = $perk->pivot->value;

                // Handle array-based perks
                $nestedArrays = false;
                // todo: refactor all of this
                // partially copied from Race::getUnitPerkValueForUnitSlot
                if (str_contains($perkValue, ',')) {
                    $perkValue = explode(',', $perkValue);

                    foreach ($perkValue as $key => $value) {
                        if (!str_contains($value, ';')) {
                            continue;
                        }

                        $nestedArrays = true;
                        $perkValue[$key] = explode(';', $value);
                    }
                }

                // Special case for pairings
                if ($perk->key === 'defense_from_pairing' || $perk->key === 'offense_from_pairing' || $perk->key === 'pairing_limit') {
                    $slot = (int)$perkValue[0];
                    $pairedUnit = $race->units->filter(static function ($unit) use ($slot) {
                        return ($unit->slot === $slot);
                    })->first();

                    $perkValue[0] = $pairedUnit->name;
                    if (isset($perkValue[2]) && $perkValue[2] > 1) {
                        $perkValue[0] = str_plural($perkValue[0]);
                    } else {
                        $perkValue[2] = 1;
                    }
                }
                  // Special case for unit_production
                  if ($perk->key === 'unit_production') {
                      $slot = (int)$perkValue[0];
                      $pairedUnit = $race->units->filter(static function ($unit) use ($slot) {
                          return ($unit->slot === $slot);
                      })->first();

                      $perkValue[0] = $pairedUnit->name;
                      if (isset($perkValue[2]) && $perkValue[2] > 1) {
                          $perkValue[0] = str_plural($perkValue[0]);
                      } else {
                          $perkValue[2] = 1;
                      }
                  }
                // Special case for conversions and dies_into
                if ($perk->key === 'conversion' || $perk->key === 'dies_into') {
                    $unitSlotsToConvertTo = array_map('intval', str_split($perkValue));
                    $unitNamesToConvertTo = [];

                    foreach ($unitSlotsToConvertTo as $slot) {
                        $unitToConvertTo = $race->units->filter(static function ($unit) use ($slot) {
                            return ($unit->slot === $slot);
                        })->first();

                        $unitNamesToConvertTo[] = str_plural($unitToConvertTo->name);
                    }

                    $perkValue = generate_sentence_from_array($unitNamesToConvertTo);
                } elseif ($perk->key === 'staggered_conversion') {
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


                if (is_array($perkValue)) {
                    if ($nestedArrays) {
                        foreach ($perkValue as $nestedKey => $nestedValue) {
                            $helpStrings[$unitType] .= ('<li>' . vsprintf($perkTypeStrings[$perk->key], $nestedValue) . '</li>');
                        }
                    } else {
                        $helpStrings[$unitType] .= ('<li>' . vsprintf($perkTypeStrings[$perk->key], $perkValue) . '</li>');
                    }
                } else {
                    $helpStrings[$unitType] .= ('<li>' . sprintf($perkTypeStrings[$perk->key], $perkValue) . '</li>');
                }
            }

            if ($unit->need_boat === false) {
                $helpStrings[$unitType] .= ('<li>No boats needed.</li>');
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

    public function getUnitTypeIconHtml(string $unitType, Race $race = null): string
    {
        switch ($unitType) {
            case 'draftees':
                $iconClass = 'fa fa-user';
                $colorClass = 'text-green';
                break;

            case 'unit1':
                $iconClass = 'ra ra-sword';
                $colorClass = 'text-green';
                break;

            case 'unit2':
                $iconClass = 'ra ra-shield';
                $colorClass = 'text-green';
                break;

            case 'unit3':
                $iconClass = 'ra ra-shield';
                $colorClass = 'text-light-blue';
                break;

            case 'unit4':
                $iconClass = 'ra ra-sword';
                $colorClass = 'text-light-blue';
                break;

            case 'spies':
                $iconClass = 'fa fa-user-secret';
                $colorClass = 'text-green';
                break;

            case 'wizards':
                $iconClass = 'ra ra-fairy-wand';
                $colorClass = 'text-green';
                break;

            case 'archmages':
                $iconClass = 'ra ra-fairy-wand';
                $colorClass = 'text-light-blue';
                break;

            default:
                return '';
        }

        if ($race && in_array($unitType, ['unit1', 'unit2', 'unit3', 'unit4'])) {
            $unit = $race->units->filter(function ($unit) use ($unitType) {
                return ($unit->slot == (int)str_replace('unit', '', $unitType));
            })->first();
            if ($unit->type) {
                list($type, $proficiency) = explode('_', $unit->type);
                if (strtolower($type) == 'offensive') {
                    $iconClass = 'ra ra-sword';
                } elseif (strtolower($type) == 'defensive') {
                    $iconClass = 'ra ra-shield';
                } elseif (strtolower($type) == 'hybrid') {
                    $iconClass = 'ra ra-crossed-swords';
                }
                if (strtolower($proficiency) == 'specialist') {
                    $colorClass = 'text-green';
                } elseif (strtolower($proficiency) == 'elite') {
                    $colorClass = 'text-light-blue';
                }
            }
        }

        return "<i class=\"$iconClass $colorClass\"></i>";
    }

    public function getConvertedUnitsString(array $convertedUnits, Race $race): string
    {
        $result = 'In addition, your army converts some of the enemy casualties into ';
        $convertedUnitsFiltered = array_filter($convertedUnits, function ($item) {
            return $item > 0;
        });

        $numberOfUnitTypesConverted = count($convertedUnitsFiltered);
        $i = 1;

        // todo: this can probably be refactored to use generate_sentence_from_array() in helpers.php
        foreach ($convertedUnitsFiltered as $slotNumber => $amount) {
            if ($i !== 1) {
                if ($numberOfUnitTypesConverted === $i) {
                    $result .= ' and ';
                } else {
                    $result .= ', ';
                }
            }

            $formattedAmount = number_format($amount);

            $result .= "{$formattedAmount} {$race->units[$slotNumber - 1]->name}s";

            $i++;
        }

        $result .= '!';

        return $result;
    }

    # Norse champions
    public function getChampionsString(int $champions): string
    {
      if ($champions > 0)
      {
        $result = number_format($champions) . ' of your brave fallen soldiers have become legendary champions.';
      }
      else
      {
        $result = 'No legendary champions arose from this battle.';
      }

        return $result;
    }

    # Demon Soul collection
    public function getSoulCollectionString(int $souls): string
    {
      if ($souls > 0)
      {
        $result = 'By slaying enemy soldiers, you collect ' . number_format($souls) . ' souls.';
      }
      else
      {
        $result = 'You do not collect any souls.';
      }

        return $result;
    }


}
