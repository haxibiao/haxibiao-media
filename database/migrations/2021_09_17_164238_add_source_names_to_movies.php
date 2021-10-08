<?php

use Haxibiao\Media\Movie;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSourceNamesToMovies extends Migration
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
            if (!Schema::hasColumn($movies_table, 'source_names')) {
                $table->string('source_names')->nullable()->index();
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
