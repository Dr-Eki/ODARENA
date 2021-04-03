<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDecayStatsToDominions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('dominions', static function (Blueprint $table) {

            # Platinum
            $table->unsignedInteger('stat_total_food_decayed')->after('stat_total_champion_spent_training')->default(0);
            $table->unsignedInteger('stat_total_food_consumed')->after('stat_total_food_decayed')->default(0);
            $table->unsignedInteger('stat_total_lumber_rotted')->after('stat_total_food_consumed')->default(0);
            $table->unsignedInteger('stat_total_mana_drained')->after('stat_total_lumber_rotted')->default(0);
            $table->unsignedInteger('stat_total_mana_cast')->after('stat_total_mana_drained')->default(0);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('dominions', static function (Blueprint $table) {
            $table->dropColumn([
              'stat_total_food_decayed',
              'stat_total_food_consumed',
              'stat_total_lumber_rotted',
              'stat_total_mana_drained',
              'stat_total_mana_cast',
            ]);
        });
    }
}
