<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPeasantsAndDrafteesAliasToRaces extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('races', function (Blueprint $table) {
            $table->string('peasants_alias')->after('home_land_type')->nullable()->default(NULL);
            $table->string('draftees_alias')->after('peasants_alias')->nullable()->default(NULL);
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
              'peasants_alias',
              'draftees_alias'
          ]);
        });
    }
}
