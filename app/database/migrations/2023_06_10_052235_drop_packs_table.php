<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropPacksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('dominions')) {
            Schema::table('dominions', function (Blueprint $table) {
                // Attempt to drop the foreign key
                try {
                    $table->dropForeign('dominions_pack_id_foreign');
                } catch (\Exception $e) {
                    // Handle the exception if the foreign key doesn't exist
                    Log::info('Attempted to drop a non-existent foreign key: dominions_pack_id_foreign');
                }
            });
        }
    
        if (Schema::hasTable('packs')) {
            Schema::dropIfExists('packs');
        }
    }
    
    

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('packs', function (Blueprint $table) {
            // Restore if doesn't exist
            Schema::create('packs', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('round_id')->unsigned();
                $table->integer('realm_id')->unsigned();
                $table->integer('creator_dominion_id')->unsigned();
                $table->string('name');
                $table->string('password');
                $table->timestamps();
            });
        });
    }
}
