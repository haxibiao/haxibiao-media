<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLinkMovie extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //长视频（电影）关联短视频post，合集collection，电影(movie)解说类
        if (Schema::hasTable('link_movie')) {
            return;
        }
        Schema::create('link_movie', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('movie_id');
            $table->morphs('linked');
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
        Schema::dropIfExists('relation_movie');
    }
}
