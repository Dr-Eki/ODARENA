<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDominionTickStates extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dominion_tick_states', function (Blueprint $table) {
            $table->id();
            
            $table->integer('dominion_id')->unsigned();
            $table->integer('dominion_protection_tick');

            $table->integer('daily_land')->unsigned();
            $table->integer('daily_gold')->unsigned();
            $table->integer('monarchy_vote_for_dominion_id')->unsigned();
            $table->integer('tick_voted')->unsigned();
            $table->text('most_recent_improvement_resource');
            $table->text('most_recent_exchange_from');
            $table->text('most_recent_exchange_to');
            $table->text('notes');
            $table->text('deity');
            $table->integer('devotion_ticks')->unsigned();
            $table->integer('draft_rate')->unsigned();
            $table->integer('morale')->unsigned();
            $table->integer('peasants')->unsigned();
            $table->integer('peasants_last_hour');
            $table->decimal('prestige', 16, 8);
            $table->integer('xp')->unsigned();
            $table->integer('spy_strength')->unsigned();
            $table->integer('wizard_strength')->unsigned();
            $table->integer('protection_ticks')->unsigned();

            $table->text('buildings')->nullable();
            $table->text('cooldown')->nullable();
            $table->text('improvements')->nullable();
            $table->text('land')->nullable();
            $table->text('resources')->nullable();
            $table->text('spells')->nullable();
            $table->text('advancements')->nullable();
            $table->text('units')->nullable();
            $table->text('queues')->nullable();

            $table->timestamps();

            $table->foreign('dominion_id')->references('id')->on('dominions');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dominion_tick_states');
    }
}
