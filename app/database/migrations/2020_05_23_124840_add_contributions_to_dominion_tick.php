<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddContributionsToDominionTick extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dominion_tick', function (Blueprint $table) {
        $table->unsignedInteger('resource_food_contribution')->after('resource_mana_drain')->default(0);
        $table->unsignedInteger('resource_lumber_contribution')->after('resource_food_contribution')->default(0);
        $table->unsignedInteger('resource_ore_contribution')->after('resource_lumber_contribution')->default(0);


        $table->unsignedInteger('resource_food_contributed')->after('resource_ore_contribution')->default(0);
        $table->unsignedInteger('resource_lumber_contributed')->after('resource_food_contributed')->default(0);
        $table->unsignedInteger('resource_ore_contributed')->after('resource_lumber_contributed')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dominion_tick', function (Blueprint $table) {
            $table->dropColumn([
              'resource_food_contribution',
              'resource_lumber_contribution',
              'resource_ore_contribution',
              'resource_food_contributed',
              'resource_lumber_contributed',
              'resource_ore_contributed',
            ]);
        });
    }
}
