<?php

namespace Modules\Product\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $discountId = $this->route('id');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('discounts', 'code')->ignore($discountId)],
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
            'start_date' => ['nullable', 'date'],
            'end_date' => [
                'nullable',
                'date',
                Rule::when(
                    fn () => $this->input('start_date') !== null || $this->route('id') !== null,
                    ['after_or_equal:'.($this->input('start_date') ?? $this->route('id')?->start_date ?? 'today')]
                ),
            ],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
