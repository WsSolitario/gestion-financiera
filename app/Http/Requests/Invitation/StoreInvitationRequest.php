<?php

namespace App\Http\Requests\Invitation;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvitationRequest extends FormRequest
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
            'invitee_email'   => ['required', 'email', 'max:255'],
            'group_id'        => ['required', 'uuid'],
            'expires_in_days' => ['sometimes', 'integer', 'min:1', 'max:90'],
        ];
    }
}
