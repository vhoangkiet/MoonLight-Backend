<?php

namespace Modules\Product\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Modules\Product\Catalog\Enums\CategoryStatus;
use Modules\Product\Catalog\Models\Category;

class UpdateCategoryRequest extends FormRequest
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
        $categoryId = $this->route('id');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->whereNull('deleted_at'),
                function ($attribute, $value, $fail) use ($categoryId) {
                    if ((int) $value === (int) $categoryId) {
                        $fail('Category cannot be its own parent');
                    }
                },
            ],
            'description' => ['nullable', 'string'],
            'position' => ['nullable', 'integer', 'min:0'],
            'status' => ['sometimes', 'string', Rule::enum(CategoryStatus::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.max' => 'Category name must not exceed 255 characters',
            'parent_id.exists' => 'Parent category does not exist or has been deleted',
            'status.enum' => 'Status must be either active or inactive',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $categoryId = $this->route('id');
            $parentId = $this->input('parent_id');

            if ($parentId && $categoryId) {
                // Check if the new parent is a descendant of the current category
                // (which would create a circular reference)
                $parentCategory = Category::find($parentId);
                if ($parentCategory && $parentCategory->isDescendantOf((int) $categoryId)) {
                    $validator->errors()->add('parent_id', 'Cannot set a descendant as parent');
                }
            }
        });
    }
}
