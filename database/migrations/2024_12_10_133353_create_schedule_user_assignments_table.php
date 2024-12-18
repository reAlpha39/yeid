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
            $table->string('user_id');
            $table->unsignedBigInteger('task_item_id');
            $table->timestamp('assigned_date');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('employeecode')->on('mas_employee');
            $table->foreign('task_item_id')->references('item_id')->on('schedule_task_item');
        });
    }

    public function down()
    {
        Schema::dropIfExists('schedule_user_assignments');
    }
};
