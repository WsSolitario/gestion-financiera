<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use App\Jobs\SendPushNotification;

class PaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_empty_list(): void
    {
        $user = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Tester',
            'email' => 'tester@example.com',
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/payments');

        $response->assertStatus(200)
                 ->assertJson([
                     'data' => [],
                 ]);
    }

    public function test_approve_dispatches_push_job(): void
    {
        Bus::fake();

        $payer = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Payer',
            'email' => 'payer@example.com',
        ]);

        $receiver = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Receiver',
            'email' => 'receiver@example.com',
        ]);

        $paymentId = (string) Str::uuid();

        DB::table('payments')->insert([
            'id' => $paymentId,
            'from_user_id' => $payer->id,
            'to_user_id' => $receiver->id,
            'amount' => 10,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($receiver, 'sanctum');

        $response = $this->postJson("/api/payments/{$paymentId}/approve");

        $response->assertStatus(200);

        Bus::assertDispatched(SendPushNotification::class, function ($job) use ($payer) {
            return $job->userId === $payer->id && $job->title === 'Pago aprobado';
        });
    }

    public function test_update_accepts_evidence_url(): void
    {
        $payer = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Payer',
            'email' => 'payer@example.com',
        ]);

        $receiver = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Receiver',
            'email' => 'receiver@example.com',
        ]);

        $paymentId = (string) Str::uuid();

        DB::table('payments')->insert([
            'id' => $paymentId,
            'from_user_id' => $payer->id,
            'to_user_id' => $receiver->id,
            'amount' => 50,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($payer, 'sanctum');

        $url = 'https://example.com/comprobante.jpg';

        $response = $this->putJson("/api/payments/{$paymentId}", [
            'evidence_url' => $url,
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('payment.evidence_url', $url);

        $this->assertDatabaseHas('payments', [
            'id' => $paymentId,
            'evidence_url' => $url,
        ]);
    }
}
