<?php

namespace Modules\Product\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class IndexVoucherRequest extends FormRequest
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
            'is_active' => ['nullable', 'boolean'],
            'sort_by' => ['nullable', 'string', 'in:code,name,value,valid_from,valid_until,created_at'],
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
            'sort_by.in' => 'Sort field must be one of: code, name, value, valid_from, valid_until, created_at',
            'sort_order.in' => 'Sort order must be either asc or desc',
            'per_page.min' => 'Items per page must be at least 1',
            'per_page.max' => 'Items per page must not exceed 100',
        ];
    }
}
