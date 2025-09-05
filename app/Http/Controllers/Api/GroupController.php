<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Http\Requests\Group\StoreGroupRequest;
use App\Http\Requests\Group\UpdateGroupRequest;
use App\Http\Requests\Group\AddMemberRequest;
use App\Http\Requests\Group\UpdateMemberRoleRequest;

class GroupController extends Controller
{
    /**
     * GET /api/groups
     * Lista grupos a los que pertenece el usuario (o es owner).
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $groups = DB::table('groups as g')
            ->leftJoin('group_members as gm', function ($join) use ($userId) {
                $join->on('gm.group_id', '=', 'g.id')
                     ->where('gm.user_id', '=', $userId);
            })
            ->where(function ($q) use ($userId) {
                // Pertenece por ser owner o por tener fila en group_members (LEFT JOIN)
                $q->where('g.owner_id', '=', $userId)
                  ->orWhereNotNull('gm.user_id');
            })
            ->selectRaw(
                "g.id,
                 g.name,
                 g.description,
                 g.owner_id,
                 g.created_at,
                 COALESCE(gm.role::text, CASE WHEN g.owner_id = ? THEN 'owner' END) AS my_role,
                 (SELECT COUNT(*) FROM group_members m WHERE m.group_id = g.id) AS members_count",
                [$userId]
            )
            ->orderByDesc('g.created_at')
            ->get()
            ->map(function ($g) {
                return [
                    'id'            => $g->id,
                    'name'          => $g->name,
                    'description'   => $g->description,
                    'owner_id'      => $g->owner_id,
                    'created_at'    => $g->created_at,
                    'my_role'       => $g->my_role ?? 'member',
                    'members_count' => (int) $g->members_count,
                ];
            });

        return response()->json(['data' => $groups], 200);
    }

    /**
     * POST /api/groups
     * Crea un grupo. El creador se vuelve owner y miembro.
     * Body: { name: string, description?: string }
     */
    public function store(StoreGroupRequest $request): JsonResponse
    {
        $userId = $request->user()->id;

        $data = $request->validated();

        $group = DB::transaction(function () use ($data, $userId) {
            $groupId = (string) Str::uuid();

            DB::table('groups')->insert([
                'id'          => $groupId,
                'name'        => $data['name'],
                'description' => $data['description'] ?? null,
                'owner_id'    => $userId,
                // created_at: lo define la BD si tienes default; si no, usa now()
            ]);

            // Si tu ENUM group_role incluye 'owner', esto está bien.
            // Si NO lo incluye, cambia 'owner' por 'admin'.
            DB::table('group_members')->insert([
                'id'        => (string) Str::uuid(),
                'group_id'  => $groupId,
                'user_id'   => $userId,
                'role'      => 'owner',
                'joined_at' => now(),
            ]);

            return DB::table('groups')->where('id', $groupId)->first();
        });

        return response()->json([
            'message' => 'Grupo creado',
            'group'   => $this->formatGroup($group),
        ], 201);
    }

    /**
     * GET /api/groups/{group}
     * Detalle del grupo + lista de miembros.
     */
    public function show(string $groupId, Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $group = DB::table('groups')->where('id', $groupId)->first();
        if (!$group) return response()->json(['message' => 'Grupo no encontrado'], 404);
        if (!$this->userInGroup($userId, $groupId)) return response()->json(['message' => 'No autorizado'], 403);

        $members = DB::table('group_members as gm')
            ->join('users as u', 'u.id', '=', 'gm.user_id')
            ->where('gm.group_id', $groupId)
            ->select('u.id as user_id', 'u.name', 'u.email', 'gm.role', 'gm.joined_at')
            // Comparar sobre texto para evitar choques enum/text
            ->orderByRaw("CASE gm.role::text WHEN 'owner' THEN 0 WHEN 'admin' THEN 1 ELSE 2 END")
            ->orderBy('u.name')
            ->get();

        return response()->json([
            'group'   => $this->formatGroup($group),
            'members' => $members,
            'my_role' => $this->userRole($group, $userId),
        ], 200);
    }

    /**
     * PUT /api/groups/{group}
     * owner o admin pueden actualizar name/description.
     */
    public function update(string $groupId, UpdateGroupRequest $request): JsonResponse
    {
        $userId = $request->user()->id;
        $group  = DB::table('groups')->where('id', $groupId)->first();
        if (!$group) return response()->json(['message' => 'Grupo no encontrado'], 404);

        $actorRole = $this->userRole($group, $userId);
        if (!in_array($actorRole, ['owner', 'admin'], true)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $data = $request->validated();

        if (empty($data)) {
            return response()->json(['message' => 'Nada que actualizar'], 422);
        }

        DB::table('groups')->where('id', $groupId)->update($data);

        $updated = DB::table('groups')->where('id', $groupId)->first();
        return response()->json([
            'message' => 'Grupo actualizado',
            'group'   => $this->formatGroup($updated),
        ], 200);
    }

    /**
     * DELETE /api/groups/{group}
     * Solo owner.
     */
    public function destroy(string $groupId, Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $group  = DB::table('groups')->where('id', $groupId)->first();
        if (!$group) return response()->json(['message' => 'Grupo no encontrado'], 404);
        if ($group->owner_id !== $userId) {
            return response()->json(['message' => 'Solo el owner puede eliminar el grupo'], 403);
        }

        DB::table('groups')->where('id', $groupId)->delete(); // CASCADE se encarga del resto
        return response()->json(null, 204);
    }

    /**
     * POST /api/groups/{group}/members
     * owner o admin agregan un miembro existente.
     * Body: { user_id: uuid, role?: 'member'|'admin' }
     */
    public function addMember(string $groupId, AddMemberRequest $request): JsonResponse
    {
        $userId = $request->user()->id;
        $group  = DB::table('groups')->where('id', $groupId)->first();
        if (!$group) return response()->json(['message' => 'Grupo no encontrado'], 404);

        $actorRole = $this->userRole($group, $userId);
        if (!in_array($actorRole, ['owner', 'admin'], true)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $data = $request->validated();

        $existsUser = DB::table('users')->where('id', $data['user_id'])->exists();
        if (!$existsUser) {
            return response()->json(['message' => 'Usuario no encontrado'], 422);
        }

        $already = DB::table('group_members')
            ->where('group_id', $groupId)
            ->where('user_id', $data['user_id'])
            ->exists();
        if ($already) {
            return response()->json(['message' => 'El usuario ya es miembro del grupo'], 409);
        }

        DB::table('group_members')->insert([
            'id'        => (string) Str::uuid(),
            'group_id'  => $groupId,
            'user_id'   => $data['user_id'],
            'role'      => $data['role'] ?? 'member',
            'joined_at' => now(),
        ]);

        return response()->json(['message' => 'Miembro agregado'], 201);
    }

    /**
     * PUT /api/groups/{group}/members/{user}
     * Cambiar rol de un miembro. Reglas en docstring.
     */
    public function updateMemberRole(string $groupId, string $targetUserId, UpdateMemberRoleRequest $request): JsonResponse
    {
        $userId = $request->user()->id;
        $group  = DB::table('groups')->where('id', $groupId)->first();
        if (!$group) return response()->json(['message' => 'Grupo no encontrado'], 404);

        $actorRole  = $this->userRole($group, $userId);
        $targetRole = $this->userRole($group, $targetUserId);

        $data = $request->validated();
        $newRole = $data['role'];

        $isTargetMember = DB::table('group_members')
            ->where('group_id', $groupId)->where('user_id', $targetUserId)->exists();

        if ($newRole === 'owner') {
            if ($actorRole !== 'owner') {
                return response()->json(['message' => 'Solo el owner puede transferir la propiedad'], 403);
            }
            if ($group->owner_id === $targetUserId) {
                return response()->json(['message' => 'Ya es owner'], 409);
            }

            DB::transaction(function () use ($group, $groupId, $targetUserId, $isTargetMember) {
                if (!$isTargetMember) {
                    DB::table('group_members')->insert([
                        'id'        => (string) Str::uuid(),
                        'group_id'  => $groupId,
                        'user_id'   => $targetUserId,
                        'role'      => 'owner',
                        'joined_at' => now(),
                    ]);
                } else {
                    DB::table('group_members')
                        ->where('group_id', $groupId)
                        ->where('user_id', $targetUserId)
                        ->update(['role' => 'owner']);
                }

                $oldOwnerId = $group->owner_id;
                $oldExists = DB::table('group_members')
                    ->where('group_id', $groupId)->where('user_id', $oldOwnerId)->exists();

                if ($oldExists) {
                    DB::table('group_members')
                        ->where('group_id', $groupId)
                        ->where('user_id', $oldOwnerId)
                        ->update(['role' => 'admin']);
                } else {
                    DB::table('group_members')->insert([
                        'id'        => (string) Str::uuid(),
                        'group_id'  => $groupId,
                        'user_id'   => $oldOwnerId,
                        'role'      => 'admin',
                        'joined_at' => now(),
                    ]);
                }

                DB::table('groups')->where('id', $groupId)->update(['owner_id' => $targetUserId]);
            });

            return response()->json(['message' => 'Propiedad transferida'], 200);
        }

        if (!in_array($actorRole, ['owner', 'admin'], true)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        if ($targetUserId === $group->owner_id) {
            return response()->json(['message' => 'No puedes modificar el rol del owner (usa transfer a owner)'], 403);
        }
        if ($actorRole === 'admin' && $targetRole === 'admin') {
            return response()->json(['message' => 'Un admin no puede modificar a otro admin'], 403);
        }
        if (!$isTargetMember) {
            return response()->json(['message' => 'El usuario no es miembro del grupo'], 404);
        }

        DB::table('group_members')
            ->where('group_id', $groupId)
            ->where('user_id', $targetUserId)
            ->update(['role' => $newRole]);

        return response()->json(['message' => 'Rol actualizado'], 200);
    }

    /**
     * DELETE /api/groups/{group}/members/{user}
     */
    public function removeMember(string $groupId, string $targetUserId, Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $group  = DB::table('groups')->where('id', $groupId)->first();
        if (!$group) return response()->json(['message' => 'Grupo no encontrado'], 404);

        $actorRole  = $this->userRole($group, $userId);
        $targetRole = $this->userRole($group, $targetUserId);

        if (!$this->userInGroup($targetUserId, $groupId)) {
            return response()->json(['message' => 'El usuario no es miembro del grupo'], 404);
        }
        if ($targetUserId === $group->owner_id) {
            return response()->json(['message' => 'No puedes eliminar al owner del grupo'], 403);
        }
        if ($actorRole === 'admin' && $targetRole !== 'member') {
            return response()->json(['message' => 'Un admin solo puede eliminar a miembros (no admins/owner)'], 403);
        }
        if (!in_array($actorRole, ['owner', 'admin'], true)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        DB::table('group_members')
            ->where('group_id', $groupId)
            ->where('user_id', $targetUserId)
            ->delete();

        return response()->json(null, 204);
    }

    // ===========================
    // Helpers
    // ===========================

    private function userRole(object $group, string $userId): ?string
    {
        if ($group->owner_id === $userId) {
            return 'owner';
        }
        $role = DB::table('group_members')
            ->where('group_id', $group->id)
            ->where('user_id', $userId)
            ->value('role');

        return $role ?: null;
    }

    private function userInGroup(string $userId, string $groupId): bool
    {
        $inMembers = DB::table('group_members')
            ->where('group_id', $groupId)
            ->where('user_id', $userId)
            ->exists();

        if ($inMembers) return true;

        $isOwner = DB::table('groups')
            ->where('id', $groupId)
            ->where('owner_id', $userId)
            ->exists();

        return $isOwner;
    }

    private function formatGroup(object $g): array
    {
        return [
            'id'          => $g->id,
            'name'        => $g->name,
            'description' => $g->description,
            'owner_id'    => $g->owner_id,
            'created_at'  => $g->created_at ?? null,
        ];
    }
}
