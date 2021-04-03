<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateImprovementPerksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('improvement_perks', function (Blueprint $table) {
          $table->increments('id');
          $table->integer('improvement_id')->unsigned();
          $table->integer('improvement_perk_type_id')->unsigned();
          $table->string('value')->nullable();
          $table->timestamps();

          $table->foreign('improvement_id')->references('id')->on('improvements');
          $table->foreign('improvement_perk_type_id')->references('id')->on('improvement_perk_types');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('improvement_perks');
    }
}
