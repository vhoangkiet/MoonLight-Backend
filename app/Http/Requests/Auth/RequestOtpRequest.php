<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseRequest;

class RequestOtpRequest extends BaseRequest
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
        ];
    }
}
