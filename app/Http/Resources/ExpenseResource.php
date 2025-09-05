<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
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
            'description'      => $this->description,
            'total_amount'     => $this->formatMoney($this->total_amount),
            'payer_id'         => $this->payer_id,
            'group_id'         => $this->group_id,
            'ticket_image_url' => $this->ticket_image_url,
            'ocr_status'       => $this->ocr_status,
            'status'           => $this->status,
            'expense_date'     => $this->expense_date,
            'created_at'       => $this->created_at ?? null,
            'updated_at'       => $this->updated_at ?? null,
            'participants'     => $this->when(isset($this->participants), $this->participants),
            'role'             => $this->when(isset($this->role), $this->role),
        ];
    }

    private function formatMoney($value): string
    {
        return number_format((float) ($value ?? 0), 2, '.', '');
    }
}
