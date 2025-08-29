<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('description');
            $table->decimal('amount_monthly', 10, 2);
            $table->unsignedInteger('months');
            $table->timestampsTz();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('recurring_payment_viewers', function (Blueprint $table) {
            $table->uuid('recurring_payment_id');
            $table->uuid('user_id');
            $table->primary(['recurring_payment_id', 'user_id']);
            $table->foreign('recurring_payment_id')->references('id')->on('recurring_payments')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_payment_viewers');
        Schema::dropIfExists('recurring_payments');
    }
};
