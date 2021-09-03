<?php

namespace OpenDominion\Services\Dominion;

use DB;
use Carbon\Carbon;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\DominionResource;
use OpenDominion\Models\Resource;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Calculators\Dominion\ResourceCalculator;
use OpenDominion\Helpers\ResourceHelper;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Models\GameEvent;

class ResourceService
{
    public function __construct()
    {
        $this->resourceHelper = app(ResourceHelper::class);
        $this->resourceCalculator = app(ResourceCalculator::class);
        $this->queueService = app(QueueService::class);
    }

    public function updateResources(Dominion $dominion, array $resourceKeys): void
    {
        foreach($resourceKeys as $resourceKey => $amount)
        {
            # Positive values: create or update DominionResource
            if($amount > 0)
            {
                $resource = Resource::where('key', $resourceKey)->first();
                $amount = intval(max(0, $amount));

                if($this->resourceCalculator->dominionHasResource($dominion, $resourceKey))
                {
                    DB::transaction(function () use ($dominion, $resource, $amount)
                    {
                        DominionResource::where('dominion_id', $dominion->id)->where('resource_id', $resource->id)
                        ->increment('amount', $amount);
                    });
                }
                else
                {
                    DB::transaction(function () use ($dominion, $resource, $amount)
                    {
                        DominionResource::create([
                            'dominion_id' => $dominion->id,
                            'resource_id' => $resource->id,
                            'amount' => $amount
                        ]);
                    });
                }
            }
            # Negative values: update or delete DominionResource
            else
            {
                $resource = Resource::where('key', $resourceKey)->first();
                $owned = $this->resourceCalculator->getAmount($dominion, $resource->key);

                $amountToRemove = min(abs($amount), $owned);

                if($this->resourceCalculator->dominionHasResource($dominion, $resourceKey))
                {
                    if($amountToRemove <= $owned)
                    {
                        # Let's try storing 0s instead of deleting.
                        DB::transaction(function () use ($dominion, $resource, $amountToRemove)
                        {
                            DominionResource::where('dominion_id', $dominion->id)->where('resource_id', $resource->id)
                            ->decrement('amount', $amountToRemove);
                        });
                    }
                    # All
                    /*
                    elseif($amountToRemove == $owned)
                    {
                        DB::transaction(function () use ($dominion, $resource)
                        {
                            DominionResource::where('dominion_id', $dominion->id)->where('resource_id', $resource->id)
                            ->delete();
                        });
                    }
                    */
                    else
                    {
                        dd('[MEGA ERROR] Trying to remove more of a resource than you have. This might have been a temporary glitch due to multiple simultaneous events. Try again, but please report your findings on Discord.', $resource, $amountToRemove, $owned);
                    }
                }
            }
        }
    }

}