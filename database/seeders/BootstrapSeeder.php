<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;

class BootstrapSeeder extends Seeder
{
    public function run(): void
    {
        // Si ya hay usuarios, no hacemos nada.
        if (DB::table('users')->count() > 0) {
            $this->command?->warn('BootstrapSeeder: ya existen usuarios. Saliendo sin cambios.');
            return;
        }

        // Puedes sobreescribir por .env
        $ownerName  = env('BOOTSTRAP_OWNER_NAME',  'Owner');
        $ownerEmail = env('BOOTSTRAP_OWNER_EMAIL', 'owner@test.com');
        $ownerPass  = env('BOOTSTRAP_OWNER_PASSWORD', 'Password123!');
        $groupName  = env('BOOTSTRAP_GROUP_NAME',  'Casa Roomies');
        $groupDesc  = env('BOOTSTRAP_GROUP_DESC',  'Grupo inicial');

        // ===== Usuario Owner =====
        $ownerId = (string) Str::uuid();

        DB::table('users')->insert([
            'id'                 => $ownerId,
            'name'               => $ownerName,
            'email'              => $ownerEmail,
            // Tu esquema usa password_hash
            'password_hash'      => Hash::make($ownerPass),
            'profile_picture_url'=> null,
            'phone_number'       => null,
            'is_active'          => true,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        // ===== Grupo inicial =====
        $groupId = (string) Str::uuid();
        DB::table('groups')->insert([
            'id'          => $groupId,
            'name'        => $groupName,
            'description' => $groupDesc,
            'owner_id'    => $ownerId,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        // (Opcional) también en group_members con rol owner si existe la tabla
        if (DB::getSchemaBuilder()->hasTable('group_members')) {
            DB::table('group_members')->insert([
                'id'        => (string) Str::uuid(),
                'group_id'  => $groupId,
                'user_id'   => $ownerId,
                'role'      => 'owner',
                'joined_at' => now(),
            ]);
        }

        // ===== Token Sanctum =====
        // Asegúrate que App\Models\User use HasApiTokens
        $owner = User::where('id', $ownerId)->first();
        $token = $owner->createToken('bootstrap')->plainTextToken;

        $this->command?->info('================ Bootstrap listo ================');
        $this->command?->info('Owner: ' . $ownerEmail);
        $this->command?->info('Password: ' . $ownerPass);
        $this->command?->info('Grupo inicial: ' . $groupName . ' (ID: ' . $groupId . ')');
        $this->command?->info('Sanctum Token (Bearer):');
        $this->command?->line($token);

        // Guardar token a archivo
        @file_put_contents(storage_path('app/bootstrap_owner_token.txt'), $token . PHP_EOL);
        $this->command?->info('Token guardado en: storage/app/bootstrap_owner_token.txt');
    }
}
