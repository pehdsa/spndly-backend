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

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'emails' => ['required', 'array', 'max:50'],
            'emails.*' => ['required', 'string', 'email', 'max:255'],
            'role' => ['required', 'string', new Enum(UserRole::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'emails.required' => 'At least one email address is required.',
            'emails.array' => 'Emails must be an array.',
            'emails.max' => 'You can invite a maximum of 50 people at once.',
            'emails.*.required' => 'Each email address is required.',
            'emails.*.email' => 'Please provide valid email addresses.',
            'role.required' => 'A role is required.',
        ];
    }
}
