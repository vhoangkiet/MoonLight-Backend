<?php

namespace Modules\Product\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReorderCategoriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'orders' => [
                'required',
                'array',
                function ($attribute, $value, $fail) {
                    if (count($value) !== count(array_unique($value))) {
                        $fail('The orders array contains duplicate category IDs.');
                    }
                    // Validate that all keys are integers (positions)
                    foreach (array_keys($value) as $key) {
                        if (! is_int($key)) {
                            $fail('Position keys must be integers.');
                            break;
                        }
                    }
                },
            ],
            'orders.*' => [
                'integer',
                Rule::exists('categories', 'id')->whereNull('deleted_at'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'orders.required' => 'The orders array is required.',
            'orders.array' => 'The orders must be an array.',
            'orders.*.integer' => 'Each order ID must be an integer.',
            'orders.*.exists' => 'One or more category IDs do not exist.',
        ];
    }
}
