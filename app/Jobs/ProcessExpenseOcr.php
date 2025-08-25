<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessExpenseOcr implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $expenseId;

    public function __construct(string $expenseId)
    {
        $this->expenseId = $expenseId;
    }

    public function handle(): void
    {
        // ⚠️ Aquí iría tu integración real con OCR.
        // Por ahora, simulamos que “leyó” el ticket y encontró algo.
        $fakeRawText = "OCR OK. Texto de ejemplo.\nTOTAL: 123.45";

        DB::table('expenses')
            ->where('id', $this->expenseId)
            ->update([
                'ocr_status'   => 'completed',
                'ocr_raw_text' => $fakeRawText,
                'updated_at'   => now(),
            ]);

        Log::info('[OCR] Expense procesado', ['expense_id' => $this->expenseId]);
    }
}
