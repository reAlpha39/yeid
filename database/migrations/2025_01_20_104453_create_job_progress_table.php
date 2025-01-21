<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('job_progress', function (Blueprint $table) {
            $table->id();
            $table->string('job_type');
            $table->string('status');  // queued, processing, completed, completed_with_errors, failed
            $table->float('progress')->default(0);
            $table->integer('total_items')->default(0);
            $table->integer('processed_items')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Index for quick lookups
            $table->index(['job_type', 'status']);
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('job_progress');
    }
};
