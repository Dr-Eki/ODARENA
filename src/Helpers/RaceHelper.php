<?php

namespace OpenDominion\Helpers;

use OpenDominion\Models\Race;
use OpenDominion\Models\RacePerkType;
use OpenDominion\Models\Resource;
use OpenDominion\Models\UnitPerk;
use OpenDominion\Models\UnitPerkType;

use OpenDominion\Models\Dominion;

use OpenDominion\Services\Dominion\StatsService;

class RaceHelper
{

    protected $chroniclesHelper;
    protected $unitHelper;
    protected $statsService;

    public function __construct()
    {
        $this->chroniclesHelper = app(ChroniclesHelper::class);
        $this->unitHelper = app(UnitHelper::class);
        $this->statsService = app(StatsService::class);
    }

    public function getPerkDescriptionHtmlWithValue(RacePerkType $perkType): ?array
    {
        $valueType = '%';
        $booleanValue = false;
        switch($perkType->key) {
            case 'defensive_power':
                $negativeBenefit = false;
                $description = 'Defensive power';
                break;
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
            case 'barren_housing_only_on_water':
                $negativeBenefit = true;
                $description = 'No housing from land other than water';
                $booleanValue = true;
                break;
            case 'extra_barren_housing':
                $negativeBenefit = false;
                $description = 'Extra housing from barren land';
                $valueType = '';
                break;
            case 'extra_barren_housing_per_victory':
                $negativeBenefit = false;
                $description = 'Extra housing from barren land per victory';
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
            case 'extra_research_slots':
                $negativeBenefit = false;
                $description = 'Extra research slot';
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
            case 'prayer_production_raw_from_population':
                $negativeBenefit = false;
                $description = 'All population produces';
                $valueType = ' prayers per tick';
                $booleanValue = 'static';
                break;
            case 'cosmic_alignment_decay':
                $negativeBenefit = false;
                $description = 'Cosmic alignments decay per tick';
                $booleanValue = 'static';
                $valueType = '%';
                break;
            case 'sapling_decay':
                $negativeBenefit = false;
                $description = 'Sapling decay per tick';
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
            case 'xp_generation_raw_from_draftees':
                $negativeBenefit = false;
                $description = 'XP generated per draftee';
                $booleanValue = 'static';
                $valueType = ' / tick';
                break;
            case 'food_production_raw_from_draftees':
                $negativeBenefit = false;
                $description = 'Food produced per draftee';
                $booleanValue = 'static';
                $valueType = ' / tick';
                break;
            case 'gold_production_raw_from_draftees':
                $negativeBenefit = false;
                $description = 'Gold produced per draftee';
                $booleanValue = 'static';
                $valueType = ' / tick';
                break;
            case 'ore_production_raw_from_draftees':
                $negativeBenefit = false;
                $description = 'Ore produced per draftee';
                $booleanValue = 'static';
                $valueType = ' / tick';
                break;
            case 'ash_production_raw_from_draftees':
                $negativeBenefit = false;
                $description = 'Ash gathered per draftee';
                $booleanValue = 'static';
                $valueType = ' / tick';
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
            case 'no_prestige':
                $negativeBenefit = false;
                $description = 'No prestige';
                $booleanValue = true;
                break;
            case 'no_prestige_loss_on_failed_invasions':
                $negativeBenefit = false;
                $description = 'No prestige change on failed invasions';
                $booleanValue = true;
                break;
            case 'no_morale_loss_on_failed_invasions':
                $negativeBenefit = false;
                $description = 'No morale change on failed invasions';
                $booleanValue = true;
                break;
            case 'no_morale_changes':
                $negativeBenefit = false;
                $description = 'No morale changes';
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
            case 'cannot_build':
                $negativeBenefit = true;
                $description = 'Cannot construct buildings';
                $booleanValue = true;
                break;
            case 'growth_cannot_build':
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
            case 'improvements_per_net_victory':
                $negativeBenefit = false;
                $description = 'Improvements bonus';
                $valueType = '% per net victory (min 0)';
                $booleanValue = 'static';
                break;
            case 'improvement_points':
                $negativeBenefit = false;
                $description = 'Improvement points';
                $valueType = '%';
                $booleanValue = 'static';
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
            case 'cannot_issue_decrees':
                $negativeBenefit = true;
                $description = 'Cannot issue decrees';
                $booleanValue = true;
                break;
            case 'cannot_perform_sorcery':
                $negativeBenefit = true;
                $description = 'Cannot perform sorcery';
                $booleanValue = true;
                break;
            case 'cannot_sabotage':
                $negativeBenefit = true;
                $description = 'Cannot sabotage';
                $booleanValue = true;
                break;
            case 'cannot_send_expeditions':
                $negativeBenefit = true;
                $description = 'Cannot send expeditions';
                $booleanValue = true;
                break;
            case 'cannot_steal':
                $negativeBenefit = true;
                $description = 'Cannot steal';
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
                $description = 'Cannot train Archmages';
                $booleanValue = true;
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
            case 'spies_training_time':
                $negativeBenefit = false;
                $description = 'Spies training time:';
                $booleanValue = 'static';
                $valueType = '&nbsp;ticks';
                break;
            case 'wizards_training_time':
                $negativeBenefit = false;
                $description = 'Wizards training time:';
                $booleanValue = 'static';
                $valueType = '&nbsp;ticks';
                break;
            case 'archmages_training_time':
                $negativeBenefit = false;
                $description = 'Archmages training time:';
                $booleanValue = 'static';
                $valueType = '&nbsp;ticks';
                break;
            case 'reduced_conversions':
                $negativeBenefit = false;
                $description = 'Reduced conversions';
                break;
            case 'exchange_rate':
                $negativeBenefit = false;
                $description = 'Exchange rates';
                break;
            case 'does_not_kill':
                $negativeBenefit = false;
                $description = 'Does not kill units';
                $booleanValue = true;
                break;
            case 'prestige_gains':
                $negativeBenefit = false;
                $description = 'Prestige gains';
                break;
            case 'no_drafting':
                $negativeBenefit = false;
                $description = 'No drafting';
                $booleanValue = true;
                break;
            case 'no_draftee_for_spies':
                $negativeBenefit = false;
                $description = 'No draftee required to train spies';
                $booleanValue = true;
                break;
            case 'no_draftee_for_wizards':
                $negativeBenefit = false;
                $description = 'No draftee required to train spies';
                $booleanValue = true;
                break;
            case 'draftees_count_as_wizards':
                $negativeBenefit = false;
                $description = 'Draftees count as wizards';
                $valueType = '';
                $booleanValue = 'static';
                break;
            case 'draftee_dp':
                $negativeBenefit = true;
                $description = 'DP per draftee';
                $valueType = '';
                $booleanValue = 'static';
                break;
            case 'peasant_dp':
                $negativeBenefit = true;
                $description = 'DP per peasant';
                $valueType = '';
                $booleanValue = 'static';
                break;
            case 'increased_construction_speed':
                $negativeBenefit = false;
                $description = 'Increased construction speed';
                $valueType = ' ticks';
                break;
            case 'drafting':
                $negativeBenefit = false;
                $description = 'Peasants drafted per tick';
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
            case 'morale_per_percentage_castle_buildings':
                $negativeBenefit = false;
                $description = 'Morale per percentage of castle buildings';
                $valueType = '';
            break;
            case 'improvements':
                $negativeBenefit = false;
                $description = 'Improvements';
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
            case 'max_advancements_level':
                $negativeBenefit = false;
                $description = 'Max advancements level';
                $valueType = '';
                $booleanValue = 'static';
                break;
            case 'cannot_tech':
                $negativeBenefit = true;
                $description = 'Cannot level up advancements';
                $booleanValue = true;
                break;
            case 'cannot_improve':
                $negativeBenefit = true;
                $description = 'Cannot use improvements';
                $booleanValue = true;
                break;
            case 'advancement_costs':
                $negativeBenefit = true;
                $description = 'Cost of technological advancements';
                break;
            case 'experience_points_per_acre':
                $negativeBenefit = false;
                $description = 'XP gained per acre on successful invasions';
                break;
            case 'xp_gains':
                $negativeBenefit = false;
                $description = 'XP per acre on invasions';
                break;
            case 'damage_from_lightning_bolt':
                $negativeBenefit = true;
                $description = 'Damage from Lightning Bolts';
                $booleanValue = false;
                break;
            case 'damage_from_fireball':
                $negativeBenefit = true;
                $description = 'Damage from Fireballs';
                $booleanValue = false;
                break;
            case 'damage_from_blight':
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
            case 'draftees_produce_food':
                $negativeBenefit = false;
                $description = 'Draftees produce food';
                $valueType = ' food/tick';
                $booleanValue = false;
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
            case 'cannot_research':
                $negativeBenefit = true;
                $description = 'Cannot research';
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
            case 'max_gunpowder_per_cannon':
                $negativeBenefit = false;
                $description = 'Max gunpowder storage';
                $valueType = ' per Cannon';
                $booleanValue = 'static';
                break;
            case 'title_bonus':
                $negativeBenefit = false;
                $description = 'Ruler Title bonus';
                $booleanValue = false;
                break;
            case 'no_ruler_title_perks':
                $negativeBenefit = false;
                $description = 'No perks from title';
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
            case 'deity_power':
                $negativeBenefit = false;
                $description = 'Deity perks';
                break;
            case 'cannot_submit_to_deity':
                $negativeBenefit = true;
                $description = 'Cannot submit to a deity';
                $booleanValue = true;
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
            case 'starts_devoted_to_druva':
                $negativeBenefit = false;
                $description = 'Starts devoted to Druva';
                $booleanValue = true;
                break;
            case 'starts_devoted_to_elskas':
                $negativeBenefit = false;
                $description = 'Starts devoted to Elskas';
                $booleanValue = true;
                break;
            case 'starts_devoted_to_glimj':
                $negativeBenefit = false;
                $description = 'Starts devoted to Glimj';
                $booleanValue = true;
                break;
            case 'starts_devoted_to_tiranthael':
                $negativeBenefit = false;
                $description = 'Starts devoted to Tiranthael';
                $booleanValue = true;
                break;
            case 'starts_devoted_to_urugdakh':
                $negativeBenefit = false;
                $description = 'Starts devoted to Urugdakh';
                $booleanValue = true;
                break;
            case 'gains_strength':
                $negativeBenefit = false;
                $description = 'Gains strength';
                $booleanValue = true;
                break;
            case 'grows_bodyparts':
                $negativeBenefit = false;
                $description = 'Grows bodyparts';
                $booleanValue = true;
                break;
            case 'improvements_from_souls':
                $negativeBenefit = false;
                $description = 'Souls increase improvements';
                $booleanValue = true;
                break;
            case 'sabotage_damage_dealt':
                $negativeBenefit = true;
                $description = 'Sabotage damage dealt';
                $valueType = '%';
                $booleanValue = 'static';
                break;
            case 'sabotage_damage_suffered':
                $negativeBenefit = true;
                $description = 'Sabotage damage suffered';
                $valueType = '%';
                $booleanValue = 'static';
                break;
            case 'caverns_required_to_send_units':
                $negativeBenefit = true;
                $description = 'Caverns required to send units';
                $booleanValue = true;
                break;
            case 'light_drains_mana':
                $negativeBenefit = false;
                $description = 'Light drains';
                $valueType = ' mana/tick';
                $booleanValue = 'static';
                break;
            case 'can_capture_buildings':
                $negativeBenefit = false;
                $description = 'Can capture buildings on successful invasions';
                $booleanValue = true;
                break;
            case 'can_desecrate':
                $negativeBenefit = false;
                $description = 'Can desecrate';
                $booleanValue = true;
                break;
            case 'can_invade_at_any_morale':
                $negativeBenefit = false;
                $description = 'Can invade regardless of morale';
                $booleanValue = true;
                break;
            case 'saplings_per_forest':
                $negativeBenefit = false;
                $description = 'Saplings stored per acre of forest';
                $booleanValue = true;
                break;
            case 'enemy_casualties':
                $negativeBenefit = false;
                $description = 'Enemy casualties';
                $valueType = '%';
                break;
            case 'starting_land_only_home_terrain':
                $negativeBenefit = false;
                $description = 'Starting land is only home terrain';
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
            'Cires' => 'Ciresine',
            'Cult' => 'Cultist',
            'Dark Elf' => 'Dark Elven',
            'Demon' => 'Demonic',
            'Dimensionalists' => 'Dimensionalist',
            'Dwarg' => 'Dwargen',
            'Elementals' => 'Elemental',
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
            'Werewolves' => 'Werewolven',
            'Wood Elf' => 'Wood Elven',

            'Gorm' => 'Gormish',
            'Arwe' => 'Arwae',
        ];

        return isset($adjectives[$race->name]) ? $adjectives[$race->name] : $race->name;

    }

    public function getRacePerksHelpString(Race $race): string
    {
        $string = '<ul>';

        # Peasant production
        if(!$race->getPerkValue('no_population'))
        {
            $string .= $this->getPeasantsTerm($race) . ' production: ';

            $x = 0;
            $peasantProductions = count($race->peasants_production);
    
            foreach($race->peasants_production as $resourceKey => $amount)
            {
                $string .= '<li>';
    
                $resource = Resource::where('key', $resourceKey)->first();
                $x++;
    
                if($x < $peasantProductions)
                {
                    $string .= number_format($amount,2) . ' ' . $resource->name . ',';
                }
                else
                {
                    $string .= number_format($amount,2) . ' ' . $resource->name;
                }
    
                $string .= '</li>';
            }
        }

        # Faction perks
        foreach ($race->perks as $perk)
        {
            $string .= '<li>';

            $perkDescription = $this->getPerkDescriptionHtmlWithValue($perk);
            $string .= $perkDescription['description'] . ': ' . $perkDescription['value'];

            $string .= '</li>';
        }

        $string .= '</ul>';

        $string = str_replace('"',"'", $string);

        return $string;
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

    public function getAttritionTermVerb(?Race $race): string
    {
        $defaultValue = 'disappeared';

        if ($race === null) {
            return $defaultValue;
        }

        $lookup = [
            'snow_elf' => 'left us',
        ];

        return $lookup[$race->key] ?? $defaultValue;

    }

    public function getEvolutionTermVerb(?Race $race): string
    {
        $defaultValue = 'evolved';

        if ($race === null) {
            return $defaultValue;
        }

        $lookup = [
            'vampires' => 'advanced',
        ];

        return $lookup[$race->key] ?? $defaultValue;

    }

    public function getSummoningTermVerb(?Race $race): string
    {
        $defaultValue = 'summoned';

        if ($race === null) {
            return $defaultValue;
        }
    
        $lookup = [
            'snow_elf' => 'arrived',
            'growth' => 'mutated',
        ];
    
        return $lookup[$race->key] ?? $defaultValue;
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

    #   *   *   *   *    *    *   *   *   *   *   *   *   *   *   *   *   *   *   *   *   *   *   *   *   *   *   #
    #   BEGIN CHRONICLES

    public function getDominionCountForRace(Race $race, bool $inclueActiveRounds = false, int $maxRoundsAgo = 999): int
    {
        return $this->getRaceDominions($race, $maxRoundsAgo)->count();
    }

    public function getRaceDominions(Race $race, bool $inclueActiveRounds = false, int $maxRoundsAgo = 20)
    {
          $dominions = Dominion::where('race_id', $race->id)
                        ->where('is_locked','=',0)
                        ->where('protection_ticks','=',0)
                        ->whereRaw(' round_id >= (SELECT max(number) FROM rounds) - ?', [$maxRoundsAgo])
                        ->get();

          if(!$inclueActiveRounds)
          {
              foreach($dominions as $key => $dominion)
              {
                  if(!$dominion->round->hasEnded())
                  {
                      $dominions->forget($key);
                  }
              }
          }

          return $dominions;
    }

    public function getTotalLandForRace(Race $race, bool $lifetime = true): int
    {
        $totalLand = 0;

        $maxRoundsAgo = $this->chroniclesHelper->getDefaultRoundsAgo();
        if($lifetime)
        {
            $maxRoundsAgo = $this->chroniclesHelper->getMaxRoundNumber();
        }

        foreach($this->getRaceDominions($race, false, $maxRoundsAgo) as $dominion)
        {
            $totalLand += $dominion->land;
        }

        return $totalLand;
    }

    public function getMaxLandForRace(Race $race, bool $lifetime = true): int
    {
        $land = 0;

        $maxRoundsAgo = $this->chroniclesHelper->getDefaultRoundsAgo();
        if($lifetime)
        {
            $maxRoundsAgo = $this->chroniclesHelper->getMaxRoundNumber();
        }

        foreach($this->getRaceDominions($race, false, $maxRoundsAgo) as $dominion)
        {
            $land = max($land, $dominion->land);
        }

        return $land;
    }

    public function getStatSumForRace(Race $race, string $statKey): float
    {
        $value = 0.00;

        foreach($this->getRaceDominions($race) as $dominion)
        {
            $value += $this->statsService->getStat($dominion, $statKey);
        }

        return $value;
    }

    public function getStatMaxForRace(Race $race, string $statKey): float
    {
        $value = 0.00;

        foreach($this->getRaceDominions($race) as $dominion)
        {
            $value = max($this->statsService->getStat($dominion, $statKey), $value);
        }

        return $value;
    }

    public function getUniqueRulersCountForRace(Race $race, bool $lifetime = true): int
    {
        $rulers = [];

        $maxRoundsAgo = $this->chroniclesHelper->getDefaultRoundsAgo();
        if($lifetime)
        {
            $maxRoundsAgo = $this->chroniclesHelper->getMaxRoundNumber();
        }

        foreach($this->getRaceDominions($race, false, $maxRoundsAgo) as $dominion)
        {
            if(!$dominion->isAbandoned())
            {
                $rulers[] = $dominion->user->id;
            }
        }

        $rulers = array_unique($rulers);

        return count($rulers);
    }

    # Reserved for future where Race class is extended.
    public function getBasePsionicStrength(Race $race): float
    {
        return $race->psionic_strength ?: 1;
    }

    public function getAllUnitsAttributes(Race $race): array
    {
        $attributes = [];
        foreach($race->units as $unit)
        {
            foreach($unit->type as $attribute)
            {
                $attributes[] = $attribute;
            }
        }
        return array_unique($attributes);
    }

    # Checks if any of the race's units have the given πerk
    public function checkIfRaceUnitsHavePerks(Race $race, array $perkKeys): bool
    {

        foreach($perkKeys as $perkKey)
        {
            $unitPerkType = UnitPerkType::where('key', $perkKey)->first();    

            if(!$unitPerkType)
            {
                return false;
            }

            $unitPerk = UnitPerk::where('unit_perk_type_id', $unitPerkType->id)->first();
    
            if(!$unitPerk)
            {
                return false;
            }
            
            foreach($race->units as $unit)
            {
                if($unit->perks->contains($unitPerk))
                {
                    return true;
                }
            }    
        }

        return false;
    }
}
