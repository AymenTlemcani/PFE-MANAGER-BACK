<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('students', function (Blueprint $table) {
            $table->id('student_id');
            $table->foreignId('user_id')->constrained('users', 'user_id')->unique();
            $table->string('name');
            $table->string('surname');
            $table->enum('master_option', ['GL', 'IA', 'RSD', 'SIC']);
            $table->decimal('overall_average', 4, 2);
            $table->year('admission_year');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('students');
    }
};