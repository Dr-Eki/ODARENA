<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSoulsDestroyedAndLandDiscoveredStatsToDominions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dominions', function (Blueprint $table) {
            $table->integer('stat_total_soul_destroyed')->default(0)->after('stat_total_soul_spent_improving');
            $table->integer('stat_total_land_discovered')->default(0)->after('stat_total_land_conquered');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dominions', function (Blueprint $table) {
            $table->dropColumn([
                'stat_total_soul_destroyed',
                'stat_total_land_discovered',
            ]);
        });
    }
}
