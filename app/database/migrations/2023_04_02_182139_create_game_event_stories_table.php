<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGameEventStoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_event_stories', function (Blueprint $table) {
            $table->id();

            $table->uuid('game_event_id');
            $table->longText('story');

            $table->timestamps();

            # Foreign keys
            $table->foreign('game_event_id')->references('id')->on('game_events');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('game_event_stories');
    }
}
