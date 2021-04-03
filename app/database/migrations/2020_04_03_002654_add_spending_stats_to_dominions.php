<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSpendingStatsToDominions extends Migration
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
            $table->unsignedInteger('stat_total_platinum_spent_training')->after('stat_total_land_lost')->default(0);
            $table->unsignedInteger('stat_total_platinum_spent_building')->after('stat_total_platinum_spent_training')->default(0);
            $table->unsignedInteger('stat_total_platinum_spent_rezoning')->after('stat_total_platinum_spent_building')->default(0);
            $table->unsignedInteger('stat_total_platinum_spent_exploring')->after('stat_total_platinum_spent_rezoning')->default(0);
            $table->unsignedInteger('stat_total_platinum_spent_improving')->after('stat_total_platinum_spent_exploring')->default(0);

            # Food
            $table->unsignedInteger('stat_total_food_spent_training')->after('stat_total_platinum_spent_improving')->default(0);
            $table->unsignedInteger('stat_total_food_spent_building')->after('stat_total_food_spent_training')->default(0);
            $table->unsignedInteger('stat_total_food_spent_rezoning')->after('stat_total_food_spent_building')->default(0);
            $table->unsignedInteger('stat_total_food_spent_exploring')->after('stat_total_food_spent_rezoning')->default(0);
            $table->unsignedInteger('stat_total_food_spent_improving')->after('stat_total_food_spent_exploring')->default(0);

            # Lumber
            $table->unsignedInteger('stat_total_lumber_spent_training')->after('stat_total_food_spent_improving')->default(0);
            $table->unsignedInteger('stat_total_lumber_spent_building')->after('stat_total_lumber_spent_training')->default(0);
            $table->unsignedInteger('stat_total_lumber_spent_rezoning')->after('stat_total_lumber_spent_building')->default(0);
            $table->unsignedInteger('stat_total_lumber_spent_exploring')->after('stat_total_lumber_spent_rezoning')->default(0);
            $table->unsignedInteger('stat_total_lumber_spent_improving')->after('stat_total_lumber_spent_exploring')->default(0);

            # Mana
            $table->unsignedInteger('stat_total_mana_spent_training')->after('stat_total_lumber_spent_improving')->default(0);
            $table->unsignedInteger('stat_total_mana_spent_building')->after('stat_total_mana_spent_training')->default(0);
            $table->unsignedInteger('stat_total_mana_spent_rezoning')->after('stat_total_mana_spent_building')->default(0);
            $table->unsignedInteger('stat_total_mana_spent_exploring')->after('stat_total_mana_spent_rezoning')->default(0);
            $table->unsignedInteger('stat_total_mana_spent_improving')->after('stat_total_mana_spent_exploring')->default(0);

            # Ore
            $table->unsignedInteger('stat_total_ore_spent_training')->after('stat_total_mana_spent_improving')->default(0);
            $table->unsignedInteger('stat_total_ore_spent_building')->after('stat_total_ore_spent_training')->default(0);
            $table->unsignedInteger('stat_total_ore_spent_rezoning')->after('stat_total_ore_spent_building')->default(0);
            $table->unsignedInteger('stat_total_ore_spent_exploring')->after('stat_total_ore_spent_rezoning')->default(0);
            $table->unsignedInteger('stat_total_ore_spent_improving')->after('stat_total_ore_spent_exploring')->default(0);

            # Gem
            $table->unsignedInteger('stat_total_gem_spent_training')->after('stat_total_ore_spent_improving')->default(0);
            $table->unsignedInteger('stat_total_gem_spent_building')->after('stat_total_gem_spent_training')->default(0);
            $table->unsignedInteger('stat_total_gem_spent_rezoning')->after('stat_total_gem_spent_building')->default(0);
            $table->unsignedInteger('stat_total_gem_spent_exploring')->after('stat_total_gem_spent_rezoning')->default(0);
            $table->unsignedInteger('stat_total_gem_spent_improving')->after('stat_total_gem_spent_exploring')->default(0);

            # Units
            $table->unsignedInteger('stat_total_unit1_spent_training')->after('stat_total_gem_spent_improving')->default(0);
            $table->unsignedInteger('stat_total_unit2_spent_training')->after('stat_total_unit1_spent_training')->default(0);
            $table->unsignedInteger('stat_total_unit3_spent_training')->after('stat_total_unit2_spent_training')->default(0);
            $table->unsignedInteger('stat_total_unit4_spent_training')->after('stat_total_unit3_spent_training')->default(0);
            $table->unsignedInteger('stat_total_spies_spent_training')->after('stat_total_unit4_spent_training')->default(0);
            $table->unsignedInteger('stat_total_wizards_spent_training')->after('stat_total_spies_spent_training')->default(0);
            $table->unsignedInteger('stat_total_archmages_spent_training')->after('stat_total_wizards_spent_training')->default(0);

            # Soul
            $table->unsignedInteger('stat_total_soul_spent_training')->after('stat_total_archmages_spent_training')->default(0);

            # Wild Yeti
            $table->unsignedInteger('stat_total_wild_yeti_spent_training')->after('stat_total_soul_spent_training')->default(0);

            # Champion
            $table->unsignedInteger('stat_total_champion_spent_training')->after('stat_total_wild_yeti_spent_training')->default(0);

            # Peasants
            $table->unsignedInteger('stat_total_peasant_spent_training')->after('stat_total_champion_spent_training')->default(0);
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
              'stat_total_platinum_spent_training',
              'stat_total_platinum_spent_building',
              'stat_total_platinum_spent_rezoning',
              'stat_total_platinum_spent_exploring',
              'stat_total_platinum_spent_improving',
              'stat_total_food_spent_training',
              'stat_total_food_spent_building',
              'stat_total_food_spent_rezoning',
              'stat_total_food_spent_exploring',
              'stat_total_food_spent_improving',
              'stat_total_lumber_spent_training',
              'stat_total_lumber_spent_building',
              'stat_total_lumber_spent_rezoning',
              'stat_total_lumber_spent_exploring',
              'stat_total_lumber_spent_improving',
              'stat_total_mana_spent_training',
              'stat_total_mana_spent_building',
              'stat_total_mana_spent_rezoning',
              'stat_total_mana_spent_exploring',
              'stat_total_mana_spent_improving',
              'stat_total_ore_spent_training',
              'stat_total_ore_spent_building',
              'stat_total_ore_spent_rezoning',
              'stat_total_ore_spent_exploring',
              'stat_total_ore_spent_improving',
              'stat_total_gem_spent_training',
              'stat_total_gem_spent_building',
              'stat_total_gem_spent_rezoning',
              'stat_total_gem_spent_exploring',
              'stat_total_gem_spent_improving',
              'stat_total_unit1_spent_training',
              'stat_total_unit2_spent_training',
              'stat_total_unit3_spent_training',
              'stat_total_unit4_spent_training',
              'stat_total_spies_spent_training',
              'stat_total_wizards_spent_training',
              'stat_total_archmages_spent_training',
              'stat_total_wild_yeti_spent_training',
              'stat_total_soul_spent_training',
              'stat_total_champion_spent_training',
            ]);
        });
    }
}
