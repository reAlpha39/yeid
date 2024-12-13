<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('schedule_activity_progress', function (Blueprint $table) {
            $table->id('progress_id');
            $table->unsignedBigInteger('activity_id');
            $table->integer('month');
            $table->integer('year');
            $table->integer('total_tasks');
            $table->integer('completed_on_time');
            $table->integer('completed_delayed');
            $table->integer('not_completed');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('activity_id')->references('activity_id')->on('schedule_activities');
        });
    }

    public function down()
    {
        Schema::dropIfExists('schedule_activity_progress');
    }
};
