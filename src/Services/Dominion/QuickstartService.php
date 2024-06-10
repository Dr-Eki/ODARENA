<?php

namespace OpenDominion\Services\Dominion;

use Auth;
use Symfony\Component\Yaml\Yaml;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

use OpenDominion\Models\Advancement;
use OpenDominion\Models\Decree;
use OpenDominion\Models\DecreeState;
use OpenDominion\Models\Deity;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\DominionDecreeState;
use OpenDominion\Models\Quickstart;
use OpenDominion\Models\Race;
use OpenDominion\Models\Title;

use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\ImprovementHelper;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Helpers\RaceHelper;
use OpenDominion\Helpers\TitleHelper;

use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;

use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Services\Dominion\ProtectionService;
use OpenDominion\Services\Dominion\StatsService;


class QuickstartService
{

    /** @var BuildingHelper */
    protected $buildingHelper;

    /** @var ImprovementHelper */
    protected $improvementHelper;

    /** @var LandHelper */
    protected $landHelper;


    /** @var RaceHelper */
    protected $raceHelper;

    /** @var TitleHelper */
    protected $titleHelper;

    /** @var BuildingCalculator */
    protected $buildingCalculator;

    /** @var ImprovementCalculator */
    protected $improvementCalculator;

    /** @var LandCalculator */
    protected $landCalculator;


    /** @var MilitaryCalculator */
    protected $militaryCalculator;

    /** @var NetworthCalculator */
    protected $networthCalculator;

    /** @var PopulationCalculator */
    protected $populationCalculator;

    /** @var SpellCalculator */
    protected $spellCalculator;

    /** @var ProtectionService */
    protected $protectionService;

    /** @var QueueService */
    protected $queueService;

    /** @var ResourceService */
    protected $resourceService;

    /** @var StatsService */
    protected $statsService;

    public function __construct()
    {
        $this->buildingHelper = app(BuildingHelper::class);
        $this->improvementHelper = app(ImprovementHelper::class);
        $this->landHelper = app(LandHelper::class);
        $this->raceHelper = app(RaceHelper::class);
        $this->titleHelper = app(TitleHelper::class);

        $this->buildingCalculator = app(BuildingCalculator::class);
        $this->improvementCalculator = app(ImprovementCalculator::class);
        $this->landCalculator = app(LandCalculator::class);
        $this->militaryCalculator = app(MilitaryCalculator::class);
        $this->networthCalculator = app(NetworthCalculator::class);
        $this->populationCalculator = app(PopulationCalculator::class);
        $this->spellCalculator = app(SpellCalculator::class);

        $this->statsService = app(StatsService::class);
        $this->protectionService = app(ProtectionService::class);
        $this->queueService = app(QueueService::class);
    }

    public function generateQuickstartFile(Dominion $dominion)
    {

        $basics = sprintf(
"
name: %s (%s) Quickstart by %s
description: 
race: %s
title: %s
deity: %s
land: %s
offensive_power: 0
defensive_power: 0\n",
            $dominion->race->name,
            $dominion->title->name,
            $dominion->user->display_name,
            $dominion->race->name,
            $dominion->title->name,
            $dominion->hasDeity() ? $dominion->deity->name : null,
            $dominion->land,
          );

        $parameters = sprintf(
"draft_rate: %s
morale: %s
prestige: %s
peasants: %s
protection_ticks: %s
devotion_ticks: %s
spy_strength: %s
wizard_strength: %s
xp: %s\n",
            $dominion->draft_rate,
            $dominion->morale,
            $dominion->prestige,
            $dominion->peasants,
            $dominion->protection_ticks,
            $dominion->hasDeity() ? $dominion->devotion->duration : 0,
            $dominion->spy_strength,
            $dominion->wizard_strength,
            $dominion->xp
          );

        $buildings = "\nbuildings:\n";
        foreach ($this->buildingCalculator->getDominionBuildingsAvailableAndOwned($dominion) as $building)
        {
            $buildings .= "    {$building->key}: " . $dominion->{'building_' . $building->key} . "\n";# {$this->buildingCalculator->getBuildingAmountOwned($dominion, $building)}\n";
        }

        $improvements = "\nimprovements:\n";
        foreach($this->improvementHelper->getImprovementsByRace($dominion->race) as $improvement)
        {
            $improvements .= "    $improvement->key: {$this->improvementCalculator->getDominionImprovementAmountInvested($dominion, $improvement)}\n";
        }

        $resources = "\nresources:\n";
        foreach($dominion->race->resources as $resourceKey)
        {
            $resources .= "    $resourceKey: {$dominion->{'resource_' . $resourceKey}}\n";
        }

        $spells = "\nspells:\n";
        foreach($this->spellCalculator->getActiveSpells($dominion) as $dominionSpell)
        {
            $spells .= "    {$dominionSpell->spell->key}: {$dominionSpell->duration},{$dominionSpell->cooldown}\n";
        }

        $advancements = "\nadvancements:\n";
        foreach($dominion->advancements->sortBy('key') as $dominionAdvancement)
        {
            $advancement = Advancement::findOrFail($dominionAdvancement->pivot->advancement_id);
            $advancements .= "    {$advancement->key}: {$dominionAdvancement->pivot->level}\n";
        }

        $techs = "\ntechs:\n";
        foreach($dominion->techs->sortBy('key') as $dominionTech)
        {
            $techs .= "    - {$dominionTech->key}\n";
        }

        $terrains = "\nterrains:\n";
        foreach($dominion->terrains->sortBy('key') as $dominionTerrain)
        {
            $terrains .= "   {$dominionTerrain->key}: {$dominionTerrain->pivot->amount}\n";
        }

        $decreeStates = "\ndecree_states:\n";
        foreach(DominionDecreeState::where('dominion_id', $dominion->id)->get() as $dominionDecreeState)
        {
            $decree = Decree::find($dominionDecreeState->decree_id);
            $decreeState = DecreeState::findOrFail($dominionDecreeState->decree_state_id);

            $decreeStates .= "    - {$decree->key},{$decreeState->key},0\n";
        }

        $units = "\nunits:\n";
        $units .= sprintf(
            "    draftees: %s\n    unit1: %s\n    unit2: %s\n    unit3: %s\n    unit4: %s\n    unit5: %s\n    unit6: %s\n    unit7: %s\n    unit8: %s\n    unit9: %s\n    unit10: %s\n    spies: %s\n    wizards: %s\n    archmages: %s\n",
            $dominion->military_draftees,
            $dominion->military_unit1,
            $dominion->military_unit2,
            $dominion->military_unit3,
            $dominion->military_unit4,
            $dominion->military_unit5,
            $dominion->military_unit6,
            $dominion->military_unit7,
            $dominion->military_unit8,
            $dominion->military_unit9,
            $dominion->military_unit10,
            $dominion->military_spies,
            $dominion->military_wizards,
            $dominion->military_archmages,
        );

        $queues = "\nqueues:\n";
        foreach($dominion->queues as $index => $queue)
        {
            $queues .= "    - {$queue->source},{$queue->resource},{$queue->hours},{$queue->amount}\n";
        }

        if(Auth::user())
        {
            $credits = "\n\n# Generated by " . Auth::user()->display_name . ", " . now() . ".";
        }
        else
        {
            $credits = "\n\n# Generated by command at " . now() . ".";
        }

        $string = $basics . $parameters . $buildings . $improvements . $resources . $spells . $advancements . $techs . $terrains . $decreeStates . $units . $queues . $credits;
        
        return $string;
    }

    public function saveQuickstart(Dominion $dominion, string $name = null,  int $offensivePower = null, int $defensivePower = null, string $description = null, bool $isPublic = false): Quickstart
    {

        if ($name == null or strlen($name) == 0)
        {
            $name = "{$dominion->race->name} ({$dominion->title->name}) by {$dominion->user->display_name}"; 
        }

        $quickstartData = $this->generateQuickstartFile($dominion);

        $data = Yaml::parse($quickstartData, Yaml::PARSE_OBJECT_FOR_MAP);

        $race = Race::where('name', $data->race)->first();

        $quickstartsToSync[] = $data->name;

        if(isset($data->deity))
        {
            $deity = Deity::where('name', $data->deity)->first();
        }
        else
        {
            $deity = null;
        }

        if(isset($data->title))
        {
            $title = Title::where('name', $data->title)->first();
        }

        // Quickstart
        $quickstart = Quickstart::create(
            [
                'name' => $name ?? object_get($data, 'name'),
                'description' => $description ?? object_get($data, 'description'),
                'race_id' => $race->id,
                'deity_id' => isset($deity) ? $deity->id : null,
                'title_id' => isset($title) ? $title->id : null,
                'user_id' => $dominion->user->id,
                'enabled' => object_get($data, 'enabled', 1),
                'offensive_power' => $offensivePower ?? object_get($data, 'offensive_power', 0),
                'defensive_power' => $defensivePower ?? object_get($data, 'defensive_power', 0),
                
                'land' => object_get($data, 'land', 1000),
                'draft_rate' => object_get($data, 'draft_rate', 50),
                'devotion_ticks' => isset($deity) ? max(min(object_get($data, 'devotion_ticks', 0), 96),0) : 0,
                'morale' => object_get($data, 'morale', 100),
                'peasants' => object_get($data, 'peasants', 2000),
                'prestige' => object_get($data, 'prestige', 400),
                'protection_ticks' => max(min(object_get($data, 'protection_ticks', 0), 96),0),
                'spy_strength' => object_get($data, 'spy_strength', 100),
                'wizard_strength' => object_get($data, 'wizard_strength', 100),
                'xp' => object_get($data, 'xp', 0),

                'buildings' => object_get($data, 'buildings', []),
                'improvements' => object_get($data, 'improvements', []),
                'resources' => object_get($data, 'resources', []),
                'spells' => object_get($data, 'spells', []),
                'advancements' => object_get($data, 'advancements', []),
                'decree_states' => object_get($data, 'decree_states', []),
                'techs' => object_get($data, 'techs', []),
                'terrains' => object_get($data, 'terrains', []),
                'units' => object_get($data, 'units', []),
                'queues' => object_get($data, 'queues', []),
            ]);

        return $quickstart;
    }


    public function importQuickstart(string $source, int $quickstartId, string $apiKey)
    {
        $user = Auth::user();

        if(env('APP_ENV') == 'local')
        {
            $url = "http://host.docker.internal/api/v1/quickstarts/{$quickstartId}/{$apiKey}";
        }
        elseif($source == 'sim')
        {
            $url = "https://sim.opendominion.com/api/v1/quickstarts/{$quickstartId}/{$apiKey}";
        }
        else
        {
            $url = "https://opendominion.com/api/v1/quickstarts/{$quickstartId}/{$apiKey}";
        }

        $client = new Client();

        try {
            $response = $client->get($url);
            $responseBody = $response->getBody()->getContents();
            $quickstartData = json_decode($responseBody);
    
            if ($quickstartData === null || !is_object($quickstartData) || !isset($quickstartData->name) || empty($quickstartData)) {
                throw new \Exception('Failed to import quickstart. Make sure quickstart ID and API key are correct for the source.');
            }
    
        } catch (RequestException $e) {
            throw new \Exception('Failed to import quickstart: ' . $e->getMessage());
        }
        
        try {
            $quickstart = Quickstart::create([
            'name' => $quickstartData->name,
            'description' => $quickstartData->description ?? '',
            'race_id' => $quickstartData->race_id,
            'deity_id' => $quickstartData->deity_id ?? null,
            'title_id' => $quickstartData->title_id,
            'user_id' => $user->id,
            'offensive_power' => $quickstartData->offensive_power ?? 0,
            'defensive_power' => $quickstartData->defensive_power ?? 0,
            'enabled' => $quickstartData->enabled ?? 1,
            'is_public' => $quickstartData->is_public ?? 0,
            'draft_rate' => $quickstartData->draft_rate ?? 50,
            'devotion_ticks' => $quickstartData->devotion_ticks ?? 0,
            'morale' => $quickstartData->morale ?? 100,
            'peasants' => $quickstartData->peasants ?? 0,
            'prestige' => $quickstartData->prestige ?? 0,
            'protection_ticks' => $quickstartData->protection_ticks ?? 0,
            'spy_strength' => $quickstartData->spy_strength ?? 0,
            'wizard_strength' => $quickstartData->wizard_strength ?? 0,
            'xp' => $quickstartData->xp ?? 0,
            'land' => $quickstartData->land ?? 1000,
            'buildings' => $quickstartData->buildings ?? [],
            'improvements' => $quickstartData->improvements ?? [],
            'resources' => $quickstartData->resources ?? [],
            'spells' => $quickstartData->spells ?? [],
            'advancements' => $quickstartData->advancements ?? [],
            'decree_states' => $quickstartData->decree_states ?? [],
            'techs' => $quickstartData->techs ?? [],
            'terrains' => $quickstartData->terrains ?? [],
            'units' => $quickstartData->units ?? [],
            'queues' => $quickstartData->queues ?? [],
        ]);
        } catch (\Exception $e) {
            throw new \Exception('Failed to create Quickstart: ' . $e->getMessage());
        }

    
        return $quickstart;
    }

}
