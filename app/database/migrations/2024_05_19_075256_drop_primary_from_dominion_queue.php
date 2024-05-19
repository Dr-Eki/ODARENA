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
        Schema::table('dominion_queue', function (Blueprint $table) {
            // Drop the foreign key constraint on dominion_id
            $table->dropForeign(['dominion_id']);
    
            // Drop the existing primary key
            $table->dropPrimary();
    
            // Add the new id column
            $table->bigIncrements('id')->first();
    
            // Optionally, you can add a composite unique index on the previous primary key columns if needed
            $table->unique(['dominion_id', 'source', 'resource', 'hours']);
    
            // Recreate the foreign key constraint on dominion_id
            $table->foreign('dominion_id')->references('id')->on('dominions'); // Adjust 'dominions' to the correct table name
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dominion_queue', function (Blueprint $table) {
            // Drop the new id column
            $table->dropColumn('id');

            // Drop the unique constraint
            $table->dropUnique(['dominion_id', 'source', 'resource', 'hours']);

            // Re-add the previous primary key
            $table->primary(['dominion_id', 'source', 'resource', 'hours']);

            // Recreate the foreign key constraint on dominion_id
            $table->foreign('dominion_id')->references('id')->on('dominion_table'); // Adjust 'dominion_table' to the correct table name
        });
    }
};
