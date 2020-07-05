<?php

use Haxibiao\Media\Spider;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpidersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('spiders')) {
            return;
        }

        Schema::create('spiders', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->index()->comment('用户ID');
            $table->string('source_url')->index()->comment('源URL');
            $table->json('raw')->nullable()->comment('源信息');
            $table->json('data')->nullable();
            $table->tinyInteger('status')->default(Spider::WATING_STATUS);
            $table->string('spider_type')->nullable();
            $table->unsignedBigInteger('spider_id')->index()->nullable();
            $table->unsignedInteger('count')->default(1)->comment('爬虫粘贴次数');
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
        Schema::dropIfExists('spiders');
    }
}
