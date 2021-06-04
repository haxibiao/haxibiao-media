<?php

use Haxibiao\Media\Audible;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAudiblesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
		if(Schema::hasTable('audio_books')){
			Schema::rename('audio_books','audibles');
		}
    	if(Schema::hasTable('audibles')){
    		return;
		}
        Schema::create('audibles', function (Blueprint $table) {
            $table->id();

            $table->string('name')->index()->comment('名字');
			$table->string('introduction')->nullable()->comment('简介');
			$table->string('announcer')->nullable()->comment('播音人');
			$table->string('cover')->nullable()->comment('封面');
			$table->string('type_names')->nullable()->comment('类别');
			$table->integer('count_chapters')->default(0)->comment("总集数");
			$table->json('data')->nullable()->comment('章节信息');
			$table->tinyInteger('status')->nullable()->default(Audible::STATUS_OF_PUBLISH)->comment('-1为下架状态，-2资源损坏 , 0未标示，1正常影片,');
			$table->boolean('is_over')->default(false)->comment('是否完结');
			$table->string('source_key', 50)->unique()->comment('资源UID');

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
        Schema::dropIfExists('audio_books');
    }
}
