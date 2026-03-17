<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class CreateInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('phone_numbers') && is_array($this->phone_numbers)) {
            $normalized = array_map(function ($phone) {
                $phone = preg_replace('/\D/', '', $phone);

                if (strlen($phone) <= 11) {
                    $phone = '55'.$phone;
                }

                return $phone;
            }, $this->phone_numbers);

            $this->merge(['phone_numbers' => $normalized]);
        }
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'phone_numbers' => ['required', 'array', 'max:50'],
            'phone_numbers.*' => ['required', 'string', 'max:20', 'regex:/^55\d{11}$/'],
            'role' => ['required', 'string', new Enum(UserRole::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone_numbers.required' => 'At least one phone number is required.',
            'phone_numbers.array' => 'Phone numbers must be an array.',
            'phone_numbers.max' => 'You can invite a maximum of 50 people at once.',
            'phone_numbers.*.required' => 'Each phone number is required.',
            'phone_numbers.*.regex' => 'Each phone number must be a valid Brazilian mobile number (DDD + 9 digits).',
            'role.required' => 'A role is required.',
        ];
    }
}
