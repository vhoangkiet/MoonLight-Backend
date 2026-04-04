<?php

namespace Modules\Product\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Product\Catalog\Enums\VariantStatus;

class UpdateProductVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $variantId = $this->route('id');

        return [
            'sku' => ['nullable', 'string', 'max:50', Rule::unique('product_variants', 'sku')->ignore($variantId)],
            'shape' => ['sometimes', 'string', 'max:50'],
            'length' => ['sometimes', 'string', 'max:50'],
            'tonal_palette' => ['sometimes', 'string', 'max:50'],
            'size' => ['sometimes', 'string', 'max:20'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'string', Rule::enum(VariantStatus::class)],
        ];
    }
}
