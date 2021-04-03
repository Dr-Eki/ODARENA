<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDominionTickStatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dominion_tick_states', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('dominion_id')->unsigned()->nullable();

            $table->timestamp('tick')->nullable()->default(now());

            $table->integer('peasants')->unsigned()->default(0);
            $table->integer('prestige')->unsigned()->default(0);
            $table->integer('draft_rate')->unsigned()->default(0);
            $table->integer('morale')->unsigned()->default(0);
            $table->integer('spy_strength')->unsigned()->default(0);
            $table->integer('wizard_strength')->unsigned()->default(0);

            $table->integer('resource_platinum')->unsigned()->default(0);
            $table->integer('resource_food')->unsigned()->default(0);
            $table->integer('resource_lumber')->unsigned()->default(0);
            $table->integer('resource_mana')->unsigned()->default(0);
            $table->integer('resource_ore')->unsigned()->default(0);
            $table->integer('resource_gems')->unsigned()->default(0);
            $table->integer('resource_tech')->unsigned()->default(0);
            $table->integer('resource_boats')->unsigned()->default(0);
            $table->integer('resource_champion')->unsigned()->default(0);
            $table->integer('resource_soul')->unsigned()->default(0);
            $table->integer('resource_wild_yeti')->unsigned()->default(0);
            $table->integer('resource_blood')->unsigned()->default(0);

            $table->integer('improvement_markets')->unsigned()->default(0);
            $table->integer('improvement_keep')->unsigned()->default(0);
            $table->integer('improvement_spires')->unsigned()->default(0);
            $table->integer('improvement_forges')->unsigned()->default(0);
            $table->integer('improvement_walls')->unsigned()->default(0);
            $table->integer('improvement_irrigation')->unsigned()->default(0);
            $table->integer('improvement_armory')->unsigned()->default(0);
            $table->integer('improvement_infirmary')->unsigned()->default(0);
            $table->integer('improvement_workshops')->unsigned()->default(0);
            $table->integer('improvement_observatory')->unsigned()->default(0);
            $table->integer('improvement_cartography')->unsigned()->default(0);
            $table->integer('improvement_hideouts')->unsigned()->default(0);
            $table->integer('improvement_forestry')->unsigned()->default(0);
            $table->integer('improvement_refinery')->unsigned()->default(0);
            $table->integer('improvement_granaries')->unsigned()->default(0);
            $table->integer('improvement_harbor')->unsigned()->default(0);
            $table->integer('improvement_tissue')->unsigned()->default(0);

            $table->integer('military_draftees')->unsigned()->default(0);
            $table->integer('military_unit1')->unsigned()->default(0);
            $table->integer('military_unit2')->unsigned()->default(0);
            $table->integer('military_unit3')->unsigned()->default(0);
            $table->integer('military_unit4')->unsigned()->default(0);
            $table->integer('military_spies')->unsigned()->default(0);
            $table->integer('military_wizards')->unsigned()->default(0);
            $table->integer('military_archmages')->unsigned()->default(0);

            $table->integer('land_plain')->unsigned()->default(0);
            $table->integer('land_mountain')->unsigned()->default(0);
            $table->integer('land_swamp')->unsigned()->default(0);
            $table->integer('land_forest')->unsigned()->default(0);
            $table->integer('land_hill')->unsigned()->default(0);
            $table->integer('land_water')->unsigned()->default(0);

            $table->integer('building_home')->unsigned()->default(0);
            $table->integer('building_alchemy')->unsigned()->default(0);
            $table->integer('building_farm')->unsigned()->default(0);
            $table->integer('building_smithy')->unsigned()->default(0);
            $table->integer('building_masonry')->unsigned()->default(0);
            $table->integer('building_ore_mine')->unsigned()->default(0);
            $table->integer('building_gryphon_nest')->unsigned()->default(0);
            $table->integer('building_tower')->unsigned()->default(0);
            $table->integer('building_wizard_guild')->unsigned()->default(0);
            $table->integer('building_temple')->unsigned()->default(0);
            $table->integer('building_diamond_mine')->unsigned()->default(0);
            $table->integer('building_school')->unsigned()->default(0);
            $table->integer('building_lumberyard')->unsigned()->default(0);
            $table->integer('building_forest_haven')->unsigned()->default(0);
            $table->integer('building_factory')->unsigned()->default(0);
            $table->integer('building_guard_tower')->unsigned()->default(0);
            $table->integer('building_shrine')->unsigned()->default(0);
            $table->integer('building_barracks')->unsigned()->default(0);
            $table->integer('building_dock')->unsigned()->default(0);
            $table->integer('building_ziggurat')->unsigned()->default(0);
            $table->integer('building_mycelia')->unsigned()->default(0);
            $table->integer('building_tissue')->unsigned()->default(0);

            $table->integer('protection_ticks')->unsigned()->default(0);
            $table->integer('is_locked')->unsigned()->default(0);

            $table->foreign('dominion_id')->references('id')->on('dominions');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dominion_tick_states');
    }
}
