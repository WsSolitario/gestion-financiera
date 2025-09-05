// app/Http/Controllers/Api/ExpenseController.php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Services\ExpenseService;

use App\Http\Requests\Expense\StoreExpenseRequest;
use App\Http\Requests\Expense\UpdateExpenseRequest;
use App\Http\Requests\Expense\ApproveExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Jobs\ProcessExpenseOcr;

class ExpenseController extends Controller
{
    public function __construct(private ExpenseService $expenseService) {}

    /**
     * GET /api/expenses
     */
    public function index(Request $request): JsonResponse
    {
        $userId  = $request->user()->id;
        $groupId = $request->query('group_id', $request->query('groupId'));
        $startQ  = $request->query('start_date', $request->query('startDate'));
        $endQ    = $request->query('end_date',   $request->query('endDate'));

        $start = $startQ ? Carbon::parse($startQ)->startOfDay() : null;
        $end   = $endQ   ? Carbon::parse($endQ)->endOfDay()     : null;

        $result = $this->expenseService->listExpenses($userId, $groupId, $start, $end);
        return response()->json($result, 200);
    }

    /**
     * POST /api/expenses
     */
    public function store(StoreExpenseRequest $request): JsonResponse
    {
        $userId = $request->user()->id;
        $data   = $request->validated();

        $result = $this->expenseService->createExpense($userId, $data);
        return response()->json($result, 201);
    }

    /**
     * GET /api/expenses/{expenseId}
     */
    public function show(string $expenseId, Request $request): JsonResponse
    {
        $expense = DB::table('expenses')->where('id', $expenseId)->first();
        if (!$expense) return response()->json(['message' => 'Gasto no encontrado'], 404);

        if (!$this->userInExpenseContext($request->user()->id, $expense)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

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
     */
    public function update(string $expenseId, UpdateExpenseRequest $request): JsonResponse
    {
        $expense = DB::table('expenses')->where('id', $expenseId)->first();
        if (!$expense) return response()->json(['message' => 'Gasto no encontrado'], 404);
        if ($expense->status !== 'pending') return response()->json(['message' => 'No se puede editar un gasto no-pending'], 409);
        if ($expense->payer_id !== $request->user()->id) return response()->json(['message' => 'Solo el pagador puede editar este gasto'], 403);

        $data = $request->validated();

        if (isset($data['participants'])) {
            $uniqueUserIds = collect($data['participants'])->pluck('user_id')->unique()->values();
            foreach ($uniqueUserIds as $uid) $this->assertGroupMembershipOrFail($uid, $expense->group_id);

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

            if (array_key_exists('description', $data))  $updateRow['description']  = $data['description'];
            if (array_key_exists('total_amount', $data)) $updateRow['total_amount'] = $data['total_amount'];
            if (array_key_exists('expense_date', $data)) $updateRow['expense_date'] = $data['expense_date'];

            if (array_key_exists('has_ticket', $data)) {
                if ($data['has_ticket']) {
                    $updateRow['ticket_image_url'] = $data['ticket_image_url'];
                    $updateRow['ocr_status']       = 'pending';
                    $updateRow['ocr_raw_text']     = null;
                } else {
                    $updateRow['ticket_image_url'] = null;
                    $updateRow['ocr_status']       = 'skipped';
                    $updateRow['ocr_raw_text']     = null;
                }
            } elseif (array_key_exists('ticket_image_url', $data)) {
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

            if (!empty($updateRow)) {
                DB::table('expenses')->where('id', $expenseId)->update($updateRow);
            }

            if (isset($data['participants'])) {
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

        if ($updated->ticket_image_url && $updated->ocr_status === 'pending') {
            ProcessExpenseOcr::dispatch($updated->id);
        }

        return (new ExpenseResource($updated))
            ->additional(['message' => 'Gasto actualizado'])
            ->response();
    }

    /**
     * DELETE /api/expenses/{expenseId}
     */
    public function destroy(string $expenseId, Request $request): JsonResponse
    {
        $expense = DB::table('expenses')->where('id', $expenseId)->first();
        if (!$expense) return response()->json(['message' => 'Gasto no encontrado'], 404);
        if ($expense->payer_id !== $request->user()->id) {
            return response()->json(['message' => 'Solo el pagador puede eliminar este gasto'], 403);
        }

        DB::transaction(fn() => DB::table('expenses')->where('id', $expenseId)->delete());
        return response()->json(null, 204);
    }

    /**
     * POST /api/expenses/{expenseId}/approve
     */
    public function approve(string $expenseId, ApproveExpenseRequest $request): JsonResponse
    {
        $expense = DB::table('expenses')->where('id', $expenseId)->first();
        if (!$expense) return response()->json(['message' => 'Gasto no encontrado'], 404);

        if ($expense->payer_id !== $request->user()->id) {
            return response()->json(['message' => 'Solo el pagador puede aprobar este gasto con el esquema actual.'], 403);
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

    // Helpers
    private function assertGroupMembershipOrFail(string $userId, string $groupId): void
    {
        $exists = DB::table('group_members')->where('group_id', $groupId)->where('user_id', $userId)->exists();
        if (!$exists) {
            throw ValidationException::withMessages(['group_id' => ['El usuario no pertenece al grupo especificado.']]);
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

        return DB::table('group_members')
            ->where('group_id', $expense->group_id)
            ->where('user_id', $userId)
            ->exists();
    }

    private function money($value): string
    {
        return number_format((float) ($value ?? 0), 2, '.', '');
    }
}
