<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class OtpVerification extends BaseModel
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'otp_hash',
        'expires_at',
        'attempts',
        'last_sent_at',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'last_sent_at' => 'datetime',
            'attempts' => 'int',
        ];
    }
}
