<?php

namespace OpenDominion\Models\Hold;

use OpenDominion\Models\AbstractModel;

/**
 * OpenDominion\Models\Dominion\Tick
 *
 * @property int $id
 * @property int $dominion_id
 * @property int $prestige
 * @property int $peasants
 * @property int $morale
 * @property float $spy_strength
 * @property float $wizard_strength
 * @property int $resource_gold
 * @property int $resource_food
 * @property int $resource_lumber
 * @property int $resource_mana
 * @property int $resource_ore
 * @property int $resource_gems
 * @property int $military_draftees
 * @property int $military_unit1
 * @property int $military_unit2
 * @property int $military_unit3
 * @property int $military_unit4
 * @property int $military_spies
 * @property int $military_wizards
 * @property int $military_archmages
 * @property int $land_plain
 * @property int $land_mountain
 * @property int $land_swamp
 * @property int $land_cavern
 * @property int $land_forest
 * @property int $land_hill
 * @property int $land_water
 * @property int $building_home
 * @property int $building_alchemy
 * @property int $building_farm
 * @property int $building_smithy
 * @property int $building_masonry
 * @property int $building_ore_mine
 * @property int $building_gryphon_nest
 * @property int $building_tower
 * @property int $building_wizard_guild
 * @property int $building_temple
 * @property int $building_gem_mine
 * @property int $building_school
 * @property int $building_lumberyard
 * @property int $building_forest_haven
 * @property int $building_factory
 * @property int $building_guard_tower
 * @property int $building_shrine
 * @property int $building_barracks
 * @property int $building_dock
 * @property-read \OpenDominion\Models\Dominion $dominion
 */
class Tick extends AbstractModel
{
    protected $table = 'hold_tick';

    protected $casts = [
        'peasants' => 'integer',
        'morale' => 'integer',
        'land' => 'integer',
    ];

    protected $guarded = ['id', 'updated_at'];

    protected $dates = ['updated_at'];

    public function hold()
    {
        return $this->belongsTo(\OpenDominion\Models\Hold::class);
    }

    const CREATED_AT = null;
}
