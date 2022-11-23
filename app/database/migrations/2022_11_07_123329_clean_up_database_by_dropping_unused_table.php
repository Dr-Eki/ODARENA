<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CleanUpDatabaseByDroppingUnusedTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('active_spells');
        Schema::dropIfExists('daily_rankings');
        Schema::dropIfExists('info_ops');
        Schema::dropIfExists('siege_events');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {  
        # Nah.
    }
}
