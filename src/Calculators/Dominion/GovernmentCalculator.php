<?php

namespace OpenDominion\Calculators\Dominion;

#use DB;
#use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Realm;

use OpenDominion\Helpers\GovernmentHelper;
use OpenDominion\Models\ProtectorshipOffer;

class GovernmentCalculator
{
    public function __construct()
    {
        $this->governmentHelper = app(GovernmentHelper::class);
    }

    protected const TICKS_BETWEEN_VOTES = 192;

    public function canVote(Dominion $dominion): bool
    {
        if(isset($dominion->tick_voted))
        {
            #dd($dominion->tick_voted < ($dominion->round->ticks - static::TICKS_BETWEEN_VOTES), $dominion->tick_voted);
            return $dominion->tick_voted < ($dominion->round->ticks - static::TICKS_BETWEEN_VOTES);
        }

        return true;
    }

    public function getTicksUntilCanVote(Dominion $dominion): int
    {
        $tickWhenCanVote = $dominion->tick_voted + static::TICKS_BETWEEN_VOTES;
        $currentTick = $dominion->round->ticks;

        if($tickWhenCanVote <= $currentTick)
        {
            return 0; #Can vote now
        }

        return $tickWhenCanVote - $currentTick;
    }

    public function getUnprotectedArtilleryDominions(Realm $realm): Collection
    {
        $dominions = $realm->dominions->flatten();
        $unprotectedArtilleryDominions = $dominions->filter(function ($dominion) {
            return ($dominion->race->name == 'Artillery' and !$dominion->hasProtector());
        });

        return $unprotectedArtilleryDominions;
    }

    public function getAvailableProtecetors(Realm $realm): Collection
    {
        $protectorRaces = $this->governmentHelper->getProtectorRaces();#'Black Orc', 'Dark Elf', 'Icekin', 'Legion', 'Orc', 'Reptilians'];
        $dominions = $realm->dominions->flatten();
        $availableProtectors = $dominions->filter(function ($dominion, $protectorRaces) {
            return (in_array($dominion->race->name, $protectorRaces) and !$dominion->isProtector());
        });

        return $availableProtectors;
    }

    public function canOfferProtectorship(Dominion $protector): bool
    {
        if($protector->isProtector())
        {
            return false;
        }

        if($protector->protectorshipOffered->count() > 0)
        {
            return false;
        }

        // Check if dominion race is a protector race
        return $this->governmentHelper->getProtectorRaces()->contains($protector->race);
    }

    public function canRescindProtectorshipOffer(Dominion $protector, ProtectorshipOffer $protectorshipOffer): bool
    {
        if($protectorshipOffer->protector->id == $protector->id)
        {
            return true;
        }

        return false;
    }

    public function canBeProtector(Dominion $protector): bool
    {
        if($protector->isProtector())
        {
            return false;
        }

        // Check if dominion race is a protector race
        return $this->governmentHelper->getProtectorRaces()->contains($protector->race);
    }

    public function canBeProtected(Dominion $protected): bool
    {
        if($protected->race->name == 'Artillery' and !$protected->hasProtector() and !$protected->isProtector())
        {
            return true;
        }

        return false;
    }


}
