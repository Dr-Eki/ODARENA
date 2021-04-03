<?php

namespace OpenDominion\Calculators\Dominion;

use Illuminate\Support\Collection;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\GuardMembershipService;
use OpenDominion\Services\Dominion\ProtectionService;

# ODA
use OpenDominion\Calculators\Dominion\MilitaryCalculator;

class RangeCalculator
{
    public const MINIMUM_RANGE = 0.4;

    /** @var LandCalculator */
    protected $landCalculator;

    /** @var ProtectionService */
    protected $protectionService;

    /** @var GuardMembershipService */
    protected $guardMembershipService;

    /** @var MilitaryCalculator */
    protected $militaryCalculator;

    /**
     * RangeCalculator constructor.
     *
     * @param LandCalculator $landCalculator
     * @param ProtectionService $protectionService
     * @param GuardMembershipService $guardMembershipService
     */
    public function __construct(
        LandCalculator $landCalculator,
        ProtectionService $protectionService,
        GuardMembershipService $guardMembershipService,
        MilitaryCalculator $militaryCalculator
    ) {
        $this->landCalculator = $landCalculator;
        $this->protectionService = $protectionService;
        $this->guardMembershipService = $guardMembershipService;
        $this->militaryCalculator = $militaryCalculator;
    }

    /**
     * Checks whether dominion $target is in range of dominion $self.
     *
     * @param Dominion $self
     * @param Dominion $target
     * @return bool
     */
    public function isInRange(Dominion $self, Dominion $target): bool
    {
        $selfLand = $this->landCalculator->getTotalLand($self);
        $targetLand = $this->landCalculator->getTotalLand($target);

        $selfModifier = $this->getRangeModifier($self);
        $targetModifier = $this->getRangeModifier($target);

        return (
          (
            ($targetLand >= ($selfLand * $selfModifier)) &&
            ($targetLand <= ($selfLand / $selfModifier)) &&
            ($selfLand >= ($targetLand * $targetModifier)) &&
            ($selfLand <= ($targetLand / $targetModifier))
          )

            # Or was recently invaded by the target in the last three hours.
            or $this->militaryCalculator->getRecentlyInvadedCountByAttacker($self, $target, 3)
        );
    }

    /**
     * Resets guard application status of $self dominion if $target dominion is out of guard range.
     *
     * @param Dominion $self
     * @param Dominion $target
     */
    public function checkGuardApplications(Dominion $self, Dominion $target): void
    {
        #$isRoyalGuardApplicant = $this->guardMembershipService->isRoyalGuardApplicant($self);
        $isEliteGuardApplicant = $this->guardMembershipService->isEliteGuardApplicant($self);

        if ($isEliteGuardApplicant) {
            $selfLand = $this->landCalculator->getTotalLand($self);
            $targetLand = $this->landCalculator->getTotalLand($target);

            // Reset Peacekeepers League (Royal Guard) application if out of range
            /*
            if ($isRoyalGuardApplicant) {
                $guardModifier = $this->guardMembershipService::ROYAL_GUARD_RANGE;
                if (($targetLand < ($selfLand * $guardModifier)) || ($targetLand > ($selfLand / $guardModifier))) {
                    $this->guardMembershipService->joinRoyalGuard($self);
                }
            }
            */

            // Reset Warriors League (Elite Guard) application if out of range
            if ($isEliteGuardApplicant) {
                $guardModifier = $this->guardMembershipService::ELITE_GUARD_RANGE;
                if (($targetLand < ($selfLand * $guardModifier)) || ($targetLand > ($selfLand / $guardModifier))) {
                    $this->guardMembershipService->joinEliteGuard($self);
                }
            }
        }
    }

    /**
     * Returns the $target dominion range compared to $self dominion.
     *
     * Return value is a percentage (eg 114.28~) used for displaying. For calculation purposes, divide this by 100.
     *
     * @param Dominion $self
     * @param Dominion $target
     * @return float
     * @todo: should probably change this (and all its usages) to return without *100
     *
     */
    public function getDominionRange(Dominion $self, Dominion $target): float
    {
        $selfLand = $this->landCalculator->getTotalLand($self);
        $targetLand = $this->landCalculator->getTotalLand($target);

        return (($targetLand / $selfLand) * 100);
    }

    /**
     * Helper function to return a colored <span> class for a $target dominion range.
     *
     * @param Dominion $self
     * @param Dominion $target
     * @return string
     */
    public function getDominionRangeSpanClass(Dominion $self, Dominion $target): string
    {
        $range = $this->getDominionRange($self, $target);

        if ($range >= 120) {
            return 'text-red';
        }

        if ($range >= 75) {
            return 'text-green';
        }

        if ($range >= 66) {
            return 'text-muted';
        }

        return 'text-gray';
    }

    /**
     * Get the dominion range modifier.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getRangeModifier(Dominion $dominion): float
    {
        if ($this->guardMembershipService->isEliteGuardMember($dominion)) {
            return $this->guardMembershipService::ELITE_GUARD_RANGE;
        }

        /*
        if ($this->guardMembershipService->isRoyalGuardMember($dominion)) {
            return $this->guardMembershipService::ROYAL_GUARD_RANGE;
        }
        */

        if ($this->guardMembershipService->isBarbarianGuardMember($dominion)) {
            return $this->guardMembershipService::ROYAL_GUARD_RANGE;
        }

        return self::MINIMUM_RANGE;
    }

    /**
     * Returns all dominions in range of a dominion.
     *
     * @param Dominion $self
     * @return Collection
     */
    public function getDominionsInRange(Dominion $self): Collection
    {
        return $self->round->activeDominions()
            ->with(['realm', 'round'])
            ->get()
            ->filter(function ($dominion) use ($self) {
                return (

                    # Not in the same realm; and
                    ($dominion->realm->id !== $self->realm->id) and

                    # Is in range; and
                    $this->isInRange($self, $dominion) and

                    # Is not in protection;
                    !$this->protectionService->isUnderProtection($dominion) and

                    # Is not locked;
                    $dominion->is_locked !== 1
                );
            })
            ->sortByDesc(function ($dominion) {
                return $this->landCalculator->getTotalLand($dominion);
            })
            ->values();
    }


        /**
         * Returns all dominions in range of a dominion.
         *
         * @param Dominion $self
         * @return Collection
         */
        public function getFriendlyDominionsInRange(Dominion $self): Collection
        {
            return $self->round->activeDominions()
                ->with(['realm', 'round'])
                ->get()
                ->filter(function ($dominion) use ($self) {
                    return (

                        # In the same realm; and
                        ($dominion->realm->id === $self->realm->id) and

                        # Is in range; and
                        $this->isInRange($self, $dominion) and

                        # Is not in protection;
                        !$this->protectionService->isUnderProtection($dominion) and

                        # Is not locked;
                        $dominion->is_locked !== 1
                    );
                })
                ->sortByDesc(function ($dominion) {
                    return $this->landCalculator->getTotalLand($dominion);
                })
                ->values();
        }

}
