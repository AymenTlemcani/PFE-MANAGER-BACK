
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id('notification_id');
            $table->foreignId('user_id')->constrained('users', 'user_id');
            $table->text('message');
            $table->enum('notification_type', ['Email', 'InApp']);
            $table->timestamp('sent_date');
            $table->boolean('is_read')->default(false);
            $table->string('related_entity_type');
            $table->integer('related_entity_id');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('notifications');
    }
};