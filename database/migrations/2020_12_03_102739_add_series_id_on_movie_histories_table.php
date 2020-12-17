<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSeriesIdOnMovieHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Schema::table('movie_histories', function (Blueprint $table) {
        //     if (!Schema::hasColumn('movie_histories', 'series_id')) {
        //         $table->unsignedInteger('series_id')->nullable()->default(1)->index()->comment('集数index,记录在数组中的偏移量');
        //     }
        // });
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
