<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseRequest;

class VerifyOtpRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'otp' => 'required|digits:6',
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
            'otp.required' => __('api.auth.otp_required'),
            'otp.digits' => __('api.auth.otp_invalid'),
        ];
    }
}
