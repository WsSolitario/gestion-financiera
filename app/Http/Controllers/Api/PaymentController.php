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
            ->leftJoin('users as payer', 'payer.id', '=', 'p.payer_id')
            ->leftJoin('users as recv',  'recv.id',  '=', 'p.receiver_id')
            ->when($direction === 'incoming', fn($qq) => $qq->where('p.receiver_id', $userId))
            ->when($direction === 'outgoing', fn($qq) => $qq->where('p.payer_id', $userId))
            ->when(!$direction, function ($qq) use ($userId) {
                $qq->where(function ($w) use ($userId) {
                    $w->where('p.payer_id', $userId)->orWhere('p.receiver_id', $userId);
                });
            })
            ->when($status, fn($qq) => $qq->where('p.status', $status))
            ->when($groupId, function ($qq) use ($groupId) {
                $qq->whereExists(function ($sub) use ($groupId) {
                    $sub->from('expense_participants as ep')
                        ->join('expenses as e', 'e.id', '=', 'ep.expense_id')
                        ->whereColumn('ep.payment_id', 'p.id')
                        ->where('e.group_id', $groupId);
                });
            })
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
            'expense_participant_ids' => ['required', 'array', 'min:1'],
            'expense_participant_ids.*' => ['uuid'],
            'amount'          => ['sometimes', 'numeric', 'min:0'],
            'payment_method'  => ['sometimes', 'nullable', 'string', 'max:100'],
            'proof_url'       => ['sometimes', 'nullable', 'url'],
            'signature'       => ['sometimes', 'nullable', 'string'],
            'payment_date'    => ['sometimes', 'nullable', 'date'],
        ]);

        $payment = null;

        DB::transaction(function () use ($request, $userId, $data, &$payment) {
            // Re-leemos y bloqueamos filas EP para evitar carreras
            $eps = DB::table('expense_participants as ep')
                ->join('expenses as e', 'e.id', '=', 'ep.expense_id')
                ->select('ep.*', 'e.payer_id', 'e.group_id')
                ->whereIn('ep.id', $data['expense_participant_ids'])
                ->lockForUpdate()
                ->get();

            if ($eps->count() !== count($data['expense_participant_ids'])) {
                abort(response()->json(['message' => 'Algunos participantes no existen'], 422));
            }

            foreach ($eps as $ep) {
                if ($ep->user_id !== $userId) {
                    abort(response()->json(['message' => 'Solo puedes pagar tus propias deudas'], 403));
                }
                if ($ep->is_paid) {
                    abort(response()->json(['message' => 'Alguno de los participantes ya está pagado'], 409));
                }
                if (!empty($ep->payment_id)) {
                    abort(response()->json(['message' => 'Alguno de los participantes ya está ligado a otro pago pendiente'], 409));
                }
            }

            $receiverIds = $eps->pluck('payer_id')->unique();
            if ($receiverIds->count() !== 1) {
                abort(response()->json(['message' => 'Todos los participantes deben corresponder al mismo receptor'], 422));
            }
            $receiverId = $receiverIds->first();

            $sum = (float) $eps->sum('amount_due');
            if (isset($data['amount']) && $this->money($data['amount']) !== $this->money($sum)) {
                abort(response()->json(['message' => 'El monto no coincide con la suma de las deudas seleccionadas'], 422));
            }

            $paymentId = (string) Str::uuid();

            DB::table('payments')->insert([
                'id'             => $paymentId,
                'payer_id'       => $userId,
                'receiver_id'    => $receiverId,
                'amount'         => $sum,
                'payment_method' => $data['payment_method'] ?? null,
                'proof_url'      => $data['proof_url'] ?? null,
                'signature'      => $data['signature'] ?? null,
                'status'         => 'pending',
                'payment_date'   => $data['payment_date'] ?? null,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            // Ligar EPs al pago solo si siguen sin pago
            DB::table('expense_participants')
                ->whereIn('id', $eps->pluck('id')->all())
                ->whereNull('payment_id')
                ->update(['payment_id' => $paymentId]);

            $payment = DB::table('payments as p')
                ->leftJoin('users as payer', 'payer.id', '=', 'p.payer_id')
                ->leftJoin('users as recv',  'recv.id',  '=', 'p.receiver_id')
                ->where('p.id', $paymentId)
                ->select('p.*', 'payer.name as payer_name', 'recv.name as receiver_name')
                ->first();
        });

        return response()->json([
            'message' => 'Pago creado (pendiente de aprobación del receptor)',
            'payment' => $payment ? $this->formatPaymentRow($payment, $userId) : null,
        ], 201);
    }

    public function show(string $paymentId, Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $model = Payment::query()->find($paymentId);
        if (!$model) return response()->json(['message' => 'Pago no encontrado'], 404);

        // Authorize only after confirming the payment exists
        $this->authorize('view', $model);

        $p = DB::table('payments as p')
            ->leftJoin('users as payer', 'payer.id', '=', 'p.payer_id')
            ->leftJoin('users as recv',  'recv.id',  '=', 'p.receiver_id')
            ->where('p.id', $paymentId)
            ->select('p.*', 'payer.name as payer_name', 'recv.name as receiver_name')
            ->first();
        if (!$p) return response()->json(['message' => 'Pago no encontrado'], 404);

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

        $payload = $this->formatPaymentRow($p, $userId);
        $payload['participants'] = $eps;

        return response()->json($payload, 200);
    }

    public function update(string $paymentId, Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $model = Payment::query()->find($paymentId);
        if (!$model) return response()->json(['message' => 'Pago no encontrado'], 404);
        if ($model->status !== 'pending') return response()->json(['message' => 'Solo puedes actualizar pagos pendientes'], 409);

        $this->authorize('update', $model);

        $data = $request->validate([
            'payment_method' => ['sometimes', 'nullable', 'string', 'max:100'],
            'proof_url'      => ['sometimes', 'nullable', 'url'],
            'signature'      => ['sometimes', 'nullable', 'string'],
        ]);

        if (empty($data)) return response()->json(['message' => 'Nada que actualizar'], 422);

        $data['updated_at'] = now();
        DB::table('payments')->where('id', $paymentId)->update($data);

        $updated = DB::table('payments as p')
            ->leftJoin('users as payer', 'payer.id', '=', 'p.payer_id')
            ->leftJoin('users as recv',  'recv.id',  '=', 'p.receiver_id')
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

        $model = Payment::query()->find($paymentId);
        if (!$model) return response()->json(['message' => 'Pago no encontrado'], 404);
        if ($model->status !== 'pending') return response()->json(['message' => "El pago no está pendiente (estado: {$model->status})"], 409);

        $this->authorize('approve', $model);

        $sum = DB::table('expense_participants')->where('payment_id', $paymentId)->sum('amount_due');
        if ($this->money($sum) !== $this->money($model->amount)) {
            return response()->json(['message' => 'Inconsistencia: la suma de EPs no coincide con el monto del pago'], 422);
        }

        DB::transaction(function () use ($paymentId, $model) {
            DB::table('payments')->where('id', $paymentId)->update([
                'status'       => 'completed',
                'payment_date' => $model->payment_date ?? now(),
                'updated_at'   => now(),
            ]);

            DB::table('expense_participants')
                ->where('payment_id', $paymentId)
                ->update(['is_paid' => true]);
        });

        $updated = DB::table('payments as p')
            ->leftJoin('users as payer', 'payer.id', '=', 'p.payer_id')
            ->leftJoin('users as recv',  'recv.id',  '=', 'p.receiver_id')
            ->where('p.id', $paymentId)
            ->select('p.*', 'payer.name as payer_name', 'recv.name as receiver_name')
            ->first();

        return response()->json([
            'message' => 'Pago aprobado',
            'payment' => $this->formatPaymentRow($updated, $userId),
        ], 200);
    }

    // Rechaza un pago pendiente y libera los EPs asociados
    public function reject(string $paymentId, Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $model = Payment::query()->find($paymentId);
        if (!$model) return response()->json(['message' => 'Pago no encontrado'], 404);
        if ($model->status !== 'pending') return response()->json(['message' => 'Solo puedes rechazar pagos pendientes'], 409);

        $this->authorize('reject', $model);

        DB::transaction(function () use ($paymentId) {
            // liberar EPs
            DB::table('expense_participants')
                ->where('payment_id', $paymentId)
                ->update(['payment_id' => null]);

            DB::table('payments')
                ->where('id', $paymentId)
                ->update(['status' => 'rejected', 'updated_at' => now()]);
        });

        return response()->json(['message' => 'Pago rechazado'], 200);
    }

    public function due(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $base = DB::table('expense_participants as ep')
            ->join('expenses as e', 'e.id', '=', 'ep.expense_id')
            ->join('users as u', 'u.id', '=', 'e.payer_id')
            ->where('ep.user_id', $userId)
            ->where('ep.is_paid', false);

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
        $direction = $p->payer_id === $currentUserId ? 'outgoing' :
            ($p->receiver_id === $currentUserId ? 'incoming' : 'other');

        return [
            'id'            => $p->id,
            'amount'        => $this->money($p->amount),
            'status'        => $p->status,
            'payment_date'  => $p->payment_date,
            'payment_method'=> $p->payment_method,
            'proof_url'     => $p->proof_url,
            'signature'     => $p->signature,
            'payer_id'      => $p->payer_id,
            'payer_name'    => $p->payer_name ?? null,
            'receiver_id'   => $p->receiver_id,
            'receiver_name' => $p->receiver_name ?? null,
            'direction'     => $direction,
        ];
    }

    private function money($value): string
    {
        return number_format((float) ($value ?? 0), 2, '.', '');
    }
}
