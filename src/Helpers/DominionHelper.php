<?php

namespace OpenDominion\Helpers;

use DB;
use User;

use OpenDominion\Models\Dominion;
use OpenDominion\Models\GameEvent;
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


    public function canChangeName(Dominion $dominion): bool
    {
        return ($dominion->round->hasStarted() or $dominion->protection_ticks > 0);
    }

    public function isAllowedDominionName(string $dominionName, bool $isNameChange = false, ?Dominion $dominion = null): bool
    {
        $barbarianUsers = DB::table('users')
            ->where('users.email', 'like', 'barbarian%@odarena.com')
            ->pluck('users.id')
            ->toArray();

        foreach($barbarianUsers as $barbarianUserId)
        {
            $barbarianUser = User::findorfail($barbarianUserId);

            if(stristr($dominionName, $barbarianUser->display_name))
            {
                return false;
            }
        }
        
        return true;
    }

    public function isNameUnique(Dominion $dominion, string $name): bool
    {
        # Dominion name must be unique for the round
        $dominions = $dominion->round->dominions;
    
        # Check if $name is in any $dominions->name
        foreach ($dominions as $existingDominion)
        {
            if ($existingDominion->id != $dominion->id && $existingDominion->name == $name) {
                return false; // The name already exists within this round
            }
        }
    
        return true; // The name is unique within this round
    }
    

}
