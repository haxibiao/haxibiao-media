<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToMovieHistories extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('movie_histories', function (Blueprint $table) {
            if (!Schema::hasColumn('movie_histories', 'series_name')) {
                $table->string('series_name')->nullable();
            }

            // 修复旧表字段结构
            $table->string('progress')->nullable()->change();
            $table->string('last_watch_time')->nullable()->change();
            $table->unsignedInteger('series_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
