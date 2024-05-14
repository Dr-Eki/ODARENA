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
        Schema::table('dominion_buildings', function (Blueprint $table) {
            $table->renameColumn('owned', 'amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dominion_buildings', function (Blueprint $table) {
            $table->renameColumn('amount', 'owned');
        });
    }
};
