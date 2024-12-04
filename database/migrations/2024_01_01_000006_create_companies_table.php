<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('companies', function (Blueprint $table) {
            $table->id('company_id');
            $table->foreignId('user_id')->constrained('users', 'user_id')->unique();
            $table->string('company_name');
            $table->string('contact_name');
            $table->string('contact_surname');
            $table->string('industry');
            $table->text('address');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('companies');
    }
};