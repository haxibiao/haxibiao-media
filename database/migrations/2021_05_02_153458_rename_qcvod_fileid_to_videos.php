<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameQcvodFileidToVideos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('videos', function (Blueprint $table) {

            if (Schema::hasColumn('videos', 'qcvod_fileid')) {
                if (!Schema::hasColumn('videos', 'fileid')) {
                    // $table->string('fileid')->nullable()->index()->comment('vod的fileid');
                    $table->renameColumn('qcvod_fileid', 'fileid');
                }
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
        Schema::table('videos', function (Blueprint $table) {
            //
        });
    }
}
