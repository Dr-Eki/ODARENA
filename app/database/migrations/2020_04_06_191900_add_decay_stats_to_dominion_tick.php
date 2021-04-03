<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDecayStatsToDominionTick extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('dominion_tick', static function (Blueprint $table) {

            # Platinum
            $table->unsignedInteger('resource_food_consumption')->after('improvement_tissue')->default(0);
            $table->unsignedInteger('resource_food_decay')->after('resource_food_consumption')->default(0);
            $table->unsignedInteger('resource_lumber_rot')->after('resource_food_decay')->default(0);
            $table->unsignedInteger('resource_mana_drain')->after('resource_lumber_rot')->default(0);

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
              'resource_food_consumption',
              'resource_food_decay',
              'resource_lumber_rot',
              'resource_mana_drain',
            ]);
        });
    }
}
