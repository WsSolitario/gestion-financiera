<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use App\Models\User;
use App\Models\RecurringPayment;

class SendRecurringPaymentRemindersCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_outputs_reminder(): void
    {
        Carbon::setTestNow('2025-01-01');

        $user = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Owner',
            'email' => 'owner@example.com',
        ]);

        RecurringPayment::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'title' => 'Pago tarjeta',
            'description' => 'Pago tarjeta',
            'amount_monthly' => 100,
            'months' => 12,
            'start_date' => '2025-01-10',
            'day_of_month' => 10,
            'reminder_days_before' => 9,
        ]);

        $this->artisan('recurring-payments:send-reminders')
            ->expectsOutput("Reminder for payment Pago tarjeta to user {$user->id}")
            ->assertExitCode(0);
    }
}
