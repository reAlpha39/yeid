<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMasDepartmentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mas_department', function (Blueprint $table) {
            // Primary key
            $table->id();

            // Required fields
            $table->string('code')->unique();
            $table->string('name');

            // Timestamps (created_at, updated_at)
            $table->timestamps();

            // Soft delete (deleted_at)
            $table->softDeletes();

            // Indexes
            $table->index('code');
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mas_department');
    }
}
