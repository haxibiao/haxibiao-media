<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateComicsDetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('comics_detail')) {
            return;
        }
        Schema::create('comics_detail', function (Blueprint $table) {
            $table->id();
            $table->integer('sort')->comment('排序');
            $table->unsignedInteger('comic_id')->comment('漫画ID');
            $table->string('chapter')->comment('章节');
            $table->string('url')->comment('图片地址');
            $table->string('thumbnail_url')->comment('缩略图地址');
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
        Schema::dropIfExists('comics_detail');
    }
}
