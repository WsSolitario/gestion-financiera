<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use App\Models\User;

class RecurringPaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_and_share_recurring_payment(): void
    {
        $owner = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Owner',
            'email' => 'owner@example.com',
        ]);

        $viewer = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Viewer',
            'email' => 'viewer@example.com',
        ]);

        $this->actingAs($owner, 'sanctum');

        $payload = [
            'description' => 'Pago tarjeta',
            'amount_monthly' => 100.50,
            'months' => 12,
            'shared_with' => [$viewer->id],
        ];

        $resp = $this->postJson('/api/recurring-payments', $payload);
        $resp->assertStatus(201)->assertJsonPath('data.description', 'Pago tarjeta');

        // Owner can list it
        $listOwner = $this->getJson('/api/recurring-payments');
        $listOwner->assertStatus(200)->assertJsonFragment(['description' => 'Pago tarjeta']);

        // Viewer can also list it
        $this->actingAs($viewer, 'sanctum');
        $listViewer = $this->getJson('/api/recurring-payments');
        $listViewer->assertStatus(200)->assertJsonFragment(['description' => 'Pago tarjeta']);
    }
}
