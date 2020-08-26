<?php

namespace OpenDominion\Calculators\Dominion\Actions;

use OpenDominion\Models\Dominion;

class BankingCalculator
{
    /**
     * Returns resources and prices for exchanging.
     *
     * @param Dominion $dominion
     * @return array
     */
    public function getResources(Dominion $dominion): array
    {

        $foodSell = 0;
        $manaSell = 0;
        if($dominion->race->getPerkMultiplier('can_sell_food'))
        {
          $foodSell = 0.50;
        }
        if($dominion->race->getPerkMultiplier('can_sell_mana'))
        {
          $manaSell = 0.10;
        }

        $resources = [
            'resource_platinum' => [
                'label' => 'Platinum',
                'buy' => 1.0,
                'sell' => 0.5,
                'max' => $dominion->resource_platinum,
            ],
            'resource_lumber' => [
                'label' => 'Lumber',
                'buy' => 1.0,
                'sell' => 0.5,
                'max' => $dominion->resource_lumber,
            ],
            'resource_ore' => [
                'label' => 'Ore',
                'buy' => 1.0,
                'sell' => 0.5,
                'max' => $dominion->resource_ore,
            ],
            'resource_gems' => [
                'label' => 'Gems',
                'buy' => 0.0,
                'sell' => 2.0,
                'max' => $dominion->resource_gems,
            ],
            'resource_food' => [
                'label' => 'Food',
                'buy' => 0.5,
                'sell' => $foodSell,
                'max' => $dominion->resource_food,
            ],
            'resource_mana' => [
                'label' => 'Mana',
                'buy' => 0,
                'sell' => $manaSell,
                'max' => $dominion->resource_mana,
            ],
        ];

          $bonus = 1;

          // Get racial bonus
          $bonus += $dominion->race->getPerkMultiplier('exchange_bonus');

          // Techs
          $bonus += $dominion->getTechPerkMultiplier('exchange_rate');

          // Ruler Title: Merchant
          $bonus += $dominion->title->getPerkMultiplier('exchange_rate') * $dominion->title->getPerkBonus($dominion);;

          $resources['resource_platinum']['sell'] *= $bonus;
          $resources['resource_lumber']['sell'] *= $bonus;
          $resources['resource_ore']['sell'] *= $bonus;
          $resources['resource_gems']['sell'] *= $bonus;
          $resources['resource_food']['sell'] *= $bonus;
          $resources['resource_mana']['sell'] *= $bonus;

        return $resources;
    }
}
