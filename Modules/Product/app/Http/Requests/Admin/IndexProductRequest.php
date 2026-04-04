<?php

namespace Modules\Product\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Product\Catalog\Enums\ProductStatus;

class IndexProductRequest extends FormRequest
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
            'status' => ['nullable', 'string', Rule::enum(ProductStatus::class)],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'sort_by' => ['nullable', 'string', 'in:name,created_at,id'],
            'sort_order' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sort_by.in' => 'Sort field must be one of: name, created_at, id',
            'sort_order.in' => 'Sort order must be either asc or desc',
            'per_page.min' => 'Items per page must be at least 1',
            'per_page.max' => 'Items per page must not exceed 100',
        ];
    }
}
