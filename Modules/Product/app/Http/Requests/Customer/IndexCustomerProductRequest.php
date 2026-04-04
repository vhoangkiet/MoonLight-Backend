<?php

namespace Modules\Product\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class IndexCustomerProductRequest extends FormRequest
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
            'search' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'per_page.min' => 'Items per page must be at least 1',
            'per_page.max' => 'Items per page must not exceed 100',
        ];
    }
}
