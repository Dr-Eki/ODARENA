<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTerrainIdToBuildings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('buildings', function (Blueprint $table) {
            
            $table->unsignedBigInteger('terrain_id')->nullable()->after('land_type');
            $table->foreign('terrain_id')->references('id')->on('terrains');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('buildings', function (Blueprint $table) {
            
            $table->dropForeign('buildings_terrain_id_foreign');
            $table->dropColumn('terrain_id');

        });
    }
}
