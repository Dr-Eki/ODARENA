<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('dominion_queue', function (Blueprint $table) {
            if (Schema::hasColumn('dominion_queue', 'target_id')) {
                #$table->dropForeign(['target_id']);
                $table->dropColumn('target_id');
            }

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dominion_queue', function (Blueprint $table) {
            $table->unsignedInteger('target_id')->nullable()->after('dominion_id');
        });
    }
};
