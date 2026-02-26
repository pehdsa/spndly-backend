<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseRequest extends FormRequest
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
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->whereNull('deleted_at')],
            'payment_method_id' => ['required', 'integer', Rule::exists('payment_methods', 'id')->whereNull('deleted_at')],
            'description' => ['nullable', 'string', 'max:255'],
            'amount_cents' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category_id.required' => 'A category is required.',
            'category_id.exists' => 'The selected category is invalid.',
            'payment_method_id.required' => 'A payment method is required.',
            'payment_method_id.exists' => 'The selected payment method is invalid.',
            'amount_cents.required' => 'An amount is required.',
            'amount_cents.min' => 'The amount must be at least 1 cent.',
        ];
    }
}
