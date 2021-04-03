<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSpiesAndWizardsTrainedAndLostToDominions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dominions', function (Blueprint $table) {
            $table->unsignedInteger('stat_total_spies_trained')->after('stat_total_unit4_lost')->default(0);
            $table->unsignedInteger('stat_total_wizards_trained')->after('stat_total_spies_trained')->default(0);
            $table->unsignedInteger('stat_total_archmages_trained')->after('stat_total_wizards_trained')->default(0);


            $table->unsignedInteger('stat_total_spies_lost')->after('stat_total_unit4_trained')->default(0);
            $table->unsignedInteger('stat_total_wizards_lost')->after('stat_total_spies_lost')->default(0);
            $table->unsignedInteger('stat_total_archmages_lost')->after('stat_total_wizards_lost')->default(0);
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
            'stat_total_spies_trained',
            'stat_total_wizards_trained',
            'stat_total_archmages_trained',
            'stat_total_spies_lost',
            'stat_total_wizards_lost',
            'stat_total_archmages_lost',
        ]);
      });
    }
}
