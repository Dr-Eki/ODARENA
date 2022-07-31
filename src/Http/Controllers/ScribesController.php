<?php

namespace OpenDominion\Http\Controllers;

use OpenDominion\Calculators\Dominion\Actions\TrainingCalculator;
use OpenDominion\Calculators\Dominion\EspionageCalculator;
use OpenDominion\Calculators\Dominion\ResourceCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;

use OpenDominion\Helpers\ArtefactHelper;
use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\DecreeHelper;
use OpenDominion\Helpers\DeityHelper;
use OpenDominion\Helpers\EspionageHelper;
use OpenDominion\Helpers\ImprovementHelper;
use OpenDominion\Helpers\ResourceHelper;
use OpenDominion\Helpers\TitleHelper;
use OpenDominion\Helpers\TechHelper;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Helpers\LandImprovementHelper;
use OpenDominion\Helpers\RaceHelper;
use OpenDominion\Helpers\SpellHelper;
use OpenDominion\Helpers\UnitHelper;

use OpenDominion\Models\Artefact;
use OpenDominion\Models\Decree;
use OpenDominion\Models\Deity;
use OpenDominion\Models\Building;
use OpenDominion\Models\Improvement;
use OpenDominion\Models\Quickstart;
use OpenDominion\Models\Race;
use OpenDominion\Models\Resource;
use OpenDominion\Models\Title;
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

    public function getQuickstarts()
    {
        return view('pages.scribes.quickstarts', [
            'quickstarts' => Quickstart::where('enabled', true)->get(),
        ]);
    }

    public function getQuickstart(int $quickstartId)
    {
        return view('pages.scribes.quickstart', [
            'quickstart' => Quickstart::findOrFail($quickstartId),
            'raceHelper' => app(RaceHelper::class),
            'unitHelper' => app(UnitHelper::class),
        ]);
    }

    public function getRace(string $raceName)
    {
        $raceName = ucwords(str_replace('-', ' ', $raceName));

        $race = Race::where('name', $raceName)->firstOrFail();

        $resources = Resource::orderBy('name')->get();

        $buildingHelper = app(BuildingHelper::class);
        $buildings = $buildingHelper->getBuildingsByRace($race)->sortBy('name');

        $improvementHelper = app(ImprovementHelper::class);
        $improvements = $improvementHelper->getImprovementsByRace($race)->sortBy('name');

        return view('pages.scribes.faction', [
            'landHelper' => app(LandHelper::class),
            'unitHelper' => app(UnitHelper::class),
            'raceHelper' => app(RaceHelper::class),
            'spellHelper' => app(SpellHelper::class),
            'trainingCalculator' => app(TrainingCalculator::class),
            'race' => $race,
            'buildings' => $buildings,
            'buildingHelper' => $buildingHelper,
            'landHelper' => app(LandHelper::class),
            'LandImprovementHelper' => app(LandImprovementHelper::class),
            'improvements' => $improvements,
            'improvementHelper' => $improvementHelper,
            'espionageHelper' => app(EspionageHelper::class),
            'espionageCalculator' => app(EspionageCalculator::class),
            'spyops' => Spyop::all()->where('enabled',1)->keyBy('key')->sortBy('name'),
            'spells' => Spell::all()->where('enabled',1)->keyBy('key')->sortBy('name'),
            'spellCalculator' => app(SpellCalculator::class),
            'resourcespellCalculator' => app(ResourceCalculator::class),
            'resources' => $resources,
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
            'spells' => Spell::all()->where('enabled',1)->keyBy('key')->sortBy('key'),
            'spellHelper' => app(SpellHelper::class)
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

    public function getImprovements()
    {
        return view('pages.scribes.improvements', [
            'improvementHelper' => app(ImprovementHelper::class),
            'improvements' => Improvement::all()->where('enabled',1)->sortBy('name'),
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
        $titles = Title::all()->where('enabled',1)->keyBy('key')->sortBy('name');
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
            'spells' => Spell::all()->where('enabled',1)->keyBy('key')->sortBy('name'),
            'spellHelper' => app(SpellHelper::class),
            'spellCalculator' => app(SpellCalculator::class),
        ]);
    }

    public function getSabotage()
    {
        return view('pages.scribes.sabotage', [
            'spyops' => Spyop::all()->where('enabled',1)->keyBy('key')->sortBy('name'),
            'espionageHelper' => app(EspionageHelper::class),
        ]);
    }

    public function getDecrees()
    {
        return view('pages.scribes.decrees', [
            'decrees' => Decree::all()->where('enabled',1)->sortBy('name'),
            'decreeHelper' => app(DecreeHelper::class),
        ]);
    }

    public function getDeities()
    {
        return view('pages.scribes.deities', [
            'deities' => Deity::all()->where('enabled',1)->keyBy('key')->sortBy('name'),
            'deityHelper' => app(DeityHelper::class),
        ]);
    }

    public function getResources()
    {
        return view('pages.scribes.resources', [
            'resources' => Resource::all()->where('enabled',1)->keyBy('key')->sortBy('name'),
            'resourceHelper' => app(ResourceHelper::class),
        ]);
    }

    public function getArtefacts()
    {
        return view('pages.scribes.artefacts', [
            'artefacts' => Artefact::all()->where('enabled',1)->keyBy('key')->sortBy('name'),
            'artefactHelper' => app(ArtefactHelper::class),
        ]);
    }

}
