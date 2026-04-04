<?php

namespace Modules\Product\Catalog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Product\Catalog\Enums\VariantStatus;
use Modules\Product\Database\Factories\ProductVariantFactory;

class ProductVariant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'sku',
        'shape',
        'length',
        'tonal_palette',
        'size',
        'price',
        'stock',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => VariantStatus::class,
            'price' => 'decimal:2',
            'stock' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)
            ->withTrashed();
    }

    public function scopeActive($query)
    {
        return $query->where('status', VariantStatus::ACTIVE);
    }

    public function scopeInStock($query)
    {
        return $query->where('stock', '>', 0);
    }

    public function isInStock(): bool
    {
        return $this->stock > 0;
    }

    public function getFullName(): string
    {
        $productName = $this->product?->name ?? 'Unknown Product';

        return "{$productName} - {$this->shape} {$this->length} {$this->tonal_palette} ({$this->size})";
    }

    protected static function newFactory()
    {
        return ProductVariantFactory::new();
    }
}
