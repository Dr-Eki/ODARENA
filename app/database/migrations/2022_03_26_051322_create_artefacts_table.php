<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateArtefactsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('artefacts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('key')->unique();
            $table->string('name');
            $table->text('description');
            $table->integer('base_power')->unsigned()->default(0);
            $table->text('excluded_races')->nullable();
            $table->text('exclusive_races')->nullable();
            $table->integer('deity_id')->nullable()->unsigned();
            $table->integer('enabled')->default(1);

            $table->foreign('deity_id')->references('id')->on('deities');

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
        Schema::dropIfExists('artefacts');
    }
}