<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDominionBuildingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dominion_buildings', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('dominion_id')->unsigned();
            $table->integer('building_id')->unsigned();
            $table->integer('owned')->unsigned()->default(0);
            $table->timestamps();

            $table->foreign('dominion_id')->references('id')->on('dominions');
            $table->foreign('building_id')->references('id')->on('buildings');
            $table->unique(['dominion_id', 'building_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dominion_buildings');
    }
}
