<?php

use Haxibiao\Media\Movie;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateRankToMoviesTable extends Migration
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
            //先确保index不重复创建失败
            $sm            = Schema::getConnection()->getDoctrineSchemaManager();
            $doctrineTable = $sm->listTableDetails($movies_table);
            if ($doctrineTable->hasIndex('movies_rank_index')) {
                $table->dropIndex('movies_rank_index');
            }

            if (Schema::hasColumn($movies_table, 'rank')) {
                $table->integer('rank')->index()->default(0)->comment('权重')->change();
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
