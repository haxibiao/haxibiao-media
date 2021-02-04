<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDanmuTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('danmu', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id')->comment("弹幕发送者id");
            $table->unsignedInteger('series')->comment("弹幕所属的series");
            $table->string("content", 50)->default("")->comment("弹幕内容");
            $table->string("color", 25)->default("")->comment("弹幕颜色");
            $table->unsignedInteger("type")->default(0)->comment("发送弹幕类型  0：滚动， 1：顶部 ，2：底部");
            $table->string("time")->default("")->comment("发送弹幕时间");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('barrage');
    }
}
