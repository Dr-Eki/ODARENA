<?php

namespace OpenDominion\Models;

use OpenDominion\Models\AbstractModel;

class TickChange extends AbstractModel
{
    protected $table = 'tick_changes';

    protected $casts = [
        'amount' => 'float',
        'status' => 'integer',
        'type' => 'string',
    ];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $dates = ['created_at', 'updated_at'];

    public function source()
    {
        return $this->morphTo();
    }

    public function target()
    {
        return $this->morphTo();
    }

}
