<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'name'  => ['sometimes','string','max:100'],
            'email' => [
                'sometimes','email','max:255',
                Rule::unique('users','email')->ignore($userId, 'id'),
            ],
            'profile_picture_url' => ['sometimes','nullable','url'],
            'phone_number'        => ['sometimes','nullable','string','max:50'],
        ];
    }
}
