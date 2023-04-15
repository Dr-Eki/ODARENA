<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddImageToGameEventStories extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('game_event_stories', function (Blueprint $table) {
            $table->longText('image')->nullable()->after('story');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('game_event_stories', function (Blueprint $table) {
            $table->dropColumn('image');
        });
    }
}
