<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMaxPowerToRealmArtefacts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('realm_artefacts', function (Blueprint $table) {
            $table->integer('max_power')->unsigned()->default(0)->after('power');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('realm_artefacts', function (Blueprint $table) {
            $table->dropColumn([
                'max_power',
            ]);
        });
    }
}
