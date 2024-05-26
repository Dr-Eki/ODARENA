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
            xtlog("[{$dominion->id}] **** Updating buildings for dominion: " . json_encode($buildingKeys));

            foreach($buildingKeys as $buildingKey => $amount)
            {

                xtLog("[{$dominion->id}] ***** Updating building {$buildingKey}) by {$amount}");

                $building = Building::fromKey($buildingKey);

                if(!$building)
                {
                    xtLog("[{$dominion->id}] ***** Building with key {$buildingKey} not found", 'error');
                    continue;
                }
    
                $dominionHasBuilding = DominionBuilding::where('dominion_id', $dominion->id)
                    ->where('building_id', $building->id)
                    ->exists();
    
                if($dominionHasBuilding)
                {

                    $dominionBuilding = DominionBuilding::where('dominion_id', $dominion->id)
                        ->where('building_id', $building->id)
                        ->first();

                    xtLog("[{$dominion->id}] ***** DominionBuilding to be updated from " . $dominionBuilding->amount ?? 0 . " by {$amount}");

                    $dominionBuilding->amount += $amount;
    
                    if ($dominionBuilding->amount <= 0)
                    {
                        $id = $dominionBuilding->id;
                        $dominionBuilding->delete();
                        xtLog("[{$dominion->id}] ****** DominionBuilding {$id} deleted");
                    }
                    else
                    {
                        $dominionBuilding->save();
                        xtLog("[{$dominion->id}] ****** DominionBuilding {$dominionBuilding->id} amount updated to {$dominionBuilding->amount}");
                    }
                }
                elseif($amount > 0)
                {
                    $dominionBuilding = DominionBuilding::create([
                        'dominion_id' => $dominion->id,
                        'building_id' => $building->id,
                        'amount' => $amount,
                    ]);

                    $dominionBuilding->save();

                    xtLog("[{$dominion->id}] ****** DominionBuilding (building {$building->name}) created with amount {$amount} and ID {$dominionBuilding->id}");
                }
                else
                {
                    xtLog("[{$dominion->id}] ****** DominionBuilding not found or amount <= 0", 'error');
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
