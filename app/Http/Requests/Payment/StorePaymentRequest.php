<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

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
            'expense_participant_ids'   => ['required', 'array', 'min:1'],
            'expense_participant_ids.*' => ['uuid'],
            'amount'                    => ['sometimes', 'numeric', 'min:0'],
            'payment_method'            => ['sometimes', 'nullable', 'string', 'max:100'],
            'proof_url'                 => ['sometimes', 'nullable', 'url'],
            'signature'                 => ['sometimes', 'nullable', 'string'],
            'payment_date'              => ['sometimes', 'nullable', 'date'],
        ];
    }
}
