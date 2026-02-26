<?php

namespace App\Http\Requests;

use App\Models\Expense;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class UpdateExpenseRequest extends FormRequest
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
        /** @var Expense $expense */
        $expense = $this->route('expense');

        return [
            'category_id' => [
                'sometimes',
                'integer',
                Rule::exists('categories', 'id')->when(
                    $this->has('category_id') && (int) $this->input('category_id') !== $expense->category_id,
                    fn (Exists $rule) => $rule->whereNull('deleted_at')
                ),
            ],
            'payment_method_id' => [
                'sometimes',
                'integer',
                Rule::exists('payment_methods', 'id')->when(
                    $this->has('payment_method_id') && (int) $this->input('payment_method_id') !== $expense->payment_method_id,
                    fn (Exists $rule) => $rule->whereNull('deleted_at')
                ),
            ],
            'description' => ['sometimes', 'nullable', 'string', 'max:255'],
            'amount_cents' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category_id.exists' => 'The selected category is invalid.',
            'payment_method_id.exists' => 'The selected payment method is invalid.',
            'amount_cents.min' => 'The amount must be at least 1 cent.',
        ];
    }
}
