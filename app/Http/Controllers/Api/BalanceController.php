<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BalanceController extends Controller
{
    public function show(string $groupId, Request $request)
    {
        $userId = $request->user()->id;
        $belongs = DB::table('group_members')->where('group_id', $groupId)->where('user_id', $userId)->exists();
        if (!$belongs) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $rows = DB::table('group_members as gm')
            ->join('users as u', 'u.id', '=', 'gm.user_id')
            ->leftJoin('expenses as e', function ($join) use ($groupId) {
                $join->on('e.group_id', '=', 'gm.group_id')
                    ->where('e.status', 'approved');
            })
            ->leftJoin('expense_participants as ep', 'ep.expense_id', '=', 'e.id')
            ->leftJoin('payments as p', function ($join) {
                $join->on('p.id', '=', 'ep.payment_id')
                    ->where('p.status', 'approved');
            })
            ->where('gm.group_id', $groupId)
            ->selectRaw('
                gm.user_id,
                u.name,
                COALESCE(SUM(CASE WHEN e.payer_id = gm.user_id THEN e.total_amount ELSE 0 END),0)
                - COALESCE(SUM(CASE WHEN ep.user_id = gm.user_id AND ep.is_paid = false THEN ep.amount_due ELSE 0 END),0)
                + COALESCE(SUM(CASE WHEN p.from_user_id = gm.user_id THEN p.unapplied_amount ELSE 0 END),0)
                - COALESCE(SUM(CASE WHEN p.to_user_id = gm.user_id THEN p.unapplied_amount ELSE 0 END),0)
                AS balance
            ')
            ->groupBy('gm.user_id', 'u.name')
            ->get();

        return response()->json([
            'group_id' => $groupId,
            'as_of' => Carbon::today()->toDateString(),
            'data' => $rows,
        ]);
    }

    public function previewSettlements(string $groupId, Request $request)
    {
        $balances = $this->show($groupId, $request)->getData(true)['data'];
        $transfers = $this->suggestSettlements($balances);
        return response()->json([
            'group_id' => $groupId,
            'transfers' => $transfers,
        ]);
    }

    private function suggestSettlements(array $balances): array
    {
        $creditors = [];
        $debtors = [];
        foreach ($balances as $b) {
            $amt = (int) round($b['balance'] * 100);
            if ($amt > 0) $creditors[] = ['user_id' => $b['user_id'], 'amt' => $amt];
            if ($amt < 0) $debtors[] = ['user_id' => $b['user_id'], 'amt' => -$amt];
        }
        usort($creditors, fn($a, $b) => $b['amt'] <=> $a['amt']);
        usort($debtors, fn($a, $b) => $b['amt'] <=> $a['amt']);

        $transfers = [];
        while ($creditors && $debtors) {
            $c =& $creditors[0];
            $d =& $debtors[0];
            $x = min($c['amt'], $d['amt']);
            $transfers[] = [
                'from_user_id' => $d['user_id'],
                'to_user_id' => $c['user_id'],
                'amount' => $x / 100,
            ];
            $c['amt'] -= $x;
            $d['amt'] -= $x;
            if ($c['amt'] === 0) array_shift($creditors);
            if ($d['amt'] === 0) array_shift($debtors);
        }
        return $transfers;
    }
}
