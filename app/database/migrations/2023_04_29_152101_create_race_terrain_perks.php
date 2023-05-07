<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRaceTerrainPerks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('race_terrain_perks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('race_terrain_id');
            $table->unsignedBigInteger('race_terrain_perk_type_id');
            $table->string('value')->nullable();

            $table->foreign('race_terrain_id')->references('id')->on('race_terrains');
            $table->foreign('race_terrain_perk_type_id')->references('id')->on('race_terrain_perk_types');
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
        Schema::dropIfExists('race_terrain_perks');
    }
}
