<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Rename columns
        DB::statement('ALTER TABLE payments DROP CONSTRAINT IF EXISTS payments_payer_id_fkey');
        DB::statement('ALTER TABLE payments DROP CONSTRAINT IF EXISTS payments_receiver_id_fkey');
        DB::statement('DROP INDEX IF EXISTS idx_payments_payer');
        DB::statement('DROP INDEX IF EXISTS idx_payments_recv');
        DB::statement('ALTER TABLE payments RENAME COLUMN payer_id TO from_user_id');
        DB::statement('ALTER TABLE payments RENAME COLUMN receiver_id TO to_user_id');

        // Enum payment_status
        DB::statement("ALTER TYPE payment_status RENAME TO payment_status_old");
        DB::statement("CREATE TYPE payment_status AS ENUM ('pending','approved','rejected')");
        DB::statement("ALTER TABLE payments ALTER COLUMN status TYPE payment_status USING status::text::payment_status");
        DB::statement("DROP TYPE payment_status_old");

        // Enum ocr_processing_status
        DB::statement("ALTER TYPE ocr_processing_status RENAME TO ocr_processing_status_old");
        DB::statement("CREATE TYPE ocr_processing_status AS ENUM ('pending','processing','done','failed')");
        DB::statement("ALTER TABLE expenses ALTER COLUMN ocr_status TYPE ocr_processing_status USING ocr_status::text::ocr_processing_status");
        DB::statement("DROP TYPE ocr_processing_status_old");

        Schema::table('payments', function (Blueprint $table) {
            $table->uuid('group_id')->nullable()->after('to_user_id');
            $table->text('note')->nullable();
            $table->text('evidence_url')->nullable();
            $table->decimal('unapplied_amount', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreign('group_id')->references('id')->on('groups')->onDelete('cascade');
            $table->foreign('from_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('to_user_id')->references('id')->on('users')->onDelete('set null');
        });

        DB::statement('CREATE INDEX payments_group_from_to_status ON payments(group_id, from_user_id, to_user_id, status)');

        Schema::table('expenses', function (Blueprint $table) {
            $table->index(['group_id', 'status', 'expense_date'], 'expenses_group_status_date');
        });

        Schema::table('expense_participants', function (Blueprint $table) {
            $table->index('expense_id', 'expense_participants_expense_id_idx');
            $table->index(['user_id', 'is_paid'], 'expense_participants_user_paid_idx');
        });
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS payments_group_from_to_status');
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
            $table->dropForeign(['from_user_id']);
            $table->dropForeign(['to_user_id']);
            $table->dropColumn(['group_id', 'note', 'evidence_url', 'unapplied_amount']);
            $table->dropTimestamps();
        });

        DB::statement('ALTER TABLE payments RENAME COLUMN from_user_id TO payer_id');
        DB::statement('ALTER TABLE payments RENAME COLUMN to_user_id TO receiver_id');
        DB::statement('CREATE INDEX idx_payments_payer ON payments(payer_id)');
        DB::statement('CREATE INDEX idx_payments_recv ON payments(receiver_id)');
        DB::statement('ALTER TABLE payments ADD CONSTRAINT payments_payer_id_fkey FOREIGN KEY (payer_id) REFERENCES users(id) ON DELETE SET NULL');
        DB::statement('ALTER TABLE payments ADD CONSTRAINT payments_receiver_id_fkey FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE SET NULL');

        // revert enums
        DB::statement("CREATE TYPE payment_status_old AS ENUM ('pending','completed','failed')");
        DB::statement("ALTER TABLE payments ALTER COLUMN status TYPE payment_status_old USING status::text::payment_status_old");
        DB::statement("DROP TYPE payment_status");
        DB::statement("ALTER TYPE payment_status_old RENAME TO payment_status");

        DB::statement("CREATE TYPE ocr_processing_status_old AS ENUM ('pending','completed','failed','skipped')");
        DB::statement("ALTER TABLE expenses ALTER COLUMN ocr_status TYPE ocr_processing_status_old USING ocr_status::text::ocr_processing_status_old");
        DB::statement("DROP TYPE ocr_processing_status");
        DB::statement("ALTER TYPE ocr_processing_status_old RENAME TO ocr_processing_status");

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex('expenses_group_status_date');
        });
        Schema::table('expense_participants', function (Blueprint $table) {
            $table->dropIndex('expense_participants_expense_id_idx');
            $table->dropIndex('expense_participants_user_paid_idx');
        });
    }
};
