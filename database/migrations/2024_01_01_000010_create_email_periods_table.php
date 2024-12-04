<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('email_periods', function (Blueprint $table) {
            $table->id('period_id');
            $table->string('period_name')->unique();
            $table->enum('target_audience', ['Students', 'Teachers', 'Companies', 'Administrators', 'All']);
            $table->timestamp('start_date');
            $table->timestamp('closing_date');
            $table->enum('status', ['Draft', 'Active', 'Closed', 'Cancelled']);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('email_periods');
    }
};