<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mas_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained('mas_department');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone');
            $table->string('nik')->unique();
            $table->string('role_access');
            $table->string('status');
            $table->string('control_access');
            $table->string('password');
            $table->rememberToken();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mas_user');
    }
};
