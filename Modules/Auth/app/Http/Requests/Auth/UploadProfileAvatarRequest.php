<?php

namespace Modules\Auth\Http\Requests\Auth;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class UploadProfileAvatarRequest extends BaseRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'avatar' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'avatar.required' => __('An avatar image is required.'),
            'avatar.image' => __('The avatar must be an image file.'),
            'avatar.mimes' => __('The avatar must be a JPEG, PNG, or WebP image.'),
            'avatar.max' => __('The avatar may not be greater than 2MB.'),
        ];
    }
}
