<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\User;
use App\Models\RegistrationToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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

    public function test_registration_token_is_consumed_only_once_concurrently(): void
    {
        $email = 'concurrent@example.com';
        $regToken = RegistrationToken::create([
            'id' => (string) Str::uuid(),
            'email' => $email,
            'token' => Str::random(40),
            'status' => 'pending',
        ]);

        $payload = [
            'name' => 'Test User',
            'email' => $email,
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'registration_token' => $regToken->token,
        ];

        $tempFile = tempnam(sys_get_temp_dir(), 'child');

        $pid = pcntl_fork();
        if ($pid === 0) {
            DB::disconnect();
            DB::reconnect();
            $response = $this->postJson('/api/auth/register', $payload);
            file_put_contents($tempFile, (string) $response->status());
            exit(0);
        }

        $responseParent = $this->postJson('/api/auth/register', $payload);
        pcntl_wait($status);
        $childStatus = (int) file_get_contents($tempFile);
        @unlink($tempFile);

        $this->assertEqualsCanonicalizing([201, 422], [$responseParent->status(), $childStatus]);
        $this->assertSame('used', $regToken->fresh()->status);
        $this->assertEquals(1, User::where('email', $email)->count());
    }
}
