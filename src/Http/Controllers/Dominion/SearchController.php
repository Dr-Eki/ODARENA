<?php

namespace OpenDominion\Http\Controllers\Dominion;

use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\GuardMembershipService;
use OpenDominion\Services\Dominion\ProtectionService;

#ODA
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;

class SearchController extends AbstractDominionController
{
    public function getSearch()
    {
        $guardMembershipService = app(GuardMembershipService::class);
        $landCalculator = app(LandCalculator::class);
        $networthCalculator = app(NetworthCalculator::class);
        $protectionService = app(ProtectionService::class);
        $rangeCalculator = app(RangeCalculator::class);
        $militaryCalculator = app(MilitaryCalculator::class);
        $spellCalculator = app(SpellCalculator::class);

        $dominion = $this->getSelectedDominion();

        $dominions = Dominion::query()
            ->with([
                'queues',
                'round',
                'realm',
                'race',
                'race.perks',
                'race.units',
                'race.units.perks',
            ])
            ->where('round_id', $dominion->round_id)
            ->get();

        return view('pages.dominion.search', compact(
            'guardMembershipService',
            'landCalculator',
            'networthCalculator',
            'protectionService',
            'rangeCalculator',
            'militaryCalculator',
            'spellCalculator',
            'dominions'
        ));
    }
}
