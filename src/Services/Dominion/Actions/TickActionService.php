<?php

namespace OpenDominion\Services\Dominion\Actions;

use DB;
use Auth;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\ProtectionService;
use OpenDominion\Services\Dominion\TickService;
use OpenDominion\Services\NotificationService;
use OpenDominion\Traits\DominionGuardsTrait;

class TickActionService
{
    use DominionGuardsTrait;

    /** @var ProtectionService */
    protected $protectionService;

    /** @var TickService */
    protected $tickService;

    /** @var NotificationService */
    protected $notificationService;

    /**
     * TickActionService constructor.
     *
     * @param ProtectionService $protectionService
     */
    public function __construct(
        ProtectionService $protectionService,
        TickService $tickService,
        NotificationService $notificationService
    ) {
        $this->protectionService = $protectionService;
        $this->tickService = $tickService;
        $this->notificationService = $notificationService;
    }

    /**
     * Invades dominion $target from $dominion.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @param array $units
     * @return array
     * @throws GameException
     */
    public function tickDominion(Dominion $dominion, int $ticks = 1): array
    {
        $this->guardLockedDominion($dominion);

        if($ticks == 0 or !$ticks)
        {
            dd('Ticks is 0 or not set');
        }

        for ($tick = 1; $tick <= $ticks; $tick++)
        {
            DB::transaction(function () use ($dominion) {
                // Checks
                if($dominion->user_id !== Auth::user()->id)
                {
                    throw new GameException('You cannot tick for other dominions than your own.');
                }

                if($dominion->protection_ticks <= 0)
                {
                    throw new GameException('You do not have any protection ticks left.');
                }

                if($dominion->round->hasEnded())
                {
                    throw new GameException('The round has ended.');
                }
                
                if($dominion->race->name == 'Artillery' and !$dominion->hasProtector() and $dominion->protection_ticks == 1 and ($dominion->hasProtector() and !$dominion->protector->isUnderProtection()))
                {
                    throw new GameException('You cannot leave the magical state of protection until a Protector has guaranteed your protection. The Protector not be under the magical state of protection.');
                }

                // Run the tick.
                $this->tickService->tickManually($dominion);
            });
        }

        $this->notificationService->sendNotifications($dominion, 'irregular_dominion');
        return [
            'message' => 'Tick processed. You now have ' . $dominion->protection_ticks . ' ' . str_plural('tick', $dominion->protection_ticks) . ' left.',
            'alert-type' => 'success',
            'redirect' => route('dominion.status')
        ];
    }
}
