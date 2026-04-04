<?php

namespace Modules\Product\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Product\Catalog\Enums\VariantStatus;

class StoreProductVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sku' => ['nullable', 'string', 'max:50', 'unique:product_variants,sku'],
            'shape' => ['required', 'string', 'max:50'],
            'length' => ['required', 'string', 'max:50'],
            'tonal_palette' => ['required', 'string', 'max:50'],
            'size' => ['required', 'string', 'max:20'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'string', Rule::enum(VariantStatus::class)],
        ];
    }
}
