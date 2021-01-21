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
        if (Schema::hasTable('movie_histories')) {
            return;
        }
        Schema::create('movie_histories', function (Blueprint $table) {
            $table->increments('id');
            $table->string('progress')->nullable()->comment('观看进度,记录观看到视频的第几秒');
            $table->string('last_watch_time')->comment('最后观看时间')->nullable();
            $table->integer('movie_id')->index()->comment('电影');
            $table->unsignedInteger('series_id')->nullable()->default(0)->index()->comment('集数index,记录在数组中的偏移量');
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
