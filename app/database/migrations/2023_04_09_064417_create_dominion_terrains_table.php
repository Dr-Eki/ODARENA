<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDominionTerrainsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dominion_terrains', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('dominion_id');
            $table->foreign('dominion_id')->references('id')->on('dominions');

            $table->unsignedBigInteger('terrain_id'); 
            $table->foreign('terrain_id')->references('id')->on('terrains');

            $table->integer('amount')->default(0);

            $table->timestamps();

            $table->unique(['dominion_id', 'terrain_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dominion_terrains');
    }
}
