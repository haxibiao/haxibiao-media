<?php

use Haxibiao\Media\Movie;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInfosToMovies extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //兼容本地容器多项目共享meiachain的movies模式
        $movies_table = Movie::getTableName();
        Schema::table($movies_table, function (Blueprint $table) use ($movies_table) {
            if (!Schema::hasColumn($movies_table, 'play_lines')) {
                $table->json('play_lines')->nullable()->comment('播放线路');
            }
            if (!Schema::hasColumn($movies_table, 'finished')) {
                $table->boolean('finished')->nullable()->comment('是否完结');
            }
            if (!Schema::hasColumn($movies_table, 'has_playurl')) {
                $table->boolean('has_playurl')->nullable()->comment('是否具备播放线路');
            }
            if (!Schema::hasColumn($movies_table, 'custom_type')) {
                $table->text('custom_type')->nullable()->comment('影片类型:电影/电视剧。。');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

    }
}
