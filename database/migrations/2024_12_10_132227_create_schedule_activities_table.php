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
            $table->string('machineno');
            $table->string('activity_name');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('machineno')->references('machineno')->on('mas_machine');
        });
    }

    public function down()
    {
        Schema::dropIfExists('schedule_activities');
    }
};
