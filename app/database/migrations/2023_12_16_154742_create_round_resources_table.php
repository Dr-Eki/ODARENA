<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoundResourcesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('round_resources', function (Blueprint $table) {
            $table->id();

            $table->integer('round_id')->unsigned();
            $table->unsignedInteger('resource_id')->unsigned();
            $table->unsignedInteger('amount')->default(0);

            $table->foreign('round_id')->references('id')->on('rounds');
            $table->foreign('resource_id')->references('id')->on('resources');
            $table->unique(['round_id', 'resource_id']);

            
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
        Schema::dropIfExists('round_resources');
    }
}
