
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('defense_sessions', function (Blueprint $table) {
            $table->id('session_id');
            $table->foreignId('project_id')->constrained('projects', 'project_id');
            $table->string('room');
            $table->date('date');
            $table->time('time');
            $table->integer('duration');
            $table->enum('status', ['Scheduled', 'Completed', 'Cancelled']);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('defense_sessions');
    }
};