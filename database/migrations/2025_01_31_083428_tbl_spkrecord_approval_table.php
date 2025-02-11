<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_spkrecord_approval', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('record_id');
            $table->unsignedBigInteger('department_id');

            $table->unsignedBigInteger('creatad_by');
            $table->string('pic')->nullable();

            $table->unsignedBigInteger('supervisor_approved_by')->nullable();
            $table->timestamp('supervisor_approved_at')->nullable();
            $table->unsignedBigInteger('manager_approved_by')->nullable();
            $table->timestamp('manager_approved_at')->nullable();

            $table->unsignedBigInteger('supervisor_mtc_approved_by')->nullable();
            $table->timestamp('supervisor_mtc_approved_at')->nullable();
            $table->unsignedBigInteger('manager_mtc_approved_by')->nullable();
            $table->timestamp('manager_mtc_approved_at')->nullable();

            $table->enum('approval_status', [
                'pending',
                'partially_approved',
                'approved',
                'rejected',
                'revision',
                'finish'
            ])->default('pending');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('record_id')->references('recordid')->on('tbl_spkrecord');
            $table->foreign('department_id')->references('id')->on('mas_department');
            $table->foreign('created_by')->references('id')->on('mas_user');
            $table->foreign('pic')->references('employeecode')->on('mas_employee');
            $table->foreign('supervisor_approved_by')->references('id')->on('mas_user');
            $table->foreign('manager_approved_by')->references('id')->on('mas_user');
            $table->foreign('supervisor_mtc_approved_by')->references('id')->on('mas_user');
            $table->foreign('manager_mtc_approved_by')->references('id')->on('mas_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_spkrecord_approval');
    }
};
