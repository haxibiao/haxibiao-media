<?php

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
        Schema::table('movies', function (Blueprint $table) {
            if (!Schema::hasColumn('movies', 'play_lines')) {
                $table->json('play_lines')->nullable()->comment('播放线路');
            }
            if (!Schema::hasColumn('movies', 'finished')) {
                $table->boolean('finished')->nullable()->comment('是否完结');
            }
            if (!Schema::hasColumn('movies', 'has_playurl')) {
                $table->boolean('has_playurl')->nullable()->comment('是否具备播放线路');
            }
            if (!Schema::hasColumn('movies', 'custom_type')) {
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
        Schema::table('movies', function (Blueprint $table) {
            //
        });
    }
}
