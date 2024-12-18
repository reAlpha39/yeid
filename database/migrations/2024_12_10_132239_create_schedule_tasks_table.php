<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('schedule_tasks', function (Blueprint $table) {
            $table->id('task_id');
            $table->unsignedBigInteger('activity_id');
            $table->string('machine_id');
            $table->string('task_name');
            $table->integer('frequency_times');
            $table->string('frequency_period');
            $table->integer('start_week');
            $table->integer('duration');
            $table->integer('manpower_required');
            $table->string('pic');
            $table->integer('cycle_time');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('activity_id')->references('activity_id')->on('schedule_activities');
            $table->foreign('machine_id')->references('machineno')->on('mas_machine');
        });
    }

    public function down()
    {
        Schema::dropIfExists('schedule_tasks');
    }
};
