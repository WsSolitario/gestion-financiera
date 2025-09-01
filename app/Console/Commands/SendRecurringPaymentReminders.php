<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RecurringPayment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class SendRecurringPaymentReminders extends Command
{
    protected $signature = 'recurring-payments:send-reminders';

    protected $description = 'Send reminders for upcoming recurring payments';

    public function handle(): int
    {
        $today = Carbon::today();

        RecurringPayment::query()
            ->chunk(100, function ($payments) use ($today) {
                foreach ($payments as $payment) {
                    $next = Carbon::parse($payment->start_date)->setDay($payment->day_of_month);

                    while ($next->lt($today)) {
                        $next->addMonthNoOverflow()->setDay($payment->day_of_month);
                    }

                    if ($today->diffInDays($next) == $payment->reminder_days_before) {
                        $message = "Reminder for payment {$payment->title} to user {$payment->user_id}";
                        $this->info($message);
                        Log::info($message);
                    }
                }
            });

        return Command::SUCCESS;
    }
}
