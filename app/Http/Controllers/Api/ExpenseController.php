<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Jobs\ProcessExpenseOcr;

use App\Models\Expense;
use App\Models\ExpenseParticipant;
use App\Models\Group;
use App\Http\Resources\ExpenseResource;

class ExpenseController extends Controller
{
    /**
     * GET /api/expenses
     * Lista gastos donde el usuario es pagador o participante.
     * Filtros opcionales: ?groupId=UUID&startDate=YYYY-MM-DD&endDate=YYYY-MM-DD
     *
     * Cada gasto incluye un campo `status` que puede ser `pending`, `approved`,
     * `rejected` o `completed` cuando todos los participantes pagaron su parte.
     */
    public function index(Request $request): JsonResponse
    {
        $userId  = $request->user()->id;
        $groupId = $request->query('groupId');
        $start   = $request->query('startDate') ? Carbon::parse($request->query('startDate'))->startOfDay() : null;
        $end     = $request->query('endDate')   ? Carbon::parse($request->query('endDate'))->endOfDay()   : null;

        $q = DB::table('expenses as e')
            ->leftJoin('expense_participants as ep', 'ep.expense_id', '=', 'e.id')
            ->where(function ($q) use ($userId) {
                $q->where('e.payer_id', $userId)
                  ->orWhere('ep.user_id', $userId);
            })
            ->when($groupId, fn($q) => $q->where('e.group_id', $groupId))
            ->when($start,   fn($q) => $q->where('e.expense_date', '>=', $start->toDateString()))
            ->when($end,     fn($q) => $q->where('e.expense_date', '<=', $end->toDateString()))
            ->select('e.*')
            ->distinct()
            ->orderByDesc('e.expense_date')
            ->orderByDesc('e.created_at');

        $items = $q->paginate(15);
        $expenseIds = collect($items->items())->pluck('id')->all();
        $participants = DB::table('expense_participants as ep')
            ->join('users as u', 'u.id', '=', 'ep.user_id')
            ->whereIn('ep.expense_id', $expenseIds)
            ->select('ep.*', 'u.name as user_name', 'u.email as user_email')
            ->get()
            ->groupBy('expense_id');

        $collection = collect($items->items())->map(function ($e) use ($participants, $userId) {
            $e->participants = ($participants[$e->id] ?? collect())->map(function ($p) {
                return [
                    'id'         => $p->id,
                    'user_id'    => $p->user_id,
                    'user_name'  => $p->user_name,
                    'user_email' => $p->user_email,
                    'amount_due' => number_format((float) $p->amount_due, 2, '.', ''),
                    'is_paid'    => (bool) $p->is_paid,
                    'payment_id' => $p->payment_id,
                ];
            })->values();
            $e->role = $e->payer_id === $userId ? 'payer' : 'participant';
            return $e;
        });

        $items->setCollection($collection);

        return ExpenseResource::collection($items)
            ->additional([
                'filters' => [
                    'groupId'   => $groupId,
                    'startDate' => $start?->toDateString(),
                    'endDate'   => $end?->toDateString(),
                ],
            ])->response();
    }

    /**
     * POST /api/expenses
     * Crea un gasto + participantes.
     * Body:
     * {
     *   description: string,
     *   total_amount: number,
     *   group_id: uuid,
     *   expense_date: 'YYYY-MM-DD',
     *   has_ticket: boolean,
     *   ticket_image_url?: string (requerida si has_ticket=true),
     *   participants: [{ user_id: uuid, amount_due: number }, ...]
     * }
     */
    public function store(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $data = $this->validateStore($request);

        // Validar que el pagador (usuario actual) y TODOS los participantes pertenecen al grupo
        $this->assertGroupMembershipOrFail($userId, $data['group_id']);
        $uniqueUserIds = collect($data['participants'])->pluck('user_id')->unique()->values();
        foreach ($uniqueUserIds as $uid) {
            $this->assertGroupMembershipOrFail($uid, $data['group_id']);
        }

        // Validar suma de participantes = total_amount
        $sum = collect($data['participants'])->sum(fn($p) => (float)$p['amount_due']);
        if ($this->money($sum) !== $this->money($data['total_amount'])) {
            throw ValidationException::withMessages([
                'participants' => ['La suma de amount_due no coincide con total_amount.'],
            ]);
        }

        // Lógica de ticket / OCR
        $hasTicket = (bool) $data['has_ticket'];
        $ticketUrl = $hasTicket ? ($data['ticket_image_url'] ?? null) : null;
        $ocrStatus = $hasTicket ? 'pending' : 'skipped';

        $expense = DB::transaction(function () use ($data, $userId, $ticketUrl, $ocrStatus) {
            $expenseId = (string) Str::uuid();

            DB::table('expenses')->insert([
                'id'               => $expenseId,
                'description'      => $data['description'],
                'total_amount'     => $data['total_amount'],
                'payer_id'         => $userId,
                'group_id'         => $data['group_id'],
                'ticket_image_url' => $ticketUrl,          // null si no hay ticket
                'ocr_status'       => $ocrStatus,          // 'pending' o 'skipped'
                'ocr_raw_text'     => null,
                'status'           => 'pending',
                'expense_date'     => $data['expense_date'],
            ]);

            // Insertar participantes (UUIDs)
            $rows = [];
            foreach ($data['participants'] as $p) {
                $rows[] = [
                    'id'         => (string) Str::uuid(),
                    'expense_id' => $expenseId,
                    'user_id'    => $p['user_id'],
                    'amount_due' => $p['amount_due'],
                    'is_paid'    => false,
                    'payment_id' => null,
                ];
            }
            DB::table('expense_participants')->insert($rows);

            return DB::table('expenses')->where('id', $expenseId)->first();
        });

        // Lanzar OCR solo si corresponde
        if ($hasTicket && $ticketUrl) {
            ProcessExpenseOcr::dispatch($expense->id);
        }

        return (new ExpenseResource($expense))
            ->additional(['message' => 'Gasto creado'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * GET /api/expenses/{expenseId}
     */
    public function show(string $expenseId, Request $request): JsonResponse
    {
        $expense = DB::table('expenses')->where('id', $expenseId)->first();
        if (!$expense) {
            return response()->json(['message' => 'Gasto no encontrado'], 404);
        }

        // Seguridad: solo pagador, participante o miembro del grupo puede ver
        if (!$this->userInExpenseContext($request->user()->id, $expense)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        // Participantes
        $parts = DB::table('expense_participants as ep')
            ->join('users as u', 'u.id', '=', 'ep.user_id')
            ->where('expense_id', $expenseId)
            ->select('ep.*', 'u.name as user_name', 'u.email as user_email')
            ->get()
            ->map(function ($p) {
                return [
                    'id'         => $p->id,
                    'user_id'    => $p->user_id,
                    'user_name'  => $p->user_name,
                    'user_email' => $p->user_email,
                    'amount_due' => $this->money($p->amount_due),
                    'is_paid'    => (bool) $p->is_paid,
                    'payment_id' => $p->payment_id,
                ];
            });

        $expense->participants = $parts;
        $expense->role = $expense->payer_id === $request->user()->id ? 'payer' : 'participant';
        return (new ExpenseResource($expense))->response();
    }

    /**
     * PUT /api/expenses/{expenseId}
     * Solo cuando status = 'pending'. Solo el pagador puede editar.
     * Permite actualizar campos básicos y, opcionalmente, reemplazar participantes.
     */
    public function update(string $expenseId, Request $request): JsonResponse
    {
        $expense = DB::table('expenses')->where('id', $expenseId)->first();
        if (!$expense) {
            return response()->json(['message' => 'Gasto no encontrado'], 404);
        }
        if ($expense->status !== 'pending') {
            return response()->json(['message' => 'No se puede editar un gasto no-pending'], 409);
        }
        if ($expense->payer_id !== $request->user()->id) {
            return response()->json(['message' => 'Solo el pagador puede editar este gasto'], 403);
        }

        // Validación flexible para update (incluye has_ticket)
        $validator = Validator::make($request->all(), [
            'description'        => ['sometimes', 'string'],
            'total_amount'       => ['sometimes', 'numeric', 'min:0'],
            'expense_date'       => ['sometimes', 'date_format:Y-m-d'],

            'has_ticket'         => ['sometimes', 'boolean'],
            'ticket_image_url'   => ['nullable', 'url', 'required_if:has_ticket,true', 'prohibited_unless:has_ticket,true'],

            'participants'               => ['sometimes', 'array', 'min:1'],
            'participants.*.user_id'     => ['required_with:participants', 'uuid'],
            'participants.*.amount_due'  => ['required_with:participants', 'numeric', 'min:0'],
        ]);
        $validator->validate();

        $data = $validator->validated();

        // Si hay participants, validar pertenencia + suma total
        if (isset($data['participants'])) {
            $uniqueUserIds = collect($data['participants'])->pluck('user_id')->unique()->values();
            foreach ($uniqueUserIds as $uid) {
                $this->assertGroupMembershipOrFail($uid, $expense->group_id);
            }

            // Si actualizas participants pero NO mandas total_amount, usa el total actual
            $newTotal = $data['total_amount'] ?? (float) $expense->total_amount;
            $sum = collect($data['participants'])->sum(fn($p) => (float)$p['amount_due']);
            if ($this->money($sum) !== $this->money($newTotal)) {
                throw ValidationException::withMessages([
                    'participants' => ['La suma de amount_due no coincide con total_amount.'],
                ]);
            }
        }

        $updated = DB::transaction(function () use ($expense, $expenseId, $data) {
            $updateRow = [];

            // Campos básicos
            if (array_key_exists('description', $data))  $updateRow['description']  = $data['description'];
            if (array_key_exists('total_amount', $data)) $updateRow['total_amount'] = $data['total_amount'];
            if (array_key_exists('expense_date', $data)) $updateRow['expense_date'] = $data['expense_date'];

            // Lógica de ticket / OCR
            if (array_key_exists('has_ticket', $data)) {
                if ($data['has_ticket']) {
                    // Debe venir ticket_image_url por la validación
                    $updateRow['ticket_image_url'] = $data['ticket_image_url'];
                    $updateRow['ocr_status']       = 'pending';
                    $updateRow['ocr_raw_text']     = null;
                } else {
                    // Se apaga el OCR y se limpia ticket
                    $updateRow['ticket_image_url'] = null;
                    $updateRow['ocr_status']       = 'skipped';
                    $updateRow['ocr_raw_text']     = null;
                }
            } else {
                // Sin has_ticket explícito pero cambiaron la URL
                if (array_key_exists('ticket_image_url', $data)) {
                    if ($data['ticket_image_url']) {
                        $updateRow['ticket_image_url'] = $data['ticket_image_url'];
                        $updateRow['ocr_status']       = 'pending';
                        $updateRow['ocr_raw_text']     = null;
                    } else {
                        $updateRow['ticket_image_url'] = null;
                        $updateRow['ocr_status']       = 'skipped';
                        $updateRow['ocr_raw_text']     = null;
                    }
                }
            }

            if (!empty($updateRow)) {
                DB::table('expenses')->where('id', $expenseId)->update($updateRow);
            }

            if (isset($data['participants'])) {
                // Reemplazar participantes completamente
                DB::table('expense_participants')->where('expense_id', $expenseId)->delete();

                $rows = [];
                foreach ($data['participants'] as $p) {
                    $rows[] = [
                        'id'         => (string) Str::uuid(),
                        'expense_id' => $expenseId,
                        'user_id'    => $p['user_id'],
                        'amount_due' => $p['amount_due'],
                        'is_paid'    => false,
                        'payment_id' => null,
                    ];
                }
                DB::table('expense_participants')->insert($rows);
            }

            return DB::table('expenses')->where('id', $expenseId)->first();
        });

        // Si quedó con ticket pendiente, dispara el OCR
        if ($updated->ticket_image_url && $updated->ocr_status === 'pending') {
            ProcessExpenseOcr::dispatch($updated->id);
        }

        return (new ExpenseResource($updated))
            ->additional(['message' => 'Gasto actualizado'])
            ->response();
    }

    /**
     * DELETE /api/expenses/{expenseId}
     * Solo pagador. Elimina y por FK borra participantes.
     */
    public function destroy(string $expenseId, Request $request): JsonResponse
    {
        $expense = DB::table('expenses')->where('id', $expenseId)->first();
        if (!$expense) {
            return response()->json(['message' => 'Gasto no encontrado'], 404);
        }
        if ($expense->payer_id !== $request->user()->id) {
            return response()->json(['message' => 'Solo el pagador puede eliminar este gasto'], 403);
        }

        DB::transaction(function () use ($expenseId) {
            DB::table('expenses')->where('id', $expenseId)->delete();
        });

        return response()->json(null, 204);
    }

    /**
     * POST /api/expenses/{expenseId}/approve
     * Aprobación global del gasto -> por ahora, SOLO el pagador puede pasar a 'approved'.
     * Una vez que todos los participantes paguen su parte, el gasto se marcará
     * automáticamente como `completed`.
     */
    public function approve(string $expenseId, Request $request): JsonResponse
    {
        $expense = DB::table('expenses')->where('id', $expenseId)->first();
        if (!$expense) {
            return response()->json(['message' => 'Gasto no encontrado'], 404);
        }

        // Solo pagador puede aprobar globalmente con el esquema actual
        if ($expense->payer_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Solo el pagador puede aprobar este gasto con el esquema actual.'
            ], 403);
        }

        if ($expense->status !== 'pending') {
            return response()->json(['message' => "El gasto ya está en estado '{$expense->status}'"], 409);
        }

        DB::table('expenses')->where('id', $expenseId)->update(['status' => 'approved']);

        $updated = DB::table('expenses')->where('id', $expenseId)->first();

        return (new ExpenseResource($updated))
            ->additional(['message' => 'Gasto aprobado'])
            ->response();
    }

    // ===========================
    // Helpers
    // ===========================

    private function validateStore(Request $request): array
    {
        // Valida indicador de ticket y reglas condicionales
        return $request->validate([
            'description'                => ['required', 'string'],
            'total_amount'               => ['required', 'numeric', 'min:0'],
            'group_id'                   => ['required', 'uuid'],
            'expense_date'               => ['required', 'date_format:Y-m-d'],

            'has_ticket'                 => ['required', 'boolean'],
            'ticket_image_url'           => ['nullable', 'url', 'required_if:has_ticket,true', 'prohibited_unless:has_ticket,true'],

            'participants'               => ['required', 'array', 'min:1'],
            'participants.*.user_id'     => ['required', 'uuid'],
            'participants.*.amount_due'  => ['required', 'numeric', 'min:0'],
        ]);
    }

    private function assertGroupMembershipOrFail(string $userId, string $groupId): void
    {
        $exists = DB::table('group_members')
            ->where('group_id', $groupId)
            ->where('user_id', $userId)
            ->exists();

        if (!$exists) {
            throw ValidationException::withMessages([
                'group_id' => ['El usuario no pertenece al grupo especificado.'],
            ]);
        }
    }

    private function userInExpenseContext(string $userId, object $expense): bool
    {
        if ($expense->payer_id === $userId) return true;

        $isParticipant = DB::table('expense_participants')
            ->where('expense_id', $expense->id)
            ->where('user_id', $userId)
            ->exists();

        if ($isParticipant) return true;

        // También permitimos ver si es miembro del grupo del gasto
        $inGroup = DB::table('group_members')
            ->where('group_id', $expense->group_id)
            ->where('user_id', $userId)
            ->exists();

        return $inGroup;
    }

    private function money($value): string
    {
        return number_format((float) ($value ?? 0), 2, '.', '');
    }
}
