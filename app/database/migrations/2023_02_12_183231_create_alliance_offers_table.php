<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAllianceOffersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('alliance_offers', function (Blueprint $table) {
            $table->id();

            $table->integer('inviter_realm_id')->unsigned();
            $table->integer('invited_realm_id')->unsigned();

            $table->timestamps();

            $table->foreign('inviter_realm_id')->references('id')->on('realms');
            $table->foreign('invited_realm_id')->references('id')->on('realms');

            $table->unique(['inviter_realm_id', 'invited_realm_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('alliance_offers');
    }
}
