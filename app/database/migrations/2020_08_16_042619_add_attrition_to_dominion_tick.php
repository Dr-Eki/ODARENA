<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAttritionToDominionTick extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dominion_tick', function (Blueprint $table) {
            $table->integer('attrition_unit1')->default(0);
            $table->integer('attrition_unit2')->default(0);
            $table->integer('attrition_unit3')->default(0);
            $table->integer('attrition_unit4')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dominion_tick', function (Blueprint $table) {
            $table->dropColumn('attrition_unit1');
            $table->dropColumn('attrition_unit2');
            $table->dropColumn('attrition_unit3');
            $table->dropColumn('attrition_unit4');
        });
    }
}
