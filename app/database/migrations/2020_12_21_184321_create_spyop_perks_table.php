<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSpyopPerksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('spyop_perks', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('spyop_id')->unsigned();
            $table->integer('spyop_perk_type_id')->unsigned();
            $table->string('value')->nullable();
            $table->timestamps();

            $table->foreign('spyop_id')->references('id')->on('spyops');
            $table->foreign('spyop_perk_type_id')->references('id')->on('spyop_perk_types');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('spyop_perks');
    }
}
