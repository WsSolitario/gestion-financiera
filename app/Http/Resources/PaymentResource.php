<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'group_id'         => $this->group_id,
            'amount'           => $this->formatMoney($this->amount),
            'status'           => $this->status,
            'payment_date'     => $this->payment_date,
            'payment_method'   => $this->payment_method,
            'note'             => $this->note,
            'evidence_url'     => $this->evidence_url,
            'from_user_id'     => $this->from_user_id,
            'payer_name'       => $this->payer_name ?? null,
            'to_user_id'       => $this->to_user_id,
            'receiver_name'    => $this->receiver_name ?? null,
            'direction'        => $this->direction ?? null,
            'unapplied_amount' => $this->formatMoney($this->unapplied_amount ?? 0),
            'participants'     => $this->when(isset($this->participants), $this->participants),
        ];
    }

    private function formatMoney($value): string
    {
        return number_format((float) ($value ?? 0), 2, '.', '');
    }
}
