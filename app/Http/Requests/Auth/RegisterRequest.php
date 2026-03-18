<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseRequest;

class RegisterRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => __('api.auth.email_required'),
            'email.email' => __('api.auth.email_invalid'),
            'email.unique' => __('api.auth.email_exists'),
            'password.required' => __('api.auth.password_required'),
            'password.min' => __('api.auth.password_too_short'),
        ];
    }
}
