<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use App\Models\User;

class RecurringPaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_and_list_their_recurring_payment(): void
    {
        $owner = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Owner',
            'email' => 'owner@example.com',
        ]);

        $this->actingAs($owner, 'sanctum');

        $payload = [
            'title' => 'Pago tarjeta',
            'description' => 'Pago tarjeta',
            'amount_monthly' => 100.50,
            'months' => 12,
            'start_date' => '2025-01-01',
            'day_of_month' => 1,
            'reminder_days_before' => 3,
        ];

        $resp = $this->postJson('/api/recurring-payments', $payload);
        $resp->assertStatus(201)->assertJsonPath('data.title', 'Pago tarjeta');

        $listOwner = $this->getJson('/api/recurring-payments');
        $listOwner->assertStatus(200)->assertJsonFragment(['description' => 'Pago tarjeta']);
    }

    public function test_other_users_cannot_access_recurring_payments(): void
    {
        $owner = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Owner',
            'email' => 'owner@example.com',
        ]);

        $other = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Other',
            'email' => 'other@example.com',
        ]);

        $this->actingAs($owner, 'sanctum');

        $payload = [
            'title' => 'Pago tarjeta',
            'description' => 'Pago tarjeta',
            'amount_monthly' => 100.50,
            'months' => 12,
            'start_date' => '2025-01-01',
            'day_of_month' => 1,
            'reminder_days_before' => 3,
        ];

        $this->postJson('/api/recurring-payments', $payload)->assertStatus(201);

        $this->actingAs($other, 'sanctum');
        $list = $this->getJson('/api/recurring-payments');
        $list->assertStatus(200)->assertJsonCount(0)->assertJsonMissing(['description' => 'Pago tarjeta']);
    }
}
