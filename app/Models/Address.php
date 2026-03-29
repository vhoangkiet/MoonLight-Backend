<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'label',
        'is_default',
        'line_1',
        'line_2',
        'city',
        'state',
        'postal_code',
        'country_code',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->line_1,
            $this->line_2,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country_code,
        ]);

        return implode(', ', $parts);
    }
}
