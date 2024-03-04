<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDominionUnitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::transaction(function () {
            Schema::create('dominion_units', function (Blueprint $table) {
                $table->id();

                $table->unsignedInteger('dominion_id');
                $table->foreign('dominion_id')->references('id')->on('dominions');

                $table->unsignedInteger('unit_id');
                $table->foreign('unit_id')->references('id')->on('units');

                $table->unsignedInteger('amount')->default(0);

                $table->unsignedTinyInteger('state')->default(0);

                $table->timestamps();

            });
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dominion_units');
    }
}
