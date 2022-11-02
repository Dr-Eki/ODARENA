<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTechsToQuickstarts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('quickstarts', function (Blueprint $table) {
            $table->text('techs')->nullable()->after('decree_states');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('quickstarts', function (Blueprint $table) {
            $table->dropColumn([
                'techs',
            ]);
        });
    }
}
