<?php

namespace App\Services;

use App\Jobs\ProcessExpenseOcr;
use App\Models\Expense;
use App\Models\ExpenseParticipant;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ExpenseService
{
    public function listExpenses(string $userId, ?string $groupId, ?Carbon $start, ?Carbon $end): array
    {
        $query = Expense::with(['participants.user'])
            ->where(function ($q) use ($userId) {
                $q->where('payer_id', $userId)
                    ->orWhereHas('participants', function ($qq) use ($userId) {
                        $qq->where('user_id', $userId);
                    });
            })
            ->when($groupId, fn($q) => $q->where('group_id', $groupId))
            ->when($start, fn($q) => $q->whereDate('expense_date', '>=', $start))
            ->when($end, fn($q) => $q->whereDate('expense_date', '<=', $end))
            ->orderByDesc('expense_date')
            ->orderByDesc('created_at');

        /** @var LengthAwarePaginator $items */
        $items = $query->paginate(15);

        $data = $items->getCollection()->map(function (Expense $e) use ($userId) {
            $participants = $e->participants->map(function (ExpenseParticipant $p) {
                return [
                    'id'         => $p->id,
                    'user_id'    => $p->user_id,
                    'user_name'  => $p->user?->name,
                    'user_email' => $p->user?->email,
                    'amount_due' => $this->money($p->amount_due),
                    'is_paid'    => $p->is_paid,
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
                'expense_date'     => optional($e->expense_date)->toDateString(),
                'participants'     => $participants,
                'role'             => $e->payer_id === $userId ? 'payer' : 'participant',
            ];
        });

        return [
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
        ];
    }

    public function createExpense(string $userId, array $data): array
    {
        $this->assertGroupMembershipOrFail($userId, $data['group_id']);
        $uniqueUserIds = collect($data['participants'])->pluck('user_id')->unique();
        foreach ($uniqueUserIds as $uid) {
            $this->assertGroupMembershipOrFail($uid, $data['group_id']);
        }

        $sum = collect($data['participants'])->sum(fn($p) => (float)$p['amount_due']);
        if ($this->money($sum) !== $this->money($data['total_amount'])) {
            throw ValidationException::withMessages([
                'participants' => ['La suma de amount_due no coincide con total_amount.'],
            ]);
        }

        $hasTicket = (bool) $data['has_ticket'];
        $ticketUrl = $hasTicket ? ($data['ticket_image_url'] ?? null) : null;
        $ocrStatus = $hasTicket ? 'pending' : 'skipped';

        $expense = DB::transaction(function () use ($data, $userId, $ticketUrl, $ocrStatus) {
            $expense = Expense::create([
                'id'               => (string) Str::uuid(),
                'description'      => $data['description'],
                'total_amount'     => $data['total_amount'],
                'payer_id'         => $userId,
                'group_id'         => $data['group_id'],
                'ticket_image_url' => $ticketUrl,
                'ocr_status'       => $ocrStatus,
                'ocr_raw_text'     => null,
                'status'           => 'pending',
                'expense_date'     => $data['expense_date'],
            ]);

            $rows = [];
            foreach ($data['participants'] as $p) {
                $rows[] = [
                    'id'         => (string) Str::uuid(),
                    'user_id'    => $p['user_id'],
                    'amount_due' => $p['amount_due'],
                    'is_paid'    => false,
                    'payment_id' => null,
                ];
            }
            $expense->participants()->createMany($rows);

            return $expense;
        });

        if ($hasTicket && $ticketUrl) {
            ProcessExpenseOcr::dispatch($expense->id);
        }

        return [
            'message' => 'Gasto creado',
            'expense' => $this->formatExpense($expense),
        ];
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

    private function formatExpense(Expense $e): array
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
            'expense_date'     => optional($e->expense_date)->toDateString(),
            'created_at'       => $e->created_at,
            'updated_at'       => $e->updated_at,
        ];
    }

    private function money($value): string
    {
        return number_format((float) ($value ?? 0), 2, '.', '');
    }
}
