<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddChatIdToMovieRooms extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('movie_rooms', function (Blueprint $table) {
            if (!Schema::hasColumn('movie_rooms', 'chat_id')) {
                $table->unsignedInteger('chat_id')->index()->comment('关联群聊');
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
        Schema::table('movie_rooms', function (Blueprint $table) {
            //
        });
    }
}
