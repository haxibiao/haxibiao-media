<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EditSourceUrlVarchar500ToSpiders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('spiders', function (Blueprint $table) {
            //兼容抖音爬虫的source_url偶尔达到355chars
            $table->string('source_url', 500)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('spiders', function (Blueprint $table) {
            //
        });
    }
}
