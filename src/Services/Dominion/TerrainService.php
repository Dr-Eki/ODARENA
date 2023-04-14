<?php

namespace OpenDominion\Services\Dominion;

use DB;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\DominionTerrain;
use OpenDominion\Models\Terrain;
use OpenDominion\Helpers\TerrainHelper;

class TerrainService
{

    protected $terrainHelper;

    public function __construct()
    {
        $this->terrainHelper = app(TerrainHelper::class);
    }

    public function update(Dominion $dominion, array $terrainKeys): void
    {
        foreach($terrainKeys as $terrainKey => $amount)
        {
            # Positive values: create or update DominionTerrain
            if($amount > 0)
            {
                $terrain = Terrain::where('key', $terrainKey)->first();
                $amount = intval(max(0, $amount));

                if($dominion->{'get'.$terrainKey.'Terrain'})
                {
                    DB::transaction(function () use ($dominion, $terrain, $amount)
                    {
                        DominionTerrain::where('dominion_id', $dominion->id)->where('terrain_id', $terrain->id)
                        ->increment('amount', $amount);
                    });
                }
                else
                {
                    DB::transaction(function () use ($dominion, $terrain, $amount)
                    {
                        DominionTerrain::create([
                            'dominion_id' => $dominion->id,
                            'terrain_id' => $terrain->id,
                            'amount' => $amount
                        ]);
                    });
                }
            }
            # Negative values: update or delete DominionTerrain
            else
            {
                $terrain = Terrain::where('key', $terrainKey)->first();

                $owned = $dominion->{'terrain_' . $terrainKey};#$this->terrainCalculator->getAmount($dominion, $terrain->key);

                $amountToRemove = min(abs($amount), $owned);

                if($owned)
                {
                    if($amountToRemove <= $owned)
                    {
                        DB::transaction(function () use ($dominion, $terrain, $amountToRemove)
                        {
                            DominionTerrain::where('dominion_id', $dominion->id)->where('terrain_id', $terrain->id)
                            ->decrement('amount', $amountToRemove);
                        });
                    }
                    else
                    {
                        dd('[MEGA ERROR] Trying to remove more of a terrain than you have. This might have been a temporary glitch due to multiple simultaneous events. Try again, but please report your findings on Discord.', $terrain, $amountToRemove, $owned);
                    }
                }
            }
        }
    }

    

}
