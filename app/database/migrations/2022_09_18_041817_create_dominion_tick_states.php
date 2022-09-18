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
        Schema::create('dominion_states', function (Blueprint $table) {
            $table->id();
            
            $table->integer('dominion_id')->unsigned();
            $table->integer('dominion_protection_tick');
            $table->string('type')->default('tick');

            $table->integer('daily_land')->unsigned()->default(0);
            $table->integer('daily_gold')->unsigned()->default(0);
            $table->integer('monarchy_vote_for_dominion_id')->unsigned()->nullable();
            $table->integer('tick_voted')->unsigned()->nullable();
            $table->text('most_recent_improvement_resource')->default('gems');
            $table->text('most_recent_exchange_from')->default('gold');
            $table->text('most_recent_exchange_to')->default('gold');
            $table->text('notes')->nullable();
            $table->text('deity')->nullable();
            $table->integer('devotion_ticks')->unsigned()->default(0);
            $table->integer('draft_rate')->unsigned()->default(50);
            $table->integer('morale')->unsigned()->default(100);
            $table->integer('peasants')->unsigned()->default(0);
            $table->integer('peasants_last_hour')->default(0);
            $table->decimal('prestige', 16, 8)->default(600.0);
            $table->integer('xp')->unsigned()->default(0);
            $table->integer('spy_strength')->unsigned()->default(100);
            $table->integer('wizard_strength')->unsigned()->default(100);
            $table->integer('ticks')->default(0);
            $table->integer('protection_ticks')->unsigned()->default(0);

            $table->text('buildings')->nullable();
            $table->text('cooldown')->nullable();
            $table->text('decree_states')->nullable();
            $table->text('improvements')->nullable();
            $table->text('land')->nullable();
            $table->text('resources')->nullable();
            $table->text('spells')->nullable();
            $table->text('advancements')->nullable();
            $table->text('units')->nullable();
            $table->text('queues')->nullable();

            $table->timestamps();

            $table->foreign('dominion_id')->references('id')->on('dominions');
            $table->unique(['dominion_id', 'dominion_protection_tick']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dominion_states');
    }
}
