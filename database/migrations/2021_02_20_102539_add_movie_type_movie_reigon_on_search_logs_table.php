<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMovieTypeMovieReigonOnSearchLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('search_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('search_logs', 'movie_type')) {
                $table->string('movie_type', 50)->nullable();
                $table->string('movie_reigon', 50)->nullable();
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
