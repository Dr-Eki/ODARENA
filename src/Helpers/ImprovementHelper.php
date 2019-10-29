<?php

namespace OpenDominion\Helpers;

class ImprovementHelper
{
    public function getImprovementTypes(string $race): array
    {

      if($race == 'Growth')
      {
        $improvementTypes[] = 'tissue';
      }
      else
      {
        $improvementTypes = array(
            'science',
            'keep',
            'towers',
            'forges',
            'walls',
            'harbor',
            'armory',
            'infirmary'
          );
      }

      // For rules in ImproveActionRequest (???)
      if($race == 'any_race')
      {
        $improvementTypes[] = 'tissue';
      }

      return $improvementTypes;

    }

    public function getImprovementRatingString(string $improvementType): string
    {
        $ratingStrings = [
            'science' => '+%s%% platinum production',
            'keep' => '+%s%% max population',
            'towers' => '+%1$s%% wizard power, +%1$s%% mana production, -%1$s%% damage from spells',
            'forges' => '+%s%% offensive power',
            'walls' => '+%s%% defensive power',
            'harbor' => '+%s%% food production, boat production & protection',
            'armory' => '-%s%% unit training costs',
            'infirmary' => '-%s%% fewer casualties',
            'tissue' => '+%s%% cells',
        ];

        return $ratingStrings[$improvementType] ?: null;
    }

    public function getImprovementHelpString(string $improvementType): string
    {
        $helpStrings = [
            'science' => 'Improvements to science increase your platinum production.<br><br>Max +20% platinum production.',
            'keep' => 'Improvements to your keep increase your maximum population.<br><br>Max +30% max population.',
            'towers' => 'Improvements to your towers increase your wizard strength, mana production, and reduce damage from harmful spells.<br><br>Max +40% base towers.',
            'forges' => 'Improvements to your forges increase your offensive power.<br><br>Max +30% offensive power.',
            'walls' => 'Improvements to your walls increase your defensive power.<br><br>Max +30% defensive power.',
            'harbor' => 'Improvements to your harbor improve your food production, boat production and boat protection.<br><br>Max +40% base harbor.',
            'armory' => 'Improvements to your armory reduces the cost of training military units.<br><br>Max -20% cost.',
            'infirmary' => 'Improvements to your infirmary reduces casualties suffered in battle.<br><br>Max -30% casualties.',
            'tissue' => 'Feed the tissue to grow more cells.',
        ];

        return $helpStrings[$improvementType] ?: null;
    }

    // temp
    public function getImprovementImplementedString(string $improvementType): ?string
    {
        if ($improvementType === 'towers') {
            return '<abbr title="Partially implemented" class="label label-warning">PI</abbr>';
        }

        return null;
    }
}
