<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMessageMstTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('message_msts', function (Blueprint $table) {
            $table->id();
            $table->string('type')->default('サンプルテキスト');
            $table->integer('seq')->unsigned()->default(0);
            $table->string('message');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->unique(['type', 'seq']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('message_msts');
    }
}
