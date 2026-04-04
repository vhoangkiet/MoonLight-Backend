<?php

namespace Modules\User\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($userId)],
            'phone' => ['nullable', 'string', 'max:20'],
            'avatar' => ['sometimes', 'image', 'mimes:jpeg,png,webp', 'max:2048'],
        ];
    }
}
