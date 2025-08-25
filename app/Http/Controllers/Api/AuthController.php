<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Invitation;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Http\Controllers\Controller;

class AuthController extends Controller
{
    /**
     * POST /api/auth/register
     * Requiere: name, email, password, password_confirmation, invitation_token
     */
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'                => ['required', 'string', 'max:100'],
            'email'               => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'            => ['required', 'string', 'min:8', 'confirmed'],
            'invitation_token'    => ['required', 'string'],
            'profile_picture_url' => ['sometimes', 'nullable', 'url'],
            'phone_number'        => ['sometimes', 'nullable', 'string', 'max:50'],
        ]);

        /** @var Invitation|null $invitation */
        $invitation = Invitation::where('token', $data['invitation_token'])->first();

        if (! $invitation) {
            throw ValidationException::withMessages([
                'invitation_token' => ['Token de invitación no encontrado.'],
            ]);
        }

        if ($invitation->status !== 'pending') {
            throw ValidationException::withMessages([
                'invitation_token' => ['La invitación no está disponible (estado: '.$invitation->status.').'],
            ]);
        }

        // Expira solo si existe expires_at y ya pasó
        $isExpired = !empty($invitation->expires_at)
            && Carbon::parse($invitation->expires_at)->isPast();

        if ($isExpired) {
            $invitation->update(['status' => 'expired']);
            throw ValidationException::withMessages([
                'invitation_token' => ['La invitación ha expirado.'],
            ]);
        }

        // Si invitee_email está seteado, debe coincidir con el email de registro
        if (!empty($invitation->invitee_email)
            && strcasecmp($invitation->invitee_email, $data['email']) !== 0) {
            throw ValidationException::withMessages([
                'email' => ['El email no coincide con el de la invitación.'],
            ]);
        }

        $now = now();

        /** @var \App\Models\User $user */
        $user = DB::transaction(function () use ($data, $invitation, $now) {
            // Generar UUID explícito para evitar user_id NULL en pivots
            $user                 = new User();
            $user->id             = (string) Str::uuid();
            $user->name           = $data['name'];
            $user->email          = $data['email'];

            // ⬅️ Usa la columna de tu esquema
            $user->password_hash  = Hash::make($data['password']);

            $user->profile_picture_url = $data['profile_picture_url'] ?? null;
            $user->phone_number        = $data['phone_number'] ?? null;

            // Si tu tabla tiene defaults/triggers para created_at/updated_at puedes omitir:
            // $user->created_at = $now;
            // $user->updated_at = $now;

            $user->save();

            // Insertar relación en group_members
            $already = DB::table('group_members')
                ->where('group_id', $invitation->group_id)
                ->where('user_id', $user->id)
                ->exists();

            if (! $already) {
                DB::table('group_members')->insert([
                    'id'        => DB::raw('gen_random_uuid()'),
                    'group_id'  => $invitation->group_id,
                    'user_id'   => $user->id,
                    'role'      => 'member',
                    'joined_at' => $now,
                ]);
            }

            // Marcar invitación como aceptada (solo campos existentes)
            $invitation->update([
                'status' => 'accepted',
            ]);

            return $user;
        });

        // Token Sanctum
        $deviceName = $request->header('X-Device-Name', 'api');
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'message' => 'Registro exitoso',
            'token'   => $token,
            'user'    => new UserResource($user),
        ], 201);
    }

    /**
     * POST /api/auth/login
     * Requiere: email, password
     */
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        /** @var User|null $user */
        $user = User::whereRaw('LOWER(email) = ?', [mb_strtolower($data['email'])])->first();

        // ⬅️ Verifica contra password_hash (según tu esquema)
        if (! $user || ! Hash::check($data['password'], $user->password_hash)) {
            return response()->json(['message' => 'Credenciales inválidas'], 401);
        }

        $deviceName = $request->header('X-Device-Name', 'api');
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'message' => 'Login correcto',
            'token'   => $token,
            'user'    => new UserResource($user),
        ], 200);
    }

    /**
     * POST /api/auth/logout
     * ?all=true para cerrar sesión en todos los dispositivos.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($request->boolean('all')) {
            $user->tokens()->delete();
        } else {
            $token = $user->currentAccessToken();
            if ($token) {
                $token->delete();
            }
        }

        return response()->json(['message' => 'Sesión cerrada'], 200);
    }
}
