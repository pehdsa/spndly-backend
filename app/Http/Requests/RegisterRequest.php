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

    protected function prepareForValidation(): void
    {
        if ($this->filled('phone_number')) {
            $phone = preg_replace('/\D/', '', $this->phone_number);

            if (strlen($phone) <= 11) {
                $phone = '55'.$phone;
            }

            $this->merge(['phone_number' => $phone]);
        }
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'size:64'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->whereNull('deleted_at')],
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
            'email.required' => 'An email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email address is already in use.',
            'phone_number.required' => 'A phone number is required.',
            'phone_number.regex' => 'The phone number must contain only digits.',
            'phone_number.unique' => 'This phone number is already in use.',
            'password.required' => 'A password is required.',
            'password.confirmed' => 'Password confirmation does not match.',
        ];
    }
}
