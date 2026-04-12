<?php

namespace Modules\User\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class UploadProfileAvatarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'avatar' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:5000'],
        ];
    }
}
