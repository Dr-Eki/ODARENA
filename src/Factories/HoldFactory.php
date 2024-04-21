<?php

namespace OpenDominion\Factories;

use DB;
use Log;
use Illuminate\Support\Str;
use Symfony\Component\Yaml\Yaml;
use Illuminate\Filesystem\Filesystem;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Hold;
use OpenDominion\Models\Race;
use OpenDominion\Models\Round;
use OpenDominion\Models\Title;

use OpenDominion\Services\Hold\ResourceService;

class HoldFactory
{

    protected $filesystem;
    protected $resourceService;

    public function __construct()
    {
        $this->filesystem = app(Filesystem::class);
        $this->resourceService = app(ResourceService::class);
    }

    /**
     * Creates and returns a new Dominion instance.
     *
     * @param User $user
     * @param Realm $realm
     * @param Race $race
     * @param Title $title
     * @param string $rulerName
     * @param string $dominionName
     * @param Pack|null $pack
     * @return Dominion
     * @throws GameException
     */
    public function create(Round $round): ?Hold
    {
        $yamlHolds = $this->filesystem->get(base_path('app/data/holds.yml'));
        $parsedHolds = collect(Yaml::parse($yamlHolds, Yaml::PARSE_OBJECT_FOR_MAP));
    
        // Retrieve current holds for the round
        $currentHolds = Hold::where('round_id', $round->id)->get();
    
        // Filter out any holds that already exist in currentHolds based on a unique attribute such as 'name'
        $newHolds = $parsedHolds->reject(function ($hold) use ($currentHolds) {
            return $currentHolds->contains('name', $hold->name);
        });
    
        // If all holds are already used, handle this case (e.g., throw an exception or log a message)
        if ($newHolds->isEmpty())
        {
            Log::info('Tried to spawn a hold, but no holds available to spawn.');
            throw new GameException('No holds available to spawn.');
            return null;
        }
    
        // Select a random hold from the filtered collection
        $holdData = $newHolds->random();
    
        $hold = null;
    
        DB::transaction(function () use ($round, $holdData, &$hold) {
            $race = $holdData->race ? Race::where('name', $holdData->race)->firstOrFail() : null;
    
            $hold = Hold::create([
                'name' => $holdData->name,
                'key' => Str::slug($holdData->name),
                'ruler_name' => $holdData->ruler_name ?? Str::random(8),
                'description' => $holdData->description,
                'round_id' => $round->id,
                'title_id' => Title::all()->random()->id,
                'race_id' => isset($race->id) ? $race->id : null,
                'status' => 1,
                'land' => config('holds.starting_land'),
                'morale' => config('holds.starting_morale'),
                'peasants' => config('holds.starting_peasants'),
                'peasants_last_hour' => 0,
                'desired_resources' => $holdData->desired_resources,
                'sold_resources' => $holdData->sold_resources,
                'tick_discovered' => $round->ticks,
                'ticks' => 0,
            ]);
        });
    
        return $hold;
    }
    

    /**
     * Get amount of buildings a new Dominion starts with.
     *
     * @return array
     */
    protected function getStartingBuildings($race, $landBase): array
    {
        # Default
        $startingBuildings = [];

        if($race->name == 'Kerranad')
        {
            $startingBuildings['aqueduct'] = 25;
            $startingBuildings['constabulary'] = 25;
            $startingBuildings['farm'] = 50;
            $startingBuildings['gold_mine'] = 100;
            $startingBuildings['harbour'] = 50;
            $startingBuildings['infirmary'] = 50;
            $startingBuildings['ore_mine'] = 100;
            $startingBuildings['residence'] = 50;
            $startingBuildings['saw_mill'] = 50;
            $startingBuildings['tavern'] = 50;
            $startingBuildings['tower'] = 50;
            $startingBuildings['wizard_guild'] = 50;
            $startingBuildings['syndicate_quarters'] = 50;
            $startingBuildings['gem_mine'] = 300;
        }
        elseif($race->name == 'Growth')
        {
          $startingBuildings['tissue'] = $landBase;
        }
        elseif($race->name == 'Myconid')
        {
          $startingBuildings['mycelia'] = $landBase;
        }
        elseif($race->name == 'Barbarian')
        {
            $availableBuildings = $this->buildingHelper->getBuildingsByRace($race);

            foreach($availableBuildings as $building)
            {
                $startingBuildings[$building->key] = round($landBase / count($availableBuildings));
            }
        }

        return $startingBuildings;
    }


}