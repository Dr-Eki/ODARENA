<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBuildingDestructionToTick extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dominion_tick', function (Blueprint $table) {
            $table->text('buildings_destroyed')->nullable()->after('crypt_bodies_spent');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dominion_tick', function (Blueprint $table) {
            $table->dropColumn([
                'buildings_destroyed',
            ]);
        });
    }
}
