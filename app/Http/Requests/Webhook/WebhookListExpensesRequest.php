<?php

namespace App\Http\Requests\Webhook;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WebhookListExpensesRequest extends FormRequest
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
            'phone_number' => ['required', 'string', 'regex:/^\d+$/'],
            'period' => ['sometimes', 'string', Rule::in(['7d', '30d', '90d', '12m'])],
            'start_date' => ['required_with:end_date', 'date_format:Y-m-d'],
            'end_date' => ['required_with:start_date', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone_number.required' => 'A phone number is required.',
            'phone_number.regex' => 'The phone number must contain only digits.',
            'period.in' => 'The period must be one of: 7d, 30d, 90d, 12m.',
            'start_date.date_format' => 'The start date must be in the format Y-m-d.',
            'start_date.required_with' => 'The start date is required when end date is present.',
            'end_date.date_format' => 'The end date must be in the format Y-m-d.',
            'end_date.required_with' => 'The end date is required when start date is present.',
            'end_date.after_or_equal' => 'The end date must be after or equal to the start date.',
        ];
    }
}
