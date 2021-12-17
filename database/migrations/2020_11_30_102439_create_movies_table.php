<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMoviesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('movies', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->index()->comment("电影名");
            $table->text('introduction')->comment("简介");
            $table->string('cover', 200)->nullable()->index()->comment("封面");
            $table->string('producer', 100)->nullable()->index()->comment("导演");
            $table->tinyInteger('status')->nullable()->default(1)->comment('0未标示，1正常影片，2尺度较大，-1为下架状态，-2资源损坏');
            $table->integer('score')->nullable()->index()->comment("评分0-10");
            $table->string('year', 100)->nullable()->index()->comment("年份");
            $table->string('type', 100)->nullable()->index()->comment("分类");
            $table->string('style', 100)->nullable()->index()->comment("风格");
            $table->string('region', 100)->nullable()->index()->comment("地区");
            $table->integer('count_series')->default(0)->comment("总集数");
            $table->string('actors', 100)->nullable()->index()->comment("演员");
            $table->json('data')->nullable()->comment('默认剧集线路数据');
            $table->json('data_source')->nullable()->comment('其他剧集线路数据');

            $table->tinyInteger('rank')->default(0)->comment('权重');
            $table->string('country', 255)->nullable()->comment('国家');
            $table->string('subname', 255)->nullable()->comment('别名');
            $table->string('tags', 255)->nullable()->comment('标签');
            $table->string('lang', 255)->nullable()->comment('语言');
            $table->string('source', 20)->nullable()->index()->comment('资源来源');
            $table->string('source_key', 50)->nullable()->index()->comment('资源外部UID');
            $table->string('movie_key', 50)->nullable()->index()->comment('资源内部UID');
            $table->string('miner', 20)->nullable()->index()->comment('资源矿工');
            $table->string('type_name', 50)->nullable()->comment('类型名');

            $table->unsignedInteger('hits')->nullable()->comment('点击次数');
            $table->unsignedInteger('count_likes')->default(0)->comment('点赞数');
            $table->unsignedInteger('count_comments')->default(0)->comment('评论数');
            $table->unsignedInteger('count_favorites')->default(0)->comment('收藏数');
            $table->unsignedInteger('count_clips')->default(0)->comment('剪辑数');

            $table->integer('user_id')->nullable()->comment('创建影片的用户');
            $table->integer('fixer_id')->nullable()->comment('修复影片的用户');
            $table->boolean('is_neihan')->default(0)->comment('是否内涵片');

            $table->timestamps();
            $table->index('updated_at');

        });

    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('movies');
    }
}
