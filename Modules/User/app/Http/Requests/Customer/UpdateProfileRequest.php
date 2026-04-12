<?php

namespace Modules\User\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'avatar' => ['sometimes', 'image', 'mimes:jpeg,png,webp', 'max:2048'],
        ];
    }
}
