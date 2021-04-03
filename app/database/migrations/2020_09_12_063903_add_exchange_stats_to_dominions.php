<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddExchangeStatsToDominions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dominions', function (Blueprint $table) {
            $table->integer('stat_total_platinum_sold')->default(0)->after('stat_total_platinum_plundered');
            $table->integer('stat_total_platinum_bought')->default(0)->after('stat_total_platinum_sold');

            $table->integer('stat_total_food_sold')->default(0)->after('stat_total_food_plundered');
            $table->integer('stat_total_food_bought')->default(0)->after('stat_total_food_sold');

            $table->integer('stat_total_ore_sold')->default(0)->after('stat_total_ore_plundered');
            $table->integer('stat_total_ore_bought')->default(0)->after('stat_total_ore_sold');

            $table->integer('stat_total_lumber_sold')->default(0)->after('stat_total_lumber_plundered');
            $table->integer('stat_total_lumber_bought')->default(0)->after('stat_total_lumber_sold');

            $table->integer('stat_total_gems_sold')->default(0)->after('stat_total_gems_plundered');
            #$table->integer('stat_total_gem_bought')->default(0)->after('stat_total_gem_sold');


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
                'stat_total_platinum_sold',
                'stat_total_platinum_bought',
                'stat_total_food_sold',
                'stat_total_food_bought',
                'stat_total_ore_sold',
                'stat_total_ore_bought',
                'stat_total_lumber_sold',
                'stat_total_lumber_bought',
                'stat_total_gems_sold',
            ]);
        });
    }
}
