<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_spkrecord_approval_note', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('approval_id');
            $table->unsignedBigInteger('user_id');
            $table->text('note');
            $table->enum('type', [
                'approved',
                'rejected',
                'revision',
                'finish',
            ])->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('approval_id')->references('id')->on('tbl_spkrecord_approval');
            $table->foreign('user_id')->references('id')->on('mas_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_spkrecord_approval_note');
    }
};
