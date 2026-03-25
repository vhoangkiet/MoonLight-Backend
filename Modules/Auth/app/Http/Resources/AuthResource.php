<?php

namespace Modules\Auth\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'avatar_url' => $this->getFirstMediaUrl('avatar') ?: null,
            'status' => $this->status,
            'email_verified_at' => $this->email_verified_at,
            'roles' => $this->getRoleNames(),
            'last_login_at' => $this->last_login_at,
        ];
    }
}
