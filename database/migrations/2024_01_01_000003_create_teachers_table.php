<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('teachers', function (Blueprint $table) {
            $table->id('teacher_id');
            $table->foreignId('user_id')
                ->constrained('users', 'user_id')
                ->onDelete('cascade')
                ->unique();
            $table->string('name');
            $table->string('surname');
            $table->date('recruitment_date');
            $table->enum('grade', ['Professor', 'Associate Professor', 'Assistant Professor']);
            $table->boolean('is_responsible')->default(false);
            $table->string('research_domain')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('teachers');
    }
};