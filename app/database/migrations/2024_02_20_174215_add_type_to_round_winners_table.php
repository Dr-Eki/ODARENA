<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTypeToRoundWinnersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('round_winners', function (Blueprint $table) {
            $table->text('type')->after('data')->default('win');       
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('round_winners', function (Blueprint $table) {
            $table->dropColumn([
                'type',
            ]);
        });
    }
}
