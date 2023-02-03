<?php

namespace OpenDominion\Http\Controllers\Dominion;

use DB;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;
use OpenDominion\Models\Realm;
use OpenDominion\Services\Dominion\StatsService;
use OpenDominion\Services\Dominion\ProtectionService;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Calculators\Dominion\ResourceCalculator;
use OpenDominion\Calculators\RealmCalculator;
use OpenDominion\Calculators\Dominion\BarbarianCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Helpers\ArtefactHelper;
use OpenDominion\Helpers\DeityHelper;
use OpenDominion\Helpers\DominionHelper;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Helpers\RaceHelper;
use OpenDominion\Helpers\RealmHelper;
use OpenDominion\Services\Dominion\BarbarianService;
use Illuminate\Support\Carbon;

class RealmController extends AbstractDominionController
{
    public function getRealm(Request $request, int $realmNumber = null)
    {
        $landCalculator = app(LandCalculator::class);
        $barbarianCalculator = app(BarbarianCalculator::class);
        $networthCalculator = app(NetworthCalculator::class);
        $protectionService = app(ProtectionService::class);
        $spellCalculator = app(SpellCalculator::class);
        $realmCalculator = app(RealmCalculator::class);
        $resourceCalculator = app(ResourceCalculator::class);
        $militaryCalculator = app(MilitaryCalculator::class);
        $artefactHelper = app(ArtefactHelper::class);
        $dominionHelper = app(DominionHelper::class);
        $landHelper = app(LandHelper::class);
        $deityHelper = app(DeityHelper::class);
        $raceHelper = app(RaceHelper::class);
        $realmHelper = app(RealmHelper::class);
        $barbarianService = app(BarbarianService::class);
        $statsService = app(StatsService::class);
        $dominionHelper = app(DominionHelper::class);

        $dominion = $this->getSelectedDominion();
        $round = $dominion->round;

        if ($realmNumber === null)
        {
            $realmNumber = (int)$dominion->realm->number;
        }

        $isOwnRealm = ($realmNumber === (int)$dominion->realm->number);

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


        $realms = Realm::where('round_id', $round->id)->get();
        foreach($realms as $aRealm) # Using "$realm" breaks other stuff
        {
            $realmNames[$aRealm->number] = $aRealm->name;
        }

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
            $realmDominionsStats['victories'] += $statsService->getStat($dominion, 'invasion_victories');
            $realmDominionsStats['total_land_conquered'] += $statsService->getStat($dominion, 'land_conquered');
            $realmDominionsStats['total_land_explored'] += $statsService->getStat($dominion, 'land_explored');
            $realmDominionsStats['total_land_discovered'] += $statsService->getStat($dominion, 'land_discovered');
            $realmDominionsStats['total_land_lost'] += $statsService->getStat($dominion, 'land_lost');
            $realmDominionsStats['prestige'] += floor($dominion->prestige);

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
        #$hoursIntoTheRound = now()->startOfHour()->diffInHours(Carbon::parse($dominion->round->start_date)->startOfHour());

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
            $alignmentNoun = 'Barbarian Horde';
            $alignmentAdjective = 'Barbarian';
            $barbarianSettings = $barbarianCalculator->getSettings();
        }
        elseif($realm->alignment == 'players')
        {
            $alignmentNoun = 'ODARENA';
            $alignmentAdjective = 'ODARENA';
        }
        elseif(($race = Race::where('key', $realm->alignment)->first()) !== null)
        {
            $alignmentNoun = $race->name;
            $alignmentAdjective = $raceHelper->getRaceAdjective($race);
        }
        else
        {
            $alignmentNoun = 'Unknown';
            $alignmentAdjective = 'Unknown';
        }

        $defaultRealmNames = [
            'The Commonwealth',
            'The Empire',
            'The Independent',
            'The Barbarian Horde'
        ];

        return view('pages.dominion.realm', compact(
            'landCalculator',
            'networthCalculator',
            'realm',
            'round',
            'dominions',
            'protectionService',
            'isOwnRealm',
            'spellCalculator',
            'realmDominionsStats',
            'realmCalculator',
            'resourceCalculator',
            'militaryCalculator',
            'dominionHelper',
            'artefactHelper',
            'deityHelper',
            'landHelper',
            'raceHelper',
            'realmHelper',
            'alignmentNoun',
            'alignmentAdjective',
            'barbarianSettings',
            #'hoursIntoTheRound',
            'statsService',
            'realmNames',
            'defaultRealmNames',
        ));
    }

    public function postChangeRealm(Request $request)
    {
        return redirect()->route('dominion.realm', (int)$request->get('realm'));
    }
}
