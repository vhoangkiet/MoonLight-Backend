<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Modules\Product\Database\Factories\CategoryFactory;
use Modules\Product\Enums\CategoryStatus;
use Modules\Product\Enums\ProductStatus;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected static function newFactory(): CategoryFactory
    {
        return new CategoryFactory;
    }

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
        'position',
        'status',
        'products_count',
    ];

    protected function casts(): array
    {
        return [
            'status' => CategoryStatus::class,
            'products_count' => 'integer',
            'position' => 'integer',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id')
            ->withTrashed();
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
            ->orderBy('position')
            ->orderBy('name');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', CategoryStatus::ACTIVE);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('name');
    }

    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function getAllDescendantIds(): array
    {
        return DB::table('category_closure')
            ->join('categories', 'category_closure.descendant_id', '=', 'categories.id')
            ->where('category_closure.ancestor_id', $this->id)
            ->where('category_closure.depth', '>', 0)
            ->whereNull('categories.deleted_at')
            ->pluck('category_closure.descendant_id')
            ->toArray();
    }

    public function getAllAncestorIds(): array
    {
        return DB::table('category_closure')
            ->join('categories', 'category_closure.ancestor_id', '=', 'categories.id')
            ->where('category_closure.descendant_id', $this->id)
            ->where('category_closure.depth', '>', 0)
            ->whereNull('categories.deleted_at')
            ->pluck('category_closure.ancestor_id')
            ->toArray();
    }

    public function getProductsCountRecursive(): int
    {
        $descendantIds = $this->getAllDescendantIds();
        $allCategoryIds = array_merge([$this->id], $descendantIds);

        return Product::whereIn('category_id', $allCategoryIds)
            ->where('status', ProductStatus::ACTIVE)
            ->count();
    }

    public function updateProductsCount(): void
    {
        $this->products_count = $this->getProductsCountRecursive();
        $this->save();
    }

    public function isDescendantOf(int $categoryId): bool
    {
        return DB::table('category_closure')
            ->join('categories', 'category_closure.ancestor_id', '=', 'categories.id')
            ->where('category_closure.ancestor_id', $categoryId)
            ->where('category_closure.descendant_id', $this->id)
            ->where('category_closure.depth', '>', 0)
            ->whereNull('categories.deleted_at')
            ->exists();
    }

    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    public function hasProducts(): bool
    {
        return $this->products()->exists();
    }

    protected static function boot(): void
    {
        parent::boot();

        static::created(function (Category $category) {
            $category->rebuildClosureTable();
        });

        static::updated(function (Category $category) {
            if ($category->isDirty('parent_id')) {
                DB::transaction(function () use ($category) {
                    // Get all descendants that need their closure entries rebuilt
                    $descendantIds = $category->getAllDescendantIds();
                    $allAffectedIds = array_merge([$category->id], $descendantIds);

                    // Delete old closure entries for all affected categories
                    DB::table('category_closure')
                        ->whereIn('descendant_id', $allAffectedIds)
                        ->delete();

                    // Rebuild closure entries for the current category
                    $category->rebuildClosureTable();

                    // Rebuild closure entries for all descendants
                    $descendants = self::whereIn('id', $descendantIds)->get();
                    foreach ($descendants as $descendant) {
                        $descendant->rebuildClosureTable();
                    }
                });
            }
        });

        static::deleting(function (Category $category) {
            // Soft delete - do NOT touch closure table
        });

        static::forceDeleting(function (Category $category) {
            // Permanent delete - cleanup closure table
            DB::table('category_closure')
                ->where('ancestor_id', $category->id)
                ->orWhere('descendant_id', $category->id)
                ->delete();
        });

        static::restored(function (Category $category) {
            // Restore closure table entries after soft delete restore
            DB::transaction(function () use ($category) {
                // Clean up any orphaned closure entries first
                DB::table('category_closure')
                    ->where('ancestor_id', $category->id)
                    ->orWhere('descendant_id', $category->id)
                    ->delete();

                $category->rebuildClosureTable();

                // Also rebuild closure entries for all descendants
                $descendantIds = $category->getAllDescendantIds();
                foreach ($descendantIds as $descendantId) {
                    $descendant = self::find($descendantId);
                    if ($descendant) {
                        $descendant->rebuildClosureTable();
                    }
                }
            });
        });
    }

    private function rebuildClosureTable(): void
    {
        DB::transaction(function () {
            // Delete old entries for this category
            DB::table('category_closure')
                ->where('descendant_id', $this->id)
                ->delete();

            $entries = [
                [
                    'ancestor_id' => $this->id,
                    'descendant_id' => $this->id,
                    'depth' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ];

            if ($this->parent_id) {
                $ancestors = DB::table('category_closure')
                    ->where('descendant_id', $this->parent_id)
                    ->get();

                foreach ($ancestors as $ancestor) {
                    $entries[] = [
                        'ancestor_id' => $ancestor->ancestor_id,
                        'descendant_id' => $this->id,
                        'depth' => $ancestor->depth + 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            DB::table('category_closure')->insert($entries);
        });
    }
}
