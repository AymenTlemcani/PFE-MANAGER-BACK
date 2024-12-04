
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('email_period_templates', function (Blueprint $table) {
            $table->id('template_id');
            $table->foreignId('period_id')->constrained('email_periods', 'period_id');
            $table->enum('template_type', ['Initial', 'Reminder', 'Closing']);
            $table->text('template_content');
            $table->string('subject');
            $table->enum('language', ['French', 'English']);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('email_period_templates');
    }
};