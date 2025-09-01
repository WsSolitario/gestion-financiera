<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_deactivate_account(): void
    {
        $password = 'secret123';
        $user = User::factory()->create([
            'password_hash' => Hash::make($password),
        ]);

        $token = $user->createToken('api')->plainTextToken;

        $response = $this->withToken($token)->deleteJson('/api/users/me');
        $response->assertNoContent();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_active' => false,
        ]);

        $this->withToken($token)->getJson('/api/users/me')->assertStatus(401);
    }
}
