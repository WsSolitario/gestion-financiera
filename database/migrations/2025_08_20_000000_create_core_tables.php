<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('profile_picture_url')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('password_hash')->nullable();
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('description');
            $table->decimal('total_amount', 10, 2);
            $table->uuid('payer_id');
            $table->uuid('group_id')->nullable();
            $table->string('ticket_image_url')->nullable();
            $table->string('ocr_status')->default('pending');
            $table->string('ocr_raw_text')->nullable();
            $table->string('status')->default('pending');
            $table->date('expense_date');
            $table->timestamps();
        });

        Schema::create('expense_participants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('expense_id');
            $table->uuid('user_id');
            $table->decimal('amount_due', 10, 2);
            $table->boolean('is_paid')->default(false);
            $table->uuid('payment_id')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('payer_id');
            $table->uuid('receiver_id');
            $table->decimal('amount', 10, 2);
            $table->string('payment_method')->nullable();
            $table->string('proof_url')->nullable();
            $table->string('signature')->nullable();
            $table->string('status');
            $table->dateTime('payment_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('expense_participants');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('users');
    }
};
