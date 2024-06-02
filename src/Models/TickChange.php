<?php

namespace OpenDominion\Models;

use Illuminate\Database\Eloquent\Model;

class TickChange extends Model
{
    protected $table = 'tick_changes';

    protected $fillable = [
        'tick',
        'source_id',
        'source_type',
        'target_id',
        'target_type',
        'amount',
        'status',
        'type'
    ];

    protected $casts = [
        'tick' => 'integer',
        'amount' => 'float',
        'status' => 'integer',
        'type' => 'string',
    ];

    #protected $guarded = ['id', 'created_at', 'updated_at'];

    #protected $dates = ['created_at', 'updated_at'];

    public function source()
    {
        return $this->morphTo();
    }

    public function target()
    {
        return $this->morphTo();
    }

}
