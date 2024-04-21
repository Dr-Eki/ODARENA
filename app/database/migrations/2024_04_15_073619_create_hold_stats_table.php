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
        Schema::create('hold_stats', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('hold_id');
            $table->unsignedInteger('stat_id');
            $table->unsignedInteger('value');

            $table->foreign('hold_id')->references('id')->on('holds');
            $table->foreign('stat_id')->references('id')->on('stats');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hold_stats');
    }
};
