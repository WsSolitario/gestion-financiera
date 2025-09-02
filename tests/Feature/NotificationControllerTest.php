<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Illuminate\Support\Str;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_device_token_is_unique(): void
    {
        $user1 = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'User One',
            'email' => 'one@example.com',
        ]);

        $user2 = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'User Two',
            'email' => 'two@example.com',
        ]);

        $token = 'tok-123';

        $this->actingAs($user1, 'sanctum');
        $this->postJson('/api/notifications/register-device', [
            'device_token' => $token,
            'device_type' => 'android',
        ])->assertStatus(201);

        $this->actingAs($user2, 'sanctum');
        $this->postJson('/api/notifications/register-device', [
            'device_token' => $token,
            'device_type' => 'android',
        ])->assertStatus(200)
          ->assertJson(['message' => 'Dispositivo actualizado']);

        $this->assertDatabaseCount('user_devices', 1);
        $this->assertDatabaseHas('user_devices', [
            'device_token' => $token,
            'user_id' => $user2->id,
            'device_type' => 'android',
        ]);
    }
}
