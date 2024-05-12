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
        Schema::table('trade_ledger', function (Blueprint $table) {
            $table->json('trade_result_data')->nullable()->after('trade_dominion_sentiment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trade_ledger', function (Blueprint $table) {
            $table->dropColumn('trade_result_data');
        });
    }
};
