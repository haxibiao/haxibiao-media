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
        if (!Schema::hasTable('movies')) {
            Schema::create('movies', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100)->index()->comment("电影名");
                $table->string('introduction', 255)->index()->comment("简介");
                $table->string('cover', 200)->index()->comment("封面");
                $table->string('producer', 100)->index()->comment("导演");
                $table->string('year', 100)->index()->comment("年份");
                $table->string('type', 100)->index()->comment("分类");
                $table->string('style', 100)->index()->comment("风格");
                $table->string('region', 100)->index()->comment("地区");
                $table->integer('count_series')->default(0)->comment("总集数");
                $table->string('actors', 100)->index()->comment("演员");
                $table->json('data')->comment('剧集播放数据');
                $table->timestamps();
            });
        }
    }

//mediachain
    // `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
    //   `introduction` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '简介',
    //   `cover` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '封面',
    //   `producer` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '导演',
    //   `year` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '年份',
    //   `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '分类:动作、科幻...',
    //   `style` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '风格:武侠、玄幻...',
    //   `region` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '地区：美剧、韩剧...',
    //   `actors` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '演员',
    //   `count_series` int unsigned DEFAULT NULL COMMENT '总集数',
    //   `data` json DEFAULT NULL COMMENT '剧集详情',

//内涵电影
    //     `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
    //   `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0' COMMENT '0-待确认能播放 1-能播 -1禁用',
    //   `introduction` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    //   `cover` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    //   `cover_id` int unsigned DEFAULT NULL,
    //   `category_id` int unsigned DEFAULT NULL,
    //   `producer` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '导演',
    //   `year` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '生产年份',
    //   `count_series` int unsigned DEFAULT NULL COMMENT '总集数',
    //   `series_status` tinyint DEFAULT NULL COMMENT '集更新状态',
    //   `keywords` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '电影关键字',
    //   `actors` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '演员',

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
