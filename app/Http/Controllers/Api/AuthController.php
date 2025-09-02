<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Invitation;
use App\Models\RegistrationToken;
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
     * Requiere: name, email, password, password_confirmation, registration_token
     * Opcional: invitation_token para unirse a un grupo
     */
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'                => ['required', 'string', 'max:100'],
            'email'               => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'            => ['required', 'string', 'min:8', 'confirmed'],
            'registration_token'  => ['required', 'string'],
            'invitation_token'    => ['sometimes', 'nullable', 'string'],
            'profile_picture_url' => ['sometimes', 'nullable', 'url'],
            'phone_number'        => ['sometimes', 'nullable', 'string', 'max:50'],
        ]);

        /** @var Invitation|null $invitation */
        $invitation = null;
        if (!empty($data['invitation_token'])) {
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

            $isExpired = !empty($invitation->expires_at)
                && Carbon::parse($invitation->expires_at)->isPast();

            if ($isExpired) {
                $invitation->update(['status' => 'expired']);
                throw ValidationException::withMessages([
                    'invitation_token' => ['La invitación ha expirado.'],
                ]);
            }

            if (!empty($invitation->invitee_email)
                && strcasecmp($invitation->invitee_email, $data['email']) !== 0) {
                throw ValidationException::withMessages([
                    'email' => ['El email no coincide con el de la invitación.'],
                ]);
            }
        }

        $now = now();

        /** @var \App\Models\User $user */
        $user = DB::transaction(function () use ($data, $invitation, $now) {
            /** @var RegistrationToken|null $regToken */
            $regToken = RegistrationToken::where('token', $data['registration_token'])
                ->lockForUpdate()
                ->first();

            if (! $regToken) {
                throw ValidationException::withMessages([
                    'registration_token' => ['Token de registro no encontrado.'],
                ]);
            }

            if ($regToken->status !== 'pending') {
                throw ValidationException::withMessages([
                    'registration_token' => ['El token de registro no está disponible (estado: '.$regToken->status.').'],
                ]);
            }

            $isExpiredReg = !empty($regToken->expires_at)
                && Carbon::parse($regToken->expires_at)->isPast();

            if ($isExpiredReg) {
                RegistrationToken::where('id', $regToken->id)
                    ->where('status', 'pending')
                    ->update(['status' => 'expired']);
                throw ValidationException::withMessages([
                    'registration_token' => ['El token de registro ha expirado.'],
                ]);
            }

            if (strcasecmp($regToken->email, $data['email']) !== 0) {
                throw ValidationException::withMessages([
                    'email' => ['El email no coincide con el del token de registro.'],
                ]);
            }

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

            if ($invitation) {
                $already = DB::table('group_members')
                    ->where('group_id', $invitation->group_id)
                    ->where('user_id', $user->id)
                    ->exists();

                if (! $already) {
                    DB::table('group_members')->insert([
                        'id'        => (string) Str::uuid(),
                        'group_id'  => $invitation->group_id,
                        'user_id'   => $user->id,
                        'role'      => 'member',
                        'joined_at' => $now,
                    ]);
                }

                $invitation->update([
                    'status' => 'accepted',
                ]);
            }

            RegistrationToken::where('id', $regToken->id)
                ->where('status', 'pending')
                ->update(['status' => 'used']);

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

        if (! $user->is_active) {
            return response()->json(['message' => 'Cuenta desactivada'], 403);
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
