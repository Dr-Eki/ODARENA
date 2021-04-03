<?php

namespace OpenDominion\Services\Dominion\Actions;

use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\GuardMembershipService;
use OpenDominion\Traits\DominionGuardsTrait;

#ODA
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Calculators\Dominion\SpellCalculator;

class GuardMembershipActionService
{
    use DominionGuardsTrait;

    /** @var GuardMembershipService */
    protected $guardMembershipService;

    /** @var QueueService */
    protected $queueService;

    /** @var SpellCalculator */
    protected $spellCalculator;

    /**
     * GuardMembershipActionService constructor.
     *
     * @param GuardMembershipService $guardMembershipService
     */
    public function __construct(
        GuardMembershipService $guardMembershipService,
        QueueService $queueService,
        SpellCalculator $spellCalculator
        )
    {
        $this->guardMembershipService = $guardMembershipService;
        $this->queueService = $queueService;
        $this->spellCalculator = $spellCalculator;
    }

    /**
     * Starts royal guard application for a Dominion.
     *
     * @param Dominion $dominion
     * @return array
     * @throws GameException
     */
     /*
    public function joinRoyalGuard(Dominion $dominion): array
    {
        $this->guardLockedDominion($dominion);

        // Qur: Statis
        if($dominion->getSpellPerkValue('stasis'))
        {
            throw new GameException('You cannot join or leave Leagues while you are in stasis.');
        }

        if ($this->guardMembershipService->isEliteGuardApplicant($dominion))
        {
            throw new GameException('You have applied to join the Warriors League. To join the Peacekeepers League, you must first cancel your application to join the Warriors League.');
        }

        if (!$this->guardMembershipService->canJoinGuards($dominion))
        {
            throw new GameException('You cannot join the Peacekeepers League for the first five days of the round.');
        }

        if ($this->guardMembershipService->isRoyalGuardMember($dominion))
        {
            throw new GameException('You are already a member of the Peacekeepers League.');
        }

        if ($this->guardMembershipService->isEliteGuardMember($dominion))
        {
            throw new GameException('You are a member of the Warriors League. To join the Peacekeepers League, you must first leave the Warriors League.');
        }

        if ($this->guardMembershipService->isRoyalGuardApplicant($dominion))
        {
            throw new GameException('You have already applied to join the Peacekeepers League.');
        }

        if($dominion->race->getPerkValue('cannot_join_guards'))
        {
            throw new GameException($dominion->race->name . ' is not able to join the Leagues.');
        }

        $this->guardMembershipService->joinRoyalGuard($dominion);

        return [
            'message' => sprintf(
                'You have applied to join the Peacekepers League.'
            ),
            'data' => []
        ];
    }
    */

    /**
     * Starts elite guard application for a Dominion.
     *
     * @param Dominion $dominion
     * @return array
     * @throws GameException
     */
    public function joinEliteGuard(Dominion $dominion): array
    {
        $this->guardLockedDominion($dominion);

        // Qur: Statis
        if($dominion->getSpellPerkValue('stasis'))
        {
            throw new GameException('You cannot join or leave Leagues while you are in stasis.');
        }

        /*
        if ($this->guardMembershipService->isRoyalGuardApplicant($dominion))
        {
            throw new GameException('You have applied to join the Peacekepers League. To join the Warriors League, you must first cancel your application to join the Peacekepers League.');
        }

        if ($this->guardMembershipService->isRoyalGuardMember($dominion))
        {
            throw new GameException('You are a member of the Peacekeepers League. To join the Warriors League, you must first leave the Peacekepers League.');
        }
        */

        if ($this->guardMembershipService->isEliteGuardMember($dominion))
        {
            throw new GameException('You are already a member of the Warriors League.');
        }

        if ($this->guardMembershipService->isEliteGuardApplicant($dominion))
        {
            throw new GameException('You have already applied to join the Warriors League.');
        }

        if($dominion->race->getPerkValue('cannot_join_guards'))
        {
            throw new GameException($dominion->race->name . ' is not able to join the Leagues.');
        }

        $this->guardMembershipService->joinEliteGuard($dominion);

        return [
            'message' => sprintf(
                'You have applied to join the Warriors League.'
            ),
            'data' => []
        ];
    }

    /**
     * Leaves the royal guard or cancels an application for a Dominion.
     *
     * @param Dominion $dominion
     * @return array
     * @throws GameException
     */
     /*
    public function leaveRoyalGuard(Dominion $dominion): array
    {
        $this->guardLockedDominion($dominion);

        // Qur: Statis
        if($dominion->getSpellPerkValue('stasis'))
        {
            throw new GameException('You cannot join or leave Leagues while you are in stasis.');
        }

        if ($this->guardMembershipService->getHoursBeforeLeaveRoyalGuard($dominion))
        {
            throw new GameException('You cannot leave your League before 12 hours after joining.');
        }


        if (!$this->guardMembershipService->isRoyalGuardApplicant($dominion) && !$this->guardMembershipService->isRoyalGuardMember($dominion))
        {
            throw new GameException('You are not a member of the Peacekepers League.');
        }

        if ($this->guardMembershipService->isRoyalGuardApplicant($dominion))
        {
            $message = 'You have cancelled your Peacekepers League application.';
        }
        else
        {
            $message = 'You have left the Peacekepers League.';
        }

        $this->guardMembershipService->leaveRoyalGuard($dominion);

        return [
            'message' => $message,
            'data' => []
        ];
    }
    */

    /**
     * Leaves the elite guard or cancels an application for a Dominion.
     *
     * @param Dominion $dominion
     * @return array
     * @throws GameException
     */
    public function leaveEliteGuard(Dominion $dominion): array
    {
        $this->guardLockedDominion($dominion);

        // Qur: Statis
        if($dominion->getSpellPerkValue('stasis'))
        {
            throw new GameException('You cannot join or leave Leagues while you are in stasis.');
        }

        $totalUnitsReturning = 0;
        for ($slot = 1; $slot <= 4; $slot++)
        {
            $totalUnitsReturning += $this->queueService->getInvasionQueueTotalByResource($dominion, "military_unit{$slot}");
        }
        if ($totalUnitsReturning !== 0 and $this->guardMembershipService->isEliteGuardMember($dominion))
        {
            throw new GameException('You cannot leave the Warriors League when you have troops returning from battle.');
        }

        if ($this->guardMembershipService->getHoursBeforeLeaveEliteGuard($dominion))
        {
            throw new GameException('You cannot leave the Warriors League before 12 hours after joining.');
        }

        if (!$this->guardMembershipService->isEliteGuardApplicant($dominion) && !$this->guardMembershipService->isEliteGuardMember($dominion))
        {
            throw new GameException('You are not a member of the Warriors League.');
        }

        if ($this->guardMembershipService->isEliteGuardApplicant($dominion))
        {
            $message = 'You have cancelled your Warriors League application.';
        }
        else
        {
            $message = 'You have left the Warriors League.';
        }

        $this->guardMembershipService->leaveEliteGuard($dominion);

        return [
            'message' => $message,
            'data' => []
        ];
    }
}
