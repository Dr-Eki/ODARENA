<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\TitlePerkType
 *
 * @property int $id
 * @property string $key
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\OpenDominion\Models\Title[] $titles
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\TitlePerkType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\TitlePerkType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\TitlePerkType query()
 * @mixin \Eloquent
 */
class TitlePerkType extends AbstractModel
{
    public function titles()
    {
        return $this->belongsToMany(
            Title::class,
            'title_perks',
            'title_perk_type_id',
            'title_id'
        )
            ->withTimestamps();
    }
}
