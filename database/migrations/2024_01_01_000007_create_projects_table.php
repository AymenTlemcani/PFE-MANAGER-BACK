
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
            $table->enum('type', ['Classical', 'Innovative', 'StartUp', 'Patent']);
            $table->enum('option', ['GL', 'IA', 'RSD', 'SIC']);
            $table->enum('status', ['Proposed', 'Validated', 'Assigned', 'InProgress', 'Completed']);
            $table->foreignId('submitted_by')->constrained('users', 'user_id');
            $table->timestamp('submission_date');
            $table->timestamp('last_updated_date');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('projects');
    }
};