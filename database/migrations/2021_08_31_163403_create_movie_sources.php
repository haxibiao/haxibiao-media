<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMovieSources extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('movie_sources', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('movie_id')->index();
            $table->string('name')->comment('线路名');
            $table->string('url')->comment('线路');
            $table->tinyInteger('rank')->index()->default(0);
            $table->json('play_urls')->nullable()->comment('播放路径');
            $table->string('remark')->comment('备注')->nullable()->index();
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
        Schema::dropIfExists('movie_sources');
    }
}
