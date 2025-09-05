<?php

namespace App\Http\Requests\Report;

use Illuminate\Foundation\Http\FormRequest;

class ReportFilterRequest extends FormRequest
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
            'groupId'      => ['sometimes', 'uuid'],
            'startDate'    => ['sometimes', 'date_format:Y-m-d'],
            'endDate'      => ['sometimes', 'date_format:Y-m-d'],
            'granularity'  => ['sometimes', 'in:day,month,auto'],
            'paymentStatus'=> ['sometimes', 'in:approved,pending,rejected,any'],
        ];
    }
}
