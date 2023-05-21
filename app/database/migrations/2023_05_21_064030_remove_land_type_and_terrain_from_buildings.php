<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveLandTypeAndTerrainFromBuildings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('buildings', function (Blueprint $table) {
            # Remove land type column
            $table->dropColumn('land_type');

            # Remove terrain foreign key and terrain ID
            $table->dropForeign(['terrain_id']);
            $table->dropColumn('terrain_id');
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
            $table->string('land_type')->after('name');
            $table->unsignedBigInteger('terrain_id')->nullable()->after('land_type');
            $table->foreign('terrain_id')->references('id')->on('terrains');
        });
    }
}
