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

class ExpenseController extends Controller
{
    /**
     * GET /api/expenses
     * Lista gastos donde el usuario es pagador o participante.
     * Filtros opcionales: ?groupId=UUID&startDate=YYYY-MM-DD&endDate=YYYY-MM-DD
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

        // Enriquecemos con participantes
        $expenseIds = collect($items->items())->pluck('id')->all();
        $participants = DB::table('expense_participants as ep')
            ->join('users as u', 'u.id', '=', 'ep.user_id')
            ->whereIn('ep.expense_id', $expenseIds)
            ->select('ep.*', 'u.name as user_name', 'u.email as user_email')
            ->get()
            ->groupBy('expense_id');

        $data = collect($items->items())->map(function ($e) use ($participants, $userId) {
            $list = ($participants[$e->id] ?? collect())->map(function ($p) {
                return [
                    'id'         => $p->id,
                    'user_id'    => $p->user_id,
                    'user_name'  => $p->user_name,
                    'user_email' => $p->user_email,
                    'amount_due' => $this->money($p->amount_due),
                    'is_paid'    => (bool) $p->is_paid,
                    'payment_id' => $p->payment_id,
                ];
            })->values();

            return [
                'id'               => $e->id,
                'description'      => $e->description,
                'total_amount'     => $this->money($e->total_amount),
                'payer_id'         => $e->payer_id,
                'group_id'         => $e->group_id,
                'ticket_image_url' => $e->ticket_image_url,
                'ocr_status'       => $e->ocr_status,
                'status'           => $e->status,
                'expense_date'     => $e->expense_date,
                'participants'     => $list,
                'role'             => $e->payer_id === $userId ? 'payer' : 'participant',
            ];
        });

        return response()->json([
            'filters' => [
                'groupId'   => $groupId,
                'startDate' => $start?->toDateString(),
                'endDate'   => $end?->toDateString(),
            ],
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

        return response()->json([
            'message' => 'Gasto creado',
            'expense' => $this->formatExpense($expense),
        ], 201);
    }

    /**
     * GET /api/expenses/{expenseId}
     */
    public function show(string $expenseId, Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $expense = Expense::query()->find($expenseId);
        if (!$expense) {
            return response()->json(['message' => 'Gasto no encontrado'], 404);
        }

        $this->authorize('view', $expense);

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

        $payload = $this->formatExpense($expense);
        $payload['participants'] = $parts;

        return response()->json($payload, 200);
    }

    /**
     * PUT /api/expenses/{expenseId}
     * Solo cuando status = 'pending'. Solo el pagador puede editar.
     * Permite actualizar campos básicos y, opcionalmente, reemplazar participantes.
     */
    public function update(string $expenseId, Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $expense = Expense::query()->find($expenseId);
        if (!$expense) {
            return response()->json(['message' => 'Gasto no encontrado'], 404);
        }
        if ($expense->status !== 'pending') {
            return response()->json(['message' => 'No se puede editar un gasto no-pending'], 409);
        }

        $this->authorize('update', $expense);

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

        return response()->json([
            'message' => 'Gasto actualizado',
            'expense' => $this->formatExpense($updated),
        ], 200);
    }

    /**
     * DELETE /api/expenses/{expenseId}
     * Solo pagador. Elimina y por FK borra participantes.
     */
    public function destroy(string $expenseId, Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $expense = Expense::query()->find($expenseId);
        if (!$expense) {
            return response()->json(['message' => 'Gasto no encontrado'], 404);
        }

        $this->authorize('delete', $expense);

        DB::transaction(function () use ($expenseId) {
            DB::table('expenses')->where('id', $expenseId)->delete();
        });

        return response()->json(null, 204);
    }

    /**
     * POST /api/expenses/{expenseId}/approve
     * Aprobación global del gasto -> por ahora, SOLO el pagador puede pasar a 'approved'.
     */
    public function approve(string $expenseId, Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $expense = Expense::query()->find($expenseId);
        if (!$expense) {
            return response()->json(['message' => 'Gasto no encontrado'], 404);
        }

        $this->authorize('approve', $expense);

        if ($expense->status !== 'pending') {
            return response()->json(['message' => "El gasto ya está en estado '{$expense->status}'"], 409);
        }

        DB::table('expenses')->where('id', $expenseId)->update(['status' => 'approved']);

        $updated = DB::table('expenses')->where('id', $expenseId)->first();

        return response()->json([
            'message' => 'Gasto aprobado',
            'expense' => $this->formatExpense($updated),
        ], 200);
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


    private function formatExpense(object $e): array
    {
        return [
            'id'               => $e->id,
            'description'      => $e->description,
            'total_amount'     => $this->money($e->total_amount),
            'payer_id'         => $e->payer_id,
            'group_id'         => $e->group_id,
            'ticket_image_url' => $e->ticket_image_url,
            'ocr_status'       => $e->ocr_status,
            'status'           => $e->status,
            'expense_date'     => $e->expense_date,
            'created_at'       => $e->created_at ?? null,
            'updated_at'       => $e->updated_at ?? null,
        ];
    }

    private function money($value): string
    {
        return number_format((float) ($value ?? 0), 2, '.', '');
    }
}
