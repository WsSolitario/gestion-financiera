<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $userId   = $request->user()->id;
        $groupId  = $request->query('groupId');        // UUID (opcional)
        $startRaw = $request->query('startDate');      // YYYY-MM-DD (opcional)
        $endRaw   = $request->query('endDate');        // YYYY-MM-DD (opcional)

        $start = $startRaw ? Carbon::parse($startRaw)->startOfDay() : null;
        $end   = $endRaw   ? Carbon::parse($endRaw)->endOfDay()     : null;

        // Helpers de filtro por fecha (expenses usa expense_date (DATE), payments usa payment_date (TIMESTAMP))
        $applyExpenseDate = function ($q) use ($start, $end) {
            if ($start) $q->where('e.expense_date', '>=', $start->toDateString());
            if ($end)   $q->where('e.expense_date', '<=', $end->toDateString());
        };
        $applyPaymentDate = function ($q) use ($start, $end) {
            if ($start) $q->where('p.payment_date', '>=', $start->toDateTimeString());
            if ($end)   $q->where('p.payment_date', '<=', $end->toDateTimeString());
        };

        // ===== TOTALES =====

        // Lo que TÚ debes (tus deudas): participants donde tú eres user y no has pagado
        $totalYouOweRaw = DB::table('expense_participants as ep')
            ->join('expenses as e', 'e.id', '=', 'ep.expense_id')
            ->where('ep.user_id', $userId)
            ->where('ep.is_paid', false)
            ->when($groupId, fn($q) => $q->where('e.group_id', $groupId))
            ->tap($applyExpenseDate)
            ->sum('ep.amount_due');

        // Lo que te deben (otros te deben): participants en gastos donde tú eres payer
        $totalOwedToYouRaw = DB::table('expense_participants as ep')
            ->join('expenses as e', 'e.id', '=', 'ep.expense_id')
            ->where('e.payer_id', $userId)
            ->where('ep.user_id', '!=', $userId)
            ->where('ep.is_paid', false)
            ->when($groupId, fn($q) => $q->where('e.group_id', $groupId))
            ->tap($applyExpenseDate)
            ->sum('ep.amount_due');

        // Pendientes de que TÚ apruebes (gastos en estado pending donde participas)
        $pendingExpenseApprovals = DB::table('expense_participants as ep')
            ->join('expenses as e', 'e.id', '=', 'ep.expense_id')
            ->where('ep.user_id', $userId)
            ->where('e.status', 'pending')
            ->when($groupId, fn($q) => $q->where('e.group_id', $groupId))
            ->tap($applyExpenseDate)
            ->count();

        // Pagos pendientes de que TÚ apruebes (pagos donde tú eres receptor y están en pending)
        $pendingPaymentApprovals = DB::table('payments as p')
            ->where('p.to_user_id', $userId)
            ->where('p.status', 'pending')
            ->when($groupId, function ($q) use ($groupId) {
                // Si filtras por grupo, considera pagos ligados a participants de gastos de ese grupo
                $q->whereIn('p.id', function ($sub) use ($groupId) {
                    $sub->from('expense_participants as ep')
                        ->join('expenses as e', 'e.id', '=', 'ep.expense_id')
                        ->whereColumn('ep.payment_id', 'p.id')
                        ->where('e.group_id', $groupId)
                        ->select('ep.payment_id');
                });
            })
            ->tap($applyPaymentDate)
            ->count();

        // Grupos a los que perteneces
        $groupsCount = DB::table('group_members')
            ->where('user_id', $userId)
            ->count();

        // ===== POR GRUPO =====

        // Deuda tuya por grupo
        $youOweByGroup = DB::table('expense_participants as ep')
            ->join('expenses as e', 'e.id', '=', 'ep.expense_id')
            ->join('groups as g', 'g.id', '=', 'e.group_id')
            ->where('ep.user_id', $userId)
            ->where('ep.is_paid', false)
            ->tap($applyExpenseDate)
            ->select('g.id as group_id', 'g.name as group_name', DB::raw('SUM(ep.amount_due) as total'))
            ->groupBy('g.id', 'g.name')
            ->when($groupId, fn($q) => $q->having('group_id', '=', $groupId))
            ->orderByDesc(DB::raw('SUM(ep.amount_due)'))
            ->get();

        // Deuda de otros hacia ti por grupo
        $owedToYouByGroup = DB::table('expense_participants as ep')
            ->join('expenses as e', 'e.id', '=', 'ep.expense_id')
            ->join('groups as g', 'g.id', '=', 'e.group_id')
            ->where('e.payer_id', $userId)
            ->where('ep.user_id', '!=', $userId)
            ->where('ep.is_paid', false)
            ->tap($applyExpenseDate)
            ->select('g.id as group_id', 'g.name as group_name', DB::raw('SUM(ep.amount_due) as total'))
            ->groupBy('g.id', 'g.name')
            ->when($groupId, fn($q) => $q->having('group_id', '=', $groupId))
            ->orderByDesc(DB::raw('SUM(ep.amount_due)'))
            ->get();

        // Top deudores (quiénes te deben más)
        $topDebtors = DB::table('expense_participants as ep')
            ->join('expenses as e', 'e.id', '=', 'ep.expense_id')
            ->join('users as u', 'u.id', '=', 'ep.user_id')
            ->where('e.payer_id', $userId)
            ->where('ep.user_id', '!=', $userId)
            ->where('ep.is_paid', false)
            ->when($groupId, fn($q) => $q->where('e.group_id', $groupId))
            ->tap($applyExpenseDate)
            ->select('u.id as user_id', 'u.name as user_name', DB::raw('SUM(ep.amount_due) as total'))
            ->groupBy('u.id', 'u.name')
            ->orderByDesc(DB::raw('SUM(ep.amount_due)'))
            ->limit(5)
            ->get();

        // Top acreedores (a quién le debes más)
        $topCreditors = DB::table('expense_participants as ep')
            ->join('expenses as e', 'e.id', '=', 'ep.expense_id')
            ->join('users as u', 'u.id', '=', 'e.payer_id')
            ->where('ep.user_id', $userId)
            ->where('ep.is_paid', false)
            ->when($groupId, fn($q) => $q->where('e.group_id', $groupId))
            ->tap($applyExpenseDate)
            ->select('u.id as user_id', 'u.name as user_name', DB::raw('SUM(ep.amount_due) as total'))
            ->groupBy('u.id', 'u.name')
            ->orderByDesc(DB::raw('SUM(ep.amount_due)'))
            ->limit(5)
            ->get();

        // ===== RECIENTES =====

        // Últimos 5 gastos donde seas pagador o participante
        $recentExpenses = DB::table('expenses as e')
            ->leftJoin('expense_participants as myep', function ($join) use ($userId) {
                $join->on('myep.expense_id', '=', 'e.id')
                     ->where('myep.user_id', '=', $userId);
            })
            ->where(function ($q) use ($userId) {
                $q->where('e.payer_id', $userId)
                  ->orWhereNotNull('myep.user_id');
            })
            ->when($groupId, fn($q) => $q->where('e.group_id', $groupId))
            ->tap($applyExpenseDate)
            ->selectRaw("
                e.id,
                e.description,
                e.total_amount,
                e.expense_date,
                e.status,
                e.group_id,
                e.payer_id,
                myep.amount_due as your_amount_due,
                COALESCE(myep.is_paid, false) as your_is_paid
            ")
            ->orderByDesc('e.expense_date')
            ->limit(5)
            ->get()
            ->map(function ($row) use ($userId) {
                $role = ($row->payer_id === $userId) ? 'payer' : 'participant';
                return [
                    'id'              => $row->id,
                    'description'     => $row->description,
                    'expense_date'    => $row->expense_date,
                    'status'          => $row->status,
                    'group_id'        => $row->group_id,
                    'total_amount'    => $this->money($row->total_amount),
                    'role'            => $role,
                    'your_amount_due' => $row->your_amount_due !== null ? $this->money($row->your_amount_due) : null,
                    'your_is_paid'    => (bool) $row->your_is_paid,
                ];
            });

        // Últimos 5 pagos donde seas pagador o receptor
        $recentPayments = DB::table('payments as p')
            ->leftJoin('users as payer', 'payer.id', '=', 'p.from_user_id')
            ->leftJoin('users as recv',  'recv.id',  '=', 'p.to_user_id')
            ->where(function ($q) use ($userId) {
                $q->where('p.from_user_id', $userId)->orWhere('p.to_user_id', $userId);
            })
            ->when($groupId, function ($q) use ($groupId) {
                // Si hay groupId, filtramos pagos ligados a deudas de ese grupo
                $q->whereIn('p.id', function ($sub) use ($groupId) {
                    $sub->from('expense_participants as ep')
                        ->join('expenses as e', 'e.id', '=', 'ep.expense_id')
                        ->whereColumn('ep.payment_id', 'p.id')
                        ->where('e.group_id', $groupId)
                        ->select('ep.payment_id');
                });
            })
            ->tap($applyPaymentDate)
            ->select('p.*', 'payer.name as payer_name', 'recv.name as receiver_name')
            ->orderByDesc(DB::raw('COALESCE(p.payment_date, NOW())'))
            ->limit(5)
            ->get()
            ->map(function ($p) use ($userId) {
                $direction = $p->from_user_id === $userId ? 'outgoing' : 'incoming';
                $counterparty = $p->from_user_id === $userId
                    ? ['user_id' => $p->to_user_id, 'name' => $p->receiver_name]
                    : ['user_id' => $p->from_user_id,    'name' => $p->payer_name];

                return [
                    'id'           => $p->id,
                    'amount'       => $this->money($p->amount),
                    'status'       => $p->status,
                    'payment_date' => $p->payment_date,
                    'method'       => $p->payment_method,
                    'evidence_url' => $p->evidence_url,
                    'direction'    => $direction,
                    'counterparty' => $counterparty,
                ];
            });

        // ===== RESPUESTA =====
        return response()->json([
            'filters' => [
                'groupId'   => $groupId,
                'startDate' => $start?->toDateString(),
                'endDate'   => $end?->toDateString(),
            ],
            'totals' => [
                'you_owe'      => $this->money($totalYouOweRaw),
                'owed_to_you'  => $this->money($totalOwedToYouRaw),
            ],
            'counts' => [
                'groups'                      => $groupsCount,
                'pending_expense_approvals'   => $pendingExpenseApprovals,
                'pending_payment_approvals'   => $pendingPaymentApprovals,
            ],
            'by_group' => [
                'you_owe'     => $youOweByGroup->map(fn($g) => [
                    'group_id'   => $g->group_id,
                    'group_name' => $g->group_name,
                    'total'      => $this->money($g->total),
                ]),
                'owed_to_you' => $owedToYouByGroup->map(fn($g) => [
                    'group_id'   => $g->group_id,
                    'group_name' => $g->group_name,
                    'total'      => $this->money($g->total),
                ]),
            ],
            'recent' => [
                'expenses' => $recentExpenses,
                'payments' => $recentPayments,
            ],
        ], 200);
    }

    private function money($value): string
    {
        // Devuelve string con 2 decimales (evita problemas de floats)
        return number_format((float) ($value ?? 0), 2, '.', '');
    }
}
