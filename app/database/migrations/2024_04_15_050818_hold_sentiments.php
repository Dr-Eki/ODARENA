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
        Schema::create('hold_sentiments', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('hold_id');
            $table->morphs('target'); // This will create `target_id` and `target_type`

            $table->integer('sentiment')->default(0); // Default sentiment value set to 0

            $table->foreign('hold_id')->references('id')->on('holds')
                ->onDelete('cascade'); // Ensure foreign key constraint with cascade delete

            $table->unique(['hold_id', 'target_type', 'target_id'], 'hold_sentiment_unique');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hold_sentiments');
    }
};
