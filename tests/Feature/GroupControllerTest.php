<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class GroupControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_group(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/groups', [
            'name' => 'Mi Grupo',
            'description' => 'Descripcion',
            'profile_picture_url' => 'https://example.com/pic.png',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Grupo creado',
                'group' => [
                    'name' => 'Mi Grupo',
                    'description' => 'Descripcion',
                    'owner_id' => $user->id,
                    'profile_picture_url' => 'https://example.com/pic.png',
                ],
            ]);
    }

    public function test_owner_can_update_group(): void
    {
        $user = User::factory()->create();
        $group = Group::factory()->create(['owner_id' => $user->id]);
        DB::table('group_members')->insert([
            'id' => (string) Str::uuid(),
            'group_id' => $group->id,
            'user_id' => $user->id,
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->putJson('/api/groups/' . $group->id, [
            'description' => 'Actualizado',
            'profile_picture_url' => 'https://example.com/updated.png',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Grupo actualizado',
                'group' => [
                    'id' => $group->id,
                    'description' => 'Actualizado',
                    'profile_picture_url' => 'https://example.com/updated.png',
                ],
            ]);
    }

    public function test_group_retrieval_returns_fallback_profile_picture_url_when_missing(): void
    {
        $user = User::factory()->create();
        $group = Group::factory()->create([
            'owner_id' => $user->id,
            'profile_picture_url' => null,
        ]);
        DB::table('group_members')->insert([
            'id' => (string) Str::uuid(),
            'group_id' => $group->id,
            'user_id' => $user->id,
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        $this->actingAs($user, 'sanctum');

        $expected = 'https://api.dicebear.com/7.x/initials/svg?seed=' . urlencode($group->name);

        $this->getJson('/api/groups')
            ->assertStatus(200)
            ->assertJsonPath('data.0.profile_picture_url', $expected);

        $this->getJson('/api/groups/' . $group->id)
            ->assertStatus(200)
            ->assertJsonPath('group.profile_picture_url', $expected);
    }

    public function test_group_retrieval_returns_custom_profile_picture_url_when_present(): void
    {
        $user = User::factory()->create();
        $group = Group::factory()->create([
            'owner_id' => $user->id,
            'profile_picture_url' => 'https://example.com/custom.png',
        ]);
        DB::table('group_members')->insert([
            'id' => (string) Str::uuid(),
            'group_id' => $group->id,
            'user_id' => $user->id,
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        $this->actingAs($user, 'sanctum');

        $this->getJson('/api/groups')
            ->assertStatus(200)
            ->assertJsonPath('data.0.profile_picture_url', 'https://example.com/custom.png');

        $this->getJson('/api/groups/' . $group->id)
            ->assertStatus(200)
            ->assertJsonPath('group.profile_picture_url', 'https://example.com/custom.png');
    }

    public function test_show_returns_profile_picture_url_for_members(): void
    {
        $owner = User::factory()->create([
            'profile_picture_url' => 'https://example.com/owner.png',
        ]);
        $member = User::factory()->create([
            'profile_picture_url' => 'https://example.com/member.png',
        ]);

        $group = Group::factory()->create(['owner_id' => $owner->id]);

        DB::table('group_members')->insert([
            [
                'id' => (string) Str::uuid(),
                'group_id' => $group->id,
                'user_id' => $owner->id,
                'role' => 'owner',
                'joined_at' => now(),
            ],
            [
                'id' => (string) Str::uuid(),
                'group_id' => $group->id,
                'user_id' => $member->id,
                'role' => 'member',
                'joined_at' => now(),
            ],
        ]);

        $this->actingAs($owner, 'sanctum');

        $response = $this->getJson('/api/groups/' . $group->id);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'user_id' => $owner->id,
            'profile_picture_url' => 'https://example.com/owner.png',
        ]);
        $response->assertJsonFragment([
            'user_id' => $member->id,
            'profile_picture_url' => 'https://example.com/member.png',
        ]);
        $response->assertJsonCount(2, 'members');
    }
}
