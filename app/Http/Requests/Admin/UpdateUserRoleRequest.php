<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\BaseRequest;

class UpdateUserRoleRequest extends BaseRequest
{
    private const ALLOWED_ROLES = ['Admin', 'Staff', 'Customer'];

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'role' => ['required', 'string', 'in:'.implode(',', self::ALLOWED_ROLES)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'role.required' => __('api.admin.role_required'),
            'role.in' => __('api.admin.role_invalid'),
        ];
    }
}
