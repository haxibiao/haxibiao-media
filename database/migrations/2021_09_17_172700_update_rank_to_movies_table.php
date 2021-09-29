<?php

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
        Schema::table('movies', function (Blueprint $table) {
            //先确保index不重复创建失败
            $sm            = Schema::getConnection()->getDoctrineSchemaManager();
            $doctrineTable = $sm->listTableDetails('movies');
            if ($doctrineTable->hasIndex('movies_rank_index')) {
                $table->dropIndex('movies_rank_index');
            }

            if (Schema::hasColumn('movies', 'rank')) {
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
        Schema::table('movies', function (Blueprint $table) {
            //
        });
    }
}
