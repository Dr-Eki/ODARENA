<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddImprovementResourcesToRaces extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('races', function (Blueprint $table) {
            $table->text('improvement_resources')->nullable()->after('alignment');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('races', function (Blueprint $table) {
            $table->dropColumn([
                'improvement_resources',
            ]);
        });
    }
}