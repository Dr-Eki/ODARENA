<?php

namespace OpenDominion\Models;

use Illuminate\Database\Eloquent\Model;

class HoldSentimentEvent extends Model
{
    protected $table = 'hold_sentiment_events';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'hold_id',
        'target_id',
        'target_type',
        'sentiment',
        'description'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'sentiment' => 'int',
        'description' => 'string',
        'created_at' => 'datetime',
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


    public static function add(Hold $hold, $target, int $sentiment, string $description)
    {
        return self::create([
            'hold_id' => $hold->id,
            'target_id' => $target->id,
            'target_type' => get_class($target),
            'sentiment' => $sentiment,
            'description' => $description,
        ]);
    }
}
