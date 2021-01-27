<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangFieldsNullableToImages extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        
        Schema::table('images', function (Blueprint $table) {
            if(Schema::hasColumn('images','store_id')){
                $table->Integer('store_id')->nullable()->change();
            }
            if(Schema::hasColumn('images','product_id')){
                $table->Integer('product_id')->nullable()->change();
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
        Schema::table('images', function (Blueprint $table) {
            //
        });
    }
}
