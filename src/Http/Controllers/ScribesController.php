<?php

namespace OpenDominion\Http\Controllers;

use OpenDominion\Calculators\Dominion\Actions\TrainingCalculator;
use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\EspionageHelper;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Helpers\RaceHelper;
use OpenDominion\Helpers\SpellHelper;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Models\Building;
use OpenDominion\Models\Race;
use OpenDominion\Models\Title;
use OpenDominion\Helpers\TitleHelper;
use OpenDominion\Helpers\TechHelper;
use OpenDominion\Models\Tech;
use OpenDominion\Models\Spell;
use OpenDominion\Models\Spyop;

class ScribesController extends AbstractController
{
    public function getRaces()
    {
        $races = collect(Race::orderBy('name')->get())->groupBy('alignment')->toArray();
        return view('pages.scribes.factions', [
            'goodRaces' => $races['good'],
            'evilRaces' => $races['evil'],
            'npcRaces' => $races['npc'],
            'independentRaces' => $races['independent'],
        ]);
    }

    public function getRace(string $raceName)
    {
        $raceName = ucwords(str_replace('-', ' ', $raceName));

        $race = Race::where('name', $raceName)->firstOrFail();

        $buildingHelper = app(BuildingHelper::class);

        $buildings = $buildingHelper->getBuildingsByRace($race)->sortBy('name');

        return view('pages.scribes.faction', [
            'landHelper' => app(LandHelper::class),
            'unitHelper' => app(UnitHelper::class),
            'raceHelper' => app(RaceHelper::class),
            'spellHelper' => app(SpellHelper::class),
            'trainingCalculator' => app(TrainingCalculator::class),
            'race' => $race,
            'buildings' => $buildings,
            'buildingHelper' => $buildingHelper,
        ]);
    }

    public function getConstruction()
    {
        $buildingHelper = app(BuildingHelper::class);

        $buildingTypesPerLandType = $buildingHelper->getBuildingTypesByRace();
        $buildingTypeWithLandType = [];
        foreach ($buildingTypesPerLandType as $landType => $buildingTypes) {
            foreach($buildingTypes as $buildingType) {
                $buildingTypeWithLandType[$buildingType] = $landType;
            }
        }

        $buildingTypeWithLandType['home'] = null;

        ksort($buildingTypeWithLandType);

        $races = collect(Race::where('playable', 1)->orderBy('name')->get())->groupBy('alignment')->toArray();
        return view('pages.scribes.construction', [
            'goodRaces' => $races['good'],
            'evilRaces' => $races['evil'],
            #'npcRaces' => $races['npc'],
            #'independentRaces' => $races['independent'],
            'buildingTypeWithLandType' => $buildingTypeWithLandType,
            'buildingHelper' => $buildingHelper,
            'landHelper' => app(LandHelper::class),
        ]);
    }

    public function getBuildings()
    {
        return view('pages.scribes.buildings', [
            'buildingHelper' => app(BuildingHelper::class),
            'landHelper' => app(LandHelper::class),
            'buildings' => Building::all()->where('enabled',1)->sortBy('name'),
        ]);
    }

    public function getEspionage()
    {
        return view('pages.scribes.espionage', [
            'espionageHelper' => app(EspionageHelper::class)
        ]);
    }

    public function getMagic()
    {
        return view('pages.scribes.magic', [
            'spellHelper' => app(SpellHelper::class)
        ]);
    }

    public function getTitles()
    {
        $titles = Title::all()->where('enabled',1)->keyBy('key')->sortBy('key');
        return view('pages.scribes.titles', [
            'titles' => $titles,
            'titleHelper' => app(TitleHelper::class),
        ]);
    }

    public function getAdvancements()
    {

        $techs = Tech::all()->where('enabled',1)->keyBy('key');


        $techs = $techs->sortBy(function ($tech, $key)
        {
            return $tech['name'] . str_pad($tech['level'], 2, '0', STR_PAD_LEFT);
        });


        foreach($techs as $tech)
        {
            $techNames[] = $tech['name'];
        }

        $techNames = array_unique($techNames);

        return view('pages.scribes.advancements', [
            'techs' => $techs,
            'techNames' => $techNames,
            'techHelper' => app(TechHelper::class),
        ]);
    }

    public function getSpells()
    {
        return view('pages.scribes.spells', [
            'spells' => Spell::all()->where('enabled',1)->keyBy('key')->sortBy('key'),
            'spellHelper' => app(SpellHelper::class),
        ]);
    }

    public function getSpyops()
    {
        return view('pages.scribes.spy-ops', [
            'spyops' => Spyop::all()->where('enabled',1)->keyBy('key')->sortBy('key'),
            'espionageHelper' => app(EspionageHelper::class),
        ]);
    }

}
