<?php

namespace OpenDominion\Helpers;

use LogicException;
use OpenDominion\Models\Deity;
use OpenDominion\Models\Race;
use OpenDominion\Models\RacePerkType;

use OpenDominion\Models\Dominion;

class RaceHelper
{
    public function getPerkDescriptionHtmlWithValue(RacePerkType $perkType): ?array
    {
        $valueType = '%';
        $booleanValue = false;
        switch($perkType->key) {
            case 'construction_cost':
                $negativeBenefit = true;
                $description = 'Construction cost';
                break;
            case 'no_construction_costs':
                $negativeBenefit = true;
                $description = 'No construction costs';
                $booleanValue = true;
                break;
            case 'no_rezone_costs':
                $negativeBenefit = true;
                $description = 'No rezoning costs';
                $booleanValue = true;
                break;
            case 'rezone_cost':
                $negativeBenefit = true;
                $description = 'Rezoning cost';
                break;
            case 'extra_barren_max_population':
                $negativeBenefit = false;
                $description = 'Extra housing from barren land';
                $valueType = '';
                break;
            case 'extra_barren_forest_max_population':
                $negativeBenefit = false;
                $description = 'Population from barren forest';
                $valueType = '';
                break;
            case 'extra_barren_forest_jobs':
                $negativeBenefit = false;
                $description = 'Jobs from barren forest';
                $valueType = '';
                break;
            case 'food_consumption_mod':
                $negativeBenefit = true;
                $description = 'Food consumption';
                break;
            case 'no_food_consumption':
                $negativeBenefit = false;
                $description = 'Does not eat food';
                $booleanValue = true;
                break;
            case 'cosmic_alignment_to_invade':
                $negativeBenefit = false;
                $description = 'Cosmic alignments required to teleport units';
                $booleanValue = 'static';
                $valueType = '';
                break;
            case 'cosmic_alignment_production_raw':
                $negativeBenefit = false;
                $description = 'Cosmic alignments discovered per tick';
                $booleanValue = 'static';
                $valueType = '';
                break;
            case 'cosmic_alignment_decay':
                $negativeBenefit = false;
                $description = 'Cosmic alignments decay per tick';
                $booleanValue = 'static';
                $valueType = '%';
                break;
            case 'food_production_mod':
                $negativeBenefit = false;
                $description = 'Food production';
                break;
            case 'gems_production_mod':
                $negativeBenefit = false;
                $description = 'Gem production';
                break;
            case 'xp_generation_mod':
                $negativeBenefit = false;
                $description = 'XP generation';
                break;
            case 'immortal_wizards':
                $negativeBenefit = false;
                $description = 'Immortal wizards';
                $booleanValue = true;
                break;
            case 'immortal_spies':
                $negativeBenefit = false;
                $description = 'Immortal spies';
                $booleanValue = true;
                break;
            case 'lumber_production_mod':
                $negativeBenefit = false;
                $description = 'Lumber production';
                break;
            case 'mana_production_mod':
                $negativeBenefit = false;
                $description = 'Mana production';
                break;
            case 'max_population':
                $negativeBenefit = false;
                $description = 'Max population';
                break;
            case 'no_population':
                $negativeBenefit = false;
                $description = 'No population';
                $booleanValue = true;
                break;
            case 'ore_production_mod':
                $negativeBenefit = false;
                $description = 'Ore production';
                break;
            case 'gold_production_mod':
                $negativeBenefit = false;
                $description = 'Gold production';
                break;
            case 'spy_strength':
                $negativeBenefit = false;
                $description = 'Spy strength';
                break;
            case 'wizard_strength':
                $negativeBenefit = false;
                $description = 'Wizard strength';
                break;
            case 'indestructible_buildings':
                $negativeBenefit = true;
                $description = 'Indestructible buildings';
                $booleanValue = true;
                break;
            case 'no_lumber_theft':
                $negativeBenefit = true;
                $description = 'Cannot steal lumber';
                $booleanValue = true;
                break;
            case 'cannot_construct':
                $negativeBenefit = true;
                $description = 'Cannot construct buildings';
                $booleanValue = true;
                break;
            case 'cannot_exchange':
                $negativeBenefit = true;
                $description = 'Cannot exchange resources';
                $booleanValue = true;
                break;
            case 'improvements_interest':
                $negativeBenefit = false;
                $description = 'Improvement interest';
                $valueType = '% / tick';
                $booleanValue = 'static';
                break;
            case 'improvements_interest_random_max':
                $negativeBenefit = false;
                $description = 'Improvement interest (random max)';
                $valueType = '% / tick';
                $booleanValue = 'static';
                break;
            case 'improvements_interest_random_min':
                $negativeBenefit = false;
                $description = 'Improvement interest (random min)';
                $valueType = '% / tick';
                $booleanValue = 'static';
                break;
          case 'improvements_per_victory':
                $negativeBenefit = false;
                $description = 'Improvements';
                $valueType = '% per victory';
                $booleanValue = 'static';
                break;
          case 'land_improvements':
                $negativeBenefit = false;
                $description = 'Land based improvements';
                $booleanValue = true;
                break;
            case 'population_growth':
                $negativeBenefit = false;
                $description = 'Population growth rate';
                break;
          case 'cannot_explore':
                $negativeBenefit = true;
                $description = 'Cannot explore';
                $booleanValue = true;
                break;
          case 'cannot_invade':
                $negativeBenefit = true;
                $description = 'Cannot invade';
                $booleanValue = true;
                break;
          case 'cannot_send_expeditions':
                $negativeBenefit = true;
                $description = 'Cannot send expeditions';
                $booleanValue = true;
                break;
          case 'cannot_train_spies':
                $negativeBenefit = true;
                $description = 'Cannot train spies';
                $booleanValue = true;
                break;
          case 'cannot_train_wizards':
                $negativeBenefit = true;
                $description = 'Cannot train wizards';
                $booleanValue = true;
                break;
          case 'cannot_train_archmages':
                $negativeBenefit = true;
                $description = 'Cannot train Arch Mages';
                $booleanValue = true;
                break;
          case 'explore_cost':
                $negativeBenefit = true;
                $description = 'Cost of exploration';
                break;
          case 'spell_cost':
                $negativeBenefit = true;
                $description = 'Spell costs';
                break;
            case 'explore_time':
                $negativeBenefit = true;
                $description = 'Exploration time:';
                $valueType = ' ticks';
                break;
            case 'wizard_training_time':
                $negativeBenefit = false;
                $description = 'Wizards training time:';
                $booleanValue = 'static';
                $valueType = '&nbsp;ticks';
                break;
            case 'reduced_conversions':
                $negativeBenefit = false;
                $description = 'Reduced conversions';
                break;
            case 'exchange_bonus':
                $negativeBenefit = false;
                $description = 'Better exchange rates';
                break;
          case 'does_not_kill':
                $negativeBenefit = false;
                $description = 'Does not kill units.';
                $booleanValue = true;
                break;
            case 'prestige_gains':
                $negativeBenefit = false;
                $description = 'Prestige gains';
                break;
            case 'no_drafting':
                $negativeBenefit = true;
                $description = 'No drafting';
                $booleanValue = true;
                break;
            case 'draftee_dp':
                $negativeBenefit = true;
                $description = 'DP per draftee';
                $valueType = '';
                $booleanValue = 'static';
                break;
            case 'increased_construction_speed':
                $negativeBenefit = false;
                $description = 'Increased construction speed';
                $valueType = ' ticks';
                break;
            case 'extra_barracks_housing':
                $negativeBenefit = false;
                $description = 'Military housing in buildings that provide military housing';
                $valueType = '%';
                break;
            case 'drafting':
                $negativeBenefit = false;
                $description = 'Peasants drafted per tick:';
                $valueType = '%';
                break;
            case 'amount_stolen':
                $negativeBenefit = false;
                $description = 'Amount stolen';
                $valueType = '%';
                break;
            case 'morale_change_tick':
                $negativeBenefit = true;
                $description = 'Morale normalisation per tick';
                $valueType = '% normal rate if current morale is over base';
                break;
            case 'morale_change_invasion':
                $negativeBenefit = false;
                $description = 'Morale changes on invasion';
                $valueType = '% (gains and losses)';
                break;
            case 'cannot_build_homes':
                $negativeBenefit = true;
                $description = 'Cannot build Homes';
                $booleanValue = true;
                break;
            case 'cannot_build_barracks':
                $negativeBenefit = true;
                $description = 'Cannot build Barracks';
                $booleanValue = true;
                break;
            case 'cannot_build_wizard_guilds':
                $negativeBenefit = true;
                $description = 'Cannot build Wizard Guilds';
                $booleanValue = true;
                break;
            case 'cannot_build_forest_havens':
                $negativeBenefit = true;
                $description = 'Cannot build Forest Havens';
                $booleanValue = true;
                break;
            case 'improvements_max':
                $negativeBenefit = false;
                $description = 'Improvement bonuses max';
                break;
            case 'improvements_decay':
                $negativeBenefit = true;
                $description = 'Improvements decay';
                $valueType = '% per tick';
                $booleanValue = 'static';
                break;
            case 'cannot_tech':
                $negativeBenefit = true;
                $description = 'Cannot level up advancements';
                $booleanValue = true;
                break;
            case 'advancement_costs':
                $negativeBenefit = true;
                $description = 'Cost of technological advancements';
                break;
            case 'experience_points_per_acre':
                $negativeBenefit = false;
                $description = 'Experience points gained per acre on successful invasions';
                break;
            case 'xp_per_acre_gained':
                $negativeBenefit = false;
                $description = 'XP per acre on invasions';
                break;
            case 'damage_from_lightning_bolts':
                $negativeBenefit = true;
                $description = 'Damage from Lightning Bolts';
                $booleanValue = false;
                break;
            case 'damage_from_fireballs':
                $negativeBenefit = true;
                $description = 'Damage from Fireballs';
                $booleanValue = false;
                break;
            case 'damage_from_insect_swarm':
                $negativeBenefit = true;
                $description = 'Effect from Insect Swarm';
                $booleanValue = false;
                break;
            case 'no_gold_production':
                $negativeBenefit = false;
                $description = 'No gold production';
                $booleanValue = true;
                break;
            case 'no_lumber_production':
                $negativeBenefit = false;
                $description = 'No lumber production';
                $booleanValue = true;
                break;
            case 'peasants_produce_food':
                $negativeBenefit = true;
                $description = 'Peasants produce food';
                $valueType = ' food/tick';
                $booleanValue = false;
                break;
            case 'unemployed_peasants_produce':
                $negativeBenefit = false;
                $description = 'All workers (including unemployed) produce';
                $booleanValue = true;
                break;
            case 'draftees_produce_mana':
                $negativeBenefit = false;
                $description = 'Draftees produce mana';
                $valueType = ' mana/tick';
                $booleanValue = false;
                break;
            case 'cannot_join_guards':
                $negativeBenefit = true;
                $description = 'Cannot join guards';
                $booleanValue = true;
                break;
            case 'cannot_vote':
                $negativeBenefit = true;
                $description = 'Cannot vote for Governor';
                $booleanValue = true;
                break;
            case 'converts_killed_spies_into_souls':
                $negativeBenefit = true;
                $description = 'Converts killed spies into souls';
                $booleanValue = true;
                break;
            case 'mana_drain':
                $negativeBenefit = true;
                $description = 'Mana drain';
                $booleanValue = false;
                break;
            case 'forest_construction_cost':
                $negativeBenefit = true;
                $description = 'Forest construction cost';
                break;
            case 'salvaging':
                $negativeBenefit = false;
                $description = 'Salvages ore, lumber, and gems of unit costs from lost units';
                $valueType = '%';
                $booleanValue = 'static';
                break;
            case 'cannot_rezone':
                $negativeBenefit = true;
                $description = 'Cannot rezone';
                $booleanValue = true;
                break;
            case 'cannot_release_units':
                $negativeBenefit = true;
                $description = 'Cannot release units';
                $booleanValue = true;
                break;
            case 'max_per_round':
                $negativeBenefit = true;
                $description = 'Max dominions of this faction per round';
                $valueType = '';
                $booleanValue = 'static';
                break;
            case 'min_rounds_played':
                $negativeBenefit = true;
                $description = 'Mininum number of rounds played to play this faction';
                $valueType = ' rounds';
                $booleanValue = 'static';
                break;
            case 'title_bonus':
                $negativeBenefit = false;
                $description = 'Ruler Title bonus';
                $booleanValue = false;
                break;
          case 'gryphon_nests_generate_gryphons':
                $negativeBenefit = false;
                $description = 'Gryphon Nests produce Gryphons';
                $valueType = ' per tick (max 20% of your land as nests are populated)';
                $booleanValue = 'static';
                break;
          case 'converts_assassinated_draftees':
                $negativeBenefit = false;
                $description = 'Converts assassinated draftees';
                $booleanValue = true;
                break;
          case 'converts_executed_spies':
                $negativeBenefit = false;
                $description = 'Converts captured spies';
                $booleanValue = true;
                break;
          case 'instant_return':
                $negativeBenefit = false;
                $description = 'Units return instantly when invading';
                $booleanValue = true;
                break;
          case 'unit_gold_costs_reduced_by_prestige':
                $negativeBenefit = false;
                $description = 'Unit gold costs reduced by prestige';
                $valueType = '% per 100 prestige';
                $booleanValue = 'static';
                break;
            case 'expedition_land_gains':
                $negativeBenefit = false;
                $description = 'Expedition land gains';
                $valueType = '%';
                break;
          case 'cannot_renounce_deity':
                $negativeBenefit = true;
                $description = 'Cannot renounce deity';
                $booleanValue = true;
                break;
          case 'starts_devoted_to_azk_hurum':
                $negativeBenefit = false;
                $description = 'Starts devoted to Azk\'Hurum';
                $booleanValue = true;
                break;
          case 'starts_devoted_to_elskas':
                $negativeBenefit = false;
                $description = 'Starts devoted to Elskas';
                $booleanValue = true;
                break;
          case 'starts_devoted_to_tiranthael':
                $negativeBenefit = false;
                $description = 'Starts devoted to Tiranthael';
                $booleanValue = true;
                break;
          case 'improvements_from_souls':
                $negativeBenefit = false;
                $description = 'Souls increase improvements';
                $booleanValue = true;
                break;
          default:
                $negativeBenefit = false;
                $description = 'No description for perk: ' . $perkType->key;
                #return null;
        }

        $result = ['description' => $description, 'value' => ''];

        $valueString = "{$perkType->pivot->value}{$valueType}";

        if ($perkType->pivot->value < 0)
        {

            if($booleanValue === true)
            {
                $valueString = 'No';
            }
            elseif($booleanValue == 'static')
            {
              $valueString = $perkType->pivot->value . $valueType;
            }

            if ($negativeBenefit === true)
            {
                $result['value'] = "<span class=\"text-green\">{$valueString}</span>";
            }
            elseif($booleanValue == 'static')
            {
                $result['value'] = "<span class=\"text-blue\">{$valueString}</span>";
            }
            else
            {
                $result['value'] = "<span class=\"text-red\">{$valueString}</span>";
            }

        }
        else
        {
            $prefix = '+';
            if($booleanValue === true)
            {
                $valueString = 'Yes';
                $prefix = '';
            }
            elseif($booleanValue == 'static')
            {
              $valueString = $perkType->pivot->value . $valueType;
              $prefix = '';
            }

            if ($negativeBenefit === true)
            {
                $result['value'] = "<span class=\"text-red\">{$prefix}{$valueString}</span>";
            }
            elseif($booleanValue == 'static')
            {
                $result['value'] = "<span class=\"text-blue\">{$prefix}{$valueString}</span>";
            }
            else
            {
                $result['value'] = "<span class=\"text-green\">{$prefix}{$valueString}</span>";
            }
        }

        return $result;
    }

    public function getRaceAdjective(Race $race): string
    {
        $adjectives = [
            'Ants' => 'Ant',
            'Aurei' => 'Aureis',
            'Black Orc' => 'Black Orcish',
            'Cult' => 'Cultist',
            'Dark Elf' => 'Dark Elven',
            'Demon' => 'Demonic',
            'Dimensionalists' => 'Dimensionalist',
            'Dwarf' => 'Dwarven',
            'Elementals' => 'Elemental',
            'Firewalker' => 'Firewalking',
            'Gnome' => 'Gnomish',
            'Imperial Gnome' => 'Imperial Gnomish',
            'Kerranad' => 'city-state',
            'Lux' => 'Lucene',
            'Lycanthrope' => 'Lycanthropic',
            'Nomad' => 'Nomadic',
            'Nox' => 'Nocten',
            'Orc' => 'Orcish',
            'Qur' => 'Qurrian',
            'Snow Elf' => 'Snow Elven',
            'Sylvan' => 'Sylvan',
            'Vampires' => 'Vampiric',
            'Weres' => 'Weren',
            'Wood Elf' => 'Wood Elven',
        ];

        return isset($adjectives[$race->name]) ? $adjectives[$race->name] : $race->name;

    }

    public function hasPeasantsAlias(Race $race): bool
    {
        return $race->peasants_alias ? true : false;
    }

    public function hasDrafteesAlias(Race $race): bool
    {
        return $race->draftees_alias ? true : false;
    }

    public function getPeasantsTerm(Race $race): string
    {
        return $this->hasPeasantsAlias($race) ? ucwords($race->peasants_alias) : 'Peasant';
    }

    public function getDrafteesTerm(Race $race): string
    {
        return $this->hasDrafteesAlias($race) ? ucwords($race->draftees_alias) : 'Draftee';
    }

    public function getSpyCost(Race $race): array
    {
        $cost = explode(',', $race->spies_cost);
        $spyCost['amount'] = $cost[0];
        $spyCost['resource'] = $cost[1];

        return $spyCost;
    }
    public function getWizardCost(Race $race): array
    {
        $cost = explode(',', $race->wizards_cost);
        $wizardCost['amount'] = $cost[0];
        $wizardCost['resource'] = $cost[1];

        return $wizardCost;
    }
    public function getArchmageCost(Race $race): array
    {
        $cost = explode(',', $race->archmages_cost);
        $archmageCost['amount'] = $cost[0];
        $archmageCost['resource'] = $cost[1];

        return $archmageCost;
    }

    public function hasLandImprovements(Race $race): bool
    {
        return isset($race->land_improvements);
    }
}
