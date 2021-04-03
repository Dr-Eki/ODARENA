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
            case 'spy_strength':
                $negativeBenefit = false;
                $description = 'Spy strength:';
                break;
            case 'explore_cost':
                $negativeBenefit = true;
                $description = 'Exploration gold costs:';
                break;
            case 'explore_time':
                $negativeBenefit = true;
                $description = 'Exploration time:';
                $valueType = ' ticks';
                break;
            case 'ore_production':
                $negativeBenefit = false;
                $description = 'Ore production:';
                break;
            case 'gem_production':
                $negativeBenefit = false;
                $description = 'Gem production:';
                break;
            case 'tech_production':
                $negativeBenefit = false;
                $description = 'XP production:';
                break;
            case 'casualties':
                $negativeBenefit = true;
                $description = 'Casualties:';
                break;
            case 'conversions':
                $negativeBenefit = false;
                $description = 'Units converted (only applicable to Afflicted, Cult, and Sacred Order):';
                break;
            case 'lumber_production':
                $negativeBenefit = false;
                $description = 'Lumber production:';
                break;
            case 'exchange_rate':
                $negativeBenefit = false;
                $description = 'Better exchange rates:';
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

    public function getRulerTitlePerksForDominion(Dominion $dominion): string
    {
        $rulerTitlePerks = [];

        foreach($dominion->title->perks as $perk)
        {
            $perkDescription = $this->getPerkDescriptionHtmlWithValue($perk);
            $perkDescription = $perkDescription['description'];
            $value = $dominion->title->getPerkMultiplier($perk->key);

            $rulerTitlePerks[$perkDescription] = round(($value * $dominion->title->getPerkBonus($dominion))*100,2);
        }

        $rulerTitlePerksString = '<ul>';
        foreach($rulerTitlePerks as $perk => $value)
        {
            $rulerTitlePerksString .= '<li>' . $perk . '&nbsp;' . $value . '%</li>';
        }
        $rulerTitlePerksString .= '<ul>';

        return $rulerTitlePerksString;

    }

}
