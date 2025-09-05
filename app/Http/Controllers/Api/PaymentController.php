// app/Http/Controllers/Api/PaymentController.php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\Payment;
use App\Jobs\SendPushNotification;
use Illuminate\Validation\Rule;
use App\Services\PaymentService;
use App\Http\Resources\PaymentResource;
use App\Http\Requests\Payment\StorePaymentRequest;
use App\Http\Requests\Payment\ApprovePaymentRequest;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $paymentService) {}

    public function index(Request $request): JsonResponse
    {
        $userId    = $request->user()->id;
        $status    = $request->query('status');
        $direction = $request->query('direction');

        $groupId = $request->query('group_id', $request->query('groupId'));
        $startQ  = $request->query('start_date', $request->query('startDate'));
        $endQ    = $request->query('end_date',   $request->query('endDate'));

        $start = $startQ ? Carbon::parse($startQ)->startOfDay() : null;
        $end   = $endQ   ? Carbon::parse($endQ)->endOfDay()     : null;

        $result = $this->paymentService->listPayments($userId, $status, $direction, $groupId, $start, $end);
        return response()->json($result, 200);
    }

    public function store(StorePaymentRequest $request): JsonResponse
    {
        $userId = $request->user()->id;
        $data   = $request->validated();

        $result = $this->paymentService->createPayment($userId, $data);
        return response()->json($result, 201);
    }

    public function show(string $paymentId, Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $model = Payment::with(['payer','receiver'])->find($paymentId);
        if (!$model) return response()->json(['message' => 'Pago no encontrado'], 404);
        if ($model->from_user_id !== $userId && $model->to_user_id !== $userId) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $model->payer_name    = $model->payer?->name;
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

        $model->participants = $eps;
        $model->direction = $model->from_user_id === $userId ? 'outgoing' : 'incoming';

        return (new PaymentResource($model))->response();
    }

    public function update(string $paymentId, Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $p = DB::table('payments')->where('id', $paymentId)->first();
        if (!$p) return response()->json(['message' => 'Pago no encontrado'], 404);
        if ($p->from_user_id !== $userId) return response()->json(['message' => 'Solo el pagador puede actualizar el pago'], 403);
        if ($p->status !== 'pending') return response()->json(['message' => 'Solo puedes actualizar pagos pendientes'], 409);

        $data = $request->validate([
            'payment_method' => ['sometimes', 'nullable', 'string', Rule::in(['cash', 'transfer'])],
            'evidence_url'   => ['sometimes', 'nullable', 'url'],
            'signature'      => ['sometimes', 'nullable', 'string'],
        ]);

        if (empty($data)) return response()->json(['message' => 'Nada que actualizar'], 422);

        $data['updated_at'] = now();

        DB::table('payments')
            ->where('id', $paymentId)
            ->where('status', 'pending')
            ->update($data);

        $updated = DB::table('payments as pmt')
            ->leftJoin('users as payer', 'payer.id', '=', 'pmt.from_user_id')
            ->leftJoin('users as recv',  'recv.id',  '=', 'pmt.to_user_id')
            ->where('pmt.id', $paymentId)
            ->select('pmt.*', 'payer.name as payer_name', 'recv.name as receiver_name')
            ->first();

        $updated->direction = 'outgoing';

        return (new PaymentResource($updated))
            ->additional(['message' => 'Pago actualizado'])
            ->response();
    }

    public function approve(string $paymentId, ApprovePaymentRequest $request): JsonResponse
    {
        $userId = $request->user()->id;
        $applied = [];

        $result = DB::transaction(function () use ($paymentId, $userId, &$applied) {
            $payment = DB::table('payments')->where('id', $paymentId)->lockForUpdate()->first();

            if (!$payment) return ['error' => ['status' => 404, 'message' => 'Pago no encontrado']];
            if ($payment->to_user_id !== $userId) return ['error' => ['status' => 403, 'message' => 'Solo el receptor puede aprobar este pago']];
            if ($payment->status !== 'pending') return ['error' => ['status' => 409, 'message' => 'Solo puedes aprobar pagos pendientes']];

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

            $affectedExpenseIds = [];

            foreach ($pending as $row) {
                if ($remaining <= 0) break;
                $due   = (int) round($row->amount_due * 100);
                $apply = min($due, $remaining);

                DB::table('expense_participants')->where('id', $row->id)->update([
                    'is_paid'    => $apply === $due,
                    'payment_id' => $payment->id,
                ]);

                if ($apply < $due) {
                    DB::table('expense_participants')->insert([
                        'id'         => (string) Str::uuid(),
                        'expense_id' => $row->expense_id,
                        'user_id'    => $row->user_id,
                        'amount_due' => ($due - $apply) / 100,
                        'is_paid'    => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $applied[] = [
                    'expense_id'     => $row->expense_id,
                    'participant_id' => $row->id,
                    'amount'         => $apply / 100,
                ];

                $remaining -= $apply;
                $affectedExpenseIds[] = $row->expense_id;
            }

            DB::table('payments')->where('id', $payment->id)->update([
                'status'           => 'approved',
                'payment_date'     => now(),
                'unapplied_amount' => $remaining / 100,
                'updated_at'       => now(),
            ]);

            foreach (array_unique($affectedExpenseIds) as $expenseId) {
                $allPaid = !DB::table('expense_participants')
                    ->where('expense_id', $expenseId)
                    ->where('is_paid', false)
                    ->exists();

                if ($allPaid) {
                    DB::table('expenses')->where('id', $expenseId)->update(['status' => 'completed']);
                }
            }

            return ['payment' => $payment];
        });

        if (isset($result['error'])) {
            $err = $result['error'];
            return response()->json(['message' => $err['message']], $err['status']);
        }

        /** @var object $payment */
        $payment = $result['payment'];

        $updated = DB::table('payments as p')
            ->leftJoin('users as payer', 'payer.id', '=', 'p.from_user_id')
            ->leftJoin('users as recv',  'recv.id',  '=', 'p.to_user_id')
            ->where('p.id', $paymentId)
            ->select('p.*', 'payer.name as payer_name', 'recv.name as receiver_name')
            ->first();

        $updated->direction = $updated->from_user_id === $userId ? 'outgoing' : 'incoming';

        SendPushNotification::dispatch(
            $payment->from_user_id,
            'Pago aprobado',
            "Tu pago fue aprobado por {$updated->receiver_name}"
        );

        return (new PaymentResource($updated))
            ->additional(['message' => 'Payment approved', 'applied' => $applied])
            ->response();
    }

    public function reject(string $paymentId, Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $p = DB::table('payments')->where('id', $paymentId)->first();
        if (!$p) return response()->json(['message' => 'Pago no encontrado'], 404);
        if ($p->to_user_id !== $userId) return response()->json(['message' => 'Solo el receptor puede rechazar este pago'], 403);
        if ($p->status !== 'pending') return response()->json(['message' => 'Solo puedes rechazar pagos pendientes'], 409);

        DB::table('payments')->where('id', $paymentId)->update([
            'status'     => 'rejected',
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Payment rejected'], 200);
    }

    public function due(Request $request): JsonResponse
    {
        $userId  = $request->user()->id;
        $groupId = $request->query('group_id', $request->query('groupId'));

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

    private function money($value): string
    {
        return number_format((float) ($value ?? 0), 2, '.', '');
    }
}
