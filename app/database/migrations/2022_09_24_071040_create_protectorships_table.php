<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProtectorshipsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('protectorships', function (Blueprint $table) {
            $table->id();

            $table->integer('protector_id')->unsigned();
            $table->integer('protected_id')->unsigned();
            $table->integer('tick')->unsigned();

            $table->timestamps();

            $table->foreign('protector_id')->references('id')->on('dominions');
            $table->foreign('protected_id')->references('id')->on('dominions');

            $table->unique(['protector_id']);
            $table->unique(['protected_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('protectorships');
    }
}
