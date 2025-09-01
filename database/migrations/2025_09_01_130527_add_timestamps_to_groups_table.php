<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            if (!Schema::hasColumn('groups', 'created_at')) {
                $table->timestampTz('created_at')->nullable();
            }
            if (!Schema::hasColumn('groups', 'updated_at')) {
                $table->timestampTz('updated_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            if (Schema::hasColumn('groups', 'updated_at')) {
                $table->dropColumn('updated_at');
            }
            if (Schema::hasColumn('groups', 'created_at')) {
                $table->dropColumn('created_at');
            }
        });
    }
};
