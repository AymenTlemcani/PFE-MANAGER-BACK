
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id('log_id');
            $table->foreignId('user_id')->constrained('users', 'user_id');
            $table->enum('action', ['Create', 'Update', 'Delete']);
            $table->string('table_name');
            $table->integer('record_id');
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->timestamp('timestamp');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('audit_logs');
    }
};