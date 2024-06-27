<?php

namespace OpenDominion\Models;

class TradeRoute extends AbstractModel
{
    protected $table = 'trade_routes';

    protected $fillable = [
        'round_id',
        'dominion_id',
        'hold_id',
        'source_resource_id',
        'target_resource_id',
        'source_amount',
        'trades',
        'total_bought',
        'total_sold',
        'status',
        'tick_created',
    ];

    public function hold()
    {
        return $this->belongsTo(Hold::class, 'hold_id');
    }

    public function dominion()
    {
        return $this->belongsTo(Dominion::class, 'dominion_id');
    }

    public function queues()
    {
        return $this->hasMany(TradeRoute\Queue::class);
    }

    public function round()
    {
        return $this->belongsTo(Round::class);
    }

    public function soldResource()
    {
        return $this->belongsTo(Resource::class, 'source_resource_id');
    }

    public function boughtResource()
    {
        return $this->belongsTo(Resource::class, 'target_resource_id');
    }

    public function sourceResource()
    {
        return $this->soldResource();
    }
    public function targetResource()
    {
        return $this->boughtResource();
    }

    public function getAmountAttribute($value)
    {
        return (int)$value;
    }

    public function setAmountAttribute($value)
    {
        $this->attributes['amount'] = (int)$value;
    }

    public function getTradesAttribute($value)
    {
        return (int)$value;
    }

    public function setTradesAttribute($value)
    {
        $this->attributes['trades'] = (int)$value;
    }

    public function getTotalBoughtAttribute($value)
    {
        return (int)$value;
    }

    public function setTotalBoughtAttribute($value)
    {
        $this->attributes['total_bought'] = (int)$value;
    }

    public function getTotalSoldAttribute($value)
    {
        return (int)$value;
    }

    public function setTotalSoldAttribute($value)
    {
        $this->attributes['total_sold'] = (int)$value;
    }

    public function getStatusAttribute($value)
    {
        return (int)$value;
    }

    public function setStatusAttribute($value)
    {
        $this->attributes['status'] = (int)$value;
    }

    public function isActive(): bool
    {
        return $this->status === 1;
    }

    public function hasQueues(): bool
    {
        return $this->queues->isNotEmpty();
    }

}
