<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'group_id'     => ['required', 'uuid'],
            'from_user_id' => ['required', 'uuid'],
            'to_user_id'   => ['required', 'uuid', 'different:from_user_id'],
            'amount'       => ['required', 'numeric', 'gt:0'],
            'note'         => ['sometimes', 'nullable', 'string'],
            'evidence_url' => ['sometimes', 'nullable', 'url'],
            'payment_method' => ['sometimes', 'nullable', 'string', Rule::in(['cash', 'transfer'])],
        ];
    }
}
