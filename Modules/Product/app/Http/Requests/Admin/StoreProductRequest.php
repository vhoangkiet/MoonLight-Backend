<?php

namespace Modules\Product\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Product\Catalog\Enums\ProductStatus;

class StoreProductRequest extends FormRequest
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
            'slug' => ['nullable', 'string', 'max:255', 'unique:products,slug'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'status' => ['nullable', 'string', Rule::enum(ProductStatus::class)],
            'media_uuids' => ['nullable', 'array'],
            'media_uuids.*' => ['required', 'uuid', 'distinct'],
        ];
    }
}
