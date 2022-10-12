<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ClearOutSpells extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::disableForeignKeyConstraints();
        DB::table('spell_perks')->truncate();
        DB::table('spell_perk_types')->truncate();
        DB::table('spells')->truncate();
        DB::table('dominion_spells')->truncate();
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('spells', function (Blueprint $table) {
            // Do nothing
        });
    }
}
