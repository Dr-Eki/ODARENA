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
        Schema::create('trade_ledger', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('round_id');
            $table->unsignedInteger('dominion_id');
            $table->unsignedBigInteger('hold_id');
            $table->unsignedInteger('tick');
            $table->unsignedInteger('return_tick');
            $table->unsignedInteger('return_ticks');
            $table->unsignedInteger('source_resource_id');
            $table->unsignedInteger('target_resource_id');
            $table->unsignedInteger('source_amount');
            $table->unsignedInteger('target_amount');
            $table->integer('trade_dominion_sentiment');

            $table->foreign('round_id')->references('id')->on('rounds');
            $table->foreign('dominion_id')->references('id')->on('dominions');
            $table->foreign('hold_id')->references('id')->on('holds');
            $table->foreign('source_resource_id')->references('id')->on('resources');
            $table->foreign('target_resource_id')->references('id')->on('resources');

            $table->index(['round_id', 'dominion_id', 'hold_id', 'source_resource_id', 'target_resource_id', 'tick'], 'trade_ledger_index');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trade_ledger');
    }
};
