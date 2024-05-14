<?php

namespace OpenDominion\Services\Dominion;

use DB;
use Log;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\DominionBuilding;
use OpenDominion\Models\Building;
use OpenDominion\Services\Dominion\QueueService;

class BuildingService
{
    protected $queueService;

    public function __construct()
    {
        $this->queueService = app(QueueService::class);
    }

    public function update(Dominion $dominion, array $buildingKeys): void
    {
        DB::transaction(function () use ($dominion, $buildingKeys)
        {

            Log::info('** BuildingService::update() - Updating DominionBuildings', [
                'dominion_id' => $dominion->id,
                'building_keys' => $buildingKeys,
            ]);
            dump('** BuildingService::update() - Updating DominionBuildings', [
                'dominion_id' => $dominion->id,
                'building_keys' => $buildingKeys,
            ]);

            foreach($buildingKeys as $buildingKey => $amount)
            {

                Log::info('*** BuildingService::update() - foreach() - Updating DominionBuilding', [
                    'dominion_id' => $dominion->id,
                    'building_key' => $buildingKey,
                    'amount' => $amount,
                ]);
                dump('*** BuildingService::update() - foreach() - Updating DominionBuilding', [
                    'dominion_id' => $dominion->id,
                    'building_key' => $buildingKey,
                    'amount' => $amount,
                ]);

                $building = Building::fromKey($buildingKey);

                if(!$building)
                {
                    Log::error('*** BuildingService::update() - Building not found', [
                        'dominion_id' => $dominion->id,
                        'building_key' => $buildingKey,
                    ]);
                    dump('*** BuildingService::update() - Building not found', [
                        'dominion_id' => $dominion->id,
                        'building_key' => $buildingKey,
                    ]);
                    continue;
                }
    
                $dominionHasBuilding = DominionBuilding::where('dominion_id', $dominion->id)
                    ->where('building_id', $building->id)
                    ->exists();
    
                if($dominionHasBuilding)
                {

                    $logString = '*** BuildingService::update() - DominionBuilding to be updated from ' . $dominionHasBuilding->amount ?? 0 . ' by ' . $amount;

                    $dominionBuilding = DominionBuilding::where('dominion_id', $dominion->id)
                        ->where('building_id', $building->id)
                        ->first();
    
                    $dominionBuilding->amount += $amount;
    
                    if ($dominionBuilding->amount <= 0)
                    {
                        $dominionBuilding->delete();
                    }
                    else
                    {
                        $dominionBuilding->save();
                    }

                    $logString .= ' to ' . $dominionBuilding->amount;

                    Log::info($logString);
                    dump($logString);
                }
                elseif($amount > 0)
                {
                    $dominionBuilding = DominionBuilding::create([
                        'dominion_id' => $dominion->id,
                        'building_id' => $building->id,
                        'amount' => $amount,
                    ]);

                    $dominionBuilding->save();

                    Log::info('*** BuildingService::update() - DominionBuilding created', [
                        'dominion_id' => $dominion->id,
                        'building_id' => $building->id,
                        'amount' => $amount,
                    ]);
                    dump('*** BuildingService::update() - DominionBuilding created', [
                        'dominion_id' => $dominion->id,
                        'building_id' => $building->id,
                        'amount' => $amount,
                    ]);
                }
                else
                {
                    Log::error('*** BuildingService::update() - DominionBuilding not found or amount <= 0', [
                        'dominion_id' => $dominion->id,
                        'building_id' => $building->id,
                        'amount' => $amount,
                    ]);
                    dump('*** BuildingService::update() - DominionBuilding not found or amount <= 0', [
                        'dominion_id' => $dominion->id,
                        'building_id' => $building->id,
                        'amount' => $amount,
                    ]);
                }
            }
        });
    }

    public function construct(Dominion $dominion, array $buildingData): void
    {
        foreach($buildingData as $buildingKey => $amount)
        {
            $this->queueService->queueBuilding($dominion, $buildingKey, $amount);
        }
    }

}
