<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AbandonDominionChanges extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dominions', function (Blueprint $table)
        {
            $table->unsignedInteger('user_id')->nullable()->change();
            $table->unsignedInteger('former_user_id')->nullable()->default(null)->after('user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dominions', function (Blueprint $table)
        {
            $table->unsignedInteger('user_id')->change();
            $table->dropColumn([
                'former_user_id'
            ]);
        });
    }
}
