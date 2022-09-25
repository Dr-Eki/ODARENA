<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\Tech
 *
 * @property int $id
 * @property string $key
 * @property string $name
 * @property array $prerequisites
 * @property int $level
 * @property int $enabled
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\AdvancementPerkType[] $perks
 */
class ProtectorshipOffer extends AbstractModel
{
    protected $table = 'protectorship_offers';

    protected $casts = [
        'accepted' => 'integer',
    ];

    public function protector()
    {
        return $this->belongsTo(Dominion::class, 'protector_id');
    }

    public function protected()
    {
        return $this->belongsTo(Dominion::class, 'protected_id');
    }

}
