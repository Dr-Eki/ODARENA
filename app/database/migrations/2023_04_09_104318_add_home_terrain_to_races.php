<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHomeTerrainToRaces extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('races', function (Blueprint $table) {
            $table->unsignedBigInteger('home_terrain_id')->nullable()->after('terrains');
            $table->foreign('home_terrain_id')->references('id')->on('terrains');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('races', function (Blueprint $table) {
            
            $table->dropForeign('races_home_terrain_id_foreign');
            $table->dropColumn('home_terrain_id');
            
        });
    }
}
