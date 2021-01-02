<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSeriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('series', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('movie_id')->index()->comment('电影ID');
            $table->string('name')->nullable();
            $table->string('path')->nullable();
            $table->string('source')->nullable();
            $table->string('bucket')->nullable();
            $table->string('cover')->nullable()->comment('剧集 m3u8 cover');
            $table->tinyInteger('status')->default(0)->comment('播放状态: 0-待解析 1-已解析 -1:解析失败');
            $table->timestamps();
        });
    }

    // 内涵电影最近 schema dump
    // CREATE TABLE `series` (
    //     `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    //     `movie_id` int unsigned NOT NULL,
    //     `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    //     `path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    //     `source` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    //     `bucket` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
    //     `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0' COMMENT '播放状态: 0-待解析 1-已解析 -1:解析失败',
    //     `created_at` timestamp NULL DEFAULT NULL,
    //     `updated_at` timestamp NULL DEFAULT NULL,
    //     `cover` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'm3u8 cover',
    //     PRIMARY KEY (`id`),
    //     KEY `series_movie_id_index` (`movie_id`),
    //     KEY `series_bucket_index` (`bucket`),
    //     KEY `series_status_index` (`status`)
    //   )

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('series');
    }
}
