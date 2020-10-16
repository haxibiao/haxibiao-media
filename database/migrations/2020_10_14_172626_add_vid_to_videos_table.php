<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVidToVideosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('videos', function (Blueprint $table) {
            // 无水印分享用
            if (!Schema::hasColumn('videos', 'vid')) {
                if (Schema::hasColumn('videos', 'qcvod_fileid')) {
                    $table->string('vid', 50)->nullable()->index()->comment('视频的VID')->after('qcvod_fileid');
                } else {
                    $table->string('vid', 50)->nullable()->index()->comment('视频的VID');
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
