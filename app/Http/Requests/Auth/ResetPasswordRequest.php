<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseRequest;

class ResetPasswordRequest extends BaseRequest
{
    /**
     * @return array<string,string>
     */
    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ];
    }

    /**
     * @return array<string,string>
     */
    public function messages(): array
    {
        return [
            'email.required' => __('api.auth.email_required'),
            'email.email' => __('api.auth.email_invalid'),
            'token.required' => __('api.auth.reset_token_required'),
            'password.required' => __('api.auth.password_required'),
            'password.min' => __('api.auth.password_too_short'),
            'password.confirmed' => __('api.auth.password_confirmation_mismatch'),
        ];
    }
}
