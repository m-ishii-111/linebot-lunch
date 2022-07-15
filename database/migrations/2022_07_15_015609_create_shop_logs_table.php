<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShopLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shop_logs', function (Blueprint $table) {
            $table->id();
            $table->string('line_user_id');
            $table->string('hp_shop_id');
            $table->string('hp_genre_code');
            $table->string('hp_genre_name');
            $table->timestamp('search_date')->useCurrent();
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
        Schema::dropIfExists('shop_logs');
    }
}
