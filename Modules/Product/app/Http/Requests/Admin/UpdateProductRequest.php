<?php

namespace Modules\Product\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Product\Catalog\Enums\ProductStatus;

class UpdateProductRequest extends FormRequest
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
        $productId = $this->route('id');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('products', 'slug')->ignore($productId)],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'status' => ['nullable', 'string', Rule::enum(ProductStatus::class)],
            'media_uuids' => ['sometimes', 'array'],
            'media_uuids.*' => ['required', 'uuid', 'distinct'],
        ];
    }
}
