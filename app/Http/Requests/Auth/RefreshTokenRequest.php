<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseRequest;

class RefreshTokenRequest extends BaseRequest
{
    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'refresh_token' => ['required', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'refresh_token.required' => __('api.auth.refresh_token_required'),
            'refresh_token.max' => __('api.auth.refresh_token_invalid'),
        ];
    }
}
