<?php

namespace OpenDominion\Services\Hold;

use DB;
use OpenDominion\Models\Hold;
use OpenDominion\Models\HoldBuilding;
use OpenDominion\Models\Building;
use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Services\Dominion\QueueService;

class BuildingService
{

    protected $buildingHelper;

    protected $buildingCalculator;

    protected $queueService;

    public function __construct()
    {
        $this->buildingHelper = app(BuildingHelper::class);
        $this->buildingCalculator = app(BuildingCalculator::class);
        $this->queueService = app(QueueService::class);
    }

    public function update(Hold $hold, array $buildingKeys): void
    {
        DB::transaction(function () use ($hold, $buildingKeys) {
            foreach($buildingKeys as $buildingKey => $amount) {
                $building = Building::where('key', $buildingKey)->first();
    
                $holdHasBuilding = HoldBuilding::where('hold_id', $hold->id)
                    ->where('building_id', $building->id)
                    ->exists();
    
                if($holdHasBuilding) {
                    $HoldBuilding = HoldBuilding::where('hold_id', $hold->id)
                        ->where('building_id', $building->id)
                        ->first();
    
                    $HoldBuilding->amount += $amount;
    
                    if ($HoldBuilding->amount <= 0) {
                        $HoldBuilding->delete();
                    } else {
                        $HoldBuilding->save();
                    }
                } elseif($amount > 0) {
                    HoldBuilding::create([
                        'hold_id' => $hold->id,
                        'building_id' => $building->id,
                        'amount' => $amount,
                    ]);
                }
            }
        });
    }

    public function construct(Hold $hold, array $buildingData): void
    {
        foreach($buildingData as $buildingKey => $amount)
        {
            $this->queueService->queueBuilding($hold, $buildingKey, $amount);
        }
    }

}
