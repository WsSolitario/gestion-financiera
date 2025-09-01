<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

use App\Http\Resources\UserResource;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Requests\User\UpdatePasswordRequest;

class UserController extends Controller
{
    /**
     * GET /api/users/me
     * Devuelve el perfil del usuario autenticado.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => new UserResource($request->user()),
        ], 200);
    }

    /**
     * PUT /api/users/me
     * Actualiza nombre, foto, teléfono y opcionalmente email.
     * Usa UpdateUserRequest para validar y asegurar unique:users,email,<id>.
     */
    public function update(UpdateUserRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        // Asignación explícita para evitar problemas de mass assignment si no definiste $fillable
        if (array_key_exists('name', $data)) {
            $user->name = $data['name'];
        }
        if (array_key_exists('email', $data)) {
            $user->email = $data['email'];
        }
        if (array_key_exists('profile_picture_url', $data)) {
            $user->profile_picture_url = $data['profile_picture_url'];
        }
        if (array_key_exists('phone_number', $data)) {
            $user->phone_number = $data['phone_number'];
        }

        $user->save();       // updated_at lo actualiza tu trigger
        $user->refresh();

        return response()->json([
            'message' => 'Perfil actualizado',
            'user' => new UserResource($user),
        ], 200);
    }

    /**
     * PUT /api/users/me/password
     * Cambia la contraseña verificando la actual.
     * Revoke tokens (excepto el actual) por seguridad.
     */
    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        // Verificar contraseña actual contra password_hash de tu esquema
        if (!Hash::check($data['current_password'], $user->password_hash)) {
            throw ValidationException::withMessages([
                'current_password' => ['La contraseña actual no es válida.'],
            ]);
        }

        DB::transaction(function () use ($user, $data, $request) {
            // Guardar nueva contraseña en columna password_hash (tu esquema)
            $user->password_hash = Hash::make($data['password']);
            $user->save();

            // Revocar todos los tokens EXCEPTO el token actual (si existe)
            $currentTokenId = optional($request->user()->currentAccessToken())->id;
            $user->tokens()
                ->when($currentTokenId, fn($q) => $q->where('id', '!=', $currentTokenId))
                ->delete();
        });

        return response()->json([
            'message' => 'Contraseña actualizada',
        ], 200);
    }

    /**
     * DELETE /api/users/me
     * Elimina la cuenta y revoca tokens.
     * Con tus FKs/ON DELETE CASCADE, se limpian pertenencias/participaciones.
     */
    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();

        DB::transaction(function () use ($user) {
            // Revocar todos los tokens
            $user->tokens()->delete();

            // Desactivar usuario
            $user->is_active = false;
            $user->save();
        });

        // 204 No Content: sin cuerpo de respuesta
        return response()->noContent();
    }
}
