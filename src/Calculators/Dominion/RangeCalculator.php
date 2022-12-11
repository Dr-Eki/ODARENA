<?php

namespace OpenDominion\Calculators\Dominion;

use Illuminate\Support\Collection;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\ProtectionService;

# ODA
use OpenDominion\Calculators\Dominion\MilitaryCalculator;

class RangeCalculator
{
    public const MINIMUM_RANGE = 0.4;

    public const RECENTLY_INVADED_GRACE_PERIOD_TICKS = 12;

    /** @var LandCalculator */
    protected $landCalculator;

    /** @var ProtectionService */
    protected $protectionService;

    /** @var MilitaryCalculator */
    protected $militaryCalculator;

    /**
     * RangeCalculator constructor.
     *
     * @param LandCalculator $landCalculator
     * @param ProtectionService $protectionService
     */
    public function __construct(
        LandCalculator $landCalculator,
        MilitaryCalculator $militaryCalculator,
        SpellCalculator $spellCalculator,

        ProtectionService $protectionService
    ) {
        $this->landCalculator = $landCalculator;
        $this->militaryCalculator = $militaryCalculator;
        $this->spellCalculator = $spellCalculator;

        $this->protectionService = $protectionService;
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
        $targetModifier = $this->getRangeModifier($target, true);

        # Legion out doing Show of Force can hit its own annexed barbarians.
        if($self->getDecreePerkValue('show_of_force_invading_annexed_barbarian') and $this->spellCalculator->getAnnexedDominions($self)->contatins($target))
        {
            return true;
        }

        # Annexed Barbarians are not in range of (other) Empire dominions
        if ($this->spellCalculator->isAnnexed($target) and $self->realm->alignment == 'evil')
        {
            return false;
        }

        # 12 ticks grace period following invasions.
        if($this->militaryCalculator->getRecentlyInvadedCountByAttacker($self, $target, static::RECENTLY_INVADED_GRACE_PERIOD_TICKS))
        {
            return true;
        }

        # Otherwise, use range modifier.
        return (
            
              ($targetLand >= ($selfLand * $selfModifier)) &&
              ($targetLand <= ($selfLand / $selfModifier)) &&
              ($selfLand >= ($targetLand * $targetModifier)) &&
              ($selfLand <= ($targetLand / $targetModifier))
            );
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
     * Helper function to return a coloured <span> class for a $target dominion range.
     *
     * @param Dominion $self
     * @param Dominion $target
     * @return string
     */
    public function getDominionRangeSpanClass(Dominion $self, Dominion $target): string
    {
        $range = $this->getDominionRange($self, $target);

        if ($range >= (1/0.0075)) {
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
    public function getRangeModifier(Dominion $dominion, bool $isTarget = false): float
    {
        if($dominion->hasDeity())
        {
            if($decreeRangeMultiplier = $dominion->getDecreePerkValue('range_multiplier'))
            {
                return $decreeRangeMultiplier;
            }
            
            return $dominion->deity->range_multiplier;
        }
        elseif($dominion->getPendingDeitySubmission() and !$isTarget)
        {
            return $dominion->getPendingDeitySubmission()->range_multiplier;
        }

        return 0.4;
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

                    # Not in the same realm (unless deathmatch round); and
                    (($dominion->round->mode == 'standard' or $dominion->round->mode == 'standard-duration' or $dominion->round->mode == 'artefacts') ? ($dominion->realm->id !== $self->realm->id) : true) and

                    # Not self
                    ($dominion->id !== $self->id) and

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

                        # In the same realm (unless deathmatch round); and
                        (($dominion->round->mode == 'standard' or $dominion->round->mode == 'standard-duration') ? ($dominion->realm->id == $self->realm->id) : false) and

                        # Not self
                        ($dominion->id !== $self->id) and

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
