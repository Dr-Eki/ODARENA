<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AmendContributionToBeManaAndFoodInTick extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(Schema::hasColumn('dominion_tick','resource_lumber_contribution'))
        {
            Schema::table('dominion_tick', function (Blueprint $table) {
                $table->dropColumn('resource_lumber_contribution');
            });
        }
        if(Schema::hasColumn('dominion_tick','resource_ore_contribution'))
        {
            Schema::table('dominion_tick', function (Blueprint $table) {
                $table->dropColumn('resource_ore_contribution');
            });
        }
        if(Schema::hasColumn('dominion_tick','resource_lumber_contributed'))
        {
            Schema::table('dominion_tick', function (Blueprint $table) {
                $table->dropColumn('resource_lumber_contributed');
            });
        }
        if(Schema::hasColumn('dominion_tick','resource_ore_contributed'))
        {
            Schema::table('dominion_tick', function (Blueprint $table) {
                $table->dropColumn('resource_ore_contributed');
            });
        }

        if(!Schema::hasColumn('dominion_tick','resource_mana_contributed'))
        {
            Schema::table('dominion_tick', function (Blueprint $table) {
                $table->integer('resource_mana_contributed')->default(0)->after('resource_food_contribution');
            });
        }

        if(!Schema::hasColumn('dominion_tick','resource_mana_contribution'))
        {
            Schema::table('dominion_tick', function (Blueprint $table) {
                $table->integer('resource_mana_contribution')->default(0)->after('resource_food_contribution');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if(!Schema::hasColumn('dominion_tick','resource_lumber_contribution'))
        {
            Schema::table('dominion_tick', function (Blueprint $table) {
                $table->integer('resource_lumber_contribution')->default(0)->after('resource_food_contribution');
            });
        }
        if(!Schema::hasColumn('dominion_tick','resource_ore_contribution'))
        {
            Schema::table('dominion_tick', function (Blueprint $table) {
                $table->integer('resource_ore_contribution')->default(0)->after('resource_food_contribution');
            });
        }
        if(!Schema::hasColumn('dominion_tick','resource_lumber_contributed'))
        {
            Schema::table('dominion_tick', function (Blueprint $table) {
                $table->integer('resource_lumber_contributed')->default(0)->after('resource_food_contribution');
            });
        }
        if(!Schema::hasColumn('dominion_tick','resource_ore_contributed'))
        {
            Schema::table('dominion_tick', function (Blueprint $table) {
                $table->integer('resource_ore_contributed')->default(0)->after('resource_food_contribution');
            });
        }

        if(Schema::hasColumn('dominion_tick','resource_mana_contributed'))
        {
            Schema::table('dominion_tick', function (Blueprint $table) {
                $table->dropColumn('resource_mana_contributed');
            });
        }

        if(Schema::hasColumn('dominion_tick','resource_mana_contribution'))
        {
            Schema::table('dominion_tick', function (Blueprint $table) {
                $table->dropColumn('resource_mana_contribution');
            });
        }
    }

}
