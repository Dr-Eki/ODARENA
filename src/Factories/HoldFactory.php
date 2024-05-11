<?php

namespace OpenDominion\Factories;

use DB;
use Log;
use Illuminate\Support\Str;
use Symfony\Component\Yaml\Yaml;
use Illuminate\Filesystem\Filesystem;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\Hold;
use OpenDominion\Models\Race;
use OpenDominion\Models\Resource;
use OpenDominion\Models\Round;
use OpenDominion\Models\Title;

use OpenDominion\Calculators\HoldCalculator;

use OpenDominion\Services\Hold\BuildingService;
use OpenDominion\Services\Hold\ResourceService;

class HoldFactory
{

    protected $filesystem;
    protected $holdCalculator;

    protected $buildingService;
    protected $resourceService;

    public function __construct()
    {
        $this->filesystem = app(Filesystem::class);

        $this->holdCalculator = app(HoldCalculator::class);

        $this->buildingService = app(BuildingService::class);
        $this->resourceService = app(ResourceService::class);
    }

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
                'title_id' => Title::all()->where('enabled',1)->random()->id,
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

            foreach($hold->sold_resources as $resourceKey)
            {
                $resource = Resource::fromKey($resourceKey);
                $amountToAdd = (int)floor(config('trade.sold_resource_start_value') / $resource->trade->buy);
                $this->resourceService->update($hold, [$resourceKey => $amountToAdd]);
            }

            foreach($hold->desired_resources as $resourceKey)
            {
                $resource = Resource::fromKey($resourceKey);
                $amountToAdd = (int)floor(config('trade.desired_resource_start_value') / $resource->trade->buy);
                $this->resourceService->update($hold, [$resourceKey => $amountToAdd]);
            }

            foreach($this->holdCalculator->getNewBuildings($hold) as $buildingKey => $buildingAmount)
            {
                $this->buildingService->update($hold, [$buildingKey => $buildingAmount]);
            }

            GameEvent::create([
                'round_id' => $hold->round_id,
                'source_type' => Hold::class,
                'source_id' => $hold->id,
                'target_type' => null,
                'target_id' => null,
                'type' => 'hold_discovered',
                'data' => '',
                'tick' => $hold->round->ticks
            ]);

        });
    
        return $hold;
    }

    public function seed(Round $round): void
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
            return;
        }
    
        DB::transaction(function () use ($round, $newHolds)
        {
            foreach($newHolds as $holdData)
            {
                $race = $holdData->race ? Race::where('name', $holdData->race)->firstOrFail() : null;
        
                $hold = Hold::create([
                    'name' => $holdData->name,
                    'key' => Str::slug($holdData->name),
                    'ruler_name' => $holdData->ruler_name ?? Str::random(8),
                    'description' => $holdData->description,
                    'round_id' => $round->id,
                    'title_id' => Title::all()->random()->id,
                    'race_id' => isset($race->id) ? $race->id : null,
                    'status' => 0,
                    'land' => config('holds.starting_land'),
                    'morale' => config('holds.starting_morale'),
                    'peasants' => config('holds.starting_peasants'),
                    'peasants_last_hour' => 0,
                    'desired_resources' => $holdData->desired_resources,
                    'sold_resources' => $holdData->sold_resources,
                    'tick_discovered' => 0,
                    'ticks' => 0,
                    'status' => 0,
                ]);

                foreach($hold->sold_resources as $resourceKey)
                {
                    $resource = Resource::fromKey($resourceKey);
                    $amountToAdd = (int)floor(config('trade.sold_resource_start_value') / $resource->trade->buy);
                    $this->resourceService->update($hold, [$resourceKey => $amountToAdd]);
                }

                foreach($hold->desired_resources as $resourceKey)
                {
                    $resource = Resource::fromKey($resourceKey);
                    $amountToAdd = (int)floor(config('trade.desired_resource_start_value') / $resource->trade->buy);
                    $this->resourceService->update($hold, [$resourceKey => $amountToAdd]);
                }

                foreach($this->holdCalculator->getNewBuildings($hold) as $buildingKey => $buildingAmount)
                {
                    $this->buildingService->update($hold, [$buildingKey => $buildingAmount]);
                }

                GameEvent::create([
                    'round_id' => $hold->round_id,
                    'source_type' => Hold::class,
                    'source_id' => $hold->id,
                    'target_type' => null,
                    'target_id' => null,
                    'type' => 'hold_discovered',
                    'data' => '',
                    'tick' => $hold->round->ticks
                ]);
            }
        });
    }

}