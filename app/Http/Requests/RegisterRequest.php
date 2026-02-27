<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'size:64'],
            'name' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:50', 'regex:/^\d+$/', Rule::unique('users', 'phone_number')->whereNull('deleted_at')],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'client_id' => ['required', 'string'],
            'client_secret' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'token.required' => 'An invitation token is required.',
            'token.size' => 'The invitation token is invalid.',
            'name.required' => 'Your name is required.',
            'phone_number.required' => 'A phone number is required.',
            'phone_number.regex' => 'The phone number must contain only digits.',
            'phone_number.unique' => 'This phone number is already in use.',
            'password.required' => 'A password is required.',
            'password.confirmed' => 'Password confirmation does not match.',
        ];
    }
}
