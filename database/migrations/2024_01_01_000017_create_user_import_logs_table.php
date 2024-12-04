
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('user_import_logs', function (Blueprint $table) {
            $table->id('import_log_id');
            $table->foreignId('imported_by')->constrained('users', 'user_id');
            $table->string('import_type');
            $table->integer('total_records_imported');
            $table->integer('successful_imports');
            $table->integer('failed_imports');
            $table->timestamp('import_date');
            $table->string('import_file_name');
            $table->string('import_status');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('user_import_logs');
    }
};