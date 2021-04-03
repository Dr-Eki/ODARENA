<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBloodToUnits extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('units', static function (Blueprint $table) {
            $table->unsignedInteger('cost_blood')->after('cost_soul')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('units', static function (Blueprint $table) {
            $table->dropColumn([
              'cost_blood',
            ]);
        });
    }
}
