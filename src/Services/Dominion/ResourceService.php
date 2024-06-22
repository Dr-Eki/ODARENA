<?php

namespace OpenDominion\Services\Dominion;

use DB;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\DominionResource;
use OpenDominion\Models\Realm;
use OpenDominion\Models\RealmResource;
use OpenDominion\Models\Resource;
use OpenDominion\Models\Round;
use OpenDominion\Models\RoundResource;
use OpenDominion\Calculators\Dominion\ResourceCalculator;

class ResourceService
{

    protected $resourceCalculator;

    public function __construct()
    {
        $this->resourceCalculator = app(ResourceCalculator::class);
    }

    public function update(Dominion $dominion, array $resourceKeys): void
    {
        DB::transaction(function () use ($dominion, $resourceKeys) {
            foreach($resourceKeys as $resourceKey => $amount)
            {
                $resource = Resource::where('key', $resourceKey)->firstOrFail();
                $currentAmount = $dominion->{'resource_' . $resourceKey};

                if($amount < 0 && abs($amount) > $currentAmount)
                {
                    $amount = -$currentAmount;
                }
    
                // Get the dominion resource with a lock for update
                $dominionResource = DominionResource::where('dominion_id', $dominion->id)
                    ->where('resource_id', $resource->id)
                    ->lockForUpdate()
                    ->first();
    
                if ($dominionResource) {
                    // Update the existing dominion resource
                    $dominionResource->update([
                        'amount' => DB::raw("GREATEST(0, amount + {$amount})")
                    ]);
                } else {
                    // Create a new dominion resource
                    DominionResource::create([
                        'dominion_id' => $dominion->id,
                        'resource_id' => $resource->id,
                        'amount' => max(0, $amount)
                    ]);
                }
            }
        });
    }

    public function updateRealmResources(Realm $realm, array $resourceKeys): void
    {
        foreach($resourceKeys as $resourceKey => $amount)
        {
            # Positive values: create or update RealmResource
            if($amount > 0)
            {
                $resource = Resource::where('key', $resourceKey)->first();
                $amount = intval(max(0, $amount));

                if($this->resourceCalculator->realmHasResource($realm, $resourceKey))
                {
                    DB::transaction(function () use ($realm, $resource, $amount)
                    {
                        RealmResource::where('realm_id', $realm->id)->where('resource_id', $resource->id)
                        ->increment('amount', $amount);
                    });
                }
                else
                {
                    DB::transaction(function () use ($realm, $resource, $amount)
                    {
                        RealmResource::create([
                            'realm_id' => $realm->id,
                            'resource_id' => $resource->id,
                            'amount' => $amount
                        ]);
                    });
                }
            }
            # Negative values: update or delete RealmResource
            else
            {
                $resource = Resource::where('key', $resourceKey)->first();
                $owned = $this->resourceCalculator->getRealmAmount($realm, $resource->key);

                $amountToRemove = min(abs($amount), $owned);

                if($this->resourceCalculator->realmHasResource($realm, $resourceKey))
                {
                    if($amountToRemove <= $owned)
                    {
                        # Let's try storing 0s instead of deleting.
                        DB::transaction(function () use ($realm, $resource, $amountToRemove)
                        {
                            RealmResource::where('realm_id', $realm->id)->where('resource_id', $resource->id)
                            ->decrement('amount', $amountToRemove);
                        });
                    }
                    # All
                    /*
                    elseif($amountToRemove == $owned)
                    {
                        DB::transaction(function () use ($realm, $resource)
                        {
                            RealmResource::where('realm_id', $realm->id)->where('resource_id', $resource->id)
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

    public function updateRoundResources(Round $round, array $resourceKeys): void
    {
        foreach($resourceKeys as $resourceKey => $amount)
        {
            # Positive values: create or update RoundResource
            if($amount > 0)
            {
                $resource = Resource::where('key', $resourceKey)->first();
                $amount = intval(max(0, $amount));

                if($this->resourceCalculator->roundHasResource($round, $resourceKey))
                {
                    DB::transaction(function () use ($round, $resource, $amount)
                    {
                        RoundResource::where('round_id', $round->id)->where('resource_id', $resource->id)
                        ->increment('amount', $amount);
                    });
                }
                else
                {
                    DB::transaction(function () use ($round, $resource, $amount)
                    {
                        RoundResource::create([
                            'round_id' => $round->id,
                            'resource_id' => $resource->id,
                            'amount' => $amount
                        ]);
                    });
                }
            }
            # Negative values: update or delete RoundResource
            else
            {
                $resource = Resource::where('key', $resourceKey)->first();
                $owned = $round->{'resource_' . $resourceKey};

                $amountToRemove = min(abs($amount), $owned);

                if($this->resourceCalculator->roundHasResource($round, $resourceKey))
                {
                    if($amountToRemove <= $owned)
                    {
                        # Let's try storing 0s instead of deleting.
                        DB::transaction(function () use ($round, $resource, $amountToRemove)
                        {
                            RoundResource::where('round_id', $round->id)->where('resource_id', $resource->id)
                            ->decrement('amount', $amountToRemove);
                        });
                    }
                    # All
                    /*
                    elseif($amountToRemove == $owned)
                    {
                        DB::transaction(function () use ($round, $resource)
                        {
                            RoundResource::where('round_id', $round->id)->where('resource_id', $resource->id)
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
