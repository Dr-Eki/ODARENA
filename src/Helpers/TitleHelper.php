<?php

namespace OpenDominion\Helpers;

use LogicException;
use OpenDominion\Models\Title;
use OpenDominion\Models\TitlePerkType;

use OpenDominion\Models\Dominion;

class TitleHelper
{
    public function getPerkDescriptionHtmlWithValue(TitlePerkType $perkType): ?array
    {
        $valueType = '%';
        $booleanValue = false;
        switch($perkType->key) {
            case 'military_costs':
                $negativeBenefit = true;
                $description = 'Training costs:';
                $valueType = '% gold, ore, lumber, food, and mana costs';
                break;
            case 'unit_gold_costs':
                $negativeBenefit = true;
                $description = 'Unit gold costs:';
                break;
            case 'unit_ore_costs':
                $negativeBenefit = true;
                $description = 'Unit ore costs:';
                break;
            case 'unit_lumber_costs':
                $negativeBenefit = true;
                $description = 'Unit lumber costs:';
                break;
            case 'unit_mana_costs':
                $negativeBenefit = true;
                $description = 'Unit mana costs:';
                break;
            case 'unit_food_costs':
                $negativeBenefit = true;
                $description = 'Unit food costs:';
                break;
            case 'spell_cost':
                $negativeBenefit = true;
                $description = 'Spell costs:';
                break;
            case 'construction_cost':
                $negativeBenefit = true;
                $description = 'Construction costs:';
                break;
            case 'rezone_cost':
                $negativeBenefit = true;
                $description = 'Rezoning costs:';
                break;
            case 'improvements':
                $negativeBenefit = false;
                $description = 'Improvement points:';
                break;
            case 'all_theft':
                $negativeBenefit = true;
                $description = 'Theft losses:';
                break;
            case 'sabotage_damage_suffered':
                $negativeBenefit = true;
                $description = 'Sabotage damage suffered:';
                break;
            case 'spy_strength':
                $negativeBenefit = false;
                $description = 'Spy strength:';
                break;
            case 'spy_strength_on_defense':
                $negativeBenefit = false;
                $description = 'Spy strength on defense:';
                break;
            case 'spy_strength_on_offense':
                $negativeBenefit = false;
                $description = 'Spy strength on offense:';
                break;
            case 'theft_carry_capacity':
                $negativeBenefit = false;
                $description = 'Theft carry capacity:';
                break;
            case 'expedition_land_gains':
                $negativeBenefit = false;
                $description = 'Expedition land gains:';
                break;
            case 'expedition_prestige_gains':
                $negativeBenefit = false;
                $description = 'Expedition prestige gains:';
                break;
            case 'expedition_time':
                $negativeBenefit = true;
                $description = 'Expedition time:';
                $valueType = ' ticks';
                break;
            case 'explore_prestige_per_acre':
                $negativeBenefit = false;
                $description = 'Prestige per explored acre:';
                $valueType = '';
                break;
            case 'gold_production_mod':
                $negativeBenefit = false;
                $description = 'Gold production:';
                break;
            case 'ore_production_mod':
                $negativeBenefit = false;
                $description = 'Ore production:';
                break;
            case 'lumber_production_mod':
                $negativeBenefit = false;
                $description = 'Lumber production:';
                break;
            case 'gems_production_mod':
                $negativeBenefit = false;
                $description = 'Gem production:';
                break;
            case 'pearls_production_mod':
                $negativeBenefit = false;
                $description = 'Pearl production:';
                break;
            case 'mana_production_mod':
                $negativeBenefit = true;
                $description = 'Mana production:';
                break;
            case 'research_time':
                $negativeBenefit = true;
                $description = 'Research time:';
                break;
            case 'xp_generation_mod':
                $negativeBenefit = false;
                $description = 'XP generation:';
                break;
            case 'xp_gains':
                $negativeBenefit = false;
                $description = 'XP per acre gained:';
                break;
            case 'casualties':
                $negativeBenefit = true;
                $description = 'Casualties:';
                break;
            case 'conversions':
                $negativeBenefit = false;
                $description = 'Units converted (only applicable to Afflicted, Cult, and Sacred Order):';
                break;
            case 'exchange_rate':
                $negativeBenefit = false;
                $description = 'Exchange rates:';
                break;
            case 'mana_drain':
                $negativeBenefit = true;
                $description = 'Mana drain:';
                break;
            case 'spy_strength_recovery':
                $negativeBenefit = false;
                $description = 'Spy strength recovery:';
                $valueType = '%/tick';
                break;
            case 'prestige_gains':
                $negativeBenefit = false;
                $description = 'Prestige gains:';
                break;
            case 'morale_gains':
                $negativeBenefit = false;
                $description = 'Morale gains:';
                $valueType = '%';
                break;
            case 'increased_construction_speed':
                $negativeBenefit = false;
                $description = 'Increased construction speed';
                $valueType = ' ticks';
                break;
            case 'deity_power':
                $negativeBenefit = false;
                $description = 'Deity power:';
                break;
            default:
                $negativeBenefit = false;
                $description = 'Missing description for perk type: ' . $perkType->key;
                break;
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

    public function getRulerTitlePerksForDominion(Dominion $dominion): string
    {
        $rulerTitlePerks = [];

        foreach($dominion->title->perks as $perk)
        {
            $perkDescription = $this->getPerkDescriptionHtmlWithValue($perk);
            $perkDescription = $perkDescription['description'];
            $value = $dominion->title->getPerkMultiplier($perk->key);

            $rulerTitlePerks[$perkDescription] = round(($value * $dominion->getTitlePerkMultiplier())*100,2);
        }

        $rulerTitlePerksString = '<ul>';
        foreach($rulerTitlePerks as $perk => $value)
        {
            $rulerTitlePerksString .= '<li>' . $perk . '&nbsp;' . $value . '%</li>';
        }
        $rulerTitlePerksString .= '</ul>';

        return $rulerTitlePerksString;

    }

}
