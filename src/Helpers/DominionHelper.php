<?php

namespace OpenDominion\Helpers;

use OpenDominion\Models\Dominion;
use OpenDominion\Models\GameEvent;
#use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\ProductionCalculator;

class DominionHelper
{

    #/** @var MilitaryCalculator */
   #protected $militaryCalculator;

    /** @var ProductionCalculator */
    protected $productionCalculator;

    public function __construct()
    {
        #$this->militaryCalculator = app(MilitaryCalculator::class);
        $this->productionCalculator = app(ProductionCalculator::class);
    }

    public function isEnraged(Dominion $dominion): bool
    {
        if($dominion->race->name !== 'Sylvan')
        {
            return false;
        }
    
        $enragedMaxTicksAgo = 24;
    
        return GameEvent::query()
            ->where('tick', '>=', ($dominion->round->ticks - $enragedMaxTicksAgo))
            ->where([
                'target_type' => Dominion::class,
                'target_id' => $dominion->id,
                'type' => 'invasion',
            ])
            ->where('data->result->overwhelmed', '!=', true)
            ->exists();
    }
    

    public function getTicksActive(Dominion $dominion): int
    {
        $ticks = 0;

        return $ticks;
    }

    public function getActionsTaken(Dominion $dominion): int
    {
        $actions = 0;

        return $actions;
    }

    public function getPrestigeHelpString(Dominion $dominion): string
    {

        $string = sprintf(
            '<small class="text-muted">Effective:</small> %s<br>
            <small class="text-muted">Actual:</small> %s<br>
            <small class="text-muted">Interest:</small> %s<small class=""> / tick</small>' ,
            number_format(floor($dominion->prestige)),
            number_format(floatval($dominion->prestige),8),
            floatval($this->productionCalculator->getPrestigeInterest($dominion))
          );

        return $string;
    }


}
