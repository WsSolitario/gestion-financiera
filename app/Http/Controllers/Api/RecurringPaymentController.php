<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class RecurringPaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $items = DB::table('recurring_payments as rp')
            ->leftJoin('recurring_payment_viewers as rpv', 'rpv.recurring_payment_id', '=', 'rp.id')
            ->where(function ($q) use ($userId) {
                $q->where('rp.user_id', $userId)
                  ->orWhere('rpv.user_id', $userId);
            })
            ->select('rp.*')
            ->distinct()
            ->get();

        return response()->json($items, 200);
    }

    public function store(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $data = $request->validate([
            'title'               => ['required', 'string'],
            'description'         => ['required', 'string'],
            'amount_monthly'      => ['required', 'numeric', 'gt:0'],
            'months'              => ['required', 'integer', 'min:1'],
            'start_date'          => ['required', 'date'],
            'day_of_month'        => ['required', 'integer', 'between:1,31'],
            'reminder_days_before'=> ['required', 'integer', 'min:0'],
            'shared_with'         => ['sometimes', 'array'],
            'shared_with.*'       => ['uuid', 'exists:users,id'],
        ]);

        $id = (string) Str::uuid();

        DB::transaction(function () use ($data, $id, $userId) {
            DB::table('recurring_payments')->insert([
                'id'             => $id,
                'user_id'             => $userId,
                'title'               => $data['title'],
                'description'         => $data['description'],
                'amount_monthly'      => $data['amount_monthly'],
                'months'              => $data['months'],
                'start_date'          => $data['start_date'],
                'day_of_month'        => $data['day_of_month'],
                'reminder_days_before'=> $data['reminder_days_before'],
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);

            if (!empty($data['shared_with'])) {
                $rows = collect($data['shared_with'])->map(fn ($uid) => [
                    'recurring_payment_id' => $id,
                    'user_id'              => $uid,
                ])->all();
                DB::table('recurring_payment_viewers')->insert($rows);
            }
        });

        $item = DB::table('recurring_payments')->where('id', $id)->first();

        return response()->json(['message' => 'Recurring payment created', 'data' => $item], 201);
    }
}
