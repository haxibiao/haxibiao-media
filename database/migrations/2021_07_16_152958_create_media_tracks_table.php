<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMediaTracksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('media_tracks')) {
            return;
        }
        Schema::create('media_tracks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->default(0)->index();
            $table->morphs('media');
            $table->timestamp('start_time');
            $table->timestamp('end_time');
            $table->unsignedInteger('track_seconds')->default(0)->comment('跟踪的秒数');
            $table->string('uuid', 32)->default('');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('media_tracks');
    }
}
