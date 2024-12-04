
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('email_period_reminders', function (Blueprint $table) {
            $table->id('reminder_id');
            $table->foreignId('period_id')->constrained('email_periods', 'period_id');
            $table->timestamp('reminder_date');
            $table->integer('reminder_number');
            $table->enum('status', ['Scheduled', 'Sent', 'Cancelled']);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('email_period_reminders');
    }
};