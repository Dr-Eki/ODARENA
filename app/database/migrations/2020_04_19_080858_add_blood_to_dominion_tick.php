<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBloodToDominionTick extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('dominion_tick', static function (Blueprint $table) {
            $table->unsignedInteger('resource_blood')->after('resource_soul')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('dominion_tick', static function (Blueprint $table) {
            $table->dropColumn([
              'resource_blood',
            ]);
        });
    }
}
