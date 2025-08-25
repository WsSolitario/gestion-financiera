<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            if (!Schema::hasColumn('expenses', 'ticket_image_url')) {
                $table->text('ticket_image_url')->nullable();
            }
            if (!Schema::hasColumn('expenses', 'ocr_status')) {
                $table->string('ocr_status', 32)->default('pending');
            }
            if (!Schema::hasColumn('expenses', 'ocr_raw_text')) {
                $table->text('ocr_raw_text')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            if (Schema::hasColumn('expenses', 'ocr_raw_text')) {
                $table->dropColumn('ocr_raw_text');
            }
            if (Schema::hasColumn('expenses', 'ocr_status')) {
                $table->dropColumn('ocr_status');
            }
            if (Schema::hasColumn('expenses', 'ticket_image_url')) {
                $table->dropColumn('ticket_image_url');
            }
        });
    }
};
