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
        Schema::create('hold_prices', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('hold_id');
            $table->unsignedInteger('resource_id');

            $table->unsignedInteger('tick')->default(0);

            $table->string('action');
            $table->decimal('price', 12, 6);#->nullable();
 
            $table->foreign('hold_id')->references('id')->on('holds');
            $table->foreign('resource_id')->references('id')->on('resources');

            $table->unique(['hold_id', 'resource_id', 'action', 'tick']);


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hold_prices');
    }
};
