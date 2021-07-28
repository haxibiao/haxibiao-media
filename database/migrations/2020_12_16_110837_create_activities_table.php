<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActivitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('activities')) {
            return;
        }
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            //使用activityable来指定轮播对象
            $table->morphs('activityable');
            $table->tinyInteger('sort')->nullable()->default(1)->comment('排序，值越大越靠前');
            $table->tinyInteger('type')->comment('1:首页，2：电视剧，3：电影专题');
            $table->tinyInteger('status')->default(1)->comment('true:展示中，false:已下架');
            $table->string('title', 64)->nullable()->comment('图片标题');
            $table->string('subtitle', 64)->nullable()->comment('副标题');
            $table->string('image_url')->comment('图片链接地址');
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
        Schema::dropIfExists('activities');
    }
}
