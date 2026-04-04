<?php

namespace Modules\Product\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class CalculateProductPriceRequest extends FormRequest
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
            'product_id' => ['required', 'integer'],
            'variant_id' => ['required', 'integer'],
            'voucher_code' => ['nullable', 'string'],
        ];
    }
}
