<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUnitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('units', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('race_id')->unsigned();
            $table->integer('slot');
            $table->string('name');
            $table->integer('cost_platinum');
            $table->integer('cost_ore');


            // New unit cost resources
            $table->integer('cost_food');
            $table->integer('cost_mana');
            $table->integer('cost_gem');
            $table->integer('cost_lumber');
            $table->integer('cost_prestige');
            $table->integer('cost_boat');
            $table->integer('cost_champion');
            $table->integer('cost_soul');
            $table->integer('cost_unit1');
            $table->integer('cost_unit2');
            $table->integer('cost_unit3');
            $table->integer('cost_unit4');
            $table->integer('cost_morale');
            $table->integer('cost_wild_yeti');
            $table->integer('cost_spy');
            $table->integer('cost_wizard');
            $table->integer('cost_archmage');

            # Static NW
            $table->integer('static_networth');

            $table->float('power_offense');
            $table->float('power_defense');
            $table->boolean('need_boat')->default(true);
            $table->integer('unit_perk_type_id')->unsigned()->nullable();
            $table->string('unit_perk_type_values')->nullable();
            $table->timestamps();

            $table->foreign('race_id')->references('id')->on('races');

            $table->unique(['race_id', 'slot']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('units');
    }
}
