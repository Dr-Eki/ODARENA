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
use OpenDominion\Models\Terrain;
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

class RealmController extends AbstractDominionController
{
    public function getRealm(Request $request, $realmNumber = null)
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

        $realm = Realm::where([
                'round_id' => $round->id,
                'number' => $realmNumber,
            ])
            ->first();

        if($realm === null)
        {
            return redirect()->route('dominion.realm', ['realmNumber' => $dominion->realm->number]);
        }

        // todo: still duplicate queries on this page. investigate later

        $dominions = $realm->dominions
            ->groupBy(static function (Dominion $dominion) use ($landCalculator) {
                return $dominion->land;
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
            'terrain' => [],
          ];

        foreach($dominions as $dominion)
        {
            $realmDominionsStats['victories'] += $statsService->getStat($dominion, 'invasion_victories');
            $realmDominionsStats['total_land_conquered'] += $statsService->getStat($dominion, 'land_conquered');
            $realmDominionsStats['total_land_explored'] += $statsService->getStat($dominion, 'land_explored');
            $realmDominionsStats['total_land_discovered'] += $statsService->getStat($dominion, 'land_discovered');
            $realmDominionsStats['total_land_lost'] += $statsService->getStat($dominion, 'land_lost');
            $realmDominionsStats['prestige'] += floor($dominion->prestige);

            foreach(Terrain::all() as $terrain)
            {
                if(isset($realmDominionsStats['terrain'][$terrain->key]))
                {
                    $realmDominionsStats['terrain'][$terrain->key] += $dominion->{'terrain_'.$terrain->key};
                }
                else
                {
                    $realmDominionsStats['terrain'][$terrain->key] = $dominion->{'terrain_'.$terrain->key};
                }
            }
        }

        $barbarianSettings = [];

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
        elseif($realm->pack)
        {
            $alignmentNoun = $realmHelper->getRealmPackName($realm);
            $alignmentAdjective = $alignmentNoun;
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
            'statsService',
            'realmNames',
            'defaultRealmNames',
        ));
    }
    public function getAllRealms(Request $request)
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

        $realms = Realm::where([
                'round_id' => $round->id,
            ])
            ->get();

        # Get all dominions in all realms
        $dominions = collect();

        foreach ($realms as $realm) {
            $dominions = $dominions->concat($realm->dominions);
        }

        # Sort dominions by dominion->land
        $dominions = $dominions->sortByDesc('land');

        $realms = Realm::where('round_id', $round->id)->get();
        foreach($realms as $aRealm) # Using "$realm" breaks other stuff
        {
            $realmNames[$aRealm->number] = $aRealm->name;
        }

        $realmsDominionsStats = [
            'victories' => 0,
            'total_land_conquered' => 0,
            'total_land_explored' => 0,
            'total_land_discovered' => 0,
            'total_land_lost' => 0,
            'prestige' => 0,
            'terrain' => [],
          ];

        foreach($dominions as $dominion)
        {
            $realmsDominionsStats['victories'] += $statsService->getStat($dominion, 'invasion_victories');
            $realmsDominionsStats['total_land_conquered'] += $statsService->getStat($dominion, 'land_conquered');
            $realmsDominionsStats['total_land_explored'] += $statsService->getStat($dominion, 'land_explored');
            $realmsDominionsStats['total_land_discovered'] += $statsService->getStat($dominion, 'land_discovered');
            $realmsDominionsStats['total_land_lost'] += $statsService->getStat($dominion, 'land_lost');
            $realmsDominionsStats['prestige'] += floor($dominion->prestige);

            foreach(Terrain::all() as $terrain)
            {
                if(isset($realmsDominionsStats['terrain'][$terrain->key]))
                {
                    $realmsDominionsStats['terrain'][$terrain->key] += $dominion->{'terrain_'.$terrain->key};
                }
                else
                {
                    $realmsDominionsStats['terrain'][$terrain->key] = $dominion->{'terrain_'.$terrain->key};
                }
            }
        }

        $barbarianSettings = [];

        $alignmentNoun = '';
        $alignmentAdjective = '';

        $realmName = $request->input('realm');

        $defaultRealmNames = [
            'The Commonwealth',
            'The Empire',
            'The Independent',
            'The Barbarian Horde'
        ];

        return view('pages.dominion.realm-all', compact(
            'landCalculator',
            'networthCalculator',
            'realm',
            'round',
            'dominions',
            'protectionService',
            #'isOwnRealm',
            'spellCalculator',
            'realmsDominionsStats',
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
            'statsService',
            'realmNames',
            'defaultRealmNames',
        ));
    }
}
