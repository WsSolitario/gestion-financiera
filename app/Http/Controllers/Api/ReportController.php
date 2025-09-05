// app/Http/Controllers/Api/ReportController.php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Support\MoneyFormatter;
use App\Http\Requests\Report\ReportFilterRequest;

class ReportController extends Controller
{
    public function index(ReportFilterRequest $request): JsonResponse
    {
        $userId  = $request->user()->id;
        $data    = $request->validated();
        $groupId = $data['groupId'] ?? null;

        $start = isset($data['startDate'])
            ? Carbon::parse($data['startDate'])->startOfDay()
            : now()->subDays(30)->startOfDay();

        $end   = isset($data['endDate'])
            ? Carbon::parse($data['endDate'])->endOfDay()
            : now()->endOfDay();

        $granularity   = $data['granularity'] ?? 'auto'; // day|month|auto
        $grain         = $this->resolveGrain($granularity, $start, $end); // 'day' o 'month'
        $periodFormat  = $grain === 'day' ? 'YYYY-MM-DD' : 'YYYY-MM';

        $paymentStatus = $data['paymentStatus'] ?? 'approved'; // approved|pending|rejected|any

        // Totales (Gastos)
        $totalExpensesPaidByYou = DB::table('expenses as e')
            ->when($groupId, fn($q) => $q->where('e.group_id', $groupId))
            ->where('e.payer_id', $userId)
            ->whereBetween('e.expense_date', [$start->toDateString(), $end->toDateString()])
            ->sum('e.total_amount');

        $totalYourShare = DB::table('expense_participants as ep')
            ->join('expenses as e', 'e.id', '=', 'ep.expense_id')
            ->where('ep.user_id', $userId)
            ->when($groupId, fn($q) => $q->where('e.group_id', $groupId))
            ->whereBetween('e.expense_date', [$start->toDateString(), $end->toDateString()])
            ->sum('ep.amount_due');

        $totalOwedToYou = DB::table('expense_participants as ep')
            ->join('expenses as e', 'e.id', '=', 'ep.expense_id')
            ->where('e.payer_id', $userId)
            ->where('ep.user_id', '!=', $userId)
            ->when($groupId, fn($q) => $q->where('e.group_id', $groupId))
            ->whereBetween('e.expense_date', [$start->toDateString(), $end->toDateString()])
            ->sum('ep.amount_due');

        // Base de pagos
        $paymentsBase = DB::table('payments as p')
            ->when($paymentStatus !== 'any', fn($q) => $q->where('p.status', $paymentStatus))
            ->whereNotNull('p.payment_date')
            ->whereBetween('p.payment_date', [$start->toDateTimeString(), $end->toDateTimeString()]);

        $totalIncoming = (clone $paymentsBase)
            ->where('p.to_user_id', $userId)
            ->when($groupId, function ($q) use ($groupId) {
                $q->whereExists(function ($sub) use ($groupId) {
                    $sub->from('expense_participants as ep')
                        ->join('expenses as e', 'e.id', '=', 'ep.expense_id')
                        ->whereColumn('ep.payment_id', 'p.id')
                        ->where('e.group_id', $groupId);
                });
            })
            ->sum('p.amount');

        $totalOutgoing = (clone $paymentsBase)
            ->where('p.from_user_id', $userId)
            ->when($groupId, function ($q) use ($groupId) {
                $q->whereExists(function ($sub) use ($groupId) {
                    $sub->from('expense_participants as ep')
                        ->join('expenses as e', 'e.id', '=', 'ep.expense_id')
                        ->whereColumn('ep.payment_id', 'p.id')
                        ->where('e.group_id', $groupId);
                });
            })
            ->sum('p.amount');

        // Series temporales
        $seriesExpensesPaidByYou = DB::table('expenses as e')
            ->when($groupId, fn($q) => $q->where('e.group_id', $groupId))
            ->where('e.payer_id', $userId)
            ->whereBetween('e.expense_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw("to_char(date_trunc('$grain', e.expense_date::timestamp), '$periodFormat') as period, SUM(e.total_amount) as total")
            ->groupByRaw("date_trunc('$grain', e.expense_date::timestamp)")
            ->orderByRaw("date_trunc('$grain', e.expense_date::timestamp)")
            ->get()
            ->map(fn($r) => ['period' => $r->period, 'total' => MoneyFormatter::format($r->total)]);

        $seriesYourShare = DB::table('expense_participants as ep')
            ->join('expenses as e', 'e.id', '=', 'ep.expense_id')
            ->where('ep.user_id', $userId)
            ->when($groupId, fn($q) => $q->where('e.group_id', $groupId))
            ->whereBetween('e.expense_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw("to_char(date_trunc('$grain', e.expense_date::timestamp), '$periodFormat') as period, SUM(ep.amount_due) as total")
            ->groupByRaw("date_trunc('$grain', e.expense_date::timestamp)")
            ->orderByRaw("date_trunc('$grain', e.expense_date::timestamp)")
            ->get()
            ->map(fn($r) => ['period' => $r->period, 'total' => MoneyFormatter::format($r->total)]);

        $seriesIncoming = DB::table('payments as p')
            ->when($paymentStatus !== 'any', fn($q) => $q->where('p.status', $paymentStatus))
            ->where('p.to_user_id', $userId)
            ->whereNotNull('p.payment_date')
            ->whereBetween('p.payment_date', [$start->toDateTimeString(), $end->toDateTimeString()])
            ->when($groupId, function ($q) use ($groupId) {
                $q->whereExists(function ($sub) use ($groupId) {
                    $sub->from('expense_participants as ep')
                        ->join('expenses as e', 'e.id', '=', 'ep.expense_id')
                        ->whereColumn('ep.payment_id', 'p.id')
                        ->where('e.group_id', $groupId);
                });
            })
            ->selectRaw("to_char(date_trunc('$grain', p.payment_date), '$periodFormat') as period, SUM(p.amount) as total")
            ->groupByRaw("date_trunc('$grain', p.payment_date)")
            ->orderByRaw("date_trunc('$grain', p.payment_date)")
            ->get()
            ->map(fn($r) => ['period' => $r->period, 'total' => MoneyFormatter::format($r->total)]);

        $seriesOutgoing = DB::table('payments as p')
            ->when($paymentStatus !== 'any', fn($q) => $q->where('p.status', $paymentStatus))
            ->where('p.from_user_id', $userId)
            ->whereNotNull('p.payment_date')
            ->whereBetween('p.payment_date', [$start->toDateTimeString(), $end->toDateTimeString()])
            ->when($groupId, function ($q) use ($groupId) {
                $q->whereExists(function ($sub) use ($groupId) {
                    $sub->from('expense_participants as ep')
                        ->join('expenses as e', 'e.id', '=', 'ep.expense_id')
                        ->whereColumn('ep.payment_id', 'p.id')
                        ->where('e.group_id', $groupId);
                });
            })
            ->selectRaw("to_char(date_trunc('$grain', p.payment_date), '$periodFormat') as period, SUM(p.amount) as total")
            ->groupByRaw("date_trunc('$grain', p.payment_date)")
            ->orderByRaw("date_trunc('$grain', p.payment_date)")
            ->get()
            ->map(fn($r) => ['period' => $r->period, 'total' => MoneyFormatter::format($r->total)]);

        // Desgloses
        $byGroupPaidByYou = DB::table('expenses as e')
            ->join('groups as g', 'g.id', '=', 'e.group_id')
            ->where('e.payer_id', $userId)
            ->when($groupId, fn($q) => $q->where('e.group_id', $groupId))
            ->whereBetween('e.expense_date', [$start->toDateString(), $end->toDateString()])
            ->select('g.id as group_id', 'g.name as group_name', DB::raw('SUM(e.total_amount) as total'))
            ->groupBy('g.id', 'g.name')
            ->orderByDesc(DB::raw('SUM(e.total_amount)'))
            ->get()
            ->map(fn($r) => ['group_id' => $r->group_id, 'group_name' => $r->group_name, 'total' => MoneyFormatter::format($r->total)]);

        $byGroupYourShare = DB::table('expense_participants as ep')
            ->join('expenses as e', 'e.id', '=', 'ep.expense_id')
            ->join('groups as g', 'g.id', '=', 'e.group_id')
            ->where('ep.user_id', $userId)
            ->when($groupId, fn($q) => $q->where('e.group_id', $groupId))
            ->whereBetween('e.expense_date', [$start->toDateString(), $end->toDateString()])
            ->select('g.id as group_id', 'g.name as group_name', DB::raw('SUM(ep.amount_due) as total'))
            ->groupBy('g.id', 'g.name')
            ->orderByDesc(DB::raw('SUM(ep.amount_due)'))
            ->get()
            ->map(fn($r) => ['group_id' => $r->group_id, 'group_name' => $r->group_name, 'total' => MoneyFormatter::format($r->total)]);

        $byGroupOutgoing = DB::table('payments as p')
            ->join('expense_participants as ep', 'ep.payment_id', '=', 'p.id')
            ->join('expenses as e', 'e.id', '=', 'ep.expense_id')
            ->join('groups as g', 'g.id', '=', 'e.group_id')
            ->when($paymentStatus !== 'any', fn($q) => $q->where('p.status', $paymentStatus))
            ->where('p.from_user_id', $userId)
            ->whereNotNull('p.payment_date')
            ->whereBetween('p.payment_date', [$start->toDateTimeString(), $end->toDateTimeString()])
            ->when($groupId, fn($q) => $q->where('e.group_id', $groupId))
            ->select('g.id as group_id', 'g.name as group_name', DB::raw('SUM(ep.amount_due) as total'))
            ->groupBy('g.id', 'g.name')
            ->orderByDesc(DB::raw('SUM(ep.amount_due)'))
            ->get()
            ->map(fn($r) => ['group_id' => $r->group_id, 'group_name' => $r->group_name, 'total' => MoneyFormatter::format($r->total)]);

        $byGroupIncoming = DB::table('payments as p')
            ->join('expense_participants as ep', 'ep.payment_id', '=', 'p.id')
            ->join('expenses as e', 'e.id', '=', 'ep.expense_id')
            ->join('groups as g', 'g.id', '=', 'e.group_id')
            ->when($paymentStatus !== 'any', fn($q) => $q->where('p.status', $paymentStatus))
            ->where('p.to_user_id', $userId)
            ->whereNotNull('p.payment_date')
            ->whereBetween('p.payment_date', [$start->toDateTimeString(), $end->toDateTimeString()])
            ->when($groupId, fn($q) => $q->where('e.group_id', $groupId))
            ->select('g.id as group_id', 'g.name as group_name', DB::raw('SUM(ep.amount_due) as total'))
            ->groupBy('g.id', 'g.name')
            ->orderByDesc(DB::raw('SUM(ep.amount_due)'))
            ->get()
            ->map(fn($r) => ['group_id' => $r->group_id, 'group_name' => $r->group_name, 'total' => MoneyFormatter::format($r->total)]);

        $topOutgoingByCounterparty = DB::table('payments as p')
            ->join('users as u', 'u.id', '=', 'p.to_user_id')
            ->when($paymentStatus !== 'any', fn($q) => $q->where('p.status', $paymentStatus))
            ->where('p.from_user_id', $userId)
            ->whereNotNull('p.payment_date')
            ->whereBetween('p.payment_date', [$start->toDateTimeString(), $end->toDateTimeString()])
            ->select('u.id as user_id', 'u.name as name', DB::raw('SUM(p.amount) as total'))
            ->groupBy('u.id', 'u.name')
            ->orderByDesc(DB::raw('SUM(p.amount)'))
            ->limit(5)
            ->get()
            ->map(fn($r) => ['user_id' => $r->user_id, 'name' => $r->name, 'total' => MoneyFormatter::format($r->total)]);

        $topIncomingByCounterparty = DB::table('payments as p')
            ->join('users as u', 'u.id', '=', 'p.from_user_id')
            ->when($paymentStatus !== 'any', fn($q) => $q->where('p.status', $paymentStatus))
            ->where('p.to_user_id', $userId)
            ->whereNotNull('p.payment_date')
            ->whereBetween('p.payment_date', [$start->toDateTimeString(), $end->toDateTimeString()])
            ->select('u.id as user_id', 'u.name as name', DB::raw('SUM(p.amount) as total'))
            ->groupBy('u.id', 'u.name')
            ->orderByDesc(DB::raw('SUM(p.amount)'))
            ->limit(5)
            ->get()
            ->map(fn($r) => ['user_id' => $r->user_id, 'name' => $r->name, 'total' => MoneyFormatter::format($r->total)]);

        return response()->json([
            'filters' => [
                'groupId'       => $groupId,
                'startDate'     => $start->toDateString(),
                'endDate'       => $end->toDateString(),
                'granularity'   => $grain,
                'paymentStatus' => $paymentStatus,
            ],
            'totals' => [
                'expenses' => [
                    'paid_by_you' => MoneyFormatter::format($totalExpensesPaidByYou),
                    'your_share'  => MoneyFormatter::format($totalYourShare),
                    'owed_to_you' => MoneyFormatter::format($totalOwedToYou),
                ],
                'payments' => [
                    'incoming'    => MoneyFormatter::format($totalIncoming),
                    'outgoing'    => MoneyFormatter::format($totalOutgoing),
                    'net'         => MoneyFormatter::format($totalIncoming - $totalOutgoing),
                ],
            ],
            'timeseries' => [
                'expenses_paid_by_you' => $seriesExpensesPaidByYou,
                'your_share'           => $seriesYourShare,
                'payments_incoming'    => $seriesIncoming,
                'payments_outgoing'    => $seriesOutgoing,
            ],
            'breakdown' => [
                'by_group' => [
                    'expenses_paid_by_you' => $byGroupPaidByYou,
                    'your_share'           => $byGroupYourShare,
                    'payments_incoming'    => $byGroupIncoming,
                    'payments_outgoing'    => $byGroupOutgoing,
                ],
                'by_counterparty' => [
                    'outgoing_top5' => $topOutgoingByCounterparty,
                    'incoming_top5' => $topIncomingByCounterparty,
                ],
            ],
        ], 200);
    }

    // Helpers
    private function resolveGrain(string $granularity, Carbon $start, Carbon $end): string
    {
        if ($granularity === 'day' || $granularity === 'month') {
            return $granularity;
        }
        // auto: si el rango es <= 45 días => 'day', si no 'month'
        return $start->diffInDays($end) <= 45 ? 'day' : 'month';
    }
}
