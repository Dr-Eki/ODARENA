<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBloodToDominions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('dominions', static function (Blueprint $table) {
            $table->unsignedInteger('resource_blood')->after('resource_soul')->default(0);
            $table->unsignedInteger('stat_total_blood_spent_training')->after('stat_total_champion_spent_training')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('dominions', static function (Blueprint $table) {
            $table->dropColumn([
              'resource_blood',
              'stat_total_blood_spent_training'
            ]);
        });
    }
}
