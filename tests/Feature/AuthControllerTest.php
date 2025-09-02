<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_with_invitation(): void
    {
        $owner = User::factory()->create();
        $group = Group::factory()->create(['owner_id' => $owner->id]);

        DB::table('group_members')->insert([
            'id' => (string) Str::uuid(),
            'group_id' => $group->id,
            'user_id' => $owner->id,
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        $inviteEmail = 'invitee@example.com';
        $this->actingAs($owner, 'sanctum');
        $invResponse = $this->postJson('/api/invitations', [
            'invitee_email' => $inviteEmail,
            'group_id' => $group->id,
        ])->assertStatus(201);

        $token     = $invResponse->json('invitation.token');
        $regToken  = $invResponse->json('registration_token.token');

        $registerResponse = $this->postJson('/api/auth/register', [
            'name' => 'Invited User',
            'email' => $inviteEmail,
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'registration_token' => $regToken,
            'invitation_token' => $token,
        ]);

        $registerResponse->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'token',
                'user' => ['id', 'name', 'email'],
            ]);

        $user = User::where('email', $inviteEmail)->first();

        $this->assertDatabaseHas('group_members', [
            'group_id' => $group->id,
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('invitations', [
            'token' => $token,
            'status' => 'accepted',
        ]);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $password = 'secret123';
        $user = User::factory()->create([
            'password_hash' => Hash::make($password),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => $password,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'token',
                'user' => ['id', 'name', 'email'],
            ]);
    }

    public function test_inactive_user_cannot_login(): void
    {
        $password = 'secret123';
        $user = User::factory()->create([
            'password_hash' => Hash::make($password),
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => $password,
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Cuenta desactivada',
            ]);
    }

    public function test_login_is_throttled_after_too_many_attempts(): void
    {
        Cache::flush();

        $password = 'secret123';
        $user = User::factory()->create([
            'password_hash' => Hash::make($password),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ])->assertStatus(401);
        }

        $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertStatus(429);
    }
}
