<?php

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
        Schema::table('movies', function (Blueprint $table) {
            if (Schema::hasColumn('movies', 'status')) {
                //先确保index不重复创建失败
                $sm            = Schema::getConnection()->getDoctrineSchemaManager();
                $doctrineTable = $sm->listTableDetails('movies');
                if ($doctrineTable->hasIndex('movies_status_index')) {
                    $table->dropIndex('movies_status_index');
                }
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
