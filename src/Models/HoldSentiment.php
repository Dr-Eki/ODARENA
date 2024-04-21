<?php

namespace OpenDominion\Models;

use Illuminate\Database\Eloquent\Model;

class HoldSentiment extends Model
{
    protected $table = 'hold_sentiments';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'hold_id', 'target_id', 'target_type', 'sentiment'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'sentiment' => 'int',
    ];

    /**
     * Get the hold that owns the sentiment.
     */
    public function hold()
    {
        return $this->belongsTo(Hold::class, 'hold_id');
    }

    /**
     * Get the entity that the sentiment targets.
     */
    public function target()
    {
        return $this->morphTo();
    }
}
