<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNovelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('novels')) {
            return;
        }
        Schema::create('novels', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('introduction')->nullable();
            $table->string('cover')->nullable()->comment('封面图');
			$table->string('type_names')->nullable()->comment('类别');
            $table->string('author')->nullable()->comment('作者');
            $table->tinyInteger('status')->default(\Haxibiao\Media\Novel::STATUS_OF_PUBLISH)->comment('状态');
            $table->boolean('is_over')->default(false)->comment('是否更新完结');
			$table->unsignedInteger('count_words')->nullable()->comment('字数');
            $table->unsignedInteger('count_chapters')->comment('章节数');
            $table->string('source')->nullable()->comment('来源');
			$table->string('source_key', 50)->comment('资源UID');
            $table->timestamps();

            $table->index('name');
            $table->index('status');
            $table->index('is_over');
            $table->unique('source_key');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('novels');
    }
}
