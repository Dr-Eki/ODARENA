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
        Schema::create('hold_units', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('hold_id');
            $table->unsignedInteger('unit_id');
            $table->unsignedTinyInteger('state')->default(0);
            $table->unsignedInteger('amount')->default(0);

            $table->foreign('hold_id')->references('id')->on('holds');
            $table->foreign('unit_id')->references('id')->on('units');

            $table->unique(['hold_id', 'unit_id', 'state'], 'hold_units_unique');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hold_units');
    }
};
