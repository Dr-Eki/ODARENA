<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAbductionStatsToDominions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dominions', function (Blueprint $table) {
            $table->integer('stat_total_peasants_abducted')->default(0)->after('stat_total_units_converted');
            $table->integer('stat_total_draftees_abducted')->default(0)->after('stat_total_peasants_abducted');
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
                'stat_total_peasants_abducted',
                'stat_total_draftees_abducted',
            ]);
        });
    }
}
