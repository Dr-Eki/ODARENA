<?php

namespace OpenDominion\Calculators;

use OpenDominion\Models\Realm;
use OpenDominion\Calculators\Dominion\ResourceCalculator;

class RealmCalculator
{

    protected $resourceCalculator;

    public function __construct()
    {
        $this->resourceCalculator = app(ResourceCalculator::class);
    }

    /**
     * Calculate how many bodies in the crypt decayed this tick.
     *
     * @param Realm $realm
     * @return int
     */
    public function getCryptBodiesDecayed(Realm $realm): int
    {
        $bodiesDecayed = 0;
        $entombedBodies = 0;
        $multiplier = 1;

        if($realm->alignment !== 'evil' or $this->resourceCalculator->getRealmAmount($realm, 'body') === 0)
        {
            return $bodiesDecayed;
        }
        else
        {
            $bodiesToDecay = $this->resourceCalculator->getRealmAmount($realm, 'body');

            $dominions = $realm->dominions->flatten();
            foreach($dominions as $dominion)
            {
                $entombedBodies += $dominion->getBuildingPerkValue('crypt_bodies_decay_protection');
            }

            $bodiesToDecay -= $entombedBodies;
            $bodiesToDecay = max(0, $bodiesToDecay);

            $multiplier += $realm->getArtefactPerkMultiplier('crypt_decay');

            $bodiesToDecay *= $multiplier;

            $bodiesDecayed = max(0, round($this->resourceCalculator->getRealmAmount($realm, 'body') * 0.01));
        }

        return $bodiesDecayed;
    }

}
