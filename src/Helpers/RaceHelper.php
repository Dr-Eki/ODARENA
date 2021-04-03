<?php

namespace OpenDominion\Helpers;

use LogicException;
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
            case 'archmage_cost':
                $negativeBenefit = true;
                $description = 'Archmage cost';
                $valueType = 'p';
                break;
            case 'construction_cost':
                $negativeBenefit = true;
                $description = 'Construction cost';
                break;
            case 'rezone_cost':
                $negativeBenefit = true;
                $description = 'Rezoning cost';
                break;
            case 'defense':
                $negativeBenefit = false;
                $description = 'Defensive power';
                break;
            case 'extra_barren_max_population':
                $negativeBenefit = false;
                $description = 'Population from barren land';
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
            case 'barren_forest_lumber_production':
                $negativeBenefit = false;
                $description = 'Lumber production from barren forest';
                $booleanValue = 'static';
                $valueType = ' lumber/tick';
                break;
            case 'extra_ore_mine_housing':
                $negativeBenefit = false;
                $description = 'Additional housing in ore mines';
                $valueType = '';
                break;
            case 'food_consumption':
                $negativeBenefit = true;
                $description = 'Food consumption';
                break;
            case 'no_food_consumption':
                $negativeBenefit = false;
                $description = 'Does not eat food';
                $booleanValue = true;
                break;
            case 'food_production':
                $negativeBenefit = false;
                $description = 'Food production';
                break;
            case 'food_decay':
                $negativeBenefit = true;
                $description = 'Food decay';
                break;
            case 'lumber_decay':
                $negativeBenefit = true;
                $description = 'Lumber decay';
                break;
            case 'gem_production':
                $negativeBenefit = false;
                $description = 'Gem production';
                break;
            case 'tech_production':
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
            case 'invest_bonus':
                $negativeBenefit = false;
                $description = 'Improvement points';
                break;
            case 'lumber_production':
                $negativeBenefit = false;
                $description = 'Lumber production';
                break;
            case 'mana_production':
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
            case 'offense':
                $negativeBenefit = false;
                $description = 'Offensive power';
                break;
            case 'ore_production':
                $negativeBenefit = false;
                $description = 'Ore production';
                break;
            case 'gold_production':
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
            case 'cannot_construct':
                $negativeBenefit = true;
                $description = 'Cannot construct buildings';
                $booleanValue = true;
                break;
            case 'boat_capacity':
                $negativeBenefit = false;
                $description = 'Increased boat capacity';
                $valueType = ' units/boat';
                break;
            case 'can_invest_mana':
                $negativeBenefit = false;
                $description = 'Can use mana for improvements';
                $valueType = ' point(s) per mana';
                $booleanValue = 'static';
                break;
            case 'can_invest_soul':
                $negativeBenefit = false;
                $description = 'Can use souls for improvements';
                $booleanValue = true;
                break;
            case 'can_invest_food':
                $negativeBenefit = false;
                $description = 'Can use food for improvements';
                $booleanValue = true;
                break;
            case 'population_growth':
                $negativeBenefit = false;
                $description = 'Population growth rate';
                break;
          case 'cannot_improve_castle':
                $negativeBenefit = true;
                $description = 'Cannot use improvements';
                $booleanValue = true;
                break;
          case 'land_improvements':
                $negativeBenefit = false;
                $description = 'Land based improvements';
                $booleanValue = true;
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
            case 'guard_tax_exemption':
                $negativeBenefit = false;
                $description = 'Exempt from guard gold tax';
                $booleanValue = true;
                break;
          case 'tissue_improvement':
                $negativeBenefit = false;
                $description = 'Tissue improvements';
                $booleanValue = true;
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
            case 'all_units_trained_in_9hrs':
                $negativeBenefit = false;
                $description = 'All units trained in 9 ticks';
                $booleanValue = true;
                break;
            case 'extra_barracks_housing':
                $negativeBenefit = false;
                $description = 'Military housing in buildings that provide military housing';
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
            case 'tech_costs':
                $negativeBenefit = true;
                $description = 'Cost of technological advancements';
                break;
            case 'experience_points_per_acre':
                $negativeBenefit = false;
                $description = 'Experience points gained per acre on successful invasions';
                break;
            case 'cannot_tech':
                $negativeBenefit = true;
                $description = 'Cannot level up advancements';
                $booleanValue = true;
                break;
            case 'ore_improvement_points':
                $negativeBenefit = false;
                $description = 'Improvement points from ore';
                break;
            case 'lumber_improvement_points':
                $negativeBenefit = false;
                $description = 'Improvement points from lumber';
                break;
            case 'research_points_per_acre':
                $negativeBenefit = false;
                $description = 'Experience points per acre on invasions';
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
            case 'peasants_produce_food':
                $negativeBenefit = true;
                $description = 'Peasants produce food';
                $valueType = ' food/tick';
                $booleanValue = false;
                break;
            case 'unemployed_peasants_produce_gold':
                $negativeBenefit = false;
                $description = 'Peasants produce gold';
                $valueType = ' gold/tick';
                $booleanValue = false;
                break;
            case 'draftees_produce_mana':
                $negativeBenefit = false;
                $description = 'Draftees produce mana';
                $valueType = ' mana/tick';
                $booleanValue = false;
                break;
            case 'peasants_produce_mana':
                $negativeBenefit = false;
                $description = 'Peasants produce mana';
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
            case 'can_sell_food':
                $negativeBenefit = false;
                $description = 'Can exchange food';
                $booleanValue = true;
                break;
            case 'can_sell_mana':
                $negativeBenefit = false;
                $description = 'Can exchange mana';
                $booleanValue = true;
                break;
            case 'draftees_cannot_be_abducted':
                $negativeBenefit = false;
                $description = 'Draftees cannot be abducted';
                $booleanValue = true;
                break;
            case 'peasants_cannot_be_abducted':
                $negativeBenefit = false;
                $description = 'Peasants cannot be abducted';
                $booleanValue = true;
                break;
            case 'can_only_abduct_own':
                $negativeBenefit = false;
                $description = 'Cannot abduct peasants or draftees or be abducted';
                $booleanValue = true;
                break;
            case 'population_from_alchemy':
                $negativeBenefit = false;
                $description = 'Extra population per 1% Alchemies (max +30% population)';
                $booleanValue = false;
                break;
            case 'defense_from_forest':
                $negativeBenefit = false;
                $description = 'Defensive modifier per 1% Forest';
                $booleanValue = false;
                break;
            case 'offense_from_barren':
                $negativeBenefit = false;
                $description = 'Offensive modifier per 1% barren';
                $booleanValue = false;
                break;
            case 'forest_construction_cost':
                $negativeBenefit = true;
                $description = 'Forest construction cost';
                break;
            case 'salvaging':
                $negativeBenefit = false;
                $description = 'Salvages resources';
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
            case 'yeti_spies':
                $negativeBenefit = false;
                $description = 'Spies trained from wild yeti';
                $booleanValue = true;
                break;
            case 'yeti_wizards':
                $negativeBenefit = false;
                $description = 'Wizards trained from wild yeti';
                $booleanValue = true;
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

          default:
                return null;
        }

        $result = ['description' => $description, 'value' => ''];

        $valueString = "{$perkType->pivot->value}{$valueType}";

        if ($perkType->pivot->value < 0)
        {

            if($booleanValue === true)
            {
                $valueString = 'No';
            }
            else

            if($booleanValue == 'static')
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

        if(isset($adjectives[$race->name]))
        {
            return $adjectives[$race->name];
        }
        else
        {
            return $race->name;
        }
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
        $term = 'peasant';
        if($this->hasPeasantsAlias($race))
        {
            $term = $race->peasants_alias;
        }

        return ucwords($term);
    }

    public function getDrafteesTerm(Race $race): string
    {
        $term = 'draftee';
        if($this->hasDrafteesAlias($race))
        {
            $term = $race->draftees_alias;
        }

        return ucwords($term);
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

    /**
     * Returns the Dominion's construction materials.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getConstructionMaterials(Race $race): array
    {
        if($race->construction_materials === null)
        {
            return [];
        }
        return explode(',', $race->construction_materials);
    }

}
