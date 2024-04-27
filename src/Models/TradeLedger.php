<?php

namespace OpenDominion\Models;

class TradeLedger extends AbstractModel
{
    protected $table = 'trade_ledger';

    protected $fillable = [
        'round_id',
        'dominion_id',
        'hold_id',
        'tick',
        'return_tick',
        'return_ticks',
        'source_resource_id',
        'target_resource_id',
        'source_amount',
        'target_amount',
        'trade_dominion_sentiment',
    ];

    public function round()
    {
        return $this->belongsTo(Round::class);
    }

    public function dominion()
    {
        return $this->belongsTo(Dominion::class, 'dominion_id');
    }

    public function hold()
    {
        return $this->belongsTo(Hold::class, 'hold_id');
    }

    public function sourceResource()
    {
        return $this->belongsTo(Resource::class, 'source_resource_id');
    }

    public function targetResource()
    {
        return $this->belongsTo(Resource::class, 'target_resource_id');
    }

    public function soldResource()
    {
        return $this->sourceResource();
    }

    public function boughtResource()
    {
        return $this->targetResource();
    }

}
