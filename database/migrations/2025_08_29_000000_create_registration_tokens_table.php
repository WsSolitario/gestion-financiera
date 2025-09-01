<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('email');
            $table->string('token', 64)->unique();
            $table->dateTimeTz('expires_at')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_tokens');
    }
};
