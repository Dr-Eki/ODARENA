<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateRealmsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('realms', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('round_id')->unsigned();
            $table->integer('monarch_dominion_id')->unsigned()->nullable();
            $table->string('alignment');
            $table->integer('number');
            $table->string('name')->nullable();
            $table->timestamps();


            #$table->integer('stat_total_land_conquered')->unsigned()->default(0);
            #$table->integer('stat_total_land_explored')->unsigned()->default(0);
            #$table->integer('stat_attacking_success')->unsigned()->default(0);

            $table->foreign('round_id')->references('id')->on('rounds');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('realms');
    }
}
