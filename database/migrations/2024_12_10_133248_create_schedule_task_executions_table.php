<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('schedule_task_execution', function (Blueprint $table) {
            $table->id('execution_id');
            $table->unsignedBigInteger('task_id');
            $table->date('scheduled_date');
            $table->string('status');
            $table->date('completion_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('task_id')->references('task_id')->on('schedule_tasks');
        });
    }

    public function down()
    {
        Schema::dropIfExists('schedule_task_execution');
    }
};
