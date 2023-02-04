<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRealmAlliancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('realm_alliances', function (Blueprint $table) {
            $table->id();

            $table->integer('realm_id')->unsigned();
            $table->integer('allied_realm_id')->unsigned();
            $table->integer('established_tick')->unsigned();

            $table->timestamps();

            $table->unique(['realm_id', 'allied_realm_id']);
            $table->foreign('realm_id')->references('id')->on('realms');
            $table->foreign('allied_realm_id')->references('id')->on('realms');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('realm_alliances');
    }
}
