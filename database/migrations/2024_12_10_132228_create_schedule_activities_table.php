<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('schedule_activities', function (Blueprint $table) {
            $table->id('activity_id');
            $table->string('shop_id');
            $table->string('activity_name');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('shop_id')->references('shopcode')->on('mas_shop');
        });
    }

    public function down()
    {
        Schema::dropIfExists('schedule_activities');
    }
};
