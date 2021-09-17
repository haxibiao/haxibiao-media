<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMovieRoomsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('movie_rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('放映室名称');
            $table->string('icon')->nullable()->comment('封面');
            $table->unsignedInteger('user_id')->index()->comment('房主');
            $table->unsignedInteger('movie_id')->index()->comment('播放电影');
            $table->json('uids')->nullable()->comment('成员id');

            $table->string('progress')->nullable()->comment('观看进度,记录观看到视频的第几秒');
            $table->unsignedInteger('series_index')->nullable()->comment('集数index,记录在数组中的偏移量');
            $table->softDeletes();
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
        Schema::dropIfExists('movie_rooms');
    }
}
