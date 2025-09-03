<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Services\BrevoClient;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class InvitationController extends Controller
{
    public function __construct()
    {
        // verifyToken es público; el resto requiere auth
        $this->middleware('auth:sanctum')->except(['verifyToken']);
    }

    /**
     * GET /api/invitations
     * - ?mine=true => invitaciones dirigidas a mi email
     * - default    => invitaciones de grupos donde soy owner/admin
     * - ?groupId=UUID => filtra por grupo
     */
    public function index(Request $request): JsonResponse
    {
        $userId  = $request->user()->id;
        $email   = $request->user()->email;
        $mine    = $request->boolean('mine');
        $groupId = $request->query('groupId');

        if ($mine) {
            $q = DB::table('invitations as i')
                ->join('groups as g', 'g.id', '=', 'i.group_id')
                ->whereRaw('LOWER(i.invitee_email) = ?', [mb_strtolower($email)]);
        } else {
            $q = DB::table('invitations as i')
                ->join('groups as g', 'g.id', '=', 'i.group_id')
                ->leftJoin('group_members as gm', function ($join) use ($userId) {
                    $join->on('gm.group_id', '=', 'g.id')->where('gm.user_id', '=', $userId);
                })
                ->where(function ($q) use ($userId) {
                    $q->where('g.owner_id', $userId)->orWhereIn('gm.role', ['owner', 'admin']);
                });
        }

        $items = $q
            ->when($groupId, fn($qq) => $qq->where('i.group_id', $groupId))
            ->select('i.*', 'g.name as group_name')
            ->orderByDesc('i.expires_at')
            ->paginate(15);

        $data = collect($items->items())->map(fn($i) => $this->formatInvitation($i));

        return response()->json([
            'data' => $data,
            'pagination' => [
                'current_page' => $items->currentPage(),
                'per_page'     => $items->perPage(),
                'total'        => $items->total(),
                'last_page'    => $items->lastPage(),
            ],
        ], 200);
    }

    /**
     * POST /api/invitations
     * Body: { invitee_email, group_id, expires_in_days?=7 }
     * Requiere permiso owner/admin.
     */
    public function store(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $data = $request->validate([
            'invitee_email'   => ['required', 'email', 'max:255'],
            'group_id'        => ['required', 'uuid'],
            'expires_in_days' => ['sometimes', 'integer', 'min:1', 'max:90'],
        ]);

        if (!$this->canManageGroup($userId, $data['group_id'])) {
            return response()->json(['message' => 'No autorizado para invitar en este grupo'], 403);
        }

        // No invitar si ya es miembro del grupo
        $alreadyMember = DB::table('users as u')
            ->join('group_members as gm', 'gm.user_id', '=', 'u.id')
            ->whereRaw('LOWER(u.email) = ?', [mb_strtolower($data['invitee_email'])])
            ->where('gm.group_id', $data['group_id'])
            ->exists();

        if ($alreadyMember) {
            return response()->json(['message' => 'El usuario ya es miembro de este grupo'], 409);
        }

        // Evitar duplicar invitaciones pendientes vigentes
        $exists = DB::table('invitations')
            ->where('group_id', $data['group_id'])
            ->whereRaw('LOWER(invitee_email) = ?', [mb_strtolower($data['invitee_email'])])
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Ya existe una invitación pendiente para este email y grupo'], 409);
        }

        $token   = Str::random(64);
        $expires = now()->addDays($data['expires_in_days'] ?? 7);

        $id = (string) Str::uuid();

        DB::table('invitations')->insert([
            'id'            => $id,
            'inviter_id'    => $userId,
            'invitee_email' => $data['invitee_email'],
            'group_id'      => $data['group_id'],
            'token'         => $token,
            'status'        => 'pending',
            'expires_at'    => $expires,
        ]);

        $row = DB::table('invitations')->where('id', $id)->first();

        $registration = null;
        $userExists = DB::table('users')
            ->whereRaw('LOWER(email) = ?', [mb_strtolower($data['invitee_email'])])
            ->exists();

        if (!$userExists) {
            $regToken = Str::random(64);
            $regId    = (string) Str::uuid();

            DB::table('registration_tokens')->insert([
                'id'         => $regId,
                'email'      => $data['invitee_email'],
                'token'      => $regToken,
                'status'     => 'pending',
                'expires_at' => $expires,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $registration = [
                'token'      => $regToken,
                'expires_at' => $expires,
            ];
        }

        try {
            BrevoClient::sendInvitation($data['invitee_email'], $token);
        } catch (\Throwable $e) {
            Log::error('Error enviando invitación Brevo', ['exception' => $e->getMessage()]);
        }

        return response()->json([
            'message'           => 'Invitación creada',
            'invitation'        => $this->formatInvitation($row, true),
            'registration_token'=> $registration,
        ], 201);
    }

    /**
     * GET /api/invitations/{invitation}
     * Ver una invitación por ID (invitee u owner/admin).
     */
    public function show(string $invitationId, Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $email  = $request->user()->email;

        $inv = DB::table('invitations as i')
            ->join('groups as g', 'g.id', '=', 'i.group_id')
            ->where('i.id', $invitationId)
            ->select('i.*', 'g.name as group_name')
            ->first();

        if (!$inv) return response()->json(['message' => 'Invitación no encontrada'], 404);

        $isInvitee = (mb_strtolower($inv->invitee_email) === mb_strtolower($email));
        $canManage = $this->canManageGroup($userId, $inv->group_id);

        if (!$isInvitee && !$canManage) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        return response()->json($this->formatInvitation($inv), 200);
    }

    /**
     * DELETE /api/invitations/{invitation}
     * Marca como expirada (no borra físico).
     */
    public function destroy(string $invitationId, Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $email  = $request->user()->email;

        $inv = DB::table('invitations')->where('id', $invitationId)->first();
        if (!$inv) return response()->json(['message' => 'Invitación no encontrada'], 404);

        $isInvitee = (mb_strtolower($inv->invitee_email) === mb_strtolower($email));
        $canManage = $this->canManageGroup($userId, $inv->group_id);

        if (!$isInvitee && !$canManage) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        DB::table('invitations')->where('id', $invitationId)->update(['status' => 'expired']);

        return response()->json(null, 204);
    }

    /**
     * GET /api/invitations/token/{token}
     * Verifica token pendiente y no expirado (pública).
     */
    public function verifyToken(string $token, Request $request): JsonResponse
    {
        $inv = DB::table('invitations as i')
            ->join('groups as g', 'g.id', '=', 'i.group_id')
            ->where('i.token', $token)
            ->select('i.*', 'g.name as group_name')
            ->first();

        if (!$inv) {
            return response()->json(['message' => 'Token inválido'], 404);
        }

        $expired = $this->isExpired($inv->expires_at);
        if ($inv->status !== 'pending' || $expired) {
            if ($inv->status === 'pending' && $expired) {
                DB::table('invitations')->where('id', $inv->id)->update(['status' => 'expired']);
            }
            return response()->json(['message' => 'Invitación no válida (no pendiente o expirada)'], 422);
        }

        return response()->json($this->formatInvitation($inv), 200);
    }

    /**
     * POST /api/invitations/accept
     * Body: { token }
     * Requiere auth; el email debe coincidir con invitee_email.
     */
    public function accept(Request $request): JsonResponse
    {
        $user   = $request->user();
        $token  = $request->validate(['token' => ['required', 'string']])['token'];

        $inv = DB::table('invitations')->where('token', $token)->first();
        if (!$inv) {
            return response()->json(['message' => 'Token inválido'], 404);
        }

        $expired = $this->isExpired($inv->expires_at);
        if ($inv->status !== 'pending' || $expired) {
            if ($inv->status === 'pending' && $expired) {
                DB::table('invitations')->where('id', $inv->id)->update(['status' => 'expired']);
            }
            return response()->json(['message' => 'Invitación no válida (no pendiente o expirada)'], 422);
        }

        if (mb_strtolower($inv->invitee_email) !== mb_strtolower($user->email)) {
            return response()->json(['message' => 'El email del usuario autenticado no coincide con la invitación'], 403);
        }

        $already = DB::table('group_members')
            ->where('group_id', $inv->group_id)
            ->where('user_id', $user->id)
            ->exists();

        DB::transaction(function () use ($already, $inv, $user) {
            if (!$already) {
                DB::table('group_members')->insert([
                    'id'        => (string) Str::uuid(),
                    'group_id'  => $inv->group_id,
                    'user_id'   => $user->id,
                    'role'      => 'member',
                    'joined_at' => now(),
                ]);
            }

            DB::table('invitations')->where('id', $inv->id)->update(['status' => 'accepted']);
        });

        return response()->json([
            'message' => $already
                ? 'Ya eras miembro; invitación marcada como aceptada'
                : 'Invitación aceptada'
        ], 200);
    }

    // ===========================
    // Helpers
    // ===========================

    private function canManageGroup(string $userId, string $groupId): bool
    {
        if (DB::table('groups')->where('id', $groupId)->where('owner_id', $userId)->exists()) {
            return true;
        }

        return DB::table('group_members')
            ->where('group_id', $groupId)
            ->where('user_id', $userId)
            ->whereIn('role', ['owner', 'admin'])
            ->exists();
    }

    private function formatInvitation(object $i, bool $includeToken = false): array
    {
        $arr = [
            'id'            => $i->id,
            'group_id'      => $i->group_id,
            'group_name'    => $i->group_name ?? null,
            'inviter_id'    => $i->inviter_id,
            'invitee_email' => $i->invitee_email,
            'status'        => $i->status,
            'expires_at'    => $i->expires_at,
        ];

        if ($includeToken && isset($i->token)) {
            $arr['token'] = $i->token;
        }

        return $arr;
    }

    private function isExpired($expiresAt): bool
    {
        // Manejo seguro: si viene null/vacío o formato inválido => tratar como expirada
        if (empty($expiresAt)) {
            return true;
        }

        try {
            return Carbon::parse($expiresAt)->isPast();
        } catch (\Throwable $e) {
            return true;
        }
    }
}
