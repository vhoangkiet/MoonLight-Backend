<?php

namespace Modules\User\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'label' => ['sometimes', 'in:Home,Work,Other'],
            'is_default' => ['boolean'],
            'line_1' => ['sometimes', 'string', 'max:255'],
            'line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'string', 'max:100'],
            'state' => ['sometimes', 'string', 'size:2'],
            'postal_code' => ['sometimes', 'string', 'max:10'],
            'country_code' => ['nullable', 'string', 'size:2'],
        ];
    }
}
