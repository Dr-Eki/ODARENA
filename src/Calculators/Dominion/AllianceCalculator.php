<?php

namespace OpenDominion\Calculators\Dominion;

use Illuminate\Support\Collection;

use OpenDominion\Models\AllianceOffer;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Realm;
use OpenDominion\Models\RealmAlliance;

class AllianceCalculator
{

    private $militaryCalculator;

    public function __construct()
    {
        $this->militaryCalculator = app(MilitaryCalculator::class);
    }

    public function canFormAllianceWithRealm(Realm $source, Realm $target, Dominion $inviter): bool
    {
        # Round mode must be in factions or factions-duration
        if (!in_array($source->round->mode, ['factions', 'factions-duration'])) {
            return false;
        }

        # Inviter must be monarch
        if (!$inviter->isMonarch()) {
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

    public function canRescindAllianceOffer(Dominion $rescinder, AllianceOffer $allianceOffer): bool
    {
        if($allianceOffer->inviter->id == $rescinder->realm->id)
        {
            return true;
        }

        return false;
    }

    public function getPendingReceivedAllianceOffers(Realm $realm): Collection
    {
        return AllianceOffer::where('invited_realm_id', $realm->id)
            ->get();
    }

    public function getPendingSentAllianceOffers(Realm $realm): Collection
    {
        return AllianceOffer::where('inviter_realm_id', $realm->id)
            ->get();
    }

    public function checkPendingAllianceOfferBetweenRealms(Realm $source, Realm $target): bool
    {

        # See if there are any alliances offers between $source and $target
        $offers = AllianceOffer::where('inviter_realm_id', $source->id)
            ->where('invited_realm_id', $target->id)
            ->count();

        $offers += AllianceOffer::where('inviter_realm_id', $target->id)
            ->where('invited_realm_id', $source->id)
            ->count();

        return ($offers > 0);
    }

    public function canBreakAlliance(RealmAlliance $realmAlliance, Dominion $breaker): bool
    {
        # Breaker must be monarch
        if (!$breaker->isMonarch()) {
            return false;
        }

        # Alliance have been established at least 192 ticks ago
        if (($breaker->round->ticks - $realmAlliance->established_tick) < 192) {
            return false;
        }

        # Breaker's realm must be in this alliance
        if ($realmAlliance->getRealms()->where('id', $breaker->realm->id)->isEmpty()) {
            return false;
        }

        return true;
    }

}
