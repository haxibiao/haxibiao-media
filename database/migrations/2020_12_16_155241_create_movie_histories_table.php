<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMovieHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('movie_histories', function (Blueprint $table) {
            $table->increments('id');
            $table->string('progress')->comment('观看进度')->nullable();
            $table->string('last_watch_time')->comment('最后观看时间')->nullable();
            $table->integer('movie_id')->index()->comment('电影');
            $table->integer('user_id')->index()->comment('用户');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('movie_histories');
    }
}
