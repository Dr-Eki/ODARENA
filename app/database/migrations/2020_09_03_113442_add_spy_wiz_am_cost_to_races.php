<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSpyWizAmCostToRaces extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('races', function (Blueprint $table) {
            $table->text('spies_cost')->nullable()->after('construction_materials');
            $table->text('wizards_cost')->nullable()->after('spies_cost');
            $table->text('archmages_cost')->nullable()->after('wizards_cost');
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
                'spies_cost',
                'wizards_cost',
                'archmages_cost',
            ]);
        });
    }
}
