<?php

namespace App\Http\Requests\Expense;

use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseRequest extends FormRequest
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
            'description'               => ['required', 'string'],
            'total_amount'              => ['required', 'numeric', 'min:0'],
            'group_id'                  => ['required', 'uuid'],
            'expense_date'              => ['required', 'date_format:Y-m-d'],

            'has_ticket'                => ['required', 'boolean'],
            'ticket_image_url'          => [
                'nullable',
                'url',
                'required_if:has_ticket,true',
                'prohibited_unless:has_ticket,true',
            ],

            'participants'              => ['required', 'array', 'min:1'],
            'participants.*.user_id'    => ['required', 'uuid'],
            'participants.*.amount_due' => ['required', 'numeric', 'min:0'],
        ];
    }
}

