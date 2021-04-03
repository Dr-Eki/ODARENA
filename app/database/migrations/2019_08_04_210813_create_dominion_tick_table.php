<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDominionTickTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dominion_tick', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('dominion_id')->unsigned();
            $table->integer('prestige')->default(0);
            $table->integer('peasants')->default(0);
            $table->integer('morale')->default(0);
            $table->float('spy_strength')->default(0);
            $table->float('wizard_strength')->default(0);
            $table->integer('resource_platinum')->default(0);
            $table->integer('resource_food')->default(0);
            $table->integer('resource_food_production')->default(0);
            $table->integer('resource_lumber')->default(0);
            $table->integer('resource_lumber_production')->default(0);
            $table->integer('resource_mana')->default(0);
            $table->integer('resource_mana_production')->default(0);
            $table->integer('resource_ore')->default(0);
            $table->integer('resource_gems')->default(0);
            $table->integer('resource_tech')->default(0);
            $table->float('resource_boats')->default(0);
            $table->integer('resource_wild_yeti')->default(0);
            $table->integer('resource_wild_yeti_production')->default(0);
            $table->integer('military_draftees')->default(0);
            $table->integer('military_unit1')->default(0);
            $table->integer('military_unit2')->default(0);
            $table->integer('military_unit3')->default(0);
            $table->integer('military_unit4')->default(0);
            $table->integer('military_spies')->default(0);
            $table->integer('military_wizards')->default(0);
            $table->integer('military_archmages')->default(0);
            $table->integer('land_plain')->default(0);
            $table->integer('land_mountain')->default(0);
            $table->integer('land_swamp')->default(0);
            $table->integer('land_cavern')->default(0);
            $table->integer('land_forest')->default(0);
            $table->integer('land_hill')->default(0);
            $table->integer('land_water')->default(0);
            $table->integer('discounted_land')->default(0);
            $table->integer('building_home')->default(0);
            $table->integer('building_alchemy')->default(0);
            $table->integer('building_farm')->default(0);
            $table->integer('building_smithy')->default(0);
            $table->integer('building_masonry')->default(0);
            $table->integer('building_ore_mine')->default(0);
            $table->integer('building_gryphon_nest')->default(0);
            $table->integer('building_tower')->default(0);
            $table->integer('building_wizard_guild')->default(0);
            $table->integer('building_temple')->default(0);
            $table->integer('building_diamond_mine')->default(0);
            $table->integer('building_school')->default(0);
            $table->integer('building_lumberyard')->default(0);
            $table->integer('building_forest_haven')->default(0);
            $table->integer('building_factory')->default(0);
            $table->integer('building_guard_tower')->default(0);
            $table->integer('building_shrine')->default(0);
            $table->integer('building_barracks')->default(0);
            $table->integer('building_dock')->default(0);

            #ODA
            $table->integer('building_mycelia')->default(0);
            $table->integer('building_tissue')->default(0);
            $table->integer('building_ziggurat')->default(0);

            $table->integer('generated_land')->default(0);
            $table->integer('generated_unit1')->default(0);
            $table->integer('generated_unit2')->default(0);
            $table->integer('generated_unit3')->default(0);
            $table->integer('generated_unit4')->default(0);
            $table->integer('resource_champion')->default(0);
            $table->integer('resource_soul')->default(0);

            $table->text('starvation_casualties')->nullable();
            $table->text('pestilence_units')->nullable();
            $table->integer('protection_ticks')->default(0);
            $table->integer('peasants_sacrificed')->default(0);


            $table->integer('improvement_markets')->default(0);
            $table->integer('improvement_keep')->default(0);
            $table->integer('improvement_forges')->default(0);
            $table->integer('improvement_walls')->default(0);
            $table->integer('improvement_armory')->default(0);
            $table->integer('improvement_infirmary')->default(0);
            $table->integer('improvement_workshops')->default(0);
            $table->integer('improvement_observatory')->default(0);
            $table->integer('improvement_cartography')->default(0);
            $table->integer('improvement_towers')->default(0);
            $table->integer('improvement_hideouts')->default(0);
            $table->integer('improvement_granaries')->default(0);
            $table->integer('improvement_harbor')->default(0);
            $table->integer('improvement_forestry')->default(0);
            $table->integer('improvement_refinery')->default(0);
            $table->integer('improvement_tissue')->default(0);

            $table->timestamp('updated_at')->nullable();
        });

        Schema::table('dominion_tick', function (Blueprint $table) {
            $table->foreign('dominion_id')->references('id')->on('dominions');
        });

        Schema::table('dominions', function (Blueprint $table) {
            $table->timestamp('last_tick_at')->nullable()->after('elite_guard_active_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dominion_tick');

        Schema::table('dominions', function (Blueprint $table) {
            $table->dropColumn('last_tick_at');
        });
    }
}
