<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMostRecentExchangeResourcesDominions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dominions', function (Blueprint $table) {
            $table->string('most_recent_exchange_from')->after('most_recent_improvement_resource')->default('gold');
            $table->string('most_recent_exchange_to')->after('most_recent_exchange_from')->default('gold');
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
              'most_recent_exchange_from',
              'most_recent_exchange_to',
          ]);
        });
    }
}
