<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Database\QueryException;

class NotificationController extends Controller
{
    /**
     * POST /api/notifications/register-device
     * Body:
     *  - device_token: string (requerido)
     *  - device_type:  'android' | 'ios' | 'web' (requerido)
     *
     * Comportamiento:
     *  - Si el token NO existe => lo inserta para el usuario actual (201).
     *  - Si el token existe para el MISMO usuario => actualiza device_type si cambió (200).
     *  - Si el token existe para OTRO usuario => lo reasigna al usuario actual (200).
     */
    public function registerDevice(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'device_token' => ['required', 'string', 'max:4096'],
            'device_type'  => ['required', Rule::in(['android', 'ios', 'web'])],
        ]);

        $token = $data['device_token'];
        $type  = $data['device_type'];

        $existing = DB::table('user_devices')->where('device_token', $token)->first();

        if ($existing) {
            // Si ya existe este token, actualizamos propietario y/o tipo si es necesario
            $needsUpdate =
                ($existing->user_id !== $user->id) ||
                ($existing->device_type !== $type);

            if ($needsUpdate) {
                DB::table('user_devices')
                    ->where('id', $existing->id)
                    ->update([
                        'user_id'     => $user->id,
                        'device_type' => $type,
                        // La tabla no tiene updated_at; mantenemos created_at como histórico
                    ]);
            }

            $total = DB::table('user_devices')->where('user_id', $user->id)->count();

            return response()->json([
                'message' => $needsUpdate
                    ? 'Dispositivo actualizado'
                    : 'Dispositivo ya estaba registrado',
                'device'  => [
                    'id'           => $existing->id,
                    'user_id'      => $user->id,
                    'device_token' => $token,
                    'device_type'  => $type,
                ],
                'stats' => [
                    'total_devices_for_user' => $total,
                ],
            ], 200);
        }

        // Insertar nuevo registro
        $id = (string) Str::uuid();
        try {
            DB::table('user_devices')->insert([
                'id'           => $id,
                'user_id'      => $user->id,
                'device_token' => $token,
                'device_type'  => $type,
                'created_at'   => now(),
            ]);
        } catch (QueryException $e) {
            if ($e->getCode() !== '23505') {
                throw $e;
            }

            // Otro proceso insertó el mismo token antes; intentamos actualizar
            $existing = DB::table('user_devices')->where('device_token', $token)->first();

            $needsUpdate =
                ($existing->user_id !== $user->id) ||
                ($existing->device_type !== $type);

            if ($needsUpdate) {
                DB::table('user_devices')
                    ->where('id', $existing->id)
                    ->update([
                        'user_id'     => $user->id,
                        'device_type' => $type,
                    ]);
            }

            $total = DB::table('user_devices')->where('user_id', $user->id)->count();

            return response()->json([
                'message' => $needsUpdate
                    ? 'Dispositivo actualizado'
                    : 'Dispositivo ya estaba registrado',
                'device'  => [
                    'id'           => $existing->id,
                    'user_id'      => $user->id,
                    'device_token' => $token,
                    'device_type'  => $type,
                ],
                'stats' => [
                    'total_devices_for_user' => $total,
                ],
            ], 200);
        }

        $total = DB::table('user_devices')->where('user_id', $user->id)->count();

        return response()->json([
            'message' => 'Dispositivo registrado',
            'device'  => [
                'id'           => $id,
                'user_id'      => $user->id,
                'device_token' => $token,
                'device_type'  => $type,
            ],
            'stats' => [
                'total_devices_for_user' => $total,
            ],
        ], 201);
    }
}
