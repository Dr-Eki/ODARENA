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
        Schema::create('holds', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('round_id');
            $table->unsignedInteger('title_id');
            $table->unsignedInteger('race_id')->default(null)->nullable();

            $table->string('name');
            $table->string('key');
            $table->string('ruler_name');

            $table->text('description');

            $table->unsignedInteger('status');
            $table->unsignedInteger('land');
            $table->unsignedInteger('morale');
            $table->unsignedInteger('peasants');
            $table->unsignedInteger('peasants_last_hour');

            $table->json('desired_resources')->nullable();
            $table->json('sold_resources')->nullable();

            $table->unsignedInteger('tick_discovered')->default(0);
            $table->unsignedInteger('ticks')->default(0);

            $table->timestamps();

            $table->foreign('round_id')->references('id')->on('rounds');
            $table->foreign('title_id')->references('id')->on('titles');
            $table->foreign('race_id')->references('id')->on('races');

            $table->unique(['round_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('holds');
    }
};
