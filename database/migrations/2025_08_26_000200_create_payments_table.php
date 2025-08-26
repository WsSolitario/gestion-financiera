<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('payer_id');
            $table->uuid('receiver_id');
            $table->decimal('amount', 10, 2);
            $table->string('payment_method');
            $table->string('proof_url')->nullable();
            $table->text('signature')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('payment_date')->useCurrent();

            $table->foreign('payer_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('receiver_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
