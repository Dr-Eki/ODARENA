<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTotalGemsSpentBuildingToDominions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dominions', function (Blueprint $table) {
            $table->integer('stat_total_gems_spent_building')->default(0)->after('stat_total_gems_stolen');
            $table->integer('stat_total_gems_spent_rezoning')->default(0)->after('stat_total_gems_spent_building');
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
              'stat_total_gems_spent_building',
              'stat_total_gems_spent_rezoning'
          ]);
        });
    }
}
