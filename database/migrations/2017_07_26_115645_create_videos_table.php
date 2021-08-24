<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVideosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('videos')) {
            return;
        }

        Schema::create('videos', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->default(0)->index();
            $table->string('title')->nullable();
            $table->string('path')->nullable();
            $table->integer('duration')->default(0)->comment('时长秒');
            $table->integer('status')->default(0)->index()->comment('-1删除 0隐藏 1发布');
            //内部push sync同步尊重哈希云处理过的hash
            $table->string('hash')->nullable()->unique()->comment('避免重复上传文件');

            $table->string('cover')->nullable()->comment('截图封面,disk同video的disk');
            $table->text('json')->nullable();

            $table->string('disk')->nullable()->comment('存储位置 local,vod,cos等');
            $table->unsignedInteger('width')->nullable()->comment('宽');
            $table->unsignedInteger('height')->nullable()->comment('高');

            $table->string('collection', 100)->nullable()->index()->comment('合集');
            $table->string('collection_key', 50)->nullable()->index()->comment('合集的唯一key 例如: ainicheng_1122');
            $table->integer('movie_id')->nullable()->index()->comment('关联的电影');
            $table->string('movie_key', 50)->nullable()->index()->comment('电影的唯一key 例如: chain_1102');

            //分享粘贴视频靠sharelink回调hook
            $table->string('sharelink', 500)->nullable()->index()->comment('秒粘贴地址');
            //自己上传视频靠fileid回调hook
            $table->string('fileid')->nullable()->index()->comment('vod的fileid');
            $table->string('vid')->nullable()->index()->comment('字节系视频标识的VID');

            $table->unsignedInteger('push_url_cache_day')->default(0)->comment('预热URL日');

            $table->boolean('is_hd')->default(true)->comment("有无高清无水印");
            $table->unsignedBigInteger('count_likes')->default(0);
            $table->unsignedBigInteger('count_comments')->default(0);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('videos');
    }
}
