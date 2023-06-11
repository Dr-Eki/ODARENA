<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusToPacks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('packs', function (Blueprint $table) {
            // Add status (integer) column
            $table->integer('status')->default(0)->after('password');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('packs', function (Blueprint $table) {
            // Drop status column
            $table->dropColumn('status');
        });
    }
}
