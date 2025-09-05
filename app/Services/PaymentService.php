<?php

namespace App\Services;

use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function listPayments(string $userId, ?string $status, ?string $direction, ?string $groupId, ?Carbon $start, ?Carbon $end): array
    {
        $query = Payment::with(['payer', 'receiver'])
            ->when($direction === 'incoming', fn($q) => $q->where('to_user_id', $userId))
            ->when($direction === 'outgoing', fn($q) => $q->where('from_user_id', $userId))
            ->when(!$direction, function ($q) use ($userId) {
                $q->where(function ($w) use ($userId) {
                    $w->where('from_user_id', $userId)->orWhere('to_user_id', $userId);
                });
            })
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($groupId, fn($q) => $q->where('group_id', $groupId))
            ->when($start, fn($q) => $q->whereRaw('COALESCE(payment_date, created_at) >= ?', [$start->toDateTimeString()]))
            ->when($end, fn($q) => $q->whereRaw('COALESCE(payment_date, created_at) <= ?', [$end->toDateTimeString()]))
            ->orderByDesc(DB::raw('COALESCE(payment_date, created_at)'));

        /** @var LengthAwarePaginator $items */
        $items = $query->paginate(15);

        $data = $items->getCollection()->map(function (Payment $p) use ($userId) {
            return $this->formatPaymentRow($p, $userId);
        });

        return [
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
        ];
    }

    public function createPayment(string $userId, array $data): array
    {
        if ($data['from_user_id'] !== $userId) {
            throw ValidationException::withMessages([
                'from_user_id' => ['No puedes crear pagos a nombre de otro usuario'],
            ]);
        }

        $members = DB::table('group_members')
            ->where('group_id', $data['group_id'])
            ->whereIn('user_id', [$data['from_user_id'], $data['to_user_id']])
            ->count();
        if ($members < 2) {
            throw ValidationException::withMessages([
                'group_id' => ['Ambos usuarios deben pertenecer al grupo'],
            ]);
        }

        $payment = DB::transaction(function () use ($data) {
            $payment = Payment::create([
                'id'             => (string) Str::uuid(),
                'group_id'       => $data['group_id'],
                'from_user_id'   => $data['from_user_id'],
                'to_user_id'     => $data['to_user_id'],
                'amount'         => $data['amount'],
                'note'           => $data['note'] ?? null,
                'evidence_url'   => $data['evidence_url'] ?? null,
                'payment_method' => $data['payment_method'] ?? null,
                'status'         => 'pending',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            return $payment->load(['payer', 'receiver']);
        });

        return [
            'message' => 'Payment created',
            'payment' => $this->formatPaymentRow($payment, $userId),
        ];
    }

    private function formatPaymentRow(Payment $p, string $currentUserId): array
    {
        $direction = $p->from_user_id === $currentUserId ? 'outgoing' :
            ($p->to_user_id === $currentUserId ? 'incoming' : 'other');

        return [
            'id'               => $p->id,
            'group_id'         => $p->group_id,
            'amount'           => $this->money($p->amount),
            'status'           => $p->status,
            'payment_date'     => $p->payment_date,
            'payment_method'   => $p->payment_method,
            'note'             => $p->note,
            'evidence_url'     => $p->evidence_url,
            'from_user_id'     => $p->from_user_id,
            'payer_name'       => $p->payer?->name,
            'to_user_id'       => $p->to_user_id,
            'receiver_name'    => $p->receiver?->name,
            'direction'        => $direction,
            'unapplied_amount' => $this->money($p->unapplied_amount ?? 0),
        ];
    }

    private function money($value): string
    {
        return number_format((float) ($value ?? 0), 2, '.', '');
    }
}
