<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('projects', function (Blueprint $table) {
            $table->id('project_id');
            $table->string('title');
            $table->text('summary');
            $table->text('technologies');
            $table->text('material_needs');
            $table->enum('type', ['Classical', 'Innovative', 'StartUp', 'Patent', 'Internship']);
            $table->enum('option', ['GL', 'IA', 'RSD', 'SIC']);
            $table->enum('status', ['Proposed', 'Validated', 'Assigned', 'InProgress', 'Completed']);
            $table->foreignId('submitted_by')->constrained('users', 'user_id');
            $table->timestamp('submission_date');
            $table->timestamp('last_updated_date');
            $table->string('company_name')->nullable();
            $table->string('internship_location')->nullable();
            $table->decimal('internship_salary', 10, 2)->nullable();
            $table->date('internship_start_date')->nullable();
            $table->integer('internship_duration_months')->nullable();
            $table->timestamps(); // Keep only one timestamps() call
        });
    }

    public function down(): void {
        Schema::dropIfExists('projects');
    }
};