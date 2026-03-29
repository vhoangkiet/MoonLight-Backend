<?php

namespace Modules\Product\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVoucherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $voucherId = $this->route('id');

        return [
            'code' => ['nullable', 'string', 'max:50', Rule::unique('vouchers', 'code')->ignore($voucherId)],
            'name' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'string', Rule::in(['percentage', 'fixed'])],
            'value' => [
                'sometimes',
                'numeric',
                'min:0',
                Rule::when(
                    fn () => $this->input('type') === 'percentage',
                    ['max:100']
                ),
            ],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => [
                'nullable',
                'date',
                Rule::when(
                    fn () => $this->input('valid_from') !== null || $this->route('id') !== null,
                    ['after_or_equal:'.($this->input('valid_from') ?? $this->route('id')?->valid_from ?? 'today')]
                ),
            ],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
