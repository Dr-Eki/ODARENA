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
        Schema::create('hold_sentiment_events', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('hold_id');
            $table->foreign('hold_id')->references('id')->on('holds');

            $table->morphs('target');

            $table->integer('sentiment');
            $table->text('description')->nullable();            

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hold_sentiment_events');
    }
};
