<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseRequest;

class ChangePasswordRequest extends BaseRequest
{
    /**
     * @return array<string,string>
     */
    public function rules(): array
    {
        return [
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ];
    }

    /**
     * @return array<string,string>
     */
    public function messages(): array
    {
        return [
            'current_password.required' => __('api.auth.current_password_required'),
            'password.required' => __('api.auth.password_required'),
            'password.min' => __('api.auth.password_too_short'),
            'password.confirmed' => __('api.auth.password_confirmation_mismatch'),
        ];
    }
}
