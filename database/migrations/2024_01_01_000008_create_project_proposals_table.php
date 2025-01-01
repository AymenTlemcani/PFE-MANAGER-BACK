<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('project_proposals', function (Blueprint $table) {
            $table->id('proposal_id');
            $table->foreignId('project_id')->constrained('projects', 'project_id');
            $table->foreignId('submitted_by')->constrained('users', 'user_id');
            $table->string('co_supervisor_name')->nullable(); // Make nullable
            $table->string('co_supervisor_surname')->nullable(); // Make nullable
            $table->enum('proposal_status', ['Pending', 'Approved', 'Rejected']);
            $table->text('review_comments')->nullable();
            $table->integer('proposal_order')->default(1); // Track which proposal number this is for students
            $table->enum('proposer_type', ['Teacher', 'Student', 'Company']);
            $table->json('additional_details')->nullable(); // Store type-specific details
            $table->boolean('is_final_version')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('project_proposals');
    }
};