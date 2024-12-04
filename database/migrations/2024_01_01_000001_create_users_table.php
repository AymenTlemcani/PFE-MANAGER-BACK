<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('users', function (Blueprint $table) {
            $table->id('user_id');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('temporary_password')->nullable();
            $table->timestamp('temporary_password_expiration')->nullable();
            $table->enum('role', ['Administrator', 'Teacher', 'Student', 'Company']);
            $table->boolean('is_active')->default(true);
            $table->boolean('must_change_password')->default(false);
            $table->string('profile_picture_url')->nullable();
            $table->enum('language_preference', ['French', 'English'])->default('French');
            $table->date('date_of_birth')->nullable();
            $table->timestamps();  // This will create both created_at and updated_at
            $table->timestamp('last_login')->nullable();
        });
    }

    public function down(): void {
        Schema::dropIfExists('users');
    }
};