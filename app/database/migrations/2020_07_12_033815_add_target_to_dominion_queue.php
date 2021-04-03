<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTargetToDominionQueue extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dominion_queue', function (Blueprint $table)
        {
            $table->integer('target_id')->unsigned()->nullable()->after('dominion_id');
            $table->foreign('target_id')->references('id')->on('dominions');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dominion_queue', function (Blueprint $table) {
            $table->dropColumn('target_id');
        });
    }
}
