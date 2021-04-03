<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSpellsReflectedAndSpiesKilledStatToDominions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dominions', function (Blueprint $table) {
            $table->unsignedInteger('stat_spells_reflected')->default(0)->after('stat_spell_success');
            $table->unsignedInteger('stat_total_spies_killed')->default(0)->after('stat_spy_prestige');
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
            $table->dropColumn([
                'stat_spells_reflected',
                'stat_total_spies_killed  ',
            ]);
        });
    }
}
