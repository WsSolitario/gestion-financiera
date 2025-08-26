<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('description');
            $table->decimal('total_amount', 10, 2);
            $table->uuid('payer_id');
            $table->uuid('group_id');
            $table->string('ticket_image_url')->nullable();
            $table->string('ocr_status')->nullable();
            $table->text('ocr_raw_text')->nullable();
            $table->string('status')->default('pending');
            $table->date('expense_date');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('payer_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('group_id')->references('id')->on('groups')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
