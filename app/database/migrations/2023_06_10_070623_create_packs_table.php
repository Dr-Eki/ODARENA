<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePacksTable extends Migration
{
    public function up()
    {
        Schema::create('packs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('realm_id');
            $table->unsignedInteger('round_id');
            $table->string('password')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('realm_id')->references('id')->on('realms');
            $table->foreign('round_id')->references('id')->on('rounds');
        });
    }

    public function down()
    {
        Schema::dropIfExists('packs');
    }
}
