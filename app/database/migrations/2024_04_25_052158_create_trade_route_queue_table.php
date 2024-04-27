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
        Schema::create('trade_route_queue', function (Blueprint $table) {
            $table->id();
    
            $table->unsignedBigInteger('trade_route_id');
            $table->unsignedInteger('resource_id');
            $table->unsignedInteger('amount');
            $table->json('units');
            $table->unsignedInteger('tick')->default(12);
            $table->string('type')->default('export');
            $table->unsignedInteger('status')->default(1);
    
            $table->foreign('trade_route_id')->references('id')->on('trade_routes');
            $table->foreign('resource_id')->references('id')->on('resources');
    
            $table->unique(['trade_route_id', 'resource_id', 'tick', 'type', 'status'], 'trade_route_queue_unique');
    
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trade_route_queue');
    }
};
