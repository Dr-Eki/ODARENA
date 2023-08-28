<?php

namespace OpenDominion\Calculators\Dominion;

use DB;
use OpenDominion\Helpers\DeityHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;
use OpenDominion\Models\Deity;

class DeityCalculator
{
    /** @var LandCalculator */
    protected $landCalculator;

    /** @var DeityHelper */
    protected $deityHelper;

    /** @var array */
    protected $activeDeitys = [];

    /**
     * DeityCalculator constructor.
     *
     * @param LandCalculator $landCalculator
     * @param DeityHelper $deityHelper
     */
    public function __construct()
    {
        $this->landCalculator = app(LandCalculator::class);
        $this->deityHelper = app(DeityHelper::class);
    }

    public function isDeityAvailableToDominion(Dominion $dominion, Deity $deity): bool
    {
        return $this->isDeityAvailableToRace($dominion->race, $deity);
    }

    public function isDeityAvailableToRace(Race $race, Deity $deity): bool
    {
        $isAvailable = true;

        if(count($deity->exclusive_races) > 0 and !in_array($race->name, $deity->exclusive_races))
        {
            $isAvailable = false;
        }

        if(count($deity->excluded_races) > 0 and in_array($race->name, $deity->excluded_races))
        {
            $isAvailable = false;
        }

        return $isAvailable;
    }

}
