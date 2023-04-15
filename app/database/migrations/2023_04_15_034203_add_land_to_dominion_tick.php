<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLandToDominionTick extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dominion_tick', function (Blueprint $table) {
            $table->integer('land')->default(0)->after('military_archmages');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dominion_tick', function (Blueprint $table) {
            $table->dropColumn('land');
        });
    }
}
