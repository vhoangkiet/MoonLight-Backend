<?php

namespace Modules\Product\Promotion\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Modules\Product\Database\Factories\VoucherFactory;

class Voucher extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'type',
        'value',
        'min_order_amount',
        'max_discount_amount',
        'usage_limit',
        'usage_count',
        'valid_from',
        'valid_until',
        'is_active',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'usage_count' => 'integer',
        'usage_limit' => 'integer',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function uses(): HasMany
    {
        return $this->hasMany(VoucherUse::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', now());
            });
    }

    public function scopeByCode($query, $code)
    {
        return $query->where('code', $code);
    }

    public function isValid(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->valid_from && $this->valid_from->isFuture()) {
            return false;
        }

        if ($this->valid_until && $this->valid_until->isPast()) {
            return false;
        }

        if ($this->usage_limit !== null && $this->usage_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    public function canApply(float $orderAmount): bool
    {
        if (! $this->isValid()) {
            return false;
        }

        if ($this->min_order_amount !== null && $orderAmount < $this->min_order_amount) {
            return false;
        }

        return true;
    }

    public function calculateDiscount(float $orderAmount): float
    {
        if ($this->type === 'percentage') {
            $discount = $orderAmount * ($this->value / 100);
        } else {
            $discount = $this->value;
        }

        if ($this->max_discount_amount !== null) {
            $discount = min($discount, $this->max_discount_amount);
        }

        return min($discount, $orderAmount);
    }

    public function recordUsage(int $userId, float $orderAmount, float $discountAmount): void
    {
        DB::transaction(function () use ($userId, $orderAmount, $discountAmount): void {
            $this->uses()->create([
                'user_id' => $userId,
                'order_amount' => $orderAmount,
                'discount_amount' => $discountAmount,
                'used_at' => now(),
            ]);

            $this->increment('usage_count');
        });
    }

    protected static function newFactory()
    {
        return VoucherFactory::new();
    }
}
