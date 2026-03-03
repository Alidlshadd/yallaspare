<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'category_id' => 1,
            'name_en' => ucfirst($name),
            'name_ar' => ucfirst($name),
            'name_ku' => ucfirst($name),
            'description_en' => fake()->sentence(),
            'description_ar' => fake()->sentence(),
            'description_ku' => fake()->sentence(),
            'price' => fake()->numberBetween(1000, 50000),
            'dealer_price' => null,
            'stock_quantity' => fake()->numberBetween(0, 100),
            'sku' => 'SKU-' . Str::upper(Str::random(8)),
            'brand' => fake()->company(),
            'compatible_models' => [],
            'image' => null,
            'is_active' => true,
        ];
    }
}
