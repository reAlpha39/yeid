<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblInvrecordTable extends Migration
{
    public function up()
    {
        Schema::create('tbl_invrecord', function (Blueprint $table) {
            $table->id('recordid');
            $table->char('locationid', 1);
            $table->char('jobcode', 1);
            $table->char('jobdate', 8);
            $table->char('jobtime', 6);
            $table->string('partcode', 24);
            $table->string('partname', 127);
            $table->string('specification', 127);
            $table->string('brand', 64);
            $table->char('usedflag', 1);
            $table->decimal('quantity', 8, 2);
            $table->decimal('unitprice', 10, 2);
            $table->char('currency', 3);
            $table->decimal('total', 18, 2);
            $table->char('vendorcode', 15)->nullable();
            $table->char('note', 128);
            $table->char('employeecode', 8)->nullable();
            $table->string('machineno', 12)->nullable();
            $table->string('machinename', 50)->nullable();
            $table->dateTime('updatetime');
            $table->primary('recordid');
        });
    }

    public function down()
    {
        Schema::dropIfExists('tbl_invrecord');
    }
}

