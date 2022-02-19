<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveStatsAndImprovementsFromDominions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dominions', function (Blueprint $table) {
          $table->dropColumn([
              'improvement_markets',
              'improvement_keep',
              'improvement_towers',
              'improvement_spires',
              'improvement_forges',
              'improvement_walls',
              'improvement_harbor',
              'improvement_armory',
              'improvement_infirmary',
              'improvement_workshops',
              'improvement_observatory',
              'improvement_cartography',
              'improvement_hideouts',
              'improvement_forestry',
              'improvement_refinery',
              'improvement_granaries',
              'improvement_tissue',

              'stat_attacking_success',
              'stat_attacking_razes',
              'stat_attacking_failures',
              'stat_attacking_bottomfeeds',
              'stat_defending_success',
              'stat_defending_failures',
              'stat_espionage_success',
              'stat_spell_success',
              'stat_spells_reflected',
              'stat_total_gold_production',
              'stat_total_food_production',
              'stat_total_lumber_production',
              'stat_total_mana_production',
              'stat_total_ore_production',
              'stat_total_gem_production',
              'stat_total_tech_production',
              'stat_total_boat_production',
              'stat_total_land_explored',
              'stat_total_land_conquered',
              'stat_total_land_discovered',
              'stat_total_gold_stolen',
              'stat_total_food_stolen',
              'stat_total_lumber_stolen',
              'stat_total_mana_stolen',
              'stat_total_ore_stolen',
              'stat_total_gems_stolen',
              'stat_total_gems_spent_building',
              'stat_total_gems_spent_rezoning',
              'stat_spy_prestige',
              'stat_total_spies_killed',
              'stat_wizard_prestige',
              'stat_assassinate_draftees_damage',
              'stat_assassinate_wizards_damage',
              'stat_magic_snare_damage',
              'stat_sabotage_boats_damage',
              'stat_disband_spies_damage',
              'stat_damage_from_fireball',
              'stat_lightning_bolt_damage',
              'stat_earthquake_hours',
              'stat_great_flood_hours',
              'stat_insect_swarm_hours',
              'stat_plague_hours',
              'stat_total_wild_yeti_production',
              'stat_total_land_lost',
              'stat_total_gold_spent_training',
              'stat_total_gold_spent_building',
              'stat_total_gold_spent_rezoning',
              'stat_total_gold_spent_exploring',
              'stat_total_gold_spent_improving',
              'stat_total_gold_plundered',
              'stat_total_gold_sold',
              'stat_total_gold_bought',
              'stat_total_food_spent_training',
              'stat_total_food_spent_building',
              'stat_total_food_spent_rezoning',
              'stat_total_food_spent_exploring',
              'stat_total_food_spent_improving',
              'stat_total_food_plundered',
              'stat_total_food_sold',
              'stat_total_food_bought',
              'stat_total_lumber_spent_training',
              'stat_total_lumber_spent_building',
              'stat_total_lumber_spent_rezoning',
              'stat_total_lumber_spent_exploring',
              'stat_total_lumber_spent_improving',
              'stat_total_lumber_salvaged',
              'stat_total_lumber_plundered',
              'stat_total_lumber_sold',
              'stat_total_lumber_bought',
              'stat_total_mana_spent_training',
              'stat_total_mana_spent_building',
              'stat_total_mana_spent_rezoning',
              'stat_total_mana_spent_exploring',
              'stat_total_mana_spent_improving',
              'stat_total_mana_plundered',
              'stat_total_ore_spent_training',
              'stat_total_ore_spent_building',
              'stat_total_ore_spent_rezoning',
              'stat_total_ore_spent_exploring',
              'stat_total_ore_spent_improving',
              'stat_total_ore_salvaged',
              'stat_total_ore_plundered',
              'stat_total_ore_sold',
              'stat_total_ore_bought',
              'stat_total_gem_spent_training',
              'stat_total_gem_spent_building',
              'stat_total_gem_spent_rezoning',
              'stat_total_gem_spent_exploring',
              'stat_total_gem_spent_improving',
              'stat_total_gem_salvaged',
              'stat_total_gems_plundered',
              'stat_total_gems_sold',
              'stat_total_unit1_spent_training',
              'stat_total_unit2_spent_training',
              'stat_total_unit3_spent_training',
              'stat_total_unit4_spent_training',
              'stat_total_spies_spent_training',
              'stat_total_wizards_spent_training',
              'stat_total_archmages_spent_training',
              'stat_total_soul_spent_training',
              'stat_total_soul_spent_improving',
              'stat_total_soul_destroyed',
              'stat_total_wild_yeti_spent_training',
              'stat_total_champion_spent_training',
              'stat_total_blood_spent_training',
              'stat_total_peasant_spent_training',
              'stat_total_food_decayed',
              'stat_total_food_consumed',
              'stat_total_lumber_rotted',
              'stat_total_mana_drained',
              'stat_total_mana_cast',
              'stat_total_unit1_lost',
              'stat_total_unit2_lost',
              'stat_total_unit3_lost',
              'stat_total_unit4_lost',
              'stat_total_spies_trained',
              'stat_total_wizards_trained',
              'stat_total_archmages_trained',
              'stat_total_unit1_trained',
              'stat_total_unit2_trained',
              'stat_total_unit3_trained',
              'stat_total_unit4_trained',
              'stat_total_spies_lost',
              'stat_total_wizards_lost',
              'stat_total_archmages_lost',
              'stat_total_units_killed',
              'stat_total_units_converted',
              'stat_total_peasants_abducted',
              'stat_total_draftees_abducted',
          ]);
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
            $table->integer('improvement_markets')->unsigned()->default(0);
            $table->integer('improvement_keep')->unsigned()->default(0);
            $table->integer('improvement_towers')->unsigned()->default(0);
            $table->integer('improvement_spires')->unsigned()->default(0);
            $table->integer('improvement_forges')->unsigned()->default(0);
            $table->integer('improvement_walls')->unsigned()->default(0);
            $table->integer('improvement_harbor')->unsigned()->default(0);
            $table->integer('improvement_armory')->unsigned()->default(0);
            $table->integer('improvement_infirmary')->unsigned()->default(0);
            $table->integer('improvement_workshops')->unsigned()->default(0);
            $table->integer('improvement_observatory')->unsigned()->default(0);
            $table->integer('improvement_cartography')->unsigned()->default(0);
            $table->integer('improvement_hideouts')->unsigned()->default(0);
            $table->integer('improvement_forestry')->unsigned()->default(0);
            $table->integer('improvement_refinery')->unsigned()->default(0);
            $table->integer('improvement_granaries')->unsigned()->default(0);
            $table->integer('improvement_tissue')->unsigned()->default(0);

            $table->integer('stat_attacking_success')->unsigned()->default(0);
            $table->integer('stat_attacking_razes')->unsigned()->default(0);
            $table->integer('stat_attacking_failures')->unsigned()->default(0);
            $table->integer('stat_attacking_bottomfeeds')->unsigned()->default(0);
            $table->integer('stat_defending_success')->unsigned()->default(0);
            $table->integer('stat_defending_failures')->unsigned()->default(0);
            $table->integer('stat_espionage_success')->unsigned()->default(0);
            $table->integer('stat_spell_success')->unsigned()->default(0);
            $table->integer('stat_spells_reflected')->unsigned()->default(0);
            $table->integer('stat_total_gold_production')->unsigned()->default(0);
            $table->integer('stat_total_food_production')->unsigned()->default(0);
            $table->integer('stat_total_lumber_production')->unsigned()->default(0);
            $table->integer('stat_total_mana_production')->unsigned()->default(0);
            $table->integer('stat_total_ore_production')->unsigned()->default(0);
            $table->integer('stat_total_gem_production')->unsigned()->default(0);
            $table->integer('stat_total_tech_production')->unsigned()->default(0);
            $table->integer('stat_total_boat_production')->unsigned()->default(0);
            $table->integer('stat_total_land_explored')->unsigned()->default(0);
            $table->integer('stat_total_land_conquered')->unsigned()->default(0);
            $table->integer('stat_total_land_discovered')->unsigned()->default(0);
            $table->integer('stat_total_gold_stolen')->unsigned()->default(0);
            $table->integer('stat_total_food_stolen')->unsigned()->default(0);
            $table->integer('stat_total_lumber_stolen')->unsigned()->default(0);
            $table->integer('stat_total_mana_stolen')->unsigned()->default(0);
            $table->integer('stat_total_ore_stolen')->unsigned()->default(0);
            $table->integer('stat_total_gems_stolen')->unsigned()->default(0);
            $table->integer('stat_total_gems_spent_building')->unsigned()->default(0);
            $table->integer('stat_total_gems_spent_rezoning')->unsigned()->default(0);
            $table->integer('stat_spy_prestige')->unsigned()->default(0);
            $table->integer('stat_total_spies_killed')->unsigned()->default(0);
            $table->integer('stat_wizard_prestige')->unsigned()->default(0);
            $table->integer('stat_assassinate_draftees_damage')->unsigned()->default(0);
            $table->integer('stat_assassinate_wizards_damage')->unsigned()->default(0);
            $table->integer('stat_magic_snare_damage')->unsigned()->default(0);
            $table->integer('stat_sabotage_boats_damage')->unsigned()->default(0);
            $table->integer('stat_disband_spies_damage')->unsigned()->default(0);
            $table->integer('stat_damage_from_fireball')->unsigned()->default(0);
            $table->integer('stat_lightning_bolt_damage')->unsigned()->default(0);
            $table->integer('stat_earthquake_hours')->unsigned()->default(0);
            $table->integer('stat_great_flood_hours')->unsigned()->default(0);
            $table->integer('stat_insect_swarm_hours')->unsigned()->default(0);
            $table->integer('stat_plague_hours')->unsigned()->default(0);
            $table->integer('stat_total_wild_yeti_production')->unsigned()->default(0);
            $table->integer('stat_total_land_lost')->unsigned()->default(0);
            $table->integer('stat_total_gold_spent_training')->unsigned()->default(0);
            $table->integer('stat_total_gold_spent_building')->unsigned()->default(0);
            $table->integer('stat_total_gold_spent_rezoning')->unsigned()->default(0);
            $table->integer('stat_total_gold_spent_exploring')->unsigned()->default(0);
            $table->integer('stat_total_gold_spent_improving')->unsigned()->default(0);
            $table->integer('stat_total_gold_plundered')->unsigned()->default(0);
            $table->integer('stat_total_gold_sold')->unsigned()->default(0);
            $table->integer('stat_total_gold_bought')->unsigned()->default(0);
            $table->integer('stat_total_food_spent_training')->unsigned()->default(0);
            $table->integer('stat_total_food_spent_building')->unsigned()->default(0);
            $table->integer('stat_total_food_spent_rezoning')->unsigned()->default(0);
            $table->integer('stat_total_food_spent_exploring')->unsigned()->default(0);
            $table->integer('stat_total_food_spent_improving')->unsigned()->default(0);
            $table->integer('stat_total_food_plundered')->unsigned()->default(0);
            $table->integer('stat_total_food_sold')->unsigned()->default(0);
            $table->integer('stat_total_food_bought')->unsigned()->default(0);
            $table->integer('stat_total_lumber_spent_training')->unsigned()->default(0);
            $table->integer('stat_total_lumber_spent_building')->unsigned()->default(0);
            $table->integer('stat_total_lumber_spent_rezoning')->unsigned()->default(0);
            $table->integer('stat_total_lumber_spent_exploring')->unsigned()->default(0);
            $table->integer('stat_total_lumber_spent_improving')->unsigned()->default(0);
            $table->integer('stat_total_lumber_salvaged')->unsigned()->default(0);
            $table->integer('stat_total_lumber_plundered')->unsigned()->default(0);
            $table->integer('stat_total_lumber_sold')->unsigned()->default(0);
            $table->integer('stat_total_lumber_bought')->unsigned()->default(0);
            $table->integer('stat_total_mana_spent_training')->unsigned()->default(0);
            $table->integer('stat_total_mana_spent_building')->unsigned()->default(0);
            $table->integer('stat_total_mana_spent_rezoning')->unsigned()->default(0);
            $table->integer('stat_total_mana_spent_exploring')->unsigned()->default(0);
            $table->integer('stat_total_mana_spent_improving')->unsigned()->default(0);
            $table->integer('stat_total_mana_plundered')->unsigned()->default(0);
            $table->integer('stat_total_ore_spent_training')->unsigned()->default(0);
            $table->integer('stat_total_ore_spent_building')->unsigned()->default(0);
            $table->integer('stat_total_ore_spent_rezoning')->unsigned()->default(0);
            $table->integer('stat_total_ore_spent_exploring')->unsigned()->default(0);
            $table->integer('stat_total_ore_spent_improving')->unsigned()->default(0);
            $table->integer('stat_total_ore_salvaged')->unsigned()->default(0);
            $table->integer('stat_total_ore_plundered')->unsigned()->default(0);
            $table->integer('stat_total_ore_sold')->unsigned()->default(0);
            $table->integer('stat_total_ore_bought')->unsigned()->default(0);
            $table->integer('stat_total_gem_spent_training')->unsigned()->default(0);
            $table->integer('stat_total_gem_spent_building')->unsigned()->default(0);
            $table->integer('stat_total_gem_spent_rezoning')->unsigned()->default(0);
            $table->integer('stat_total_gem_spent_exploring')->unsigned()->default(0);
            $table->integer('stat_total_gem_spent_improving')->unsigned()->default(0);
            $table->integer('stat_total_gem_salvaged')->unsigned()->default(0);
            $table->integer('stat_total_gems_plundered')->unsigned()->default(0);
            $table->integer('stat_total_gems_sold')->unsigned()->default(0);
            $table->integer('stat_total_unit1_spent_training')->unsigned()->default(0);
            $table->integer('stat_total_unit2_spent_training')->unsigned()->default(0);
            $table->integer('stat_total_unit3_spent_training')->unsigned()->default(0);
            $table->integer('stat_total_unit4_spent_training')->unsigned()->default(0);
            $table->integer('stat_total_spies_spent_training')->unsigned()->default(0);
            $table->integer('stat_total_wizards_spent_training')->unsigned()->default(0);
            $table->integer('stat_total_archmages_spent_training')->unsigned()->default(0);
            $table->integer('stat_total_soul_spent_training')->unsigned()->default(0);
            $table->integer('stat_total_soul_spent_improving')->unsigned()->default(0);
            $table->integer('stat_total_soul_destroyed')->unsigned()->default(0);
            $table->integer('stat_total_wild_yeti_spent_training')->unsigned()->default(0);
            $table->integer('stat_total_champion_spent_training')->unsigned()->default(0);
            $table->integer('stat_total_blood_spent_training')->unsigned()->default(0);
            $table->integer('stat_total_peasant_spent_training')->unsigned()->default(0);
            $table->integer('stat_total_food_decayed')->unsigned()->default(0);
            $table->integer('stat_total_food_consumed')->unsigned()->default(0);
            $table->integer('stat_total_lumber_rotted')->unsigned()->default(0);
            $table->integer('stat_total_mana_drained')->unsigned()->default(0);
            $table->integer('stat_total_mana_cast')->unsigned()->default(0);
            $table->integer('stat_total_unit1_lost')->unsigned()->default(0);
            $table->integer('stat_total_unit2_lost')->unsigned()->default(0);
            $table->integer('stat_total_unit3_lost')->unsigned()->default(0);
            $table->integer('stat_total_unit4_lost')->unsigned()->default(0);
            $table->integer('stat_total_spies_trained')->unsigned()->default(0);
            $table->integer('stat_total_wizards_trained')->unsigned()->default(0);
            $table->integer('stat_total_archmages_trained')->unsigned()->default(0);
            $table->integer('stat_total_unit1_trained')->unsigned()->default(0);
            $table->integer('stat_total_unit2_trained')->unsigned()->default(0);
            $table->integer('stat_total_unit3_trained')->unsigned()->default(0);
            $table->integer('stat_total_unit4_trained')->unsigned()->default(0);
            $table->integer('stat_total_spies_lost')->unsigned()->default(0);
            $table->integer('stat_total_wizards_lost')->unsigned()->default(0);
            $table->integer('stat_total_archmages_lost')->unsigned()->default(0);
            $table->integer('stat_total_units_killed')->unsigned()->default(0);
            $table->integer('stat_total_units_converted')->unsigned()->default(0);
            $table->integer('stat_total_peasants_abducted')->unsigned()->default(0);
            $table->integer('stat_total_draftees_abducted')->unsigned()->default(0);
        });
    }
}
