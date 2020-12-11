<?php
/*
 * @Author: your name
 * @Date: 2020-12-11 11:04:22
 * @LastEditTime: 2020-12-11 11:06:43
 * @LastEditors: Please set LastEditors
 * @Description: In User Settings Edit
 * @FilePath: /neihan.sites/packages/haxibiao/media/database/migrations/2020_12_11_110422_add_source_key_to_movies.php
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSourceKeyToMovies extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('movies', function (Blueprint $table) {
            if (!Schema::hasColumn('movies', 'source')) {
                $table->string("source",20)->nullable()->index()->comment('资源来源');
                $table->string("source_key",50)->nullable()->index()->comment('资源UID');
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
