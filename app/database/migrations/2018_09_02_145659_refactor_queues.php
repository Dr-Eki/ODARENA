<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RefactorQueues extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Create table
        Schema::create('dominion_queue', function (Blueprint $table) {
            $table->integer('dominion_id')->unsigned();
            $table->string('source');
            $table->string('resource');
            $table->integer('hours');
            $table->integer('amount');
            $table->timestamp('created_at')->nullable();

            $table->foreign('dominion_id')->references('id')->on('dominions');

            $table->primary(['dominion_id', 'source', 'resource', 'hours']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dominion_queue');
    }
}
