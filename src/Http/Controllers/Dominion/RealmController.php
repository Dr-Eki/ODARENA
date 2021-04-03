<?php

namespace OpenDominion\Http\Controllers\Dominion;

use DB;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Realm;
use OpenDominion\Services\Dominion\GuardMembershipService;
use OpenDominion\Services\Dominion\ProtectionService;

# ODA
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Calculators\RealmCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Services\Dominion\BarbarianService;
use Illuminate\Support\Carbon;

class RealmController extends AbstractDominionController
{
    public function getRealm(Request $request, int $realmNumber = null)
    {
        $landCalculator = app(LandCalculator::class);
        $networthCalculator = app(NetworthCalculator::class);
        $protectionService = app(ProtectionService::class);
        $guardMembershipService = app(GuardMembershipService::class);
        $spellCalculator = app(SpellCalculator::class);
        $realmCalculator = app(RealmCalculator::class);
        $militaryCalculator = app(MilitaryCalculator::class);
        $landHelper = app(LandHelper::class);
        $barbarianService = app(BarbarianService::class);

        $dominion = $this->getSelectedDominion();
        $round = $dominion->round;

        if ($realmNumber === null) {
            $realmNumber = (int)$dominion->realm->number;
        }

        $isOwnRealm = ($realmNumber === (int)$dominion->realm->number);

/*
        if (!$round->hasStarted() && !$isOwnRealm) {
            $request->session()->flash(
                'alert-warning',
                'You cannot view other realms before the round has started.'
            );
            return redirect()->route('dominion.realm', (int)$dominion->realm->number);
        }
*/

        // Eager load some relational data to save on SQL queries down the road in NetworthCalculator and
        // ProtectionService
        $with = [
            'dominions.queues',
            'dominions.race',
            'dominions.race.units',
            'dominions.race.units.perks',
            'dominions.realm',
            'dominions.round',
        ];

        if ($isOwnRealm) {
            $with[] = 'dominions.user';
        }

        $realm = Realm::with($with)
            ->where([
                'round_id' => $round->id,
                'number' => $realmNumber,
            ])
            ->firstOrFail();

        // todo: still duplicate queries on this page. investigate later

        $dominions = $realm->dominions
            ->groupBy(static function (Dominion $dominion) use ($landCalculator) {
                return $landCalculator->getTotalLand($dominion);
            })
            ->sortKeysDesc()
            ->map(static function (Collection $collection) use ($networthCalculator) {
                return $collection->sortByDesc(
                    static function (Dominion $dominion) use ($networthCalculator) {
                        return $networthCalculator->getDominionNetworth($dominion);
                    });
            })
            ->flatten();

        $realmDominionsStats = [
            'victories' => 0,
            'total_land_conquered' => 0,
            'total_land_explored' => 0,
            'total_land_discovered' => 0,
            'total_land_lost' => 0,
            'prestige' => 0,
          ];

        foreach($dominions as $dominion)
        {
            $realmDominionsStats['victories'] += $dominion->stat_attacking_success;
            $realmDominionsStats['total_land_conquered'] += $dominion->stat_total_land_conquered;
            $realmDominionsStats['total_land_explored'] += $dominion->stat_total_land_explored;
            $realmDominionsStats['total_land_discovered'] += $dominion->stat_total_land_discovered;
            $realmDominionsStats['total_land_lost'] += $dominion->stat_total_land_lost;
            $realmDominionsStats['prestige'] += $dominion->prestige;

            foreach($landHelper->getLandTypes() as $landType)
            {
                if(isset($realmDominionsStats[$landType]))
                {
                    $realmDominionsStats[$landType] += $dominion->{'land_'.$landType};
                }
                else
                {
                    $realmDominionsStats[$landType] = $dominion->{'land_'.$landType};
                }
            }
        }

        $barbarianSettings = [];
        $hoursIntoTheRound = now()->startOfHour()->diffInHours(Carbon::parse($dominion->round->start_date)->startOfHour());

        if($realm->alignment == 'good')
        {
            $alignmentNoun = 'Commonwealth';
            $alignmentAdjective = 'Commonwealth';
        }
        elseif($realm->alignment == 'evil')
        {
            $alignmentNoun = 'Empire';
            $alignmentAdjective = 'Imperial';
        }
        elseif($realm->alignment == 'independent')
        {
            $alignmentNoun = 'Independent';
            $alignmentAdjective = 'Independent';
        }
        elseif($realm->alignment == 'npc')
        {
            $alignmentNoun = 'Barbarian';
            $alignmentAdjective = 'Barbarian';
            $barbarianSettings = $barbarianService->getSettings();
        }

        // Todo: refactor this hacky hacky navigation stuff
        $prevRealm = DB::table('realms')
            ->where('round_id', $round->id)
            ->where('number', '<', $realm->number)
            ->orderBy('number', 'desc')
            ->limit(1)
            ->first();

        $nextRealm = DB::table('realms')
            ->where('round_id', $round->id)
            ->where('number', '>', $realm->number)
            ->orderBy('number')
            ->limit(1)
            ->first();

        $realmCount = DB::table('realms')
            ->where('round_id', $round->id)
            ->count();

        return view('pages.dominion.realm', compact(
            'landCalculator',
            'networthCalculator',
            'realm',
            'round',
            'dominions',
            'prevRealm',
            'guardMembershipService',
            'protectionService',
            'nextRealm',
            'isOwnRealm',
            'realmCount',

            # ODA
            'spellCalculator',
            'realmDominionsStats',
            'realmCalculator',
            'militaryCalculator',
            'landHelper',
            'alignmentNoun',
            'alignmentAdjective',
            'barbarianSettings',
            'hoursIntoTheRound'
        ));
    }

    public function postChangeRealm(Request $request) // todo: RealmChangeRequest, parse realm number to int
    {
        return redirect()->route('dominion.realm', (int)$request->get('realm'));
    }
}
