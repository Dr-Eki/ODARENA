<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSalvagingStatsToDominionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dominions', function (Blueprint $table) {
            $table->unsignedInteger('stat_total_ore_salvaged')->after('stat_total_ore_spent_improving')->default(0);
            $table->unsignedInteger('stat_total_lumber_salvaged')->after('stat_total_lumber_spent_improving')->default(0);
            $table->unsignedInteger('stat_total_gem_salvaged')->after('stat_total_gem_spent_improving')->default(0);
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
              'stat_total_ore_salvaged',
              'stat_total_lumber_salvaged',
              'stat_total_gem_salvaged',
          ]);
        });
    }
}
