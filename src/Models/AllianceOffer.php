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
class AllianceOffer extends AbstractModel
{
    protected $table = 'alliance_offers';

    public function inviter()
    {
        return $this->belongsTo(Realm::class, 'inviter_realm_id');
    }

    public function invited()
    {
        return $this->belongsTo(Realm::class, 'invited_realm_id');
    }

}
