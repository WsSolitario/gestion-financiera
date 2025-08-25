<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DevSeeder extends Seeder
{
    public function run(): void
    {
        $ownerId = (string) Str::uuid();
        DB::table('users')->insert([
            'id' => $ownerId,
            'name' => 'Owner Demo',
            'email' => 'owner@example.com',
            'password_hash' => Hash::make('secret1234'),
            'profile_picture_url' => null,
            'phone_number' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $groupId = (string) Str::uuid();
        DB::table('groups')->insert([
            'id' => $groupId,
            'name' => 'Viaje Demo',
            'description' => 'Grupo de prueba',
            'owner_id' => $ownerId,
            'created_at' => now(),
        ]);

        DB::table('group_members')->insert([
            'id' => (string) Str::uuid(),
            'group_id' => $groupId,
            'user_id' => $ownerId,
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        DB::table('invitations')->insert([
            'id'            => (string) Str::uuid(),
            'inviter_id'    => $ownerId,
            'invitee_email' => 'newuser@example.com',
            'group_id'      => $groupId,
            'token'         => $token = Str::random(64),
            'status'        => 'pending',
            'expires_at'    => now()->addDays(7),
        ]);

        echo "\nToken de invitación para newuser@example.com:\n$token\n\n";
    }
}
