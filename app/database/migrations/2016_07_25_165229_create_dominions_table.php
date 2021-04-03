<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateDominionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dominions', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('user_id')->unsigned();
            $table->integer('round_id')->unsigned();
            $table->integer('realm_id')->unsigned();
            $table->integer('race_id')->unsigned();

            $table->string('name');
            $table->integer('networth');
            $table->integer('prestige');

            // todo: wizard_prestige, spy_prestige

            $table->integer('peasants')->unsigned();
            $table->integer('peasants_last_hour')->default(0);

            $table->integer('draft_rate')->unsigned();
            $table->integer('morale')->unsigned();
            $table->integer('spy_strength')->unsigned();
            $table->integer('wizard_strength')->unsigned();

            $table->integer('resource_platinum')->unsigned();
            $table->integer('resource_food')->unsigned();
            $table->integer('resource_lumber')->unsigned();
            $table->integer('resource_mana')->unsigned();
            $table->integer('resource_ore')->unsigned();
            $table->integer('resource_gems')->unsigned();
            $table->integer('resource_tech')->unsigned();
            $table->integer('resource_boats')->unsigned();
            $table->integer('resource_champion')->unsigned();
            $table->integer('resource_soul')->unsigned();
            $table->integer('resource_wild_yeti')->unsigned();

            #$table->integer('improvement_science')->unsigned();
            $table->integer('improvement_markets')->unsigned()->default(0);
            $table->integer('improvement_keep')->unsigned()->default(0);
            $table->integer('improvement_towers')->unsigned()->default(0);
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
            $table->integer('improvement_tissue')->unsigned()->default(0);

            $table->integer('military_draftees')->unsigned();
            $table->integer('military_unit1')->unsigned();
            $table->integer('military_unit2')->unsigned();
            $table->integer('military_unit3')->unsigned();
            $table->integer('military_unit4')->unsigned();
            $table->integer('military_spies')->unsigned();
            $table->integer('military_wizards')->unsigned();
            $table->integer('military_archmages')->unsigned();

            $table->integer('land_plain')->unsigned();
            $table->integer('land_mountain')->unsigned();
            $table->integer('land_swamp')->unsigned();
            $table->integer('land_cavern')->unsigned();
            $table->integer('land_forest')->unsigned();
            $table->integer('land_hill')->unsigned();
            $table->integer('land_water')->unsigned();

            $table->integer('building_home')->unsigned();
            $table->integer('building_alchemy')->unsigned();
            $table->integer('building_farm')->unsigned();
            $table->integer('building_smithy')->unsigned();
            $table->integer('building_masonry')->unsigned();
            $table->integer('building_ore_mine')->unsigned();
            $table->integer('building_gryphon_nest')->unsigned();
            $table->integer('building_tower')->unsigned();
            $table->integer('building_wizard_guild')->unsigned();
            $table->integer('building_temple')->unsigned();
            $table->integer('building_diamond_mine')->unsigned();
            $table->integer('building_school')->unsigned();
            $table->integer('building_lumberyard')->unsigned();
            $table->integer('building_forest_haven')->unsigned();
            $table->integer('building_factory')->unsigned();
            $table->integer('building_guard_tower')->unsigned();
            $table->integer('building_shrine')->unsigned();
            $table->integer('building_barracks')->unsigned();
            $table->integer('building_dock')->unsigned();

            $table->integer('building_ziggurat')->unsigned();
            $table->integer('building_mycelia')->unsigned();
            $table->integer('building_tissue')->unsigned();

            $table->integer('npc_modifier')->unsigned();
            $table->integer('protection_ticks')->unsigned();
            $table->string('most_recent_improvement_resource')->default('gems');
            $table->integer('stat_total_land_lost')->unsigned()->default(0);

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('round_id')->references('id')->on('rounds');
            $table->foreign('realm_id')->references('id')->on('realms');
            $table->foreign('race_id')->references('id')->on('races');

            $table->unique(['user_id', 'round_id']);
            $table->unique(['round_id', 'name']);
        });

        Schema::table('realms', function (Blueprint $table) {
            $table->foreign('monarch_dominion_id')->references('id')->on('dominions');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Dropping foreign keys on sqlite throws an exception since Laravel 5.7, since it never worked with sqlite to
        // begin with
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('realms', function (Blueprint $table) {
                $table->dropForeign('realms_monarch_dominion_id_foreign');
            });
        }

        Schema::drop('dominions');
    }
}
