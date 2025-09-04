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
            $table->string('title');
            $table->string('description');
            $table->decimal('amount_monthly', 10, 2);
            $table->unsignedInteger('months');
            $table->date('start_date');
            $table->unsignedTinyInteger('day_of_month');
            $table->unsignedInteger('reminder_days_before');
            $table->timestampsTz();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_payments');
    }
};
