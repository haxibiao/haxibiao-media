<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCountColumnToMovies extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('movies', function (Blueprint $table) {
            //
            if (!Schema::hasColumn('movies', 'count_likes')) {
                $table->unsignedInteger('count_likes')->default(0)->comment('点赞数');
            }
            if (!Schema::hasColumn('movies', 'count_comments')) {
                $table->unsignedInteger('count_comments')->default(0)->comment('评论数');
            }
            if (!Schema::hasColumn('movies', 'count_favorites')) {
                $table->unsignedInteger('count_favorites')->default(0)->comment('收藏数');
            }
            if (!Schema::hasColumn('movies', 'count_clips')) {
                $table->unsignedInteger('count_clips')->default(0)->comment('剪辑数');
            }
            $table->index('updated_at');

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
