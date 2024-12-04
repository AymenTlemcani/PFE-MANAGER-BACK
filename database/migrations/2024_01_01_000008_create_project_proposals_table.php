
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
            $table->string('co_supervisor_name');
            $table->string('co_supervisor_surname');
            $table->enum('proposal_status', ['Pending', 'Approved', 'Rejected']);
            $table->text('review_comments')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('project_proposals');
    }
};