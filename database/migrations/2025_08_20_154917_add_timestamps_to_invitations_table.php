<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invitations', function (Blueprint $table) {
            // Solo si NO existen; si ya existen, no pasa nada
            if (!Schema::hasColumn('invitations', 'created_at')) {
                $table->timestampTz('created_at')->nullable();
            }
            if (!Schema::hasColumn('invitations', 'updated_at')) {
                $table->timestampTz('updated_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('invitations', function (Blueprint $table) {
            if (Schema::hasColumn('invitations', 'created_at')) {
                $table->dropColumn('created_at');
            }
            if (Schema::hasColumn('invitations', 'updated_at')) {
                $table->dropColumn('updated_at');
            }
        });
    }
};
