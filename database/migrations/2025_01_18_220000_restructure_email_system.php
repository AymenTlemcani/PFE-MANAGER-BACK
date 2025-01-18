<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // Drop existing email-related tables
        Schema::dropIfExists('email_period_reminders');
        Schema::dropIfExists('email_period_templates');
        Schema::dropIfExists('email_periods');
        Schema::dropIfExists('email_templates');

        // Create campaigns table (replaces email_periods)
        Schema::create('email_campaigns', function (Blueprint $table) {
            $table->id('campaign_id');
            $table->string('name')->unique();
            $table->enum('type', ['Notification', 'Reminder', 'System']);
            $table->enum('target_audience', ['Students', 'Teachers', 'Companies', 'Administrators', 'All']);
            $table->timestamp('start_date');
            $table->timestamp('end_date');
            $table->enum('status', ['Draft', 'Active', 'Completed', 'Cancelled'])->default('Draft');
            $table->timestamps();
        });

        // Create templates table
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id('template_id');
            $table->string('name')->unique();
            $table->string('subject');
            $table->text('content');
            $table->string('description')->nullable();
            $table->json('placeholders')->nullable();
            $table->enum('type', ['System', 'Notification', 'Reminder'])->default('System');
            $table->enum('language', ['French', 'English'])->default('French');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Create email tracking table
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id('log_id');
            $table->foreignId('campaign_id')->nullable()->constrained('email_campaigns', 'campaign_id')->onDelete('set null');
            $table->foreignId('template_id')->constrained('email_templates', 'template_id')->onDelete('cascade');
            $table->string('recipient_email');
            $table->foreignId('user_id')->nullable()->constrained('users', 'user_id')->onDelete('set null');
            $table->timestamp('sent_at')->nullable();
            $table->enum('status', ['Pending', 'Sent', 'Failed'])->default('Pending');
            $table->text('error_message')->nullable();
            $table->json('template_data')->nullable();
            $table->timestamps();
        });

        // Create schedule table for reminders
        Schema::create('reminder_schedules', function (Blueprint $table) {
            $table->id('schedule_id');
            $table->foreignId('campaign_id')->constrained('email_campaigns', 'campaign_id')->onDelete('cascade');
            $table->foreignId('template_id')->constrained('email_templates', 'template_id')->onDelete('cascade');
            $table->integer('days_before_deadline');
            $table->time('send_time')->default('09:00:00');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('reminder_schedules');
        Schema::dropIfExists('email_logs');
        Schema::dropIfExists('email_templates');
        Schema::dropIfExists('email_campaigns');

        // Recreate original tables if needed
        // ...
    }
};
