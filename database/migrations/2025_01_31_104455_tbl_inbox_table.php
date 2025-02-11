<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_inbox', function (Blueprint $table) {
            $table->id();
            $table->string('source_type'); // spk_record, preventive, etc
            $table->unsignedBigInteger('source_id');
            $table->unsignedBigInteger('user_id');
            $table->string('title');
            $table->text('message');
            $table->text('metadata')->nullable(); // JSON field for additional data
            $table->string('category'); // approval, notification, alert, etc
            $table->string('status')->default('unread'); // unread, read, archived
            $table->timestamp('read_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('mas_user')->onDelete('cascade');
            $table->index(['user_id', 'status']);
            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_inbox');
    }
};
