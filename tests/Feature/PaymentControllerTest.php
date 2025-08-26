<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use App\Models\User;

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
}
