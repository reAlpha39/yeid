<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('schedule_task_item', function (Blueprint $table) {
            $table->id('item_id');
            $table->unsignedBigInteger('task_id');
            // $table->date('scheduled_date')->nullable();
            $table->integer('scheduled_week');
            $table->string('status');
            $table->string('note')->nullable();
            // $table->date('completion_date')->nullable();
            $table->integer('completion_week')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('task_id')->references('task_id')->on('schedule_tasks');
        });
    }

    public function down()
    {
        Schema::dropIfExists('schedule_task_item');
    }
};
