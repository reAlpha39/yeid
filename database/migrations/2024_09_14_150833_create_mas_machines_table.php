<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMasMachineTable extends Migration
{
    public function up()
    {
        Schema::create('mas_machine', function (Blueprint $table) {
            $table->string('machineno', 12);
            $table->string('machinename', 50);
            $table->char('plantcode', 1);
            $table->char('shopcode', 4);
            $table->string('shopname', 50)->nullable();
            $table->char('linecode', 2)->nullable();
            $table->string('modelname', 50)->nullable();
            $table->char('makercode', 6)->nullable();
            $table->string('makername', 50)->nullable();
            $table->string('serialno', 30)->nullable();
            $table->decimal('machineprice', 18)->nullable();
            $table->char('currency', 3)->nullable();
            $table->string('purchaseroot', 50)->nullable();
            $table->char('installdate', 20);
            $table->string('note', 255)->nullable();
            $table->char('status', 1);
            $table->char('rank', 1)->nullable();
            $table->dateTime('updatetime')->nullable();
            $table->primary('machineno');
        });
    }

    public function down()
    {
        Schema::dropIfExists('mas_machine');
    }
}

