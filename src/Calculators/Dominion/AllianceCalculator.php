<?php

namespace OpenDominion\Calculators\Dominion;

use Illuminate\Support\Collection;

use OpenDominion\Models\Alliance;
use OpenDominion\Models\AllianceOffer;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Realm;

class AllianceCalculator
{

    public function __construct()
    {
        $this->militaryCalculator = app(MilitaryCalculator::class);
    }

    public function canFormAllianceWithRealm(Realm $source, Realm $target): bool
    {
        # Round mode must be in factions or factions-duration
        if (!in_array($source->round->mode, ['factions', 'factions-duration'])) {
            return false;
        }

        # Realms cannot be the same
        if ($source->id === $target->id) {
            return false;
        }

        # Realms must be in the same round
        if ($source->round_id !== $target->round_id) {
            return false;
        }

        # Both realms must have monarchs
        if (!$source->hasMonarch() or !$target->hasMonarch()) {
            return false;
        }

        # Source and targot cannot have attacked each other in the last 48 ticks
        if ($this->militaryCalculator->getRecentAttacksBetweenRealms($source, $target, 48) > 0) {
            return false;
        }

        return true;
    }


}
