<?php

namespace Modules\User\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['admin', 'staff']);
    }

    public function rules(): array
    {
        return [
            'label' => ['required', 'in:Home,Work,Other'],
            'is_default' => ['boolean'],
            'line_1' => ['required', 'string', 'max:255'],
            'line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'size:2'], // 2-letter state code
            'postal_code' => ['required', 'string', 'max:10'], // ZIP code
            'country_code' => ['nullable', 'string', 'size:2'],
        ];
    }
}
