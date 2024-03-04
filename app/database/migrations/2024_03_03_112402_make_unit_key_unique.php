<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeUnitKeyUnique extends Migration
{
    public function up()
    {
        Schema::table('units', function (Blueprint $table) {
            $table->string('key')->unique()->change();
        });
    }

    public function down()
    {
        Schema::table('units', function (Blueprint $table) {
            $table->string('key')->change();
        });
    }
}