
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('jury_assignments', function (Blueprint $table) {
            $table->id('jury_id');
            $table->foreignId('project_id')->constrained('projects', 'project_id');
            $table->foreignId('examiner_id')->constrained('teachers', 'teacher_id');
            $table->foreignId('president_id')->constrained('teachers', 'teacher_id');
            $table->foreignId('supervisor_id')->constrained('teachers', 'teacher_id');
            $table->string('assignment_method');
            $table->timestamp('assignment_date');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('jury_assignments');
    }
};