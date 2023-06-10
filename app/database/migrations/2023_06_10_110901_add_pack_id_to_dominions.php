<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPackIdToDominions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dominions', function (Blueprint $table) {
            # Add Pack ID
            $table->bigInteger('pack_id')->unsigned()->nullable()->after('realm_id');
            $table->foreign('pack_id')->references('id')->on('packs')->onDelete('cascade');
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
            # Drop Pack ID
            $table->dropForeign('dominions_pack_id_foreign');
            $table->dropColumn('pack_id');
        });
    }
}
