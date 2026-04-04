<?php

namespace Modules\Product\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Product\Catalog\Enums\VariantStatus;
use Modules\Product\Catalog\Models\Product;
use Modules\Product\Catalog\Models\ProductVariant;

class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'sku' => 'SKU-'.$this->faker->unique()->randomNumber(6),
            'shape' => $this->faker->randomElement(['Round', 'Oval', 'Square', 'Rectangle']),
            'length' => $this->faker->randomElement(['30cm', '50cm', '70cm', '100cm']),
            'tonal_palette' => $this->faker->randomElement(['Warm', 'Cool', 'Neutral']),
            'size' => $this->faker->randomElement(['Small', 'Medium', 'Large']),
            'price' => $this->faker->randomFloat(2, 10, 1000),
            'stock' => $this->faker->numberBetween(0, 500),
            'status' => $this->faker->randomElement([VariantStatus::ACTIVE, VariantStatus::INACTIVE]),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => VariantStatus::ACTIVE,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => VariantStatus::INACTIVE,
        ]);
    }

    public function withProduct(Product $product): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => $product->id,
        ]);
    }

    public function withCombination(string $shape, string $length, string $tonalPalette, string $size): static
    {
        return $this->state(fn (array $attributes) => [
            'shape' => $shape,
            'length' => $length,
            'tonal_palette' => $tonalPalette,
            'size' => $size,
        ]);
    }
}
