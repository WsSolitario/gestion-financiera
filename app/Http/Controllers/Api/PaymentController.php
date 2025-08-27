<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\Payment;

class PaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $userId    = $request->user()->id;
        $status    = $request->query('status');
        $direction = $request->query('direction');
        $groupId   = $request->query('groupId');
        $start     = $request->query('startDate') ? Carbon::parse($request->query('startDate'))->startOfDay() : null;
        $end       = $request->query('endDate')   ? Carbon::parse($request->query('endDate'))->endOfDay()   : null;

        $q = DB::table('payments as p')
            ->leftJoin('users as payer', 'payer.id', '=', 'p.from_user_id')
            ->leftJoin('users as recv',  'recv.id',  '=', 'p.to_user_id')
            ->when($direction === 'incoming', fn($qq) => $qq->where('p.to_user_id', $userId))
            ->when($direction === 'outgoing', fn($qq) => $qq->where('p.from_user_id', $userId))
            ->when(!$direction, function ($qq) use ($userId) {
                $qq->where(function ($w) use ($userId) {
                    $w->where('p.from_user_id', $userId)->orWhere('p.to_user_id', $userId);
                });
            })
            ->when($status, fn($qq) => $qq->where('p.status', $status))
            ->when($groupId, fn($qq) => $qq->where('p.group_id', $groupId))
            ->when($start, fn($qq) => $qq->whereRaw('COALESCE(p.payment_date, p.created_at) >= ?', [$start->toDateTimeString()]))
            ->when($end,   fn($qq) => $qq->whereRaw('COALESCE(p.payment_date, p.created_at) <= ?', [$end->toDateTimeString()]))
            ->select('p.*', 'payer.name as payer_name', 'recv.name as receiver_name')
            ->orderByDesc(DB::raw('COALESCE(p.payment_date, p.created_at)'));

        $items = $q->paginate(15);

        $data = collect($items->items())->map(function ($p) use ($userId) {
            return $this->formatPaymentRow($p, $userId);
        });

        return response()->json([
            'filters' => [
                'status'    => $status,
                'direction' => $direction,
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

    public function store(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $data = $request->validate([
            'group_id'     => ['required', 'uuid'],
            'from_user_id' => ['required', 'uuid'],
            'to_user_id'   => ['required', 'uuid', 'different:from_user_id'],
            'amount'       => ['required', 'numeric', 'gt:0'],
            'note'         => ['sometimes', 'nullable', 'string'],
            'evidence_url' => ['sometimes', 'nullable', 'url'],
        ]);

        if ($data['from_user_id'] !== $userId) {
            return response()->json(['message' => 'No puedes crear pagos a nombre de otro usuario'], 403);
        }

        $members = DB::table('group_members')
            ->where('group_id', $data['group_id'])
            ->whereIn('user_id', [$data['from_user_id'], $data['to_user_id']])
            ->count();
        if ($members < 2) {
            return response()->json(['message' => 'Ambos usuarios deben pertenecer al grupo'], 422);
        }

        $paymentId = (string) Str::uuid();
        DB::table('payments')->insert([
            'id' => $paymentId,
            'group_id' => $data['group_id'],
            'from_user_id' => $data['from_user_id'],
            'to_user_id' => $data['to_user_id'],
            'amount' => $data['amount'],
            'note' => $data['note'] ?? null,
            'evidence_url' => $data['evidence_url'] ?? null,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payment = DB::table('payments as p')
            ->leftJoin('users as payer', 'payer.id', '=', 'p.from_user_id')
            ->leftJoin('users as recv',  'recv.id',  '=', 'p.to_user_id')
            ->where('p.id', $paymentId)
            ->select('p.*', 'payer.name as payer_name', 'recv.name as receiver_name')
            ->first();

        return response()->json([
            'message' => 'Payment created',
            'payment' => $this->formatPaymentRow($payment, $userId),
        ], 201);
    }

    public function show(string $paymentId, Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $model = Payment::with(['payer','receiver'])->find($paymentId);

        if (!$model) return response()->json(['message' => 'Pago no encontrado'], 404);
        if ($model->from_user_id !== $userId && $model->to_user_id !== $userId) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $model->payer_name = $model->payer?->name;
        $model->receiver_name = $model->receiver?->name;

        $eps = DB::table('expense_participants as ep')
            ->join('expenses as e', 'e.id', '=', 'ep.expense_id')
            ->join('users as u', 'u.id', '=', 'ep.user_id')
            ->where('ep.payment_id', $paymentId)
            ->select(
            'ep.id', 'ep.expense_id', 'ep.user_id', 'u.name as user_name', 'u.email as user_email',
            'ep.amount_due', 'ep.is_paid', 'e.group_id', 'e.description', 'e.expense_date'
        )
            ->get()
            ->map(function ($row) {
                return [
                    'id'           => $row->id,
                    'expense_id'   => $row->expense_id,
                    'group_id'     => $row->group_id,
                    'user_id'      => $row->user_id,
                    'user_name'    => $row->user_name,
                    'user_email'   => $row->user_email,
                    'amount_due'   => $this->money($row->amount_due),
                    'is_paid'      => (bool) $row->is_paid,
                    'expense_desc' => $row->description,
                    'expense_date' => $row->expense_date,
                ];
            });

        $payload = $this->formatPaymentRow($model, $userId);
        $payload['participants'] = $eps;

        return response()->json($payload, 200);
    }

    public function update(string $paymentId, Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $p = DB::table('payments')->where('id', $paymentId)->first();
        if (!$p) return response()->json(['message' => 'Pago no encontrado'], 404);
        if ($p->from_user_id !== $userId) return response()->json(['message' => 'Solo el pagador puede actualizar el pago'], 403);
        if ($p->status !== 'pending') return response()->json(['message' => 'Solo puedes actualizar pagos pendientes'], 409);

        $data = $request->validate([
            'payment_method' => ['sometimes', 'nullable', 'string', 'max:100'],
            'proof_url'      => ['sometimes', 'nullable', 'url'],
            'signature'      => ['sometimes', 'nullable', 'string'],
        ]);

        if (empty($data)) return response()->json(['message' => 'Nada que actualizar'], 422);

        $data['updated_at'] = now();
        DB::table('payments')->where('id', $paymentId)->update($data);

        $updated = DB::table('payments as p')
            ->leftJoin('users as payer', 'payer.id', '=', 'p.from_user_id')
            ->leftJoin('users as recv',  'recv.id',  '=', 'p.to_user_id')
            ->where('p.id', $paymentId)
            ->select('p.*', 'payer.name as payer_name', 'recv.name as receiver_name')
            ->first();

        return response()->json([
            'message' => 'Pago actualizado',
            'payment' => $this->formatPaymentRow($updated, $userId),
        ], 200);
    }

    public function approve(string $paymentId, Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $payment = DB::table('payments')->where('id', $paymentId)->first();
        if (!$payment) return response()->json(['message' => 'Pago no encontrado'], 404);
        if ($payment->to_user_id !== $userId) return response()->json(['message' => 'Solo el receptor puede aprobar este pago'], 403);
        if ($payment->status !== 'pending') return response()->json(['message' => 'Solo puedes aprobar pagos pendientes'], 409);

        $applied = [];

        DB::transaction(function () use ($payment, &$applied) {
            $remaining = (int) round($payment->amount * 100);

            $pending = DB::table('expense_participants as ep')
                ->join('expenses as e', 'e.id', '=', 'ep.expense_id')
                ->where('e.group_id', $payment->group_id)
                ->where('ep.user_id', $payment->from_user_id)
                ->where('e.payer_id', $payment->to_user_id)
                ->where('ep.is_paid', false)
                ->orderBy('e.expense_date')
                ->lockForUpdate()
                ->get();

            foreach ($pending as $row) {
                if ($remaining <= 0) break;
                $due = (int) round($row->amount_due * 100);
                $apply = min($due, $remaining);

                DB::table('expense_participants')->where('id', $row->id)->update([
                    'is_paid' => $apply === $due,
                    'payment_id' => $payment->id,
                ]);

                if ($apply < $due) {
                    DB::table('expense_participants')->insert([
                        'id' => (string) Str::uuid(),
                        'expense_id' => $row->expense_id,
                        'user_id' => $row->user_id,
                        'amount_due' => ($due - $apply) / 100,
                        'is_paid' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $applied[] = [
                    'expense_id' => $row->expense_id,
                    'participant_id' => $row->id,
                    'amount' => $apply / 100,
                ];

                $remaining -= $apply;
            }

            DB::table('payments')->where('id', $payment->id)->update([
                'status' => 'approved',
                'payment_date' => now(),
                'unapplied_amount' => $remaining / 100,
                'updated_at' => now(),
            ]);
        });

        $updated = DB::table('payments as p')
            ->leftJoin('users as payer', 'payer.id', '=', 'p.from_user_id')
            ->leftJoin('users as recv',  'recv.id',  '=', 'p.to_user_id')
            ->where('p.id', $paymentId)
            ->select('p.*', 'payer.name as payer_name', 'recv.name as receiver_name')
            ->first();

        return response()->json([
            'message' => 'Payment approved',
            'payment' => $this->formatPaymentRow($updated, $userId),
            'applied' => $applied,
        ], 200);
    }

    // NUEVO: rechazar pago pendiente (libera EPs)
    public function reject(string $paymentId, Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $p = DB::table('payments')->where('id', $paymentId)->first();
        if (!$p) return response()->json(['message' => 'Pago no encontrado'], 404);
        if ($p->to_user_id !== $userId) return response()->json(['message' => 'Solo el receptor puede rechazar este pago'], 403);
        if ($p->status !== 'pending') return response()->json(['message' => 'Solo puedes rechazar pagos pendientes'], 409);

        DB::table('payments')->where('id', $paymentId)->update([
            'status' => 'rejected',
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Payment rejected'], 200);
    }

    public function due(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $groupId = $request->query('group_id');

        $base = DB::table('expense_participants as ep')
            ->join('expenses as e', 'e.id', '=', 'ep.expense_id')
            ->join('users as u', 'u.id', '=', 'e.payer_id')
            ->where('ep.user_id', $userId)
            ->where('ep.is_paid', false)
            ->when($groupId, fn($q) => $q->where('e.group_id', $groupId));

        $totalGlobal = (float) $base->clone()->sum('ep.amount_due');

        $byCreditor = $base->clone()
            ->select('u.id as creditor_id', 'u.name as creditor_name', DB::raw('SUM(ep.amount_due) as total'))
            ->groupBy('u.id', 'u.name')
            ->orderByDesc(DB::raw('SUM(ep.amount_due)'))
            ->get()
            ->map(fn($r) => [
                'creditor_id'   => $r->creditor_id,
                'creditor_name' => $r->creditor_name,
                'total'         => $this->money($r->total),
            ]);

        $byGroup = $base->clone()
            ->join('groups as g', 'g.id', '=', 'e.group_id')
            ->select('g.id as group_id', 'g.name as group_name', DB::raw('SUM(ep.amount_due) as total'))
            ->groupBy('g.id', 'g.name')
            ->orderByDesc(DB::raw('SUM(ep.amount_due)'))
            ->get()
            ->map(fn($r) => [
                'group_id'   => $r->group_id,
                'group_name' => $r->group_name,
                'total'      => $this->money($r->total),
            ]);

        $recent = $base->clone()
            ->leftJoin('users as me', 'me.id', '=', 'ep.user_id')
            ->select(
                'ep.id as expense_participant_id',
                'ep.amount_due',
                'ep.payment_id',
                'e.id as expense_id',
                'e.description',
                'e.expense_date',
                'e.payer_id',
                'u.name as creditor_name'
            )
            ->orderByDesc('e.expense_date')
            ->limit(5)
            ->get()
            ->map(fn($row) => [
                'expense_participant_id' => $row->expense_participant_id,
                'expense_id'             => $row->expense_id,
                'description'            => $row->description,
                'expense_date'           => $row->expense_date,
                'creditor_id'            => $row->payer_id,
                'creditor_name'          => $row->creditor_name,
                'amount_due'             => $this->money($row->amount_due),
                'linked_payment_id'      => $row->payment_id,
            ]);

        return response()->json([
            'total_due'   => $this->money($totalGlobal),
            'by_creditor' => $byCreditor,
            'by_group'    => $byGroup,
            'recent'      => $recent,
        ], 200);
    }

    private function formatPaymentRow(object $p, string $currentUserId): array
    {
        $direction = $p->from_user_id === $currentUserId ? 'outgoing' :
            ($p->to_user_id === $currentUserId ? 'incoming' : 'other');

        return [
            'id'            => $p->id,
            'group_id'      => $p->group_id,
            'amount'        => $this->money($p->amount),
            'status'        => $p->status,
            'payment_date'  => $p->payment_date,
            'note'          => $p->note,
            'evidence_url'  => $p->evidence_url,
            'from_user_id'  => $p->from_user_id,
            'payer_name'    => $p->payer_name ?? null,
            'to_user_id'    => $p->to_user_id,
            'receiver_name' => $p->receiver_name ?? null,
            'direction'     => $direction,
            'unapplied_amount' => $this->money($p->unapplied_amount ?? 0),
        ];
    }

    private function money($value): string
    {
        return number_format((float) ($value ?? 0), 2, '.', '');
    }
}
