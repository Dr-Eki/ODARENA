<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\DominionTech
 *
 * @property int $dominion_id
 * @property int $tech_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \OpenDominion\Models\Dominion $dominion
 * @property-read \OpenDominion\Models\Spell $tech
 */
class DominionSpell extends AbstractModel
{
    protected $table = 'dominion_spells';

    public function dominion()
    {
        return $this->belongsTo(Dominion::class, 'dominion_id');
    }

    public function caster()
    {
        return $this->belongsTo(Dominion::class, 'caster_id');
    }

    public function spell()
    {
        return $this->belongsTo(Spell::class, 'spell_id');
    }
}
