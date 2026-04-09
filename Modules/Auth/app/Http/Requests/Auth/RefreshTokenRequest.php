<?php

namespace Modules\Auth\Http\Requests\Auth;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class RefreshTokenRequest extends BaseRequest
{
    protected function prepareForValidation(): void
    {
        $refreshToken = $this->input('refresh_token');

        if (is_string($refreshToken) && $refreshToken !== '') {
            return;
        }

        $cookieRefreshToken = $this->cookie('auth_token');

        if (is_string($cookieRefreshToken) && $cookieRefreshToken !== '') {
            $this->merge([
                'refresh_token' => $cookieRefreshToken,
            ]);
        }
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'refresh_token' => ['required', 'string'],
        ];
    }
}
