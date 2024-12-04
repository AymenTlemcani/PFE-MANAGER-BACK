<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        Schema::create('student_pairs', function (Blueprint $table) {
            $table->id('pair_id');
            $table->foreignId('student1_id')->constrained('students', 'student_id');
            $table->foreignId('student2_id')->constrained('students', 'student_id');
            $table->enum('status', ['Proposed', 'Accepted', 'Rejected']);
            $table->timestamp('proposed_date')->useCurrent();
            $table->timestamp('updated_date')->nullable();
            
            // Create a composite index to ensure student1_id is always less than student2_id
            // This prevents both duplicate pairs and self-pairing
            $table->unique(['student1_id', 'student2_id']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('student_pairs');
    }
};