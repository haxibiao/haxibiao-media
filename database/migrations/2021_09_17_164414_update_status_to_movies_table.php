<?php

use Haxibiao\Media\Movie;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateStatusToMoviesTable extends Migration
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
            if ($doctrineTable->hasIndex('movies_status_index')) {
                $table->dropIndex('movies_status_index');
            }
            if (Schema::hasColumn($movies_table, 'status')) {
                $table->integer('status')->nullable()->index()->default(1)->comment('0未标示，1正常影片，2尺度较大，-1为下架状态，-2资源损坏')->change();
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
