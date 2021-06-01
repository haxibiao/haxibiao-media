<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNovelChaptersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
		if (Schema::hasTable('novel_chapters')) {
			return;
		}
		Schema::create('novel_chapters', function (Blueprint $table) {
			$table->id();
			$table->unsignedInteger('novel_id');
			$table->string('title');
			$table->string('url');
			$table->unsignedInteger('index');
			$table->timestamps();

			$table->unique(['novel_id','index']);
			$table->index('index');
		});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('novel_chapters');
    }
}
