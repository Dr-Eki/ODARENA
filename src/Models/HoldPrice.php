<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\DominionBuilding
 *
 * @property int $dominion_id
 * @property int $tech_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \OpenDominion\Models\Dominion $dominion
 * @property-read \OpenDominion\Models\Tech $tech
 */
class HoldPrice extends AbstractModel
{
    protected $table = 'hold_prices';

    protected $casts = [
        'tick' => 'integer',
        'action' => 'string',
        'price' => 'float',
    ];

    public function hold()
    {
        return $this->belongsTo(Hold::class, 'hold_id');
    }

    public function resource()
    {
        return $this->belongsTo(Resource::class, 'resource_id');
    }

    public function getBuyPriceAttribute($value)
    {
        return round($value, config('trade.price_decimals'));
    }

    public function getSellPriceAttribute($value)
    {
        return round($value, config('trade.price_decimals'));
    }

    public function setBuyPriceAttribute($value)
    {
        $this->attributes['buy_price'] = round($value, config('trade.price_decimals'));
    }

    public function setSellPriceAttribute($value)
    {
        $this->attributes['sell_price'] = round($value, config('trade.price_decimals'));
    }

    # Get the latest buy price for a resource
    public static function getLatestBuyPrice($resourceId)
    {
        return self::where('resource_id', $resourceId)->where('action','buy')->orderBy('tick', 'desc')->first()->price;
    }

    # Get the latest sell price for a resource
    public static function getLatestSellPrice($resourceId)
    {
        return self::where('resource_id', $resourceId)->where('action','sell')->orderBy('tick', 'desc')->first()->price;
    }


}
