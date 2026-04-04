<?php

namespace Modules\Product\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Product\Catalog\Enums\CategoryStatus;

class StoreCategoryRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->whereNull('deleted_at'),
            ],
            'description' => ['nullable', 'string'],
            'position' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'string', Rule::enum(CategoryStatus::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Category name is required',
            'name.max' => 'Category name must not exceed 255 characters',
            'parent_id.exists' => 'Parent category does not exist or has been deleted',
            'status.required' => 'Status is required',
            'status.enum' => 'Status must be either active or inactive',
        ];
    }
}
