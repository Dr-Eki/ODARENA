<?php

namespace OpenDominion\Services\Hold;

use DB;
use OpenDominion\Models\Hold;
use OpenDominion\Models\HoldResource;
use OpenDominion\Models\Resource;
use OpenDominion\Calculators\Dominion\ResourceCalculator;
use OpenDominion\Helpers\ResourceHelper;
use OpenDominion\Services\Dominion\QueueService;

class ResourceService
{

    /** @var ResourceHelper */
    protected $resourceHelper;

    /** @var ResourceCalculator */
    protected $resourceCalculator;

    /** @var QueueService */
    protected $queueService;

    public function __construct()
    {
        $this->resourceHelper = app(ResourceHelper::class);
        $this->resourceCalculator = app(ResourceCalculator::class);
        $this->queueService = app(QueueService::class);
    }

    public function update(Hold $hold, array $resourceKeys): void
    {
        DB::transaction(function () use ($hold, $resourceKeys) {
            foreach($resourceKeys as $resourceKey => $amount) {
                $resource = Resource::where('key', $resourceKey)->first();
    
                $holdHasResource = HoldResource::where('hold_id', $hold->id)
                    ->where('resource_id', $resource->id)
                    ->exists();
    
                if($holdHasResource) {
                    $holdResource = HoldResource::where('hold_id', $hold->id)
                        ->where('resource_id', $resource->id)
                        ->first();
    
                    $holdResource->amount += $amount;
    
                    if ($holdResource->amount <= 0) {
                        $holdResource->delete();
                    } else {
                        $holdResource->save();
                    }
                } elseif($amount > 0) {
                    HoldResource::create([
                        'hold_id' => $hold->id,
                        'resource_id' => $resource->id,
                        'amount' => $amount,
                    ]);
                }
            }
        });
    }

}
