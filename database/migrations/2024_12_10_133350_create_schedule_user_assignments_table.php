<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    public function up()
    {
        Schema::create('schedule_user_assignments', function (Blueprint $table) {
            $table->id('assignment_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('task_execution_id');
            $table->timestamp('assigned_date');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('mas_user');
            $table->foreign('task_execution_id')->references('execution_id')->on('schedule_task_execution');
        });
    }

    public function down()
    {
        Schema::dropIfExists('schedule_user_assignments');
    }
};
