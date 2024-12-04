
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('jury_preferences', function (Blueprint $table) {
            $table->id('preference_id');
            $table->foreignId('teacher_id')->constrained('teachers', 'teacher_id');
            $table->foreignId('project_id')->constrained('projects', 'project_id');
            $table->integer('preference_order');
            $table->timestamp('preference_date');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('jury_preferences');
    }
};