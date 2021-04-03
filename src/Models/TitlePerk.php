<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\TitlePerk
 *
 * @property int $id
 * @property int $title_id
 * @property int $title_perk_type_id
 * @property float $value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \OpenDominion\Models\Title $title
 * @property-read \OpenDominion\Models\TitlePerkType $type
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\TitlePerk newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\TitlePerk newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\TitlePerk query()
 * @mixin \Eloquent
 */
class TitlePerk extends AbstractModel
{
    protected $casts = [
        'value' => 'float',
    ];

    public function title()
    {
        return $this->belongsTo(Title::class);
    }

    public function type()
    {
        return $this->belongsTo(TitlePerkType::class, 'title_perk_type_id');
    }
}
