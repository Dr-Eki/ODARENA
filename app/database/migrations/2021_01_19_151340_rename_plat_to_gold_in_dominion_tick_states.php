<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RenamePlatToGoldInDominionTickStates extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dominion_tick_states', function (Blueprint $table) {
            $table->renameColumn('resource_platinum', 'resource_gold');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dominion_tick_states', function (Blueprint $table) {
            $table->renameColumn('resource_gold', 'resource_platinum');
        });
    }
}
