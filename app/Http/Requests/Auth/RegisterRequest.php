<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
        $isPublic = config('app.mode_app') === 'public';

        return [
            'name'                => ['required', 'string', 'max:100'],
            'email'               => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'            => ['required', 'string', 'min:8', 'confirmed'],
            'invitation_token'    => ['sometimes', 'nullable', 'string'],
            'profile_picture_url' => ['sometimes', 'nullable', 'url'],
            'phone_number'        => ['sometimes', 'nullable', 'string', 'max:50'],
            'registration_token'  => $isPublic
                ? ['sometimes', 'nullable', 'string']
                : ['required', 'string'],
        ];
    }
}
