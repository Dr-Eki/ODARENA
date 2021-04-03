<?php

namespace OpenDominion\Services\Dominion;

use Carbon\Carbon;
use OpenDominion\Models\Dominion;

class ProtectionService
{
    /**
     * Returns whether this dominion is under protection (has protection_ticks > 0).
     *
     * @param Dominion $dominion
     * @return bool
     */
    public function isUnderProtection(Dominion $dominion): bool
    {
        return max(0, $dominion->protection_ticks);
    }

    /**
     * Returns whether this dominion is eligible to tick.
     *
     * @param Dominion $dominion
     * @return bool
     */
    public function canTick(Dominion $dominion)
    {
        return $this->isUnderProtection($dominion);
    }

    /**
     * Returns whether this dominion is eligible to delete.
     *
     * @param Dominion $dominion
     * @return bool
     */
    public function canDelete(Dominion $dominion): bool
    {
        $canDelete = false;

        if(!$dominion->round->hasStarted() or $this->canTick($dominion))
        {
            $canDelete = true;
        }

        if(request()->getHost() == 'sim.odarena.com')
        {
          $canDelete = true;
        }

        return $canDelete;
    }


}
