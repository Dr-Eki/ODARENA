<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProtectorshipsOffersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('protectorship_offers', function (Blueprint $table) {
            $table->id();

            $table->integer('protector_id')->unsigned();
            $table->integer('protected_id')->unsigned();
            $table->integer('status')->unsigned()->default(0); # 0 = not accepted, 1 = accepted, 2 = rejected

            $table->timestamps();

            $table->foreign('protector_id')->references('id')->on('dominions');
            $table->foreign('protected_id')->references('id')->on('dominions');

            $table->unique(['protector_id', 'protected_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('protectorship_offers');
    }
}
