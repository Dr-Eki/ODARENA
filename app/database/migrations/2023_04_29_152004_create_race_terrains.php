<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRaceTerrains extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('race_terrains', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('race_id');
            $table->foreign('race_id')->references('id')->on('races');

            $table->unsignedBigInteger('terrain_id'); 
            $table->foreign('terrain_id')->references('id')->on('terrains');

            $table->timestamps();

            $table->unique(['race_id', 'terrain_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('race_terrains');
    }
}
