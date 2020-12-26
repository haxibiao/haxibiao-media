<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsOnMoviesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('movies', function (Blueprint $table) {
            if (!Schema::hasColumn('movies', 'rank')) {
                $table->tinyInteger('rank')->default(0)->comment('权重');
                $table->string('country')->nullable()->comment('国家');
                $table->string('subname')->nullable()->comment('别名');
                $table->string('tags')->nullable()->comment('标签');
                $table->string('lang')->nullable()->comment('语言');

                $table->unsignedInteger('type_name')->nullable()->comment('类型名');
                $table->double('score')->nullable()->comment('评分');
            }
            if (!Schema::hasColumn('movies', 'hits')) {
                $table->unsignedInteger('hits')->nullable()->comment('点击次数');
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
        //
    }
}
