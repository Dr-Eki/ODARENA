<?php

namespace OpenDominion\Models\Dominion;

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
    protected $table = 'dominion_tick';

    protected $casts = [
        'prestige' => 'integer',
        'xp' => 'integer',
        'peasants' => 'integer',
        'morale' => 'integer',
        'spy_strength' => 'float',
        'wizard_strength' => 'float',
        'resource_gold' => 'integer',
        'resource_food' => 'integer',
        'resource_lumber' => 'integer',
        'resource_mana' => 'integer',
        'resource_ore' => 'integer',
        'resource_gems' => 'integer',
        'military_draftees' => 'integer',
        'military_unit1' => 'integer',
        'military_unit2' => 'integer',
        'military_unit3' => 'integer',
        'military_unit4' => 'integer',
        'military_unit5' => 'integer',
        'military_unit6' => 'integer',
        'military_unit7' => 'integer',
        'military_unit8' => 'integer',
        'military_unit9' => 'integer',
        'military_unit10' => 'integer',
        'military_spies' => 'integer',
        'military_wizards' => 'integer',
        'military_archmages' => 'integer',
        'land' => 'integer',
        'land_plain' => 'integer',
        'land_mountain' => 'integer',
        'land_swamp' => 'integer',
        'land_cavern' => 'integer',
        'land_forest' => 'integer',
        'land_hill' => 'integer',
        'land_water' => 'integer',
        'discounted_land' => 'integer',
        'generated_land' => 'integer',
        'generated_unit1' => 'integer',
        'generated_unit2' => 'integer',
        'generated_unit3' => 'integer',
        'generated_unit4' => 'integer',
        'generated_unit5' => 'integer',
        'generated_unit6' => 'integer',
        'generated_unit7' => 'integer',
        'generated_unit8' => 'integer',
        'generated_unit9' => 'integer',
        'generated_unit10' => 'integer',
        'starvation_casualties' => 'array',
        'pestilence_units' => 'array',
        'protection_ticks' => 'integer',
        'peasants_sacrificed' => 'integer',
        'attrition_unit1' => 'integer',
        'attrition_unit2' => 'integer',
        'attrition_unit3' => 'integer',
        'attrition_unit4' => 'integer',
        'attrition_unit5' => 'integer',
        'attrition_unit6' => 'integer',
        'attrition_unit7' => 'integer',
        'attrition_unit8' => 'integer',
        'attrition_unit9' => 'integer',
        'attrition_unit10' => 'integer',
        'crypt_bodies_spent' => 'integer',
        'buildings_destroyed' => 'array',
    ];

    protected $guarded = ['id', 'updated_at'];

    protected $dates = ['updated_at'];

    public function dominion()
    {
        return $this->belongsTo(\OpenDominion\Models\Dominion::class);
    }

    const CREATED_AT = null;
}
