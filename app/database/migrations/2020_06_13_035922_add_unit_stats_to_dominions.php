<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUnitStatsToDominions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dominions', function (Blueprint $table) {
            $table->unsignedInteger('stat_total_unit1_lost')->after('stat_total_mana_cast')->default(0);
            $table->unsignedInteger('stat_total_unit2_lost')->after('stat_total_unit1_lost')->default(0);
            $table->unsignedInteger('stat_total_unit3_lost')->after('stat_total_unit2_lost')->default(0);
            $table->unsignedInteger('stat_total_unit4_lost')->after('stat_total_unit3_lost')->default(0);

            $table->unsignedInteger('stat_total_unit1_trained')->after('stat_total_unit4_lost')->default(0);
            $table->unsignedInteger('stat_total_unit2_trained')->after('stat_total_unit1_trained')->default(0);
            $table->unsignedInteger('stat_total_unit3_trained')->after('stat_total_unit2_trained')->default(0);
            $table->unsignedInteger('stat_total_unit4_trained')->after('stat_total_unit3_trained')->default(0);

            $table->unsignedInteger('stat_total_units_killed')->after('stat_total_unit4_trained')->default(0);
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
              'stat_total_unit1_lost',
              'stat_total_unit2_lost',
              'stat_total_unit3_lost',
              'stat_total_unit4_lost',

              'stat_total_unit1_trained',
              'stat_total_unit2_trained',
              'stat_total_unit3_trained',
              'stat_total_unit4_trained',

              'stat_total_units_killed',

          ]);
        });
    }
}
