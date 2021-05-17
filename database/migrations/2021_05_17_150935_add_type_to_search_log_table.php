<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTypeToSearchLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('search_logs', function (Blueprint $table) {
            //
            if (!Schema::hasColumn('search_logs', 'type')) {
                $table->string('type')->nullable()->comment("搜索类型:电影|题库|题目");
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('search_log', function (Blueprint $table) {
            //
        });
    }
}
