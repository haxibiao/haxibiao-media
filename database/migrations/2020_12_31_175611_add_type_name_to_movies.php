<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTypeNameToMovies extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('movies', function (Blueprint $table) {

            if (!Schema::hasColumn('movies', 'type_name')) {
                $table->string('type_name', 50)->nullable()->comment('类型名');
            }

            //顺便修复score nullable
            if (Schema::hasColumn('movies', 'score')) {
                $table->integer('score')->nullable()->change();
            }

            //兼容导入数据时数据不完整的情况
            if (Schema::hasColumn('movies', 'producer')) {
                $table->string('producer', 100)->nullable()->change();
            }
            if (Schema::hasColumn('movies', 'actors')) {
                $table->string('actors', 100)->nullable()->change();
            }
            if (Schema::hasColumn('movies', 'year')) {
                $table->string('year', 100)->nullable()->change();
            }
            if (Schema::hasColumn('movies', 'type')) {
                $table->string('type', 100)->nullable()->change();
            }
            if (Schema::hasColumn('movies', 'style')) {
                $table->string('style', 100)->nullable()->change();
            }
            if (Schema::hasColumn('movies', 'region')) {
                $table->string('region', 100)->nullable()->change();
            }
            if (Schema::hasColumn('movies', 'data')) {
                $table->json('data')->nullable()->change();
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
