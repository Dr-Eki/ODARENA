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
        Schema::create('hold_queues', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('hold_id');
            $table->string('type');
            $table->morphs('item');
            $table->unsignedInteger('amount');
            $table->unsignedInteger('tick')->default(12);
            $table->unsignedInteger('status')->default(1);

            $table->foreign('hold_id')->references('id')->on('holds');

            $table->unique(['hold_id', 'type', 'item_id', 'item_type', 'tick'], 'hold_queue_unique');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hold_queues');
    }
};
