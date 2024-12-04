
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('project_assignments', function (Blueprint $table) {
            $table->id('assignment_id');
            $table->foreignId('project_id')->constrained('projects', 'project_id');
            $table->foreignId('student_id')->constrained('students', 'student_id');
            $table->foreignId('teacher_id')->constrained('teachers', 'teacher_id');
            $table->foreignId('company_id')->nullable()->constrained('companies', 'company_id');
            $table->timestamp('assignment_date');
            $table->string('assignment_method');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('project_assignments');
    }
};