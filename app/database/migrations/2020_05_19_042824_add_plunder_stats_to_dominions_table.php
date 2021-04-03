<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPlunderStatsToDominionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dominions', function (Blueprint $table) {
            $table->unsignedInteger('stat_total_ore_plundered')->after('stat_total_ore_salvaged')->default(0);
            $table->unsignedInteger('stat_total_lumber_plundered')->after('stat_total_lumber_salvaged')->default(0);
            $table->unsignedInteger('stat_total_gems_plundered')->after('stat_total_gem_salvaged')->default(0);
            $table->unsignedInteger('stat_total_food_plundered')->after('stat_total_food_spent_improving')->default(0);
            $table->unsignedInteger('stat_total_mana_plundered')->after('stat_total_mana_spent_improving')->default(0);
            $table->unsignedInteger('stat_total_platinum_plundered')->after('stat_total_platinum_spent_improving')->default(0);
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
              'stat_total_ore_plundered',
              'stat_total_lumber_plundered',
              'stat_total_gems_plundered',
              'stat_total_food_plundered',
              'stat_total_mana_plundered',
              'stat_total_platinum_plundered',
          ]);
        });
    }
}
