<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RenamePlatinumToGold extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dominions', function (Blueprint $table) {
            $table->renameColumn('daily_platinum', 'daily_gold');
            $table->renameColumn('resource_platinum', 'resource_gold');
            $table->renameColumn('stat_total_platinum_production', 'stat_total_gold_production');
            $table->renameColumn('stat_total_platinum_stolen', 'stat_total_gold_stolen');
            $table->renameColumn('stat_total_platinum_spent_training', 'stat_total_gold_spent_training');
            $table->renameColumn('stat_total_platinum_spent_building', 'stat_total_gold_spent_building');
            $table->renameColumn('stat_total_platinum_spent_rezoning', 'stat_total_gold_spent_rezoning');
            $table->renameColumn('stat_total_platinum_spent_exploring', 'stat_total_gold_spent_exploring');
            $table->renameColumn('stat_total_platinum_spent_improving', 'stat_total_gold_spent_improving');
            $table->renameColumn('stat_total_platinum_plundered', 'stat_total_gold_plundered');
            $table->renameColumn('stat_total_platinum_sold', 'stat_total_gold_sold');
            $table->renameColumn('stat_total_platinum_bought', 'stat_total_gold_bought');
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
            $table->renameColumn('daily_gold', 'daily_platinum');
            $table->renameColumn('resource_gold', 'resource_platinum');
            $table->renameColumn('stat_total_gold_production', 'stat_total_platinum_production');
            $table->renameColumn('stat_total_gold_stolen', 'stat_total_platinum_stolen');
            $table->renameColumn('stat_total_gold_spent_training', 'stat_total_platinum_spent_training');
            $table->renameColumn('stat_total_gold_spent_building', 'stat_total_platinum_spent_building');
            $table->renameColumn('stat_total_gold_spent_rezoning', 'stat_total_platinum_spent_rezoning');
            $table->renameColumn('stat_total_gold_spent_exploring', 'stat_total_platinum_spent_exploring');
            $table->renameColumn('stat_total_gold_spent_improving', 'stat_total_platinum_spent_improving');
            $table->renameColumn('stat_total_gold_plundered', 'stat_total_platinum_plundered');
            $table->renameColumn('stat_total_gold_sold', 'stat_total_platinum_sold');
            $table->renameColumn('stat_total_gold_bought', 'stat_total_platinum_bought');
        });
    }
}
