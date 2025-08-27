<?php

namespace App\Http\Requests\Expense;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExpenseRequest extends FormRequest
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
            'description'               => ['sometimes', 'string'],
            'total_amount'              => ['sometimes', 'numeric', 'min:0'],
            'expense_date'              => ['sometimes', 'date_format:Y-m-d'],

            'has_ticket'                => ['sometimes', 'boolean'],
            'ticket_image_url'          => [
                'nullable',
                'url',
                'required_if:has_ticket,true',
                'prohibited_unless:has_ticket,true',
            ],

            'participants'              => ['sometimes', 'array', 'min:1'],
            'participants.*.user_id'    => ['required_with:participants', 'uuid'],
            'participants.*.amount_due' => ['required_with:participants', 'numeric', 'min:0'],
        ];
    }
}
