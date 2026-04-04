<?php

namespace Modules\Product\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Product\Promotion\Models\Voucher;

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
                'after_or_equal:valid_from',
                function ($attribute, $value, $fail) {
                    $validFrom = $this->input('valid_from');

                    // If valid_from not provided in request, get from existing voucher
                    if ($validFrom === null && $this->route('id')) {
                        $voucher = Voucher::find($this->route('id'));
                        if ($voucher) {
                            $validFrom = $voucher->valid_from;
                        }
                    }

                    // If we have a valid_from date, validate valid_until against it
                    if ($validFrom && $value && $value < $validFrom) {
                        $fail('The valid until date must be after or equal to the valid from date.');
                    }
                },
            ],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
