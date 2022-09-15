<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEnabledToDecreeStates extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('decree_states', function (Blueprint $table) {
            $table->unsignedInteger('enabled')->default(1)->after('key');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('decree_states', function (Blueprint $table) {
            $table->dropColumn([
                'enabled',
            ]);
        });
    }
}
