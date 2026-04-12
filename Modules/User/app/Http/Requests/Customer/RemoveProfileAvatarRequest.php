<?php

namespace Modules\User\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class RemoveProfileAvatarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [];
    }
}
