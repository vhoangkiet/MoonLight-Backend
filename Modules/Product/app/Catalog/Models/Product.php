<?php

namespace Modules\Product\Catalog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Product\Catalog\Enums\ProductStatus;
use Modules\Product\Catalog\Enums\VariantStatus;
use Modules\Product\Database\Factories\ProductFactory;
use Modules\Product\Promotion\Models\Discount;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Product extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProductStatus::class,
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class)
            ->withTrashed();
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)
            ->whereNull('deleted_at')
            ->where('status', VariantStatus::ACTIVE->value);
    }

    public function allVariants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function discounts(): BelongsToMany
    {
        return $this->belongsToMany(Discount::class, 'discount_product')
            ->withPivot('product_variant_id')
            ->whereNull('deleted_at');
    }

    public function scopeActive($query)
    {
        return $query->where('status', ProductStatus::ACTIVE);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('gallery')
            ->acceptsMimeTypes([
                'image/jpeg',
                'image/png',
                'image/webp',
                'image/gif',
                'video/mp4',
                'video/webm',
                'video/quicktime',
            ]);
    }

    protected static function newFactory()
    {
        return ProductFactory::new();
    }
}
